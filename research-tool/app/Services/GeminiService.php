<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Send a message to Gemini and get a response
     */
    public function chat(string $message, array $context = []): string
    {
        try {
            // Build the conversation context
            $contents = $this->buildContents($message, $context);

            $response = Http::timeout(60)
                ->post($this->apiUrl, [
                    'key' => $this->apiKey,
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 2048,
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                    ]
                ]);

            if (!$response->successful()) {
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to get response from Gemini API');
            }

            $data = $response->json();

            // Extract the text response
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            Log::error('Unexpected Gemini response format', ['data' => $data]);
            throw new \Exception('Unexpected response format from Gemini API');

        } catch (\Exception $e) {
            Log::error('Gemini Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Build the contents array for the API request
     */
    protected function buildContents(string $message, array $context = []): array
    {
        $contents = [];

        // Add system context if provided (as a user message since Gemini doesn't have explicit system role)
        if (!empty($context['system_message'])) {
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $context['system_message']]
                ]
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [
                    ['text' => 'I understand. I will act as a research assistant and help you with your papers.']
                ]
            ];
        }

        // Add conversation history if provided
        if (!empty($context['history'])) {
            foreach ($context['history'] as $msg) {
                $contents[] = [
                    'role' => $msg['role'] === 'user' ? 'user' : 'model',
                    'parts' => [
                        ['text' => $msg['content']]
                    ]
                ];
            }
        }

        // Add the current message
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message]
            ]
        ];

        return $contents;
    }

    /**
     * Chat with paper context
     */
    public function chatWithPapers(string $message, array $papers = []): string
    {
        $context = [
            'system_message' => $this->buildSystemMessage($papers)
        ];

        return $this->chat($message, $context);
    }

    /**
     * Build system message with paper context
     */
    protected function buildSystemMessage(array $papers = []): string
    {
        $systemMessage = "You are a helpful AI research assistant. You help users understand and analyze academic papers.";

        if (!empty($papers)) {
            $systemMessage .= "\n\nThe user has selected the following papers for context:\n";
            foreach ($papers as $paper) {
                $systemMessage .= "\n- Title: {$paper['title']}";
                if (!empty($paper['author'])) {
                    $systemMessage .= "\n  Author: {$paper['author']}";
                }
                if (!empty($paper['content'])) {
                    $systemMessage .= "\n  Content excerpt: " . substr($paper['content'], 0, 1000) . "...";
                }
            }
            $systemMessage .= "\n\nPlease answer questions based on these papers when relevant.";
        } else {
            $systemMessage .= " The user hasn't selected any papers yet, so provide general research assistance.";
        }

        return $systemMessage;
    }
}