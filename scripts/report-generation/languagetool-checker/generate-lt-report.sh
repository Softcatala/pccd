#!/bin/sh
#
# Generate a report with LanguageTool.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")"

temporary_export=$(mktemp "${TMPDIR:-/tmp}/pccd-lt-export.XXXXXX")
temporary_report=$(mktemp "${TMPDIR:-/tmp}/pccd-lt-report.XXXXXX")
trap 'rm -f "$temporary_export" "$temporary_report"' 0 HUP INT TERM

# Generate separately so the report can be compared before replacing the current file.
docker compose exec -T web php scripts/report-generation/languagetool-checker/export.php > "${temporary_export}"
npx lt-filter --flagged < "${temporary_export}" > "${temporary_report}"

node ../update-report.js excluded.txt excluded_new.txt "${temporary_report}"
