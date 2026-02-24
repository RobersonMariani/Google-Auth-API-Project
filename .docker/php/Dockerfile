FROM php:8.4-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

RUN addgroup -g ${GID} appgroup \
    && adduser -u ${UID} -G appgroup -s /bin/sh -D appuser

RUN apk add --no-cache \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache \
        pcntl \
    && rm -rf /var/cache/apk/*

COPY .docker/php/php.ini "$PHP_INI_DIR/conf.d/99-custom.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize \
    && php artisan config:clear

RUN chown -R appuser:appgroup /var/www/storage /var/www/bootstrap/cache

USER appuser

EXPOSE 9000

CMD ["php-fpm"]

# ---------------------------------------------------------------------------

FROM base AS dev

USER root

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

RUN composer install --no-scripts --prefer-dist

RUN chown -R appuser:appgroup /var/www/storage /var/www/bootstrap/cache

USER appuser

CMD ["php-fpm"]
