<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('telegram', TelegramWebhookController::class)
    ->name('telegram');
