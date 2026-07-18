<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramService;
use App\Telegram\CommandRouter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramService $telegram,
        protected CommandRouter $router,
    ) {
        //
    }

    /**
     * Handle an incoming Telegram webhook update.
     */
    public function __invoke(Request $request): Response
    {
        if (! $this->hasValidSecret($request)) {
            Log::channel('telegram')->warning('Unauthorized webhook attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(401);
        }

        $update = $request->all();
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return response()->noContent();
        }

        $userId = $message['from']['id'] ?? null;

        if ((int) $userId !== (int) config('telegram.owner_id')) {
            Log::channel('telegram')->warning('Message from non-owner user rejected', [
                'user_id' => $userId,
                'chat_id' => $message['chat']['id'] ?? null,
            ]);

            return response()->noContent();
        }

        try {
            $this->router->route($update);
        } catch (Throwable $e) {
            Log::channel('telegram')->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->noContent();
    }

    /**
     * Validate the Telegram webhook secret token header.
     */
    protected function hasValidSecret(Request $request): bool
    {
        $expected = (string) config('telegram.secret_token');

        if ($expected === '') {
            return false;
        }

        $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        return hash_equals($expected, $provided);
    }
}
