<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook:register
                            {--url= : The HTTPS URL Telegram should call for updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register the Telegram bot webhook';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        $url = $this->option('url') ?: config('telegram.webhook_url');

        if (empty($url) || $url === 'http://localhost/webhooks/telegram') {
            $this->error('A valid TELEGRAM_WEBHOOK_URL is required.');

            return self::FAILURE;
        }

        $secretToken = (string) config('telegram.secret_token');

        if ($secretToken === '') {
            $this->error('A TELEGRAM_SECRET_TOKEN is required for webhook validation.');

            return self::FAILURE;
        }

        $this->info("Registering webhook URL: {$url}");

        if ($telegram->setWebhook($url, $secretToken)) {
            $this->info('Webhook registered successfully.');

            return self::SUCCESS;
        }

        $this->error('Failed to register webhook. Check the telegram log channel for details.');

        return self::FAILURE;
    }
}
