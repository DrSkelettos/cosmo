#!/bin/sh
set -e

# Ensure writable directories exist
mkdir -p /var/www/html/storage/logs /var/www/html/database /var/log/supervisor
chown -R www-data:www-data /var/www/html/storage /var/www/html/database

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
fi

exec "$@"
