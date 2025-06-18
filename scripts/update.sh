#!/usr/bin/env bash
#
# Updates dependencies to latest release.
#
# This script is called by npm update script.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")/.."

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: ./$(basename "$0") COMMAND"
    echo ""
    echo "    composer"
    echo "      Updates all Composer dependencies to latest release, including non-direct dependencies, repositories and Composer itself"
    echo "    docker"
    echo "      Updates Docker images in Docker files and docker-compose.yml to next release"
    echo "    npm"
    echo "      Updates all npm dev packages to latest release"
}

##############################################################################
# Checks whether a specific version of a hub.docker.com image exists.
# Arguments:
#   An image name (e.g. "php")
#   A versioned image tag (e.g. "8.2.0-apache-buster")
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
# If a reference tag is provided, also checks if the current version matches that tag.
# Arguments:
#   A Docker file path (e.g. ".docker/Dockerfile")
#   An image name (e.g. "php")
#   [Optional] A reference tag to compare with (e.g. "latest")
##############################################################################
check_version_docker_file() {
    local -r DOCKER_FILE="$1"
    local -r IMAGE_NAME="$2"
    local reference_tag=""

    # Handle optional third argument (reference tag)
    if [[ $# -ge 3 ]]; then
        reference_tag="$3"
    fi

    local current_version current_image next_version next_image
    current_version=$(grep -F "${IMAGE_NAME}:" "${DOCKER_FILE}" | grep -E -o '[0-9\.]+')
    current_image=$(grep -F "${IMAGE_NAME}:" "${DOCKER_FILE}" | sed -e "s/FROM ${IMAGE_NAME}://")
    next_version=$(increment_version "${current_version}")
    next_image=$(echo "${current_image}" | sed -e "s/${current_version}/${next_version}/")

    if version_exists_dockerhub "${IMAGE_NAME}" "${next_image}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_image}..."
        sed -i'.original' -e "s/${current_image}/${next_image}/" "${DOCKER_FILE}"
        rm "${DOCKER_FILE}.original"
        current_image=${next_image}
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date in ${DOCKER_FILE}."
    fi

    # If reference tag is provided, check if the current version matches it
    if [[ -n "${reference_tag}" ]]; then
        compare_docker_tags "${IMAGE_NAME}" "${current_image}" "${reference_tag}"
    fi
}

##############################################################################
# Tries to check for a newer version of a hub.docker.com image specified in a Docker Compose file, and updates it.
# Arguments:
#   A Docker Compose file path (e.g. "docker-compose.yml")
#   An image name (e.g. "mariadb")
#   [Optional] A reference tag to compare with (e.g. "lts")
##############################################################################
check_version_docker_compose() {
    local -r COMPOSE_FILE="$1"
    local -r IMAGE_NAME="$2"
    local reference_tag=""

    # Handle optional third argument (reference tag)
    if [[ $# -ge 3 ]]; then
        reference_tag="$3"
    fi

    local current_version next_version
    current_version=$(grep -F 'image:' "${COMPOSE_FILE}" | grep -F "${IMAGE_NAME}:" | grep -E -o '[0-9\.]+')
    next_version=$(increment_version "${current_version}")
    if version_exists_dockerhub "${IMAGE_NAME}" "${next_version}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_version}..."
        sed -i'.original' -e "s/${current_version}/${next_version}/" "${COMPOSE_FILE}"
        rm "${COMPOSE_FILE}.original"
        current_version=${next_version}
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date in ${COMPOSE_FILE}."
    fi

    # If reference tag is provided, check if the current version matches it
    if [[ -n "${reference_tag}" ]]; then
        compare_docker_tags "${IMAGE_NAME}" "${current_version}" "${reference_tag}"
    fi
}

##############################################################################
# Updates the version of a Docker image specified with an ARG directive in a
# Dockerfile if a newer version exists on Docker Hub.
# Arguments:
#   A Dockerfile path (e.g., "./Dockerfile")
#   An image name (e.g., "php")
#   The ARG name used to specify the image tag (e.g., "PHP_IMAGE_TAG")
#   [Optional] A reference tag to compare with (e.g. "8-apache", defaults to empty)
##############################################################################
update_dockerfile_arg_image_version() {
    local -r DOCKERFILE="$1"
    local -r IMAGE_NAME="$2"
    local -r ARG_NAME="$3"
    local reference_tag=""

    # Handle optional fourth argument (reference tag)
    if [[ $# -ge 4 ]]; then
        reference_tag="$4"
    fi

    local current_tag numeric_version suffix next_numeric_version next_tag updated_line

    # Extract the current image tag from the ARG directive
    current_tag=$(grep -E "^ARG ${ARG_NAME}=" "${DOCKERFILE}" | sed -E "s/^ARG ${ARG_NAME}=//")
    if [[ -z "${current_tag}" ]]; then
        echo "ERROR: ARG ${ARG_NAME} not found in ${DOCKERFILE}."
        return 1
    fi

    # Split the tag into the numeric version and suffix
    numeric_version=$(echo "${current_tag}" | grep -E -o '^[0-9]+\.[0-9]+\.[0-9]+')
    suffix=$(echo "${current_tag}" | sed -E "s/${numeric_version}//")

    if [[ -z "${numeric_version}" ]]; then
        echo "ERROR: Failed to parse the numeric version from the tag '${current_tag}'."
        return 1
    fi

    # Increment the numeric version
    next_numeric_version=$(increment_version "${numeric_version}")

    # Construct the next tag
    next_tag="${next_numeric_version}${suffix}"

    # Check if the incremented version exists on Docker Hub
    if version_exists_dockerhub "${IMAGE_NAME}" "${next_tag}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating ${ARG_NAME} to ${next_tag}..."

        # Update the ARG directive in the Dockerfile
        updated_line="ARG ${ARG_NAME}=${next_tag}"
        sed -i'.original' -E "s/^ARG ${ARG_NAME}=.*/${updated_line}/" "${DOCKERFILE}"
        rm "${DOCKERFILE}.original"
        current_tag=${next_tag}
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date in ${DOCKERFILE}."
    fi

    # If reference tag is provided, check if the current tag matches it
    if [[ -n "${reference_tag}" ]]; then
        compare_docker_tags "${IMAGE_NAME}" "${current_tag}" "${reference_tag}"
    fi
}
export -f update_dockerfile_arg_image_version

##############################################################################
# Compares two Docker Hub tags of the same image to check if they are identical.
# Arguments:
#   An image name (e.g. "mariadb")
#   First tag (e.g. "11.4.5-noble")
#   Second tag (e.g. "lts")
# Returns:
#   0 if the tags match (same image content)
#   1 if the tags differ
#   2 if pulling one or both images fails
##############################################################################
compare_docker_tags() {
    local -r IMAGE_NAME="$1"
    local -r TAG1="$2"
    local -r TAG2="$3"

    # Pull both images to get their actual digests
    local digest1 digest2
    digest1=$(docker pull "${IMAGE_NAME}:${TAG1}" 2> /dev/null | grep -o 'sha256:[a-f0-9]*' | head -1)
    digest2=$(docker pull "${IMAGE_NAME}:${TAG2}" 2> /dev/null | grep -o 'sha256:[a-f0-9]*' | head -1)

    if [[ -z "${digest1}" || -z "${digest2}" ]]; then
        echo "Error: Failed to pull one or both images"
        return 2
    fi

    if [[ "${digest1}" == "${digest2}" ]]; then
        echo "OK: ${IMAGE_NAME}:${TAG1} and ${IMAGE_NAME}:${TAG2} refer to the same image."
        return 0
    else
        echo "❌ Tags don't match. ${IMAGE_NAME}:${TAG1} and ${IMAGE_NAME}:${TAG2} refer to different images."
        echo "   ${IMAGE_NAME}:${TAG1} digest: ${digest1}"
        echo "   ${IMAGE_NAME}:${TAG2} digest: ${digest2}"
        return 1
    fi
}
export -f compare_docker_tags

##############################################################################
# Installs newest versions of Composer packages. See https://stackoverflow.com/a/74760024/1391963
# Arguments:
#   None
##############################################################################
update_composer() {
    # Update composer itself.
    ./composer.phar self-update

    # Update non-dev dependencies.
    ./composer.phar show --no-dev --direct --name-only |
        xargs ./composer.phar require --no-cache --update-with-all-dependencies

    # Update dev dependencies.
    grep -F -v -f \
        <(./composer.phar show --direct --no-dev --name-only | sort) \
        <(./composer.phar show --direct --name-only | sort) |
        xargs ./composer.phar require --no-cache --dev --update-with-all-dependencies
}

##############################################################################
# Updates all npm dependencies to latest stable release.
# Arguments:
#   None
##############################################################################
update_npm() {
    rm -rf node_modules package-lock.json
    jq '.devDependencies | keys | .[]' package.json | xargs npm install --save-dev --ignore-scripts
    jq '.dependencies | keys | .[]' package.json | xargs npm install --save --ignore-scripts

    # Get all outdated packages
    set +e
    local outdated
    outdated=$(npm outdated --json)
    set -e

    # Loop through each outdated package
    for package in $(echo "${outdated}" | jq -r 'keys[]'); do
        echo "Processing outdated package: ${package}"

        # Get the latest version of the package
        local latest
        latest=$(echo "${outdated}" | jq -r --arg pkg "${package}" '.[$pkg].latest')

        # Check if the package is a dev dependency
        local is_dev_dependency
        is_dev_dependency=$(jq -r --arg pkg "${package}" 'if .devDependencies[$pkg] then true else false end' package.json)

        # Install the latest version of the package
        if [[ "${is_dev_dependency}" = "true" ]]; then
            echo "Installing ${package}@${latest} as a dev dependency"
            npm install "${package}@${latest}" --save-dev
        else
            echo "Installing ${package}@${latest} as a dependency"
            npm install "${package}@${latest}" --save
        fi
    done

    # Avoid error "node_modules/.bin/lightningcss: line 1: This: command not found"
    npm ci
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

if [[ $1 == "docker" ]]; then
    update_dockerfile_arg_image_version .docker/debian.dev.Dockerfile php PHP_IMAGE_TAG apache
    check_version_docker_file .docker/alpine.dev.Dockerfile alpine latest
    check_version_docker_file .docker/web-alpine.prod.Dockerfile alpine latest
    check_version_docker_file .docker/sql.prod.Dockerfile mariadb lts
    check_version_docker_compose docker-compose.yml mariadb lts
    exit 0
fi

if [[ $1 == "npm" ]]; then
    update_npm
    exit 0
fi

usage
exit 1
