FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql mbstring dom xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app/backend
