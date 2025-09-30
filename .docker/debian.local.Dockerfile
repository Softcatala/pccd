# hadolint ignore=DL3007
FROM debian:stable-slim

LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Debian-based image for building a new release 100% inside Docker (not usually tested)."

WORKDIR /srv/app

COPY apt_packages.txt .
RUN apt-get update \
    && xargs apt-get install --no-install-recommends -y < apt_packages.txt \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN npx playwright install-deps
