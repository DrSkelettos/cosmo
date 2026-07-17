<div class="min-h-screen bg-gray-900 text-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Log Viewer</h1>
            <p class="text-gray-400">View and filter application logs</p>
        </div>

        <!-- Controls -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6 border border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Log File Selector -->
                <div>
                    <label for="log-file" class="block text-sm font-medium text-gray-300 mb-2">Log File</label>
                    <select
                        wire:model.live="selectedLog"
                        id="log-file"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        @foreach($this->logFiles as $log)
                            <option value="{{ $log }}">{{ $log }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Level Filter -->
                <div>
                    <label for="level-filter" class="block text-sm font-medium text-gray-300 mb-2">Log Level</label>
                    <select
                        wire:model.live="level"
                        id="level-filter"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="all">All Levels</option>
                        <option value="emergency">Emergency</option>
                        <option value="alert">Alert</option>
                        <option value="critical">Critical</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="notice">Notice</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-300 mb-2">Search</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        id="search"
                        placeholder="Search logs..."
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                <!-- Lines Per Page -->
                <div>
                    <label for="lines-per-page" class="block text-sm font-medium text-gray-300 mb-2">Lines Per Page</label>
                    <select
                        wire:model.live="linesPerPage"
                        id="lines-per-page"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>

            <!-- Refresh Button -->
            <div class="mt-4 flex gap-2">
                <button
                    wire:click="refreshLog"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="bg-gray-800 rounded-lg p-4 mb-6 border border-gray-700">
            <div class="flex flex-wrap gap-6 text-sm">
                <div>
                    <span class="text-gray-400">Total Lines:</span>
                    <span class="text-white font-medium ml-1">{{ count($this->filteredLines) }}</span>
                </div>
                <div>
                    <span class="text-gray-400">Showing:</span>
                    <span class="text-white font-medium ml-1">{{ count($this->paginatedLines) }}</span>
                </div>
                <div>
                    <span class="text-gray-400">Page:</span>
                    <span class="text-white font-medium ml-1">{{ $currentPage }} / {{ max(1, ceil(count($this->filteredLines) / $linesPerPage)) }}</span>
                </div>
            </div>
        </div>

        <!-- Log Content -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="bg-gray-750 px-4 py-2 border-b border-gray-700 flex items-center justify-between">
                <span class="text-sm text-gray-400">Log Content</span>
            </div>
            <div class="p-4 overflow-x-auto">
                @if(empty($selectedLog))
                    <p class="text-gray-500 text-center py-8">No log file selected</p>
                @elseif(empty($logContent))
                    <p class="text-gray-500 text-center py-8">Log file is empty or not found</p>
                @elseif(empty($this->paginatedLines))
                    <p class="text-gray-500 text-center py-8">No matching log entries</p>
                @else
                    <pre class="font-mono text-sm leading-relaxed"><code>{{ implode("\n", $this->paginatedLines) }}</code></pre>
                @endif
            </div>
        </div>

        <!-- Pagination -->
        @if(count($this->filteredLines) > $linesPerPage)
            <div class="mt-6 flex items-center justify-between">
                <button
                    wire:click="previousPage"
                    disabled="{{ $currentPage <= 1 }}"
                    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
                >
                    Previous
                </button>
                <span class="text-gray-400 text-sm">
                    Page {{ $currentPage }} of {{ max(1, ceil(count($this->filteredLines) / $linesPerPage)) }}
                </span>
                <button
                    wire:click="nextPage"
                    disabled="{{ $currentPage >= ceil(count($this->filteredLines) / $linesPerPage) }}"
                    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
                >
                    Next
                </button>
            </div>
        @endif
    </div>
</div>
