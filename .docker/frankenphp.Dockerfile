# Unpinned version intentional - this image is for testing/reference
FROM dunglas/frankenphp:alpine
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="FrankenPHP (Caddy + PHP)."

WORKDIR /srv/app

# Note: mbstring and session are bundled by FrankenPHP by default
RUN install-php-extensions apcu gd pdo_mysql

COPY .docker/php/performance.ini /usr/local/etc/php/php.ini
COPY .docker/php/security.ini /usr/local/etc/php/conf.d/security.ini
COPY .docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Project files are mounted via volume in docker-compose.frankenphp.yml

EXPOSE 80
