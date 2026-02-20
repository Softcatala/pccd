# Unpinned version intentional - this image is rarely used
FROM debian:stable-slim
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Debian-based image for building a new release 100% inside Docker (not usually tested)."

WORKDIR /srv/app

COPY apt_dev_deps.txt .

# hadolint ignore=SC2046 # We want word splitting to pass packages as separate arguments
RUN apt-get update \
    && apt-get install --no-install-recommends -y $(cat apt_dev_deps.txt) \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN npx playwright install --with-deps chromium
