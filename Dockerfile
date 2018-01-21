FROM composer:latest AS composer

COPY typo3 /app

RUN composer install --no-ansi --no-interaction --no-dev --no-progress --classmap-authoritative

FROM t3easy/php:7.1

ENV TYPO3_CONTEXT Development

COPY --chown=www-data:www-data --from=composer /app .
