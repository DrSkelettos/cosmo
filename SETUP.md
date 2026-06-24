# Personal Automation Assistant — Setup

A lightweight Laravel 12 application that exposes a Telegram bot webhook for a single owner. The bot receives commands, routes them to dedicated command classes, and logs all activity.

## Requirements

- PHP 8.2+
- SQLite PHP extension (`pdo_sqlite`, `sqlite3`)
- Composer
- A Telegram bot token from [BotFather](https://t.me/BotFather)
- A VPS with a public HTTPS URL (for the webhook)

## Installation

```bash
composer install --no-dev
```

Copy the example environment file and fill in your values:

```bash
cp .env.example .env
```

Edit `.env`:

```dotenv
APP_NAME="Cosmo Assistant"
APP_URL=https://your-vps-domain.example

TELEGRAM_BOT_TOKEN=123456789:ABC...
TELEGRAM_SECRET_TOKEN=a-random-secret-token
TELEGRAM_OWNER_ID=12345678
TELEGRAM_WEBHOOK_URL=https://your-vps-domain.example/webhooks/telegram
```

> Generate a strong `TELEGRAM_SECRET_TOKEN` with `openssl rand -hex 32`.

Create the application key and SQLite database:

```bash
php artisan key:generate
php artisan migrate
```

## Queue

The default queue driver is already set to `database`. On a VPS you should run a queue worker:

```bash
php artisan queue:work --sleep=3 --tries=3
```

For production, use Supervisor or systemd to keep the worker alive.

## Scheduler

Scheduled tasks are defined in `routes/console.php`. A placeholder weekly schedule is included.

On the VPS add this to the `crontab -e` for the application user:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Webhook registration

Register the webhook with Telegram:

```bash
php artisan telegram:webhook:register
```

You can override the URL for a single run:

```bash
php artisan telegram:webhook:register --url=https://your-vps-domain.example/webhooks/telegram
```

Telegram will now forward messages to `POST /webhooks/telegram` with the `X-Telegram-Bot-Api-Secret-Token` header.

## Commands

The bot currently responds to:

- `/help` — Shows the list of available commands.
- `/ping` — Replies with `Pong!`.

Only the configured `TELEGRAM_OWNER_ID` can send commands.

## Logs

Telegram activity is logged to `storage/logs/telegram.log` via the `telegram` log channel.

## Security

- The webhook controller rejects requests that do not include the correct `X-Telegram-Bot-Api-Secret-Token`.
- All messages from unknown Telegram user IDs are silently rejected and logged.
- Keep `.env` out of version control.

## Project structure

```
app/
├── Console/Commands/TelegramSetWebhookCommand.php
├── Http/Controllers/TelegramWebhookController.php
├── Providers/TelegramServiceProvider.php
├── Services/Telegram/TelegramService.php
└── Telegram/
    ├── Commands/HelpCommand.php
    ├── Commands/PingCommand.php
    ├── CommandRouter.php
    └── Contracts/Command.php
config/
├── telegram.php
└── logging.php (telegram channel added)
routes/
├── webhooks.php
└── console.php (scheduler placeholder)
```

## No additional migrations

Laravel 12 already ships with the `jobs`, `failed_jobs`, and `job_batches` tables required for the database queue, so no custom migrations are needed for this phase.
