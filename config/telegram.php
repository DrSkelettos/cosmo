<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Credentials
    |--------------------------------------------------------------------------
    |
    | bot_token is the token issued by BotFather. secret_token is a random
    | value used to verify incoming webhook requests. owner_id is the Telegram
    | user ID that is allowed to interact with the bot.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'secret_token' => env('TELEGRAM_SECRET_TOKEN'),

    'owner_id' => (int) env('TELEGRAM_OWNER_ID', 0),

    /*
    |--------------------------------------------------------------------------
    | Telegram API Endpoint
    |--------------------------------------------------------------------------
    |
    | The base URL used for all Telegram Bot API requests. The bot token is
    | appended to this URL.
    |
    */

    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org/bot'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | The URL that Telegram should call when new updates arrive. If no value
    | is set, APP_URL will be used together with the default webhook path.
    |
    */

    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', env('APP_URL', 'http://localhost').'/webhooks/telegram'),

    /*
    |--------------------------------------------------------------------------
    | Default Message Format
    |--------------------------------------------------------------------------
    |
    | Supported values: Markdown, MarkdownV2, HTML.
    |
    */

    'parse_mode' => env('TELEGRAM_PARSE_MODE', 'MarkdownV2'),

];
