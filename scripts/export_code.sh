#!/usr/bin/env bash
#
# Exports source code for public release.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")/.."

if [[ -d tmp/github ]]; then
    rm -rf tmp/github
fi

git clone --no-checkout git@github.com:Softcatala/pccd.git tmp/github
git archive --prefix=github/ --format=tar HEAD | (cd tmp/ && tar xf -)
(cd tmp/github && git add . && git commit -m "export source code")
echo "Source code exported to tmp/github and ready to be pushed to GitHub."
