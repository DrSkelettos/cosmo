<?php

namespace App\Http\Livewire;

use App\Ai\Agents\ChatAgent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Chat extends Component
{
    public string $message = '';

    public array $messages = [];

    public bool $loading = false;

    public function sendMessage(): void
    {
        if (empty($this->message) || $this->loading) {
            return;
        }

        $this->loading = true;

        $userMessage = $this->message;
        $this->messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];
        $this->message = '';

        try {
            $agent = new ChatAgent;
            $response = $agent->prompt($userMessage);

            $this->messages[] = [
                'role' => 'assistant',
                'content' => $response->text,
            ];
        } catch (\Exception $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Error: '.$e->getMessage(),
            ];
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
