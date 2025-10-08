<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log;
use App\Models\Paper;
use Smalot\PdfParser\Parser as PdfParser;

class ChatController extends Controller
{
    public function index()
    {
        $papers = auth()->user()->papers()->latest()->get();
        return view('chat', compact('papers'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'papers.*' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        $uploadedPapers = [];

        foreach ($request->file('papers') as $file) {
            try {
                // Store the PDF securely in private disk
                $path = $file->store('papers/' . auth()->id(), 'private');
                
                // Extract text from PDF
                $parser = new PdfParser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = $pdf->getText();
                
                // Get metadata
                $details = $pdf->getDetails();
                $pages = $pdf->getPages();
                
                // Save to database
                $paper = Paper::create([
                    'user_id' => auth()->id(),
                    'title' => $this->extractTitle($text, $details) ?? $file->getClientOriginalName(),
                    'author' => $details['Author'] ?? null,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'content' => $text,
                    'pages' => count($pages),
                    'metadata' => json_encode([
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'details' => $details,
                    ]),
                ]);
                
                $uploadedPapers[] = $paper;
                
            } catch (\Exception $e) {
                Log::error('PDF upload error: ' . $e->getMessage());
                continue;
            }
        }

        if (empty($uploadedPapers)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload papers. Please try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedPapers) . ' paper(s) uploaded successfully',
            'papers' => $uploadedPapers->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'author' => $p->author,
                'pages' => $p->pages,
            ])
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'papers' => 'nullable|array',
            'papers.*' => 'integer|exists:papers,id',
        ]);

        $message = trim($request->input('message'));
        $paperIds = array_filter($request->input('papers', []));
        
        // Get paper contents for context (user-specific for security)
        $papers = Paper::whereIn('id', $paperIds)
            ->where('user_id', auth()->id())
            ->get();
        
        // Build context from papers
        $context = $this->buildContext($papers, $message);
        
        try {
            // Call Gemini API
            $response = $this->callGeminiAPI($message, $context);
            
            return response()->json([
                'success' => true,
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            Log::error('Chat error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'message' => $message,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sorry, I encountered an error processing your message. Please try again.',
            ], 500);
        }
    }

    private function buildContext($papers, $message)
    {
        if ($papers->isEmpty()) {
            return "You are a helpful AI research assistant. The user hasn't selected any papers yet. Provide general research guidance and answer their question thoughtfully.";
        }

        $messageLower = strtolower($message);
        $relevantExcerpts = [];
        
        foreach ($papers as $paper) {
            $contentLower = strtolower($paper->content ?? '');
            
            // Check if message keywords appear in paper or include all papers if no specific match
            $hasKeywords = false;
            foreach (explode(' ', $messageLower) as $word) {
                if (strlen($word) > 3 && str_contains($contentLower, $word)) {
                    $hasKeywords = true;
                    break;
                }
            }
            
            if ($hasKeywords || $papers->count() <= 3) {
                // Include up to 4000 chars per paper
                $excerpt = $this->getRelevantExcerpt($paper->content ?? '', $messageLower, 4000);
                $relevantExcerpts[] = [
                    'title' => $paper->title,
                    'author' => $paper->author,
                    'excerpt' => $excerpt,
                ];
            }
        }

        if (empty($relevantExcerpts)) {
            // Provide basic paper info even without excerpts
            $papersList = $papers->map(fn($p) => "- {$p->title}" . ($p->author ? " by {$p->author}" : ""))->join("\n");
            return "You are a helpful AI research assistant. The user has selected these papers:\n\n{$papersList}\n\nProvide a thoughtful response based on what you know about academic research in general.";
        }

        $context = "You are a helpful AI research assistant analyzing research papers. Use the provided excerpts to answer the user's question accurately. Cite paper titles when referencing specific information. Be concise and precise.\n\n";
        
        foreach ($relevantExcerpts as $index => $paperData) {
            $context .= "=== Paper " . ($index + 1) . ": {$paperData['title']} ===\n";
            if ($paperData['author']) {
                $context .= "Author: {$paperData['author']}\n";
            }
            $context .= "\nExcerpt:\n{$paperData['excerpt']}\n\n";
        }
        
        return $context;
    }

    private function getRelevantExcerpt($content, $query, $maxLength = 4000)
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        if (empty($query)) {
            return substr($content, 0, $maxLength) . '...';
        }

        // Find the most relevant section based on query
        $words = array_filter(explode(' ', $query), fn($w) => strlen($w) > 3);
        $bestPos = 0;
        $bestScore = 0;

        foreach ($words as $word) {
            $pos = stripos($content, $word);
            if ($pos !== false && $pos > $bestScore) {
                $bestPos = $pos;
                $bestScore = $pos;
            }
        }

        // Extract context around the best match
        $start = max(0, $bestPos - 1000);
        $excerpt = substr($content, $start, $maxLength);
        
        if ($start > 0) $excerpt = '...' . $excerpt;
        if (strlen($content) > $start + $maxLength) $excerpt .= '...';
        
        return $excerpt;
    }

    private function callGeminiAPI($message, $context)
    {
        $apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('API key not configured. Please add GEMINI_API_KEY to your .env file.');
        }

        $model = config('services.gemini.model', 'gemini-1.5-flash');
        
        $fullPrompt = $context . "\n\nUser Question: " . $message . "\n\nProvide a clear, well-structured response based on the context above.";
        
        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                    'topP' => 0.95,
                    'topK' => 40,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                ],
            ]
        );

        if (!$response->successful()) {
            $status = $response->status();
            Log::error('Gemini API error', [
                'status' => $status,
                'body' => $response->body()
            ]);
            
            if ($status === 429) {
                throw new \Exception("Rate limit exceeded. Please try again in a moment.");
            } elseif ($status >= 400 && $status < 500) {
                throw new \Exception("Invalid request to AI service.");
            }
            
            throw new \Exception("Error communicating with AI service.");
        }

        $data = $response->json();
        
        // Check for safety blocks
        if (isset($data['promptFeedback']['blockReason'])) {
            throw new \Exception("Response blocked due to safety guidelines. Please rephrase your question.");
        }
        
        // Extract text from response
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($data['candidates'][0]['content']['parts'][0]['text']);
        }
        
        Log::warning('Unexpected Gemini response structure', ['data' => $data]);
        throw new \Exception('No response generated from AI service.');
    }

    private function extractTitle($text, $details)
    {
        // Try to get title from PDF metadata first
        if (isset($details['Title']) && !empty(trim($details['Title']))) {
            $title = trim($details['Title']);
            // Remove common PDF artifacts
            $title = preg_replace('/^(untitled|document|microsoft word -)/i', '', $title);
            if (strlen($title) > 5) {
                return $title;
            }
        }
        
        // Extract from first lines of text
        $lines = array_filter(
            explode("\n", $text), 
            fn($line) => !empty(trim($line))
        );
        
        foreach (array_slice($lines, 0, 10) as $line) {
            $line = trim($line);
            // Good title heuristics: not too short, not too long, no numbering
            if (strlen($line) >= 10 && 
                strlen($line) <= 200 && 
                !preg_match('/^\d+[\.\)]\s*/', $line) &&
                !preg_match('/^(page|chapter|section|table|figure)/i', $line)) {
                return $line;
            }
        }
        
        return null;
    }
}