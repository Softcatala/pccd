ARG PHP_IMAGE_TAG=8.4.8-apache-bookworm

FROM php:${PHP_IMAGE_TAG}
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Debian-based image with Apache and mod_php. Used for development."

ARG profiler

# Set working directory
WORKDIR /srv/app

# Install install-php-extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Update package list
RUN apt-get update && apt-get clean && rm -rf /var/lib/apt/lists/*

# Remove some Apache default settings provided by Debian
# Enable Apache modules
# Use PHP default development settings
# Install PHP extensions, ommitting OPcache/APCu to reduce Docker build times
RUN rm -f /etc/apache2/mods-enabled/deflate.conf /etc/apache2/mods-enabled/alias.conf && \
    a2enmod rewrite headers brotli && \
    cat /usr/local/etc/php/php.ini-development > /usr/local/etc/php/php.ini && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd intl pdo_mysql
#RUN install-php-extensions apcu opcache

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Copy project files
COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tmp ./tmp

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
