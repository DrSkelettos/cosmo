<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Chat</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Powered by Llama 3.2 via Ollama</p>
            </div>

            <div class="h-[500px] overflow-y-auto p-6 space-y-4" id="chat-messages">
                @if(empty($messages))
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        <p class="text-lg">Start a conversation with the AI assistant</p>
                        <p class="text-sm mt-2">Type your message below and press Enter or click Send</p>
                    </div>
                @endif

                @foreach($messages as $message)
                    @if($message['role'] === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-[80%] bg-blue-500 text-white rounded-lg px-4 py-2">
                                {{ $message['content'] }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-[80%] bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2">
                                {!! nl2br(e($message['content'])) !!}
                            </div>
                        </div>
                    @endif
                @endforeach

                @if($loading)
                    <div class="flex justify-start">
                        <div class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-4 py-2">
                            <div class="flex space-x-2">
                                <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-600">
                <form wire:submit.prevent="sendMessage">
                    <div class="flex space-x-2">
                        <input
                            type="text"
                            wire:model="message"
                            placeholder="Type your message..."
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            {{ $loading ? 'disabled' : '' }}
                        >
                        <button
                            type="submit"
                            {{ $loading ? 'disabled' : '' }}
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ $loading ? 'Sending...' : 'Send' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('message-sent', () => {
                const chatMessages = document.getElementById('chat-messages');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            });
        });

        @this.on('render', () => {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    </script>
</div>
