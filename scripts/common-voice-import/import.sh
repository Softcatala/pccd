#!/usr/bin/env bash
#
# Common Voice import.
#
# Import Common Voice sentences and copies the matching mp3 clips to the codebase.
# Requirements: Put CV Catalan dataset (clips directory and validated.tsv file) to in cv/. See app.php for more info.
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
