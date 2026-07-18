<?php

namespace App\Telegram;

use App\Jobs\HandleChatMessage;

class ChatHandler
{
    /**
     * Dispatch a chat message to the queue for asynchronous AI processing.
     *
     * @param  array<string, mixed>  $message
     */
    public function handle(array $message): void
    {
        HandleChatMessage::dispatch($message);
    }
}
