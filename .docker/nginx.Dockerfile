FROM alpine:3.23
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Nginx container for serving static files and proxying to PHP-FPM."

# Using Alpine's nginx package allows easy installation of nginx-mod-http-brotli,
# which would require compiling from source with the official nginx:alpine image.
RUN apk add --no-cache --update nginx nginx-mod-http-brotli

COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY .docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY .docker/nginx/security-headers.conf /etc/nginx/security-headers.conf

# Copy static files
COPY docroot /srv/app/docroot

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
