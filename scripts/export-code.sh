#!/bin/sh
#
# Exports source code for public release.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")/.."

rm -rf tmp/github
git clone git@github.com:Softcatala/pccd.git tmp/github
# Drop the previous snapshot first. tar only adds and overwrites, so files
# renamed or removed in HEAD would otherwise stay in the public repository.
git -C tmp/github rm -rq .
git archive --format=tar HEAD | (cd tmp/github && tar xf -)
(
  cd tmp/github &&
    git add -A &&
    git commit -m "export source code" &&
    git push origin master
)

echo "Source code pushed to GitHub."
