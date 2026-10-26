FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libmagickwand-dev \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install opcache pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY docker/backend/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY backend/ ./

RUN composer dump-autoload --classmap-authoritative --no-scripts

RUN mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var

USER www-data
CMD ["php-fpm", "-F"]
