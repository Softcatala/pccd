ARG PHP_IMAGE_TAG=8.5.3-apache-trixie

FROM php:${PHP_IMAGE_TAG}
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Debian-based image with Apache and mod_php. Used for development."

ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION=2.9.30
ARG profiler

WORKDIR /srv/app

# Install install-php-extensions
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/${DOCKER_PHP_EXTENSION_INSTALLER_VERSION}/install-php-extensions /usr/local/bin/

# Remove some Apache default settings provided by Debian
# Enable Apache modules
# Use PHP default development settings
# Install PHP extensions (intl is used for offline reports)
RUN rm -f /etc/apache2/mods-enabled/deflate.conf /etc/apache2/mods-enabled/alias.conf && \
    a2enmod headers brotli && \
    cat /usr/local/etc/php/php.ini-development > /usr/local/etc/php/php.ini && \
    install-php-extensions apcu gd intl pdo_mysql

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Project files are mounted via volume in docker-compose.yml

# SPX profiler
RUN if [ "$profiler" = "spx" ]; then \
        install-php-extensions spx && \
        { \
        echo "[spx]"; \
        echo "spx.http_enabled = 1"; \
        echo "spx.http_ip_whitelist = \"*\""; \
        echo "spx.http_key = \"dev\""; \
        } > /usr/local/etc/php/conf.d/spx.ini; \
    fi

# XHProf profiler
RUN if [ "$profiler" = "xhprof" ]; then \
        install-php-extensions xhprof && \
        sed -i '/<\/VirtualHost>/d' /etc/apache2/sites-available/000-default.conf && \
        { \
        echo "Alias /admin/xhprof /usr/local/lib/php/xhprof_html"; \
        echo "<Directory /usr/local/lib/php/xhprof_html/>"; \
        echo "    Options Indexes FollowSymLinks"; \
        echo "    AllowOverride FileInfo"; \
        echo "    Require all granted"; \
        echo "    php_value auto_prepend_file none"; \
        echo "    php_value memory_limit 1024M"; \
        echo "</Directory>"; \
        } >> /etc/apache2/sites-available/000-default.conf; \
        echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf && \
        { \
        echo "[xhprof]"; \
        echo "auto_prepend_file = /srv/app/src/xhprof.php"; \
        echo "xhprof.collect_additional_info = 1"; \
        echo "xhprof.output_dir = /tmp"; \
        } > /usr/local/etc/php/conf.d/xhprof.ini; \
    fi
