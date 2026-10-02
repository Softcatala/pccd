FROM nginx:1.30.5-alpine-slim
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Nginx container for serving static files and proxying to PHP-FPM."

COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY .docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY .docker/nginx/security-headers.conf /etc/nginx/security-headers.conf

# Copy static files
COPY docroot /srv/app/docroot
