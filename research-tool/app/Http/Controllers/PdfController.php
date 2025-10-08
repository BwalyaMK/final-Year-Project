<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paper;
use Smalot\PdfParser\Parser as PdfParser;

class PdfController extends Controller
{
    public function index()
    {
        $papers = auth()->user()->papers()->latest()->get();
        return view('pdfs.index', compact('papers'));
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
                \Log::error('PDF upload error: ' . $e->getMessage());
                continue;
            }
        }

        // Reload papers for the view after upload
        $allPapers = auth()->user()->papers()->latest()->get();

        return response()->json([
            'success' => true,
            'message' => count($uploadedPapers) . ' paper(s) uploaded successfully',
            'papers' => $uploadedPapers,
            'all_papers' => $allPapers->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'author' => $p->author,
                'pages' => $p->pages,
            ])
        ]);
    }

    public function show($id)
    {
        $paper = Paper::where('user_id', auth()->id())
            ->findOrFail($id);
        
        return view('pdfs.show', compact('paper'));
    }

    public function destroy($id)
    {
        $paper = Paper::where('user_id', auth()->id())
            ->findOrFail($id);
        
        // Delete file from storage
        \Storage::disk('private')->delete($paper->file_path);
        
        // Delete from database
        $paper->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Paper deleted successfully'
        ]);
    }

    public function download($id)
    {
        $paper = Paper::where('user_id', auth()->id())
            ->findOrFail($id);
        
        return \Storage::disk('private')->download(
            $paper->file_path,
            $paper->file_name
        );
    }

    private function extractTitle($text, $details)
    {
        // Try to get title from metadata
        if (isset($details['Title']) && !empty(trim($details['Title']))) {
            return trim($details['Title']);
        }
        
        // Try to extract from first non-empty lines of text
        $lines = array_filter(explode("\n", $text), fn($line) => trim($line) !== '');
        foreach (array_slice($lines, 0, 5) as $line) {
            $line = trim($line);
            if (strlen($line) > 10 && strlen($line) < 150 && !preg_match('/^\d+\.\s*/', $line)) {
                return $line;
            }
        }
        
        return null;
    }
}