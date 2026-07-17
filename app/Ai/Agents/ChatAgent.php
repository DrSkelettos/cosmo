<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Ollama)]
#[Model('llama3.2')]
class ChatAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a helpful and friendly AI assistant. Provide clear, concise, and accurate responses to user questions.';
    }
}
