#!/usr/bin/env bash
#
# Exports source code for public release.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/.."

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0") [OPTION]"
    echo "Export the project source code for public release."
    echo ""
    echo "    help"
    echo "      Shows this help and exits"
}

if [[ $1 == "help" ]]; then
    usage
    exit 0
fi

if [[ $# -gt 1 ]]; then
    echo "Too many arguments."
    usage
    exit 1
fi

if [[ -d tmp/pccd ]]; then
    rm -rf tmp/pccd
fi

git clone --no-checkout git@github.com:Softcatala/pccd.git tmp/pccd
git archive --prefix=pccd/ --format=tar HEAD | (cd tmp/ && tar xf -)
(cd tmp/pccd && git add .)
