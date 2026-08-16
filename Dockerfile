# Root-level so Render can build with its default ./Dockerfile path.
# The API itself lives in server/; everything below is scoped to it.
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql bcmath intl zip gd exif pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Manifests first so composer's layer survives unrelated code changes.
COPY server/composer.json server/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-dev --no-scripts --no-autoloader

COPY server/ ./

# dump-autoload fires post-autoload-dump -> artisan package:discover, which has
# to boot the app. Give it a .env to boot against; Render's real environment
# variables win at runtime (Dotenv won't overwrite ones already set).
RUN cp -n .env.example .env || true \
    && composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN chmod +x docker-entrypoint.sh && cp docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

ENV PORT=8000
EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
