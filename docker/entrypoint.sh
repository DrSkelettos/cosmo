#!/bin/sh
set -e

# Ensure writable directories exist
mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/sessions /var/www/html/database /var/log/supervisor
chown -R www-data:www-data /var/www/html/storage /var/www/html/database
chown www-data:www-data /var/www/html/storage/logs
chmod 664 /var/www/html/storage/logs
chown www-data:www-data /var/www/html/storage/framework/sessions
chmod 664 /var/www/html/storage/framework/sessions

# Create and set permissions for laravel.log file
touch /var/www/html/storage/logs/laravel.log
chown www-data:www-data /var/www/html/storage/logs/laravel.log
chmod 664 /var/www/html/storage/logs/laravel.log

# Create and set permissions for cron log file
touch /var/www/html/storage/logs/cron.log
chown www-data:www-data /var/www/html/storage/logs/cron.log
chmod 664 /var/www/html/storage/logs/cron.log

# Create and set permissions for cron supervisor log files
touch /var/www/html/storage/logs/cron-supervisor.log
touch /var/www/html/storage/logs/cron-supervisor.err
chown www-data:www-data /var/www/html/storage/logs/cron-supervisor.log
chown www-data:www-data /var/www/html/storage/logs/cron-supervisor.err
chmod 664 /var/www/html/storage/logs/cron-supervisor.log
chmod 664 /var/www/html/storage/logs/cron-supervisor.err

# Generate an application key if the environment does not provide one
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --no-interaction
fi

# Run migrations (creates the SQLite database and queue tables if missing)
php artisan migrate --force --no-interaction

# Fix database ownership after migrations
if [ -f /var/www/html/database/database.sqlite ]; then
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    echo "Fine! Database file found and permissions set." >&2
else
    echo "WARNING: /var/www/html/database/database.sqlite does not exist. Run migrations or create it manually." >&2
fi

exec "$@"
