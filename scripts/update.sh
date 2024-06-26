#!/usr/bin/env bash
#
# Updates Composer/PHIVE/NPM/PECL/apt-get/Maven dependencies and Docker images.
#
# This script is called by npm update script.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/.."

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: ./$(basename "$0") COMMAND"
    echo ""
    echo "    apc-gui"
    echo "      Updates apc.php file to latest revision"
    echo "    composer"
    echo "      Updates all Composer dependencies to latest release, including non-direct dependencies and repositories"
    echo "    docker"
    echo "      Updates Docker images in Docker files and docker-compose.yml to next release"
    echo "    oxipng"
    echo "      Updates oxipng cargo package in Docker files"
    echo "    opcache-gui"
    echo "      Updates OPcache GUI to latest revision"
    echo "    phive"
    echo "      Updates all PHIVE (phar) packages and phive itself to latest releases"
    echo "    npm"
    echo "      Updates all npm dev packages to latest release"
}

##############################################################################
# Checks whether a specific version of a hub.docker.com image exists.
# Arguments:
#   An image name (e.g. "php")
#   An versioned image tag (e.g. "8.2.0-apache-buster")
# Returns:
#   0 if the release exists, 1 otherwise.
##############################################################################
version_exists_dockerhub() {
    local -r IMAGE_NAME="$1"
    local -r IMAGE_TAG="$2"
    local count
    count=$(curl --silent --fail "https://hub.docker.com/v2/repositories/library/${IMAGE_NAME}/tags/?page_size=25&page=1&name=${IMAGE_TAG}" |
        jq --raw-output '.count')
    if [[ ${count} == 0 ]]; then
        return 1
    else
        return 0
    fi
}
export -f version_exists_dockerhub

##############################################################################
# Increments a version.
# Arguments:
#   A versioned image tag or version (e.g. "8.2.0-apache-buster" or "8.2.0")
# Returns:
#   Writes the incremented version to stdout (e.g. "8.2.1-apache-buster" or "8.2.1").
##############################################################################
increment_version() {
    local -r VERSION="$1"
    local current_min_version prefix next_min_version next_version

    current_min_version=$(echo "${VERSION}" | grep -E -o '[0-9]+$')
    prefix=$(echo "${VERSION}" | sed -e "s/${current_min_version}$//")
    next_min_version=$((current_min_version + 1))
    next_version="${prefix}${next_min_version}"

    echo "${next_version}"
}
export -f increment_version

##############################################################################
# Tries to check for a newer version of a hub.docker.com image specified in a Docker file, and updates it.
# Arguments:
#   A Docker file, a path (e.g. ".docker/Dockerfile")
#   An image name (e.g. "php")
##############################################################################
check_version_docker_file() {
    local -r DOCKER_FILE="$1"
    local -r IMAGE_NAME="$2"
    local current_version current_image next_version next_image

    current_version=$(grep -F "${IMAGE_NAME}:" "${DOCKER_FILE}" | grep -E -o '[0-9\.]+')
    current_image=$(grep -F "${IMAGE_NAME}:" "${DOCKER_FILE}" | sed -e "s/FROM ${IMAGE_NAME}://")
    next_version=$(increment_version "${current_version}")
    next_image=$(echo "${current_image}" | sed -e "s/${current_version}/${next_version}/")
    if version_exists_dockerhub "${IMAGE_NAME}" "${next_image}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_image}..."
        sed -i'.original' -e "s/${current_image}/${next_image}/" "${DOCKER_FILE}"
        rm "${DOCKER_FILE}.original"
        exit 1
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date in ${DOCKER_FILE}."
    fi
}

##############################################################################
# Tries to check for a newer version of a hub.docker.com image specified in a Docker Compose file, and updates it.
# Arguments:
#   A Docker Compose file, a path (e.g. "docker-compose.yml")
#   An image name (e.g. "mariadb")
##############################################################################
check_version_docker_compose() {
    local -r COMPOSE_FILE="$1"
    local -r IMAGE_NAME="$2"
    local current_version next_version

    current_version=$(grep -F 'image:' "${COMPOSE_FILE}" | grep -F "${IMAGE_NAME}:" | grep -E -o '[0-9\.]+')
    next_version=$(increment_version "${current_version}")
    if version_exists_dockerhub "${IMAGE_NAME}" "${next_version}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_version}..."
        sed -i'.original' -e "s/${current_version}/${next_version}/" "${COMPOSE_FILE}"
        rm "${COMPOSE_FILE}.original"
        exit 1
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date in ${COMPOSE_FILE}."
    fi
}

##############################################################################
# Installs newest versions of Composer packages. See https://stackoverflow.com/a/74760024/1391963
# Arguments:
#   None
##############################################################################
update_composer() {
    # Make sure repositories are updated too. See https://getcomposer.org/doc/05-repositories.md#package-2
    rm -f composer.lock
    rm -rf vendor/
    tools/composer.phar clear-cache
    tools/composer.phar install

    # Update non-dev dependencies.
    tools/composer.phar show --no-dev --direct --name-only |
        xargs tools/composer.phar require

    # Update dev dependencies.
    grep -F -v -f \
        <(tools/composer.phar show --direct --no-dev --name-only | sort) \
        <(tools/composer.phar show --direct --name-only | sort) |
        xargs tools/composer.phar require --dev

    # Mitigate https://github.com/composer/composer/issues/11698
    tools/composer.phar install
}

##############################################################################
# Updates all npm dev dependencies to latest release.
# Arguments:
#   None
##############################################################################
update_npm() {
    rm -rf node_modules package-lock.json
    jq '.devDependencies | keys | .[]' package.json | xargs npm install --save-dev --ignore-scripts
}

##############################################################################
# Updates a cargo package to the latest version in a Dockerfile.
# Arguments:
#   A Docker file, a path (e.g. "Dockerfile")
#   A cargo package name (e.g. "oxipng")
##############################################################################
update_cargo_dockerfile() {
    local -r DOCKER_FILE="$1"
    local -r PACKAGE_NAME="$2"

    echo "Updating ${DOCKER_FILE} to use latest ${PACKAGE_NAME}..."

    # Fetch the latest version from crates.io
    local latest_version
    latest_version=$(curl --silent "https://crates.io/api/v1/crates/${PACKAGE_NAME}" | jq -r '.crate.max_version')

    if [[ -z ${latest_version} ]]; then
        echo "Error: Could not fetch the latest version of ${PACKAGE_NAME}."
        exit 1
    fi

    if grep -q -r -F -m 1 "${latest_version}" "${DOCKER_FILE}"; then
        echo "Latest ${PACKAGE_NAME} version is already set (${latest_version})."
    else
        echo "Updating ${PACKAGE_NAME} version to ${latest_version}."
        sed -i'.original' -e "s/RUN cargo install ${PACKAGE_NAME} --version .*/RUN cargo install ${PACKAGE_NAME} --version ${latest_version}/" "${DOCKER_FILE}" || {
            echo "Failed to update ${PACKAGE_NAME} version in ${DOCKER_FILE}."
            exit 1
        }
        echo "${PACKAGE_NAME} version updated in ${DOCKER_FILE}."
        rm "${DOCKER_FILE}.original"
    fi
}

if [[ $# != 1 ]]; then
    usage
    exit 1
fi

if [[ $1 == "help" ]]; then
    usage
    exit 0
fi

if [[ $1 == "composer" ]]; then
    update_composer
    exit 0
fi

if [[ $1 == "phive" ]]; then
    php -d memory_limit=256M tools/phive selfupdate --trust-gpg-keys
    yes | php -d memory_limit=256M tools/phive update --force-accept-unsigned
    exit 0
fi

if [[ $1 == "apc-gui" ]]; then
    curl --silent --fail https://raw.githubusercontent.com/krakjoe/apcu/master/apc.php > src/third_party/apc.php
    exit 0
fi

if [[ $1 == "opcache-gui" ]]; then
    curl --silent --fail https://raw.githubusercontent.com/amnuts/opcache-gui/master/index.php > src/third_party/opcache-gui.php
    exit 0
fi

if [[ $1 == "npm" ]]; then
    update_npm
    exit 0
fi

if [[ $1 == "docker" ]]; then
    check_version_docker_file .docker/debian.dev.Dockerfile php
    check_version_docker_file .docker/alpine.dev.Dockerfile alpine
    check_version_docker_compose docker-compose.yml mariadb
    check_version_docker_compose docker-compose-alpine.yml mariadb
    exit 0
fi

if [[ $1 == "oxipng" ]]; then
    update_cargo_dockerfile .docker/ubuntu.build.Dockerfile oxipng
    exit 0
fi

usage
exit 1
