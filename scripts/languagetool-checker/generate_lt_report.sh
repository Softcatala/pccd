#!/usr/bin/env bash
#
# Generate a report with LanguageTool.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")"

# Run export script.
docker exec pccd-web php scripts/languagetool-checker/export.php > all.txt

# Exclude sentences with LanguageTool.
# shellcheck disable=SC2016
(
    cd ../../vendor/pereorga/pccd-lt-filter &&
        mvn package &&
        VERSION=$(mvn -q -Dexec.executable="echo" -Dexec.args='${project.version}' --non-recursive exec:exec) &&
        java -jar target/lt-filter-"${VERSION}"-jar-with-dependencies.jar \
            ../../../scripts/languagetool-checker/all.txt \
            > /dev/null \
            2> ../../../scripts/languagetool-checker/error.txt
)

# Clean up filter output.
grep -v -F 'SLF4J' error.txt > excluded.txt

# Get the new LT-excluded sentences since last commit.
git diff --unified=0 HEAD excluded.txt | grep -E '^\+[^+]' | sed 's/^\+//' > excluded_new_tmp.txt

# Only update the file if there are new entries.
if [[ "$(wc -l < excluded_new_tmp.txt)" -gt 1 ]]; then
    cp excluded_new_tmp.txt excluded_new.txt
fi

# Remove temporary files.
rm all.txt error.txt excluded_new_tmp.txt
