FROM php:8.5-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" \
        mbstring \
        zip \
        curl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-paginium.ini

WORKDIR /var/www/html
