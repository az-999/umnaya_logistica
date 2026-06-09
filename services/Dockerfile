FROM php:8.4-cli-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    icu-dev \
    libpq-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install intl pdo_pgsql pcntl sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apk del $PHPIZE_DEPS linux-headers

WORKDIR /app

COPY services/notification/composer.json services/notification/composer.lock ./
RUN composer install --no-scripts --prefer-dist --optimize-autoloader

COPY services/notification/ .
RUN rm -f .env \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi

FROM base AS api

COPY services/api/docker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]

FROM base AS worker

COPY services/worker/docker-entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
