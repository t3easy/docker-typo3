# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.1
ARG TARGET_ENVIRONMENT=production
ARG NODE_VERSION=14

FROM node:${NODE_VERSION}-alpine as node
ENV YARN_CACHE_FOLDER /tmp/cache/yarn
ENV npm_config_cache /tmp/cache/npm

RUN set -eux; \
        yarn global add gulp-cli; \
        rm -rf ${YARN_CACHE_FOLDER}; \
        gulp --version
WORKDIR /app

FROM nginx:alpine as web-base
LABEL org.opencontainers.image.source="https://github.com/t3easy/docker-typo3"
ENV CLIENT_MAX_BODY_SIZE=100m
COPY .docker/web/*.conf.template /etc/nginx/templates/
WORKDIR /app

FROM web-base as web-development

FROM ghcr.io/t3easy/php:${PHP_VERSION}-${TARGET_ENVIRONMENT} AS php-base
LABEL org.opencontainers.image.source="https://github.com/t3easy/docker-typo3"
COPY .docker/cron.sh /usr/local/bin/cron
RUN rm /var/spool/cron/crontabs/root \
 && echo '*/15 * * * * php -f /app/vendor/bin/typo3 scheduler:run' > /var/spool/cron/crontabs/www-data
COPY .docker/ca-certificates /usr/local/share/ca-certificates
RUN update-ca-certificates
RUN set -eux; \
        mkdir -p /app/config; \
        mkdir -p /app/private/fileadmin; \
        mkdir -p /app/private/typo3temp; \
        mkdir -p /app/var; \
        mkdir -p /app/var/session; \
        chown -R www-data:www-data /app

FROM php-base as php-development
ENV TYPO3_CONTEXT=Development

FROM ghcr.io/t3easy/php:${PHP_VERSION}-development AS builder
ENV XDEBUG_MODE=off
COPY . /app
# Remove .docker because it cannot be excluded with .dockerignore
RUN rm -rf /app/.docker
RUN set -eux; \
        composer install --no-ansi --no-interaction --no-dev --no-progress --classmap-authoritative; \
        typo3 cache:warmup

FROM web-base as web-production
COPY --chown=101 --from=builder /app /app

FROM php-base as php-production
COPY --chown=82 --from=builder /app .
ENV TYPO3_CONTEXT=Production
