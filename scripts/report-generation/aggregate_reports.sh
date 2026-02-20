#!/usr/bin/env bash
#
# Aggregates some reports output.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")"

cat ../../data/reports/test_tmp_repetits_*.txt > ../../data/reports/test_repetits.txt

set +e
git diff ../../data/reports/test_repetits.txt | grep -E '^( |\+)' | grep -v '^+++' > ../../data/reports/test_repetits_new.txt
set -e

cat ../../data/reports/test_tmp_imatges_url_enllac_*.txt > ../../data/reports/test_imatges_url_enllac.txt

cat ../../data/reports/test_tmp_imatges_url_imatge_*.txt > ../../data/reports/test_imatges_url_imatge.txt
