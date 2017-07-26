FROM composer:latest AS composer

COPY composer.json /app/composer.json

RUN composer install --no-ansi --no-interaction --no-dev --no-progress --classmap-authoritative

FROM t3easy/php:7.1

ENV TYPO3_CONTEXT Development

COPY --from=composer /app .

RUN vendor/bin/typo3cms install:generatepackagestates \
    && vendor/bin/typo3cms install:fixfolderstructure \
    && chown -R www-data:www-data web/
