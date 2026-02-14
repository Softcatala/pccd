#!/usr/bin/env bash
#
# Common Voice import.
#
# Imports Common Voice sentences and copies the matching mp3 clips to the
# codebase.
#
# Requirements: Put Common Voice Catalan dataset (clips directory and
# validated.tsv file) in cv/ directory. See app.php for more details.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")"

rm -rf ../../docroot/mp3
mkdir ../../docroot/mp3
docker exec pccd-web php scripts/common-voice-import/app.php > ../../install/db/db_commonvoice.sql 2> copy-clips.generated.sh
bash copy-clips.generated.sh
