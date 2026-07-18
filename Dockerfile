FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    cron \
    libsqlite3-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    curl \
    nodejs \
    npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions that are not already enabled in the base image
RUN set -eux; \
    for ext in pdo pdo_sqlite sqlite3 mbstring bcmath zip opcache; do \
        if [ "$ext" = "opcache" ]; then \
            php -m | grep -qi 'opcache' && continue; \
        fi; \
        php -m | grep -qi "^${ext}$" && continue; \
        docker-php-ext-install "$ext"; \
    done; \
    # pcntl is useful for queue workers but not available/safe in every FPM image; ignore failure
    php -m | grep -qi '^pcntl$' || docker-php-ext-install pcntl || true

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

# Copy npm files and install dependencies for layer caching
COPY package.json package-lock.json ./
RUN npm install --ignore-scripts

# Copy application code
COPY . .

# Build assets
RUN npm run build

# Create storage/database directories and set permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod 664 /var/www/html/database/database.sqlite

# Copy docker configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/cron /etc/cron.d/laravel
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod 0644 /etc/cron.d/laravel \
    && crontab /etc/cron.d/laravel \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/log/supervisor

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
