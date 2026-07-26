FROM php:8.5-fpm-bookworm

# mbstring, curl, opcache are compiled into the official image — only add zip + composer tooling.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-paginium.ini

WORKDIR /var/www/html
