# syntax=docker/dockerfile:1.7

FROM dunglas/frankenphp:1.11-php8.4-bookworm AS base

LABEL org.opencontainers.image.title="FrankenPHP application image"
LABEL org.opencontainers.image.description="Multi-stage image for development, staging, production, and CI"

WORKDIR /app

ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    SERVER_NAME=:80

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        default-jre-headless \
        pdftk-java \
    && install-php-extensions \
        pdo \
        pdo_pgsql \
        gd \
        intl \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN useradd --create-home --uid 1000 --shell /bin/bash appuser \
    && mkdir -p /app /data /config \
    && chown -R appuser:appuser /app /data /config

EXPOSE 80 443 443/udp


FROM base AS vendor_dev

COPY composer.json composer.lock ./
COPY bin ./bin
COPY config ./config
COPY public ./public
COPY src ./src
COPY templates ./templates
COPY translations ./translations
COPY migrations ./migrations
COPY .env ./.env

RUN --mount=type=cache,target=/tmp/composer/cache \
    composer install \
        --prefer-dist \
        --no-interaction \
        --no-progress


FROM base AS vendor_prod

COPY composer.json composer.lock ./
COPY bin ./bin
COPY config ./config
COPY public ./public
COPY src ./src
COPY templates ./templates
COPY translations ./translations
COPY migrations ./migrations
COPY .env ./.env

RUN --mount=type=cache,target=/tmp/composer/cache \
    composer install \
        --prefer-dist \
        --no-dev \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
        --classmap-authoritative


FROM base AS dev

ENV APP_ENV=dev

RUN install-php-extensions xdebug

COPY --from=vendor_dev /app/vendor /app/vendor
COPY --chown=appuser:appuser . /app

USER appuser


FROM base AS staging

ENV APP_ENV=prod

COPY --from=vendor_prod /app/vendor /app/vendor
COPY --chown=appuser:appuser . /app

USER appuser

RUN APP_SECRET=build-time-secret php bin/console tailwind:build --minify --env=prod --no-interaction \
    && APP_SECRET=build-time-secret php bin/console asset-map:compile --env=prod --no-interaction


FROM base AS prod

ENV APP_ENV=prod

COPY --from=vendor_prod /app/vendor /app/vendor
COPY --chown=appuser:appuser . /app

USER appuser

RUN APP_SECRET=build-time-secret php bin/console tailwind:build --minify --env=prod --no-interaction \
    && APP_SECRET=build-time-secret php bin/console asset-map:compile --env=prod --no-interaction


FROM dev AS ci

ENV APP_ENV=test

USER appuser
