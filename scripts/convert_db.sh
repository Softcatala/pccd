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

# Export schema and language table to ensure breaking changes are monitored.
mdb-schema ../"${DATABASE_FILE}" mysql > ../tmp/msaccess_schema.sql
mdb-export --insert=mysql --batch-size=1 ../"${DATABASE_FILE}" 00_EQUIVALENTS > ../tmp/equivalents_dump.sql

cat /dev/null > ../install/db/db.sql

# For some reason, mdb-export dumps some dates as 1900-01-00 00:00:00. Setting this SQL mode was necessary on MySQL.
echo "SET sql_mode = ALLOW_INVALID_DATES;" >> ../install/db/db.sql

# Drop existing tables.
echo "DROP TABLE IF EXISTS 00_PAREMIOTIPUS;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_FONTS;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_IMATGES;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_EDITORIA;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_OBRESVPR;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_EQUIVALENTS;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS common_paremiotipus;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS paremiotipus_display;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS pccd_is_installed;" >> ../install/db/db.sql

# Export schema.
mdb-schema --no-indexes --no-relations -T 00_PAREMIOTIPUS ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_FONTS ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_IMATGES ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_EDITORIA ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_OBRESVPR ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_EQUIVALENTS ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql

# Add additional columns.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN ACCEPCIO varchar (2);" >> ../install/db/db.sql

# Dump data.
mdb-export -I mysql ../"${DATABASE_FILE}" "00_PAREMIOTIPUS" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_FONTS" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_IMATGES" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_EDITORIA" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_OBRESVPR" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_EQUIVALENTS" >> ../install/db/db.sql

# Add image width and height columns for images.
echo "ALTER TABLE 00_FONTS ADD COLUMN WIDTH int NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_FONTS ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_IMATGES ADD COLUMN WIDTH int NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_IMATGES ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_OBRESVPR ADD COLUMN WIDTH int NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_OBRESVPR ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;" >> ../install/db/db.sql

# Create indexes.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD PRIMARY KEY (Id);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (PAREMIOTIPUS);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (MODISME);" >> ../install/db/db.sql
# AUTOR index is useful for counting "informants" on every page (although this is cached by APCu).
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (AUTOR);" >> ../install/db/db.sql
# ID_FONT index is useful for counting references in the "obra" page, and also in some reports.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (ID_FONT);" >> ../install/db/db.sql
# Full-text indexes are required for the multiple search combinations.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, SINONIM);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, EQUIVALENT);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, SINONIM);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, EQUIVALENT);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, SINONIM, EQUIVALENT);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, SINONIM, EQUIVALENT);" >> ../install/db/db.sql

# The rest of the tables are small, so their indexes should not impact size significantly.
echo "ALTER TABLE 00_FONTS ADD INDEX (Identificador);" >> ../install/db/db.sql
echo "ALTER TABLE 00_IMATGES ADD INDEX (PAREMIOTIPUS);" >> ../install/db/db.sql
echo "ALTER TABLE 00_EDITORIA ADD INDEX (CODI);" >> ../install/db/db.sql

# Create additional custom tables.
# Used for displaying top 10000 paremiotipus.
echo "CREATE TABLE common_paremiotipus(Paremiotipus varchar (255), Compt int, INDEX (Compt));" >> ../install/db/db.sql
# This is used because the PAREMIOTIPUS column is preprocessed to optimize sorting, and we still want to display the
# original value. It is also used to perform a faster count in get_paremiotipus_count() PHP function.
echo "CREATE TABLE paremiotipus_display(Paremiotipus varchar (255) PRIMARY KEY, Display varchar (255));" >> ../install/db/db.sql

# Normalize UTF-8 combined characters.
uconv -x nfkc ../install/db/db.sql > ../tmp/db_temp1.sql
# Revert the unwanted normalization of `...` back to `…`.
sed 's/\.\.\./…/g' ../tmp/db_temp1.sql > ../install/db/db.sql

# Delete intermediate files.
rm ../tmp/db_temp*.sql
