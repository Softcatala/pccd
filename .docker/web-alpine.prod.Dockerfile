FROM alpine:3.22
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Alpine-based image with Apache and mod_php. Used in production."

ARG ARG_MYSQL_DB
ARG ARG_MYSQL_PWD
ARG ARG_MYSQL_USER
ARG ARG_WEB_ADMIN_PWD

ENV MYSQL_DATABASE=${ARG_MYSQL_DB}
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_USER=${ARG_MYSQL_USER}
ENV WEB_ADMIN_PASSWORD=${ARG_WEB_ADMIN_PWD}

# Set working directory
WORKDIR /srv/app

# Install Apache, Apache modules, PHP and PHP extensions
# hadolint ignore=DL3018
RUN apk --no-cache --update add \
    apache2 \
    apache2-brotli \
    php84-apache2 \
    php84-apcu \
    php84-gd \
    php84-mbstring \
    php84-opcache \
    php84-pdo_mysql \
    php84-session

# Remove Apache DocumentRoot default settings
RUN sed -i '/^DocumentRoot/d' /etc/apache2/httpd.conf \
    # Do not expose unnecessary Server information
    && echo 'ServerTokens Prod' >> /etc/apache2/httpd.conf \
    # Enable necessary Apache modules
    && sed -i 's/#LoadModule\ deflate_module/LoadModule\ deflate_module/' /etc/apache2/httpd.conf \
    && sed -i 's/#LoadModule\ headers_module/LoadModule\ headers_module/' /etc/apache2/httpd.conf \
    && sed -i 's/#LoadModule\ rewrite_module/LoadModule\ rewrite_module/' /etc/apache2/httpd.conf \
    # Hide PHP
    && echo 'expose_php = Off' > /etc/php84/conf.d/security.ini

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/conf.d/vhost.conf
COPY .docker/php/performance.ini /etc/php84/conf.d/performance.ini

# Copy project files
COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tmp ./tmp
COPY tests ./tests

# Remove default Apache logs and create symbolic links to stdout and stderr
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log

# Start Apache
CMD ["httpd", "-D", "FOREGROUND"]
