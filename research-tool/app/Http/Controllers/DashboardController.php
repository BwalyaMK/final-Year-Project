<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $results = null;
        
        if ($request->has('query') && !empty($request->query)) {
            $results = $this->searchPapers($request);
        }
        
        return view('dashboard', compact('results'));
    }
    
    private function searchPapers(Request $request)
    {
        // This is where you'll integrate your academic API
        // Example APIs: Semantic Scholar, arXiv, PubMed, CrossRef, etc.
        
        $query = $request->input('query');
        $yearFrom = $request->input('year_from');
        $yearTo = $request->input('year_to');
        $sort = $request->input('sort', 'relevance');
        
        try {
            // Example using Semantic Scholar API (free, no key required)
            $response = Http::get('https://api.semanticscholar.org/graph/v1/paper/search', [
                'query' => $query,
                'limit' => 10,
                'fields' => 'title,abstract,authors,year,citationCount,venue,url,openAccessPdf',
                'year' => $this->buildYearFilter($yearFrom, $yearTo),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                $papers = collect($data['data'] ?? [])->map(function ($paper) {
                    return [
                        'title' => $paper['title'] ?? 'Untitled',
                        'abstract' => $paper['abstract'] ?? null,
                        'authors' => collect($paper['authors'] ?? [])->pluck('name')->toArray(),
                        'year' => $paper['year'] ?? null,
                        'citations' => $paper['citationCount'] ?? 0,
                        'venue' => $paper['venue'] ?? null,
                        'url' => $paper['url'] ?? null,
                        'pdf_url' => $paper['openAccessPdf']['url'] ?? null,
                    ];
                });
                
                // Apply sorting if needed
                $papers = $this->sortResults($papers, $sort);
                
                return [
                    'papers' => $papers,
                    'total' => $data['total'] ?? 0,
                ];
            }
            
            return [
                'papers' => [],
                'total' => 0,
                'error' => 'Unable to fetch results',
            ];
            
        } catch (\Exception $e) {
            return [
                'papers' => [],
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function buildYearFilter($yearFrom, $yearTo)
    {
        if ($yearFrom && $yearTo) {
            return "$yearFrom-$yearTo";
        } elseif ($yearFrom) {
            return "$yearFrom-" . date('Y');
        } elseif ($yearTo) {
            return "2000-$yearTo";
        }
        return null;
    }
    
    private function sortResults($papers, $sort)
    {
        switch ($sort) {
            case 'date':
                return $papers->sortByDesc('year')->values();
            case 'citations':
                return $papers->sortByDesc('citations')->values();
            case 'relevance':
            default:
                return $papers; // API already returns by relevance
        }
    }
}