FROM alpine:3.23
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Alpine-based image with PHP-FPM for improved concurrency."

ARG PHP_VERSION=85
ARG ARG_MYSQL_DB
ARG ARG_MYSQL_PWD
ARG ARG_MYSQL_USER
ARG ARG_WEB_ADMIN_PWD

ENV MYSQL_DATABASE=${ARG_MYSQL_DB}
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_USER=${ARG_MYSQL_USER}
ENV WEB_ADMIN_PASSWORD=${ARG_WEB_ADMIN_PWD}

WORKDIR /srv/app

# hadolint ignore=DL3018
RUN apk --no-cache --update add \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-apcu \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-pdo_mysql \
    php${PHP_VERSION}-session && \
    ln -s /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm

COPY .docker/php/performance.ini /etc/php${PHP_VERSION}/conf.d/performance.ini
COPY .docker/php/security.ini /etc/php${PHP_VERSION}/conf.d/security.ini
COPY .docker/php/fpm.conf /etc/php${PHP_VERSION}/php-fpm.d/zzz-docker.conf

COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tests ./tests
COPY tmp ./tmp

EXPOSE 9000

CMD ["php-fpm", "-F"]
