FROM alpine:3.24
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Alpine-based image with PHP-FPM for improved concurrency."

# Using Alpine's PHP packages instead of official php:fpm-alpine image avoids the need for
# docker-php-ext-install or install-php-extensions, and apk is faster for installing extensions.
ARG PHP_VERSION=85

# ENV from ARGs needed for production deployment (values baked into image at build time)
ARG ARG_MYSQL_PWD
ARG ARG_WEB_ADMIN_PWD
ENV MYSQL_DATABASE=pccd
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_USER=pccd
ENV WEB_ADMIN_PASSWORD=${ARG_WEB_ADMIN_PWD}

WORKDIR /srv/app

RUN apk add --no-cache \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-apcu \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-pdo_mysql \
    php${PHP_VERSION}-session \
  && ln -sf /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm

COPY .docker/php/custom.ini /etc/php${PHP_VERSION}/conf.d/
COPY .docker/php/fpm.conf /etc/php${PHP_VERSION}/php-fpm.d/zzz-docker.conf

# Add only the specific files/directories accessed by PHP itself
COPY src ./src
COPY docroot/index.php ./docroot/
# Error pages are referenced by both PHP and the HTTP server config
COPY docroot/404.html ./docroot/
COPY docroot/500.html ./docroot/
COPY docroot/admin/index.php ./docroot/admin/
# CSS and JS files are inlined by PHP
COPY docroot/css ./docroot/css
COPY docroot/js ./docroot/js
# Image dimensions are read by PHP
COPY docroot/img ./docroot/img
# DB date is read by the runtime, and the output of some reports is stored here too
COPY data ./data

EXPOSE 9000

CMD ["php-fpm", "-F"]
