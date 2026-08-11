FROM php:8.5-fpm-bookworm

# mbstring, curl, opcache are compiled into the official image — git, zip, composer, node for admin deploy.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Node 22 for `npm ci && npm run build:prod` inside deploy-instance-update.sh (admin UI deploy).
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/bin/npm /usr/local/bin/npm
COPY --from=node:22-bookworm-slim /usr/local/bin/npx /usr/local/bin/npx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-paginium.ini

WORKDIR /var/www/html
