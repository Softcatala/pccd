#!/usr/bin/env bash
#
# Common Voice export.
#
# Export sentences to be imported into Common Voice. This script takes around 3 minutes to complete.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")"

# Run export script, trying to filter out controversial sentences.
docker exec pccd-web php scripts/common-voice-export/app.php > filtered.txt 2> controversial.txt

# Exclude more sentences with LanguageTool.
npx lt-filter filtered.txt > pccd.txt 2> excluded.txt

# Remove temporary files.
rm filtered.txt
