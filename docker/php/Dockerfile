# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Base: PHP-FPM + the extensions this application needs.
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-bookworm AS base

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libicu-dev \
        libzip-dev \
        libssl-dev \
        pkg-config \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip opcache \
    && pecl install redis \
    && pecl install pcov \
    && docker-php-ext-enable redis pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN groupadd --gid "${GID}" app \
    && useradd --uid "${UID}" --gid app --create-home --shell /bin/bash app

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Development: source code is bind-mounted by docker-compose.
# ---------------------------------------------------------------------------
FROM base AS development

USER app

CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Production: immutable, optimized, runs as a non-root user.
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY --chown=app:app . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan package:discover --ansi \
    && chown -R app:app storage bootstrap/cache

USER app

ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]
