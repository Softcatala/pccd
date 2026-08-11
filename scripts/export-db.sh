#!/bin/sh
#
# Exports the MariaDB database.
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
  echo "Usage: ./$(basename "$0")"
}

if [ -n "$1" ]; then
  usage
  exit 1
fi

# Reads an env var from current process first, then from .env file.
# Arguments:
#   Variable name
#   Default value (optional)
# Returns:
#   Writes the resolved value to stdout.
get_env_var() {
  local VAR_NAME="$1"
  local DEFAULT_VALUE="${2:-}"
  local value
  eval "value=\${${VAR_NAME}:-}"

  if [ -z "${value}" ] && [ -f .env ]; then
    value=$(grep -m1 "^${VAR_NAME}=" .env | cut -d'=' -f2-)
  fi

  if [ -n "${value}" ]; then
    echo "${value}"
  else
    echo "${DEFAULT_VALUE}"
  fi
}

MYSQL_DATABASE="pccd"
MYSQL_USER="pccd"
MYSQL_PASSWORD=$(get_env_var MYSQL_PASSWORD)

if [ -z "${MYSQL_PASSWORD}" ]; then
  echo "ERROR: MYSQL_PASSWORD variable is not set." >&2
  exit 255
fi

# TODO: check if 'pccd_is_installed' table exists for the configured database.
#TABLE_EXISTS=$(docker compose exec -T mysql /usr/bin/mysql -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" "${MYSQL_DATABASE}" -e "SHOW TABLES LIKE 'pccd_is_installed';")
#if [ -z "${TABLE_EXISTS}" ]; then
#    echo "ERROR: Database has not been exported because project is not installed correctly." >&2
#    exit 1
#fi

# Determine the correct dump command (mysqldump or mariadb-dump).
DUMP_CMD="/usr/bin/mysqldump"
if ! docker compose exec -T mysql "${DUMP_CMD}" --version > /dev/null 2>&1; then
  DUMP_CMD="/usr/bin/mariadb-dump"
  if ! docker compose exec -T mysql "${DUMP_CMD}" --version > /dev/null 2>&1; then
    echo "ERROR: Neither mysqldump nor mariadb-dump commands are available." >&2
    exit 1
  fi
fi

docker compose exec -T mysql "${DUMP_CMD}" -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" --no-data --skip-create-options --skip-dump-date "${MYSQL_DATABASE}" > data/db/schema.sql
docker compose exec -T mysql "${DUMP_CMD}" -u"${MYSQL_USER}" -p"${MYSQL_PASSWORD}" --skip-dump-date --ignore-table="${MYSQL_DATABASE}.commonvoice" "${MYSQL_DATABASE}" > install/db/db.sql
