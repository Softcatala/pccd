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

  # Extract version number and full image tag. Version-only replacement preserves
  # any suffix (e.g., "3.20.3" → "3.20.4" updates "alpine:3.20.3" while preserving the tag format).
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

  # Use full image tag (with suffix) for accurate comparison
  if [[ -n ${reference_tag} ]]; then
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

  # Extract version number and full image tag. Version-only replacement preserves
  # any suffix (e.g., "11.4.5" → "11.4.6" works for both "mariadb:11.4.5-noble" and "mariadb:11.4.5").
  local current_version current_image next_version
  current_version=$(grep -F 'image:' "${COMPOSE_FILE}" | grep -F "${IMAGE_NAME}:" | grep -E -o '[0-9\.]+')
  current_image=$(grep -F 'image:' "${COMPOSE_FILE}" | grep -F "${IMAGE_NAME}:" | sed -E "s/.*image:[[:space:]]*${IMAGE_NAME}://" | tr -d ' ')
  next_version=$(increment_version "${current_version}")

  if version_exists_dockerhub "${IMAGE_NAME}" "${next_version}"; then
    echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_version}..."
    sed -i'.original' -e "s/${current_version}/${next_version}/" "${COMPOSE_FILE}"
    rm "${COMPOSE_FILE}.original"
    # Update the full image tag to reflect the version change (before updating current_version)
    current_image=$(echo "${current_image}" | sed -e "s/${current_version}/${next_version}/")
    current_version=${next_version}
  else
    echo "OK: ${IMAGE_NAME} Docker image is up to date in ${COMPOSE_FILE}."
  fi

  # Use full image tag (with suffix) for accurate comparison
  if [[ -n ${reference_tag} ]]; then
    compare_docker_tags "${IMAGE_NAME}" "${current_image}" "${reference_tag}"
  fi
}

##############################################################################
# Syncs a Docker image tag in a target Docker Compose file from a source file.
# Arguments:
#   Source Docker Compose file path (e.g. "docker-compose.yml")
#   Target Docker Compose file path (e.g. "docker-compose.fpm.yml")
#   An image name (e.g. "mariadb")
##############################################################################
sync_docker_compose_image_tag() {
  local -r SOURCE_COMPOSE_FILE="$1"
  local -r TARGET_COMPOSE_FILE="$2"
  local -r IMAGE_NAME="$3"
  local source_tag target_tag

  source_tag=$(grep -E "image:[[:space:]]*${IMAGE_NAME}:" "${SOURCE_COMPOSE_FILE}" | head -1 | sed -E "s/.*image:[[:space:]]*${IMAGE_NAME}://")
  target_tag=$(grep -E "image:[[:space:]]*${IMAGE_NAME}:" "${TARGET_COMPOSE_FILE}" | head -1 | sed -E "s/.*image:[[:space:]]*${IMAGE_NAME}://")

  if [[ -z ${source_tag} ]]; then
    echo "ERROR: Could not find ${IMAGE_NAME} image tag in ${SOURCE_COMPOSE_FILE}."
    return 1
  fi

  if [[ -z ${target_tag} ]]; then
    echo "ERROR: Could not find ${IMAGE_NAME} image tag in ${TARGET_COMPOSE_FILE}."
    return 1
  fi

  if [[ ${source_tag} == "${target_tag}" ]]; then
    echo "OK: ${IMAGE_NAME} image tag already in sync in ${TARGET_COMPOSE_FILE}."
    return 0
  fi

  echo "Syncing ${IMAGE_NAME} image tag in ${TARGET_COMPOSE_FILE} from ${target_tag} to ${source_tag}..."
  sed -i'.original' -E "s|(image:[[:space:]]*${IMAGE_NAME}:)${target_tag}|\1${source_tag}|" "${TARGET_COMPOSE_FILE}"
  rm "${TARGET_COMPOSE_FILE}.original"
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
  if [[ -z ${current_tag} ]]; then
    echo "ERROR: ARG ${ARG_NAME} not found in ${DOCKERFILE}."
    return 1
  fi

  # Split tag into version and suffix to preserve suffix during update
  # (e.g., "8.3.15-apache" → "8.3.16-apache")
  numeric_version=$(echo "${current_tag}" | grep -E -o '^[0-9]+\.[0-9]+\.[0-9]+')
  suffix=$(echo "${current_tag}" | sed -E "s/${numeric_version}//")

  if [[ -z ${numeric_version} ]]; then
    echo "ERROR: Failed to parse the numeric version from the tag '${current_tag}'."
    return 1
  fi

  next_numeric_version=$(increment_version "${numeric_version}")
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

  # Use full tag (with suffix) for accurate comparison
  if [[ -n ${reference_tag} ]]; then
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
  local digest1 digest2

  # Pull first image and get manifest digest
  echo "Pulling ${IMAGE_NAME}:${TAG1}..."
  docker pull "${IMAGE_NAME}:${TAG1}" > /dev/null || {
    echo "Failed to pull ${IMAGE_NAME}:${TAG1}"
    return 2
  }
  digest1=$(docker inspect --format='{{index .RepoDigests 0}}' "${IMAGE_NAME}:${TAG1}" | grep -o 'sha256:[a-f0-9]*') || true
  if [[ -z ${digest1} ]]; then
    echo "Failed to extract manifest digest from ${IMAGE_NAME}:${TAG1}"
    return 2
  fi

  # Pull second image and get manifest digest
  echo "Pulling ${IMAGE_NAME}:${TAG2}..."
  docker pull "${IMAGE_NAME}:${TAG2}" > /dev/null || {
    echo "Failed to pull ${IMAGE_NAME}:${TAG2}"
    return 2
  }
  digest2=$(docker inspect --format='{{index .RepoDigests 0}}' "${IMAGE_NAME}:${TAG2}" | grep -o 'sha256:[a-f0-9]*') || true
  if [[ -z ${digest2} ]]; then
    echo "Failed to extract manifest digest from ${IMAGE_NAME}:${TAG2}"
    return 2
  fi

  # Compare digests
  if [[ ${digest1} == "${digest2}" ]]; then
    echo "OK: ${IMAGE_NAME}:${TAG1} and ${IMAGE_NAME}:${TAG2} refer to the same image."
    return 0
  else
    echo "Tags don't match. ${IMAGE_NAME}:${TAG1} and ${IMAGE_NAME}:${TAG2} refer to different images."
    echo "   ${IMAGE_NAME}:${TAG1} digest: ${digest1}"
    echo "   ${IMAGE_NAME}:${TAG2} digest: ${digest2}"
    return 1
  fi
}
export -f compare_docker_tags

##############################################################################
# Gets the latest docker-php-extension-installer version from GitHub.
# Arguments:
#   None
# Returns:
#   The latest version tag via stdout
##############################################################################
get_latest_docker_php_extension_installer_version() {
  curl --silent --fail "https://api.github.com/repos/mlocati/docker-php-extension-installer/releases/latest" | jq -r '.tag_name'
}

##############################################################################
# Updates docker-php-extension-installer version in YAML files (GitLab CI, etc).
# Arguments:
#   File paths to update (e.g., ".gitlab-ci.yml")
##############################################################################
update_docker_php_extension_installer_version_yaml() {
  if [[ $# -lt 1 ]]; then
    echo "ERROR: No files specified for docker-php-extension-installer YAML update."
    return 1
  fi

  local current_version next_version file

  # Get current version from first file
  file="$1"
  current_version=$(grep -E 'DOCKER_PHP_EXTENSION_INSTALLER_VERSION' "${file}" | grep -E -o '[0-9]+\.[0-9]+\.[0-9]+' | head -1)

  if [[ -z ${current_version} ]]; then
    echo "ERROR: Could not find DOCKER_PHP_EXTENSION_INSTALLER_VERSION in ${file}."
    return 1
  fi

  # Get latest release from GitHub API
  next_version=$(get_latest_docker_php_extension_installer_version)

  if [[ -z ${next_version} ]]; then
    echo "ERROR: Could not fetch latest docker-php-extension-installer version from GitHub."
    return 1
  fi

  if [[ ${current_version} == "${next_version}" ]]; then
    echo "OK: docker-php-extension-installer is up to date in YAML files (${current_version})."
    return 0
  fi

  echo "docker-php-extension-installer in YAML files is out of date, updating from ${current_version} to ${next_version}..."

  # Update all specified files
  for file in "$@"; do
    if [[ ! -f ${file} ]]; then
      echo "WARNING: File ${file} not found, skipping."
      continue
    fi

    sed -i'.original' -e "s|DOCKER_PHP_EXTENSION_INSTALLER_VERSION: \"${current_version}\"|DOCKER_PHP_EXTENSION_INSTALLER_VERSION: \"${next_version}\"|g" "${file}"
    rm "${file}.original"
    echo "  Updated ${file}"
  done
}

##############################################################################
# Updates docker-php-extension-installer version in Dockerfiles.
# Arguments:
#   File paths to update (e.g., ".docker/dev.Dockerfile")
##############################################################################
update_docker_php_extension_installer_version_dockerfile() {
  if [[ $# -lt 1 ]]; then
    echo "ERROR: No files specified for docker-php-extension-installer Dockerfile update."
    return 1
  fi

  local current_version next_version file

  # Get current version from first file
  file="$1"
  current_version=$(grep -E 'ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION' "${file}" | grep -E -o '[0-9]+\.[0-9]+\.[0-9]+' | head -1)

  if [[ -z ${current_version} ]]; then
    echo "ERROR: Could not find ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION in ${file}."
    return 1
  fi

  # Get latest release from GitHub API
  next_version=$(get_latest_docker_php_extension_installer_version)

  if [[ -z ${next_version} ]]; then
    echo "ERROR: Could not fetch latest docker-php-extension-installer version from GitHub."
    return 1
  fi

  if [[ ${current_version} == "${next_version}" ]]; then
    echo "OK: docker-php-extension-installer is up to date in Dockerfiles (${current_version})."
    return 0
  fi

  echo "docker-php-extension-installer in Dockerfiles is out of date, updating from ${current_version} to ${next_version}..."

  # Update all specified files
  for file in "$@"; do
    if [[ ! -f ${file} ]]; then
      echo "WARNING: File ${file} not found, skipping."
      continue
    fi

    sed -i'.original' -e "s|ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION=${current_version}|ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION=${next_version}|g" "${file}"
    rm "${file}.original"
    echo "  Updated ${file}"
  done
}

##############################################################################
# Installs newest versions of Composer packages. See https://stackoverflow.com/a/74760024/1391963
# Arguments:
#   None
##############################################################################
update_composer() {
  # Update composer itself
  ./composer.phar self-update

  # Update non-dev dependencies
  ./composer.phar show --no-dev --direct --name-only |
    xargs ./composer.phar require --update-with-all-dependencies

  # Update dev dependencies
  grep -F -v -f \
    <(./composer.phar show --direct --no-dev --name-only | sort) \
    <(./composer.phar show --direct --name-only | sort) |
    xargs ./composer.phar require --dev --update-with-all-dependencies
}

##############################################################################
# Updates all npm dependencies to latest stable release.
# Arguments:
#   None
##############################################################################
update_npm() {
  rm -rf node_modules package-lock.json
  jq -r '.devDependencies // {} | keys | .[]' package.json | xargs -r npm install --save-dev --save-exact --ignore-scripts
  jq -r '.dependencies // {} | keys | .[]' package.json | xargs -r npm install --save --save-exact --ignore-scripts

  # Get all outdated packages
  set +e
  local outdated
  outdated=$(npm outdated --json)
  set -e

  # Loop through each outdated package
  for package in $(echo "${outdated}" | jq -r 'keys[]'); do
    echo "Processing outdated package: ${package}"

    local latest
    latest=$(echo "${outdated}" | jq -r --arg pkg "${package}" '.[$pkg].latest')
    local is_dev_dependency
    is_dev_dependency=$(jq -r --arg pkg "${package}" 'if .devDependencies[$pkg] then true else false end' package.json)

    # Install the latest version of the package
    if [[ ${is_dev_dependency} == "true" ]]; then
      echo "Installing ${package}@${latest} as a dev dependency"
      npm install "${package}@${latest}" --save-dev --save-exact --ignore-scripts
    else
      echo "Installing ${package}@${latest} as a dependency"
      npm install "${package}@${latest}" --save --save-exact --ignore-scripts
    fi
  done
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
  # Run independent operations in parallel for faster execution
  (update_docker_php_extension_installer_version_dockerfile .docker/dev.Dockerfile &&
    update_dockerfile_arg_image_version .docker/dev.Dockerfile php PHP_IMAGE_TAG apache) &
  update_docker_php_extension_installer_version_yaml .gitlab-ci.yml &
  check_version_docker_file .docker/fpm.Dockerfile alpine latest &
  check_version_docker_file .docker/nginx.Dockerfile alpine &
  check_version_docker_file .docker/sql.Dockerfile mariadb lts &
  check_version_docker_compose docker-compose.yml mariadb lts &

  # Wait for all parallel operations to complete
  wait

  # Sync the other compose files
  sync_docker_compose_image_tag docker-compose.yml docker-compose.fpm.yml mariadb
  sync_docker_compose_image_tag docker-compose.yml docker-compose.frankenphp.yml mariadb

  exit 0
fi

if [[ $1 == "npm" ]]; then
  update_npm
  exit 0
fi

usage
exit 1
