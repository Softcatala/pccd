#!/usr/bin/env bash
#
# Deletes previous volumes, runs `docker compose build` with passed arguments and executes `docker compose up`.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: ./$(basename "$0") [OPTIONS]"
    echo ""
    echo "Optional arguments:"
    echo "  OPTIONS               The options to pass to docker compose build command"
    echo "  --alpine              Use Alpine image"
    echo "  --alpine-edge         Use Alpine image (edge)"
}

for arg in "$@"; do
    if [[ "${arg}" == "--alpine" ]]; then
        DOCKERFILE_PATH=".docker/alpine.dev.Dockerfile"
        export DOCKERFILE_PATH
        shift
        break
    fi
    if [[ "${arg}" == "--alpine-edge" ]]; then
        DOCKERFILE_PATH=".docker/alpine.edge.Dockerfile"
        export DOCKERFILE_PATH
        shift
        break
    fi
done

(cd "$(dirname "$0")/.." &&
    docker compose down --volumes &&
    docker compose build --no-cache "$@" &&
    docker compose up)
