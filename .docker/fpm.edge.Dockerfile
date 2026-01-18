FROM alpine:edge
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Alpine edge-based image with PHP-FPM, for testing latest PHP."

ARG PHP_VERSION=85

WORKDIR /srv/app

# hadolint ignore=DL3018
RUN echo "http://dl-cdn.alpinelinux.org/alpine/edge/testing" >> /etc/apk/repositories && \
    apk --no-cache --update add \
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
