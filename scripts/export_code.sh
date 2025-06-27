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

mkdir -p tmp/github
git archive --format=tar HEAD | (cd tmp/github && tar xf -)
(
    cd tmp/github &&
        git init --initial-branch=master &&
        git remote add origin git@github.com:Softcatala/pccd.git &&
        git add . &&
        git commit -m "export source code" &&
        git push --force origin master
)

echo "Source code pushed to GitHub with rewritten history."
