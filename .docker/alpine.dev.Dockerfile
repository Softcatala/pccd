FROM alpine:3.19
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Alpine-based image with Apache and PHP."

# Set working directory
WORKDIR /srv/app

# Install Apache, Apache modules, PHP and PHP extensions
# hadolint ignore=DL3018
RUN apk --no-cache --update add \
    apache2 \
    apache2-brotli \
    php83-apache2 \
    php83-apcu \
    php83-common \
    php83-intl \
    php83-mbstring \
    php83-opcache \
    php83-pdo_mysql \
    php83-session

# Remove some Apache default settings
# Add AVIF type to Apache
# Disable unnecessary Apache modules
# Enable Apache modules
RUN sed -i '/^DocumentRoot/d' /etc/apache2/httpd.conf \
    && echo 'image/avif avif' >> /etc/apache2/mime.types \
    && sed -i 's/LoadModule access_compat_module/#LoadModule access_compat_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule alias_module/#LoadModule alias_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule auth_basic_module/#LoadModule auth_basic_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule authn_core_module/#LoadModule authn_core_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule authn_file_module/#LoadModule authn_file_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule authz_groupfile_module/#LoadModule authz_groupfile_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule authz_host_module/#LoadModule authz_host_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule authz_user_module/#LoadModule authz_user_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule autoindex_module/#LoadModule autoindex_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule negotiation_module/#LoadModule negotiation_module/' /etc/apache2/httpd.conf \
    && rm /etc/apache2/conf.d/languages.conf \
    && sed -i 's/LoadModule reqtimeout_module/#LoadModule reqtimeout_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule setenvif_module/#LoadModule setenvif_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule status_module/#LoadModule status_module/' /etc/apache2/httpd.conf \
    && sed -i 's/LoadModule version_module/#LoadModule version_module/' /etc/apache2/httpd.conf \
    && sed -i 's/#LoadModule\ deflate_module/LoadModule\ deflate_module/' /etc/apache2/httpd.conf \
    && sed -i 's/#LoadModule\ headers_module/LoadModule\ headers_module/' /etc/apache2/httpd.conf \
    && sed -i 's/#LoadModule\ rewrite_module/LoadModule\ rewrite_module/' /etc/apache2/httpd.conf

# Copy configuration files
COPY .docker/apache/vhost.conf /etc/apache2/conf.d/vhost.conf
#COPY .docker/php/production.ini /etc/php83/conf.d/production.ini

# Copy project files
COPY docroot ./docroot
COPY scripts ./scripts
COPY src ./src
COPY tmp ./tmp

# Remove default Apache logs and create symbolic links to stdout and stderr
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log

# Start Apache
CMD ["httpd", "-D", "FOREGROUND"]