FROM dunglas/frankenphp:php8-alpine
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="FrankenPHP (Caddy + PHP) image for simplified deployment."

WORKDIR /srv/app

RUN install-php-extensions \
    apcu \
    gd \
    pdo_mysql

COPY .docker/php/performance.ini /usr/local/etc/php/php.ini
COPY .docker/php/security.ini /usr/local/etc/php/conf.d/security.ini
COPY .docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tests ./tests
COPY tmp ./tmp

EXPOSE 80
