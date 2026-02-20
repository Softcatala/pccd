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

# Run export script and get flagged sentences with LanguageTool.
docker exec pccd-web php scripts/report-generation/languagetool-checker/export.php | npx lt-filter --flagged > ../../../data/reports/excluded.txt

# Get the new LT-excluded sentences since last commit.
git diff --unified=0 HEAD ../../../data/reports/excluded.txt | grep -E '^\+[^+]' | sed 's/^\+//' > excluded_new_tmp.txt

# Only update the file if there are new entries.
if [[ "$(wc -l < excluded_new_tmp.txt)" -gt 1 ]]; then
  cp excluded_new_tmp.txt ../../../data/reports/excluded_new.txt
fi

# Remove temporary files.
rm excluded_new_tmp.txt
