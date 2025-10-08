{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Search Header --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">Academic Research Search</h2>
                    <p class="text-gray-600 mb-6">Search millions of academic papers using semantic understanding</p>
                    
                    {{-- Search Form --}}
                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-4">
                        <div class="flex gap-3">
                            <input 
                                type="text" 
                                name="query" 
                                value="{{ request('query') }}"
                                placeholder="Search for academic papers, topics, or concepts..." 
                                class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                autofocus
                            />
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                                Search
                            </button>
                        </div>
                        
                        {{-- Advanced Filters --}}
                        <div class="flex gap-4 flex-wrap">
                            <select name="year_from" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">Year from</option>
                                @for ($year = date('Y'); $year >= 2000; $year--)
                                    <option value="{{ $year }}" {{ request('year_from') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            
                            <select name="year_to" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">Year to</option>
                                @for ($year = date('Y'); $year >= 2000; $year--)
                                    <option value="{{ $year }}" {{ request('year_to') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            
                            <select name="sort" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="relevance" {{ request('sort') == 'relevance' ? 'selected' : '' }}>Relevance</option>
                                <option value="date" {{ request('sort') == 'date' ? 'selected' : '' }}>Date</option>
                                <option value="citations" {{ request('sort') == 'citations' ? 'selected' : '' }}>Citations</option>
                            </select>
                            
                            @if(request('query'))
                                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                                    Clear filters
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Search Results --}}
            @if(request('query'))
                <div class="mb-4">
                    <p class="text-gray-600">
                        Found <span class="font-semibold text-gray-900">{{ $results['total'] ?? 0 }}</span> results for 
                        <span class="font-semibold text-indigo-600">"{{ request('query') }}"</span>
                    </p>
                </div>

                @if(isset($results['papers']) && count($results['papers']) > 0)
                    <div class="space-y-4">
                        @foreach($results['papers'] as $paper)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                                <div class="p-6">
                                    {{-- Paper Title --}}
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        <a href="{{ $paper['url'] ?? '#' }}" target="_blank" class="hover:text-indigo-600">
                                            {{ $paper['title'] }}
                                        </a>
                                    </h3>
                                    
                                    {{-- Authors --}}
                                    <p class="text-sm text-gray-600 mb-2">
                                        @if(isset($paper['authors']) && count($paper['authors']) > 0)
                                            {{ implode(', ', array_slice($paper['authors'], 0, 5)) }}
                                            @if(count($paper['authors']) > 5)
                                                <span class="text-gray-500">et al.</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">Authors not available</span>
                                        @endif
                                    </p>
                                    
                                    {{-- Metadata --}}
                                    <div class="flex gap-4 text-sm text-gray-500 mb-3">
                                        @if(isset($paper['year']))
                                            <span>📅 {{ $paper['year'] }}</span>
                                        @endif
                                        @if(isset($paper['citations']))
                                            <span>📊 {{ number_format($paper['citations']) }} citations</span>
                                        @endif
                                        @if(isset($paper['venue']))
                                            <span>📚 {{ $paper['venue'] }}</span>
                                        @endif
                                    </div>
                                    
                                    {{-- Abstract --}}
                                    @if(isset($paper['abstract']))
                                        <p class="text-gray-700 text-sm mb-3 line-clamp-3">
                                            {{ $paper['abstract'] }}
                                        </p>
                                    @endif
                                    
                                    {{-- Actions --}}
                                    <div class="flex gap-3 mt-4">
                                        <a href="{{ $paper['url'] ?? '#' }}" target="_blank" 
                                           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            View Paper →
                                        </a>
                                        @if(isset($paper['pdf_url']))
                                            <a href="{{ $paper['pdf_url'] }}" target="_blank" 
                                               class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                                📄 PDF
                                            </a>
                                        @endif
                                        <button class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                            💾 Save
                                        </button>
                                        <button class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                            🧠 Generate Summary
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Pagination --}}
                    @if(isset($results['total']) && $results['total'] > 10)
                        <div class="mt-6">
                            {{ $results['papers']->links() }}
                        </div>
                    @endif
                @else
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-12 text-center">
                            <div class="text-6xl mb-4">🔍</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No results found</h3>
                            <p class="text-gray-600">Try adjusting your search terms or filters</p>
                        </div>
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <div class="text-6xl mb-4">📚</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Start your research</h3>
                        <p class="text-gray-600 mb-6">Search millions of academic papers to find the research you need</p>
                        
                        <div class="max-w-2xl mx-auto">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Try searching for:</h4>
                            <div class="flex flex-wrap gap-2 justify-center">
                                @foreach(['Machine Learning', 'Climate Change', 'CRISPR', 'Quantum Computing', 'Neural Networks', 'Blockchain'] as $suggestion)
                                    <a href="{{ route('dashboard', ['query' => $suggestion]) }}" 
                                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-indigo-100 hover:text-indigo-700 transition">
                                        {{ $suggestion }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <style>
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</x-app-layout>