#!/usr/bin/env bash
#
# Converts the MS Access database to MariaDB.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

DATABASE_FILE="${1:-database.accdb}"
ROOT_DIR="../.."
DB_FILE="${ROOT_DIR}/${DATABASE_FILE}"
TMP_DIR="${ROOT_DIR}/data/db"
INSTALL_DB_FILE="${ROOT_DIR}/install/db/db.sql"

cd "$(dirname "$0")"

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
  echo "Usage: ./$(basename "$0") [DATABASE_FILENAME]"
  echo ""
  echo "Optional arguments:"
  echo "  DATABASE_FILENAME     The MS Access database file (default: database.accdb)"
}

if [[ -n $2 ]]; then
  usage
  exit 1
fi

if [[ ! -f ${DB_FILE} ]]; then
  echo "Error: Database file '${DB_FILE}' not found."
  exit 1
fi

# Export schema and language table to ensure breaking changes are monitored.
mdb-schema "${DB_FILE}" mysql > "${TMP_DIR}/msaccess_schema.sql"
mdb-export --insert=mysql --batch-size=1 "${DB_FILE}" 00_EQUIVALENTS > "${TMP_DIR}/equivalents_dump.sql"

cat /dev/null > "${INSTALL_DB_FILE}"

# For some reason, mdb-export dumps some dates as 1900-01-00 00:00:00. Setting this SQL mode was necessary on MySQL.
echo "SET sql_mode = ALLOW_INVALID_DATES;" >> "${INSTALL_DB_FILE}"

# Drop existing tables.
echo "DROP TABLE IF EXISTS 00_PAREMIOTIPUS;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS 00_FONTS;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS 00_IMATGES;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS 00_EDITORIA;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS 00_OBRESVPR;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS 00_EQUIVALENTS;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS RML;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS common_paremiotipus;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS paremiotipus_display;" >> "${INSTALL_DB_FILE}"
echo "DROP TABLE IF EXISTS pccd_is_installed;" >> "${INSTALL_DB_FILE}"

# Export schema.
mdb-schema --no-indexes --no-relations -T 00_PAREMIOTIPUS "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"
mdb-schema --no-indexes --no-relations -T 00_FONTS "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"
mdb-schema --no-indexes --no-relations -T 00_IMATGES "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"
mdb-schema --no-indexes --no-relations -T 00_EDITORIA "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"
mdb-schema --no-indexes --no-relations -T 00_OBRESVPR "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"
mdb-schema --no-indexes --no-relations -T 00_EQUIVALENTS "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"
mdb-schema --no-indexes --no-relations -T RML "${DB_FILE}" mysql >> "${INSTALL_DB_FILE}"

# Add additional columns.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN ACCEPCIO varchar (2);" >> "${INSTALL_DB_FILE}"

# Dump data.
mdb-export -I mysql "${DB_FILE}" "00_PAREMIOTIPUS" >> "${INSTALL_DB_FILE}"
mdb-export -I mysql "${DB_FILE}" "00_FONTS" >> "${INSTALL_DB_FILE}"
mdb-export -I mysql "${DB_FILE}" "00_IMATGES" >> "${INSTALL_DB_FILE}"
mdb-export -I mysql "${DB_FILE}" "00_EDITORIA" >> "${INSTALL_DB_FILE}"
mdb-export -I mysql "${DB_FILE}" "00_OBRESVPR" >> "${INSTALL_DB_FILE}"
mdb-export -I mysql "${DB_FILE}" "00_EQUIVALENTS" >> "${INSTALL_DB_FILE}"
mdb-export -I mysql "${DB_FILE}" "RML" >> "${INSTALL_DB_FILE}"

# Add image width and height columns for images.
echo "ALTER TABLE 00_FONTS ADD COLUMN WIDTH int NOT NULL DEFAULT 0;" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_FONTS ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_IMATGES ADD COLUMN WIDTH int NOT NULL DEFAULT 0;" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_IMATGES ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_OBRESVPR ADD COLUMN WIDTH int NOT NULL DEFAULT 0;" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_OBRESVPR ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;" >> "${INSTALL_DB_FILE}"

# Create indexes.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD PRIMARY KEY (Id);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (PAREMIOTIPUS);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (MODISME);" >> "${INSTALL_DB_FILE}"
# AUTOR index is useful for counting "informants" on every page (although this is cached by APCu).
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (AUTOR);" >> "${INSTALL_DB_FILE}"
# ID_FONT index is useful for counting references in the "obra" page, and also in some reports.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (ID_FONT);" >> "${INSTALL_DB_FILE}"
# Full-text indexes are required for the multiple search combinations.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, SINONIM);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, EQUIVALENT);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, SINONIM);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, EQUIVALENT);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, SINONIM, EQUIVALENT);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, SINONIM, EQUIVALENT);" >> "${INSTALL_DB_FILE}"
# Multilingüe.
echo "ALTER TABLE RML ADD INDEX (PAREMIOTIPUS);" >> "${INSTALL_DB_FILE}"

# The rest of the tables are small, so their indexes should not impact size significantly.
echo "ALTER TABLE 00_FONTS ADD INDEX (Identificador);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_IMATGES ADD INDEX (PAREMIOTIPUS);" >> "${INSTALL_DB_FILE}"
echo "ALTER TABLE 00_EDITORIA ADD INDEX (CODI);" >> "${INSTALL_DB_FILE}"

# Create additional custom tables.
# Used for displaying top 10000 paremiotipus.
echo "CREATE TABLE common_paremiotipus(Paremiotipus varchar (255), Compt int, INDEX (Compt));" >> "${INSTALL_DB_FILE}"
# This is used because the PAREMIOTIPUS column is preprocessed to optimize sorting, and we still want to display the
# original value. It is also used to perform a faster count in get_paremiotipus_count() PHP function.
echo "CREATE TABLE paremiotipus_display(Paremiotipus varchar (255) PRIMARY KEY, Display varchar (255));" >> "${INSTALL_DB_FILE}"

# Normalize UTF-8 combined characters, but revert normalization of `...` back to `…`.
uconv -x nfkc "${INSTALL_DB_FILE}" | sed 's/\.\.\./…/g' > "${TMP_DIR}/db_temp.sql" && mv "${TMP_DIR}/db_temp.sql" "${INSTALL_DB_FILE}"
