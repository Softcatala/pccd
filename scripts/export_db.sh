#!/usr/bin/env bash
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

if [[ -n $1 ]]; then
    usage
    exit 1
fi

# If MYSQL_ROOT_PASSWORD variable is not set, load it from the .env file.
if [[ -z ${MYSQL_ROOT_PASSWORD} ]]; then
    export "$(grep 'MYSQL_ROOT_PASSWORD=' .env | xargs)"
    if [[ -z ${MYSQL_ROOT_PASSWORD} ]]; then
        echo "ERROR: MYSQL_ROOT_PASSWORD variable is not set." >&2
        exit 255
    fi
fi
readonly MYSQL_ROOT_PASSWORD

# TODO: Check if 'pccd_is_installed' table exists.
#TABLE_EXISTS=$(docker exec pccd-mysql /usr/bin/mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" pccd -e "SHOW TABLES LIKE 'pccd_is_installed';")
#if [[ -z "${TABLE_EXISTS}" ]]; then
#    echo "ERROR: Database has not been exported because project is not installed correctly." >&2
#    exit 1
#fi

# Determine the correct dump command (mysqldump or mariadb-dump).
DUMP_CMD="/usr/bin/mysqldump"
if ! docker exec pccd-mysql "${DUMP_CMD}" --version > /dev/null 2>&1; then
    DUMP_CMD="/usr/bin/mariadb-dump"
    if ! docker exec pccd-mysql "${DUMP_CMD}" --version > /dev/null 2>&1; then
        echo "ERROR: Neither mysqldump nor mariadb-dump commands are available." >&2
        exit 1
    fi
fi

docker exec pccd-mysql "${DUMP_CMD}" -uroot -p"${MYSQL_ROOT_PASSWORD}" --no-data --skip-dump-date pccd > tmp/schema.sql
docker exec pccd-mysql "${DUMP_CMD}" -uroot -p"${MYSQL_ROOT_PASSWORD}" --skip-dump-date --ignore-table=pccd.commonvoice pccd > install/db/db.sql
