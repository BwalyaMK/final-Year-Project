{{-- resources/views/chat.blade.php --}}
<x-app-layout>
    <div class="h-[calc(100vh-4rem)] flex">
        {{-- Sidebar: Papers Library --}}
        <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
            {{-- Upload Section --}}
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900 mb-3">Your Papers</h3>
                <form id="uploadForm" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="flex items-center justify-center w-full h-32 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-md appearance-none cursor-pointer hover:border-indigo-400 focus:outline-none">
                            <span class="flex flex-col items-center space-y-2">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span class="text-sm text-gray-600">Upload PDF</span>
                            </span>
                            <input type="file" name="papers[]" class="hidden" accept=".pdf" multiple id="fileInput">
                        </label>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                        Upload Papers
                    </button>
                </form>
                <div id="uploadProgress" class="hidden mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progressBar" class="bg-indigo-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-600 mt-1" id="progressText">Uploading...</p>
                </div>
            </div>

            {{-- Papers List --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <div id="papersList">
                    @forelse($papers ?? [] as $paper)
                        <div class="paper-item p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition" data-paper-id="{{ $paper->id }}">
                            <div class="flex items-start gap-2">
                                <input type="checkbox" class="paper-checkbox mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="{{ $paper->id }}">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $paper->title }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $paper->author ?? 'Unknown Author' }}</p>
                                    <p class="text-xs text-gray-400">{{ number_format($paper->pages ?? 0) }} pages</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm">No papers yet</p>
                            <p class="text-xs mt-1">Upload PDFs to get started</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Context Selection --}}
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <button id="selectAllBtn" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    Select All Papers
                </button>
                <p class="text-xs text-gray-500 mt-2">
                    <span id="selectedCount">0</span> papers selected for context
                </p>
            </div>
        </div>

        {{-- Main Chat Area --}}
        <div class="flex-1 flex flex-col bg-gray-50">
            {{-- Chat Header --}}
            <div class="bg-white border-b border-gray-200 p-4">
                <h2 class="font-semibold text-gray-900">AI Research Assistant</h2>
                <p class="text-sm text-gray-600">Ask questions about your papers and research</p>
            </div>

            {{-- Messages Area --}}
            <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="flex justify-center">
                    <div class="max-w-2xl text-center">
                        <div class="text-6xl mb-4">🤖</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Welcome to your AI Research Assistant</h3>
                        <p class="text-gray-600 mb-4">Upload papers or select from your library, then ask questions about them.</p>
                        <div class="text-left bg-white rounded-lg p-4 shadow-sm">
                            <p class="text-sm font-semibold text-gray-700 mb-2">Try asking:</p>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• "Summarize the main findings of these papers"</li>
                                <li>• "What methodologies were used?"</li>
                                <li>• "Compare the approaches in these papers"</li>
                                <li>• "What are the key differences between X and Y?"</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Input Area --}}
            <div class="bg-white border-t border-gray-200 p-4">
                <div class="flex gap-3">
                    <input 
                        type="text" 
                        id="messageInput"
                        placeholder="Ask a question about your papers..." 
                        class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        autocomplete="off"
                    />
                    <button 
                        type="button" 
                        id="sendBtn"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Send
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Powered by Google Gemini AI • Context-aware responses
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Chat page loaded');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             document.querySelector('input[name="_token"]')?.value;
            
            if (!csrfToken) {
                console.error('CSRF token not found!');
                return;
            }

            // ========== FILE UPLOAD ==========
            const fileInput = document.getElementById('fileInput');
            const uploadForm = document.getElementById('uploadForm');
            const papersList = document.getElementById('papersList');

            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const files = e.target.files;
                    if (files.length > 0) {
                        const fileNames = Array.from(files).map(f => f.name).join(', ');
                        console.log('Selected files:', fileNames);
                        
                        const uploadBtn = uploadForm.querySelector('button[type="submit"]');
                        uploadBtn.textContent = `Upload ${files.length} PDF${files.length > 1 ? 's' : ''}`;
                    }
                });
            }

            if (uploadForm) {
                uploadForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    console.log('Upload form submitted');
                    
                    const formData = new FormData(this);
                    const files = fileInput.files;
                    
                    if (files.length === 0) {
                        alert('Please select at least one PDF file');
                        return;
                    }
                    
                    const progressDiv = document.getElementById('uploadProgress');
                    const progressBar = document.getElementById('progressBar');
                    const progressText = document.getElementById('progressText');
                    const uploadBtn = uploadForm.querySelector('button[type="submit"]');
                    
                    progressDiv.classList.remove('hidden');
                    progressBar.style.width = '0%';
                    progressText.textContent = 'Uploading...';
                    progressText.classList.remove('text-red-600');
                    uploadBtn.disabled = true;
                    
                    let progress = 0;
                    const progressInterval = setInterval(() => {
                        progress += 5;
                        if (progress <= 90) {
                            progressBar.style.width = progress + '%';
                        }
                    }, 200);
                    
                    try {
                        // const response = await fetch('{{ route("pdfs.upload") }}', {
                        //     method: 'POST',
                        //     body: formData,
                        //     headers: {
                        //         'X-CSRF-TOKEN': csrfToken
                        //     }
                        // });

                        //temp debug-upload
                        const response = await fetch('/debug-upload', {  // Change this temporarily
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            });
                        
                        clearInterval(progressInterval);
                        progressBar.style.width = '100%';
                        
                        if (response.ok) {
                            const data = await response.json();
                            console.log('Upload success:', data);
                            progressText.textContent = `Successfully uploaded ${data.papers?.length || 0} paper(s)!`;
                            
                            if (data.papers && data.papers.length > 0) {
                                updatePapersList(data.papers);
                            }
                            
                            uploadForm.reset();
                            uploadBtn.textContent = 'Upload Papers';
                            fileInput.value = '';
                            uploadBtn.disabled = false;
                            
                            setTimeout(() => {
                                progressDiv.classList.add('hidden');
                            }, 2000);
                            
                        } else {
                            const errorData = await response.json().catch(() => ({}));
                            console.error('Upload failed:', errorData);
                            progressText.textContent = 'Upload failed';
                            progressText.classList.add('text-red-600');
                            alert('Upload failed: ' + (errorData.message || 'Unknown error'));
                            uploadBtn.disabled = false;
                        }
                    } catch (error) {
                        clearInterval(progressInterval);
                        console.error('Upload error:', error);
                        progressText.textContent = 'Upload failed';
                        progressText.classList.add('text-red-600');
                        alert('Upload error: ' + error.message);
                        uploadBtn.disabled = false;
                    }
                });
            }

            function updatePapersList(newPapers) {
                console.log('Updating papers list with:', newPapers);
                
                const emptyState = papersList.querySelector('.text-center.py-8');
                if (emptyState) {
                    emptyState.remove();
                }
                
                newPapers.forEach(paper => {
                    const paperItem = createPaperElement(paper);
                    papersList.insertAdjacentHTML('afterbegin', paperItem);
                });
                
                attachCheckboxListeners();
                updateSelectedCount();
            }

            function createPaperElement(paper) {
                return `
                    <div class="paper-item p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition" data-paper-id="${paper.id}">
                        <div class="flex items-start gap-2">
                            <input type="checkbox" class="paper-checkbox mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="${paper.id}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(paper.title)}</p>
                                <p class="text-xs text-gray-500 mt-1">${escapeHtml(paper.author || 'Unknown Author')}</p>
                                <p class="text-xs text-gray-400">${paper.pages || 0} pages</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            // ========== PAPER SELECTION ==========
            const selectedCount = document.getElementById('selectedCount');
            const selectAllBtn = document.getElementById('selectAllBtn');

            function updateSelectedCount() {
                const count = document.querySelectorAll('.paper-checkbox:checked').length;
                selectedCount.textContent = count;
            }

            function attachCheckboxListeners() {
                const checkboxes = document.querySelectorAll('.paper-checkbox');
                checkboxes.forEach(cb => {
                    cb.removeEventListener('change', updateSelectedCount);
                    cb.addEventListener('change', updateSelectedCount);
                });
            }

            attachCheckboxListeners();

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    const checkboxes = document.querySelectorAll('.paper-checkbox');
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    checkboxes.forEach(cb => cb.checked = !allChecked);
                    this.textContent = allChecked ? 'Select All Papers' : 'Deselect All';
                    updateSelectedCount();
                });
            }

            // ========== CHAT FUNCTIONALITY ==========
            const messageInput = document.getElementById('messageInput');
            const chatMessages = document.getElementById('chatMessages');
            const sendBtn = document.getElementById('sendBtn');

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function addMessage(content, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex ${isUser ? 'justify-end' : 'justify-start'}`;
                
                const escapedContent = escapeHtml(content).replace(/\n/g, '<br>');
                
                messageDiv.innerHTML = `
                    <div class="max-w-2xl ${isUser ? 'bg-indigo-600 text-white' : 'bg-white text-gray-900'} rounded-lg p-4 shadow-sm">
                        <p class="text-sm whitespace-pre-wrap">${escapedContent}</p>
                    </div>
                `;
                
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function addTypingIndicator() {
                const typingDiv = document.createElement('div');
                typingDiv.id = 'typingIndicator';
                typingDiv.className = 'flex justify-start';
                typingDiv.innerHTML = `
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex space-x-2">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                `;
                chatMessages.appendChild(typingDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function removeTypingIndicator() {
                const indicator = document.getElementById('typingIndicator');
                if (indicator) indicator.remove();
            }

            async function sendMessage() {
                const message = messageInput.value.trim();
                if (!message) return;
                
                const selectedPapers = Array.from(document.querySelectorAll('.paper-checkbox:checked'))
                    .map(cb => parseInt(cb.value));
                
                const welcomeMsg = chatMessages.querySelector('.justify-center');
                if (welcomeMsg) welcomeMsg.remove();
                
                addMessage(message, true);
                messageInput.value = '';
                
                sendBtn.disabled = true;
                messageInput.disabled = true;
                addTypingIndicator();
                
                try {
                    const response = await fetch('{{ route("chat.send") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            message: message,
                            papers: selectedPapers
                        })
                    });
                    
                    removeTypingIndicator();
                    
                    if (response.ok) {
                        const data = await response.json();
                        addMessage(data.response, false);
                    } else {
                        const errorData = await response.json().catch(() => ({}));
                        addMessage(errorData.message || 'Sorry, I encountered an error.', false);
                    }
                } catch (error) {
                    console.error('Chat error:', error);
                    removeTypingIndicator();
                    addMessage('Sorry, I encountered an error. Please try again.', false);
                } finally {
                    sendBtn.disabled = false;
                    messageInput.disabled = false;
                    messageInput.focus();
                }
            }

            sendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sendMessage();
            });

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
    </script>
</x-app-layout>