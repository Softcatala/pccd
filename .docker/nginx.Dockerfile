FROM nginx:1.31-alpine-slim
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Nginx container for serving static files and proxying to PHP-FPM."

COPY .docker/nginx/nginx.conf /etc/nginx/
COPY .docker/nginx/security-headers.conf /etc/nginx/
COPY .docker/nginx/default.conf /etc/nginx/conf.d/

# Copy static files
COPY docroot /srv/app/docroot
