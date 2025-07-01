# hadolint ignore=DL3007
FROM ubuntu:latest

LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="Ubuntu-based image for building a new release."

WORKDIR /srv/app

# Copy project files
COPY . .

# Install apt-get packages
RUN apt-get update \
    && xargs apt-get install --no-install-recommends -y < apt_packages.txt \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install the rest of dev dependencies
RUN npm ci
