#!/usr/bin/env bash
#
# Decompresses provided images in Cobertes.zip, Imatges.zip and Obres-VPR.zip files.
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
    echo "Usage: ./$(basename "$0")"
}

##############################################################################
# Handles the extraction for a given .zip file.
# Arguments:
#   $1: Name of the zip file (without the .zip extension).
#   $2: Target directory to move the extracted files. It will be deleted.
##############################################################################
function unzip_file {
    local -r zip_name="$1"
    local -r target_dir="$2"

    if [[ -f "${zip_name}.zip" ]]; then
        [[ -d ${target_dir} ]] && rm -r "${target_dir}"
        7zz x "${zip_name}.zip"
        mv "${zip_name}" "${target_dir}"
        find "${target_dir}" -type f -print0 | xargs -0 chmod 644
    else
        echo "Warning: ${zip_name}.zip not found"
    fi
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

unzip_file "Cobertes" "src/images/cobertes"
unzip_file "Imatges" "src/images/paremies"

# Merge author books with other works.
if [[ -f Obres-VPR.zip ]]; then
    7zz x Obres-VPR.zip
    chmod 644 Obres-VPR/*
    mv -f Obres-VPR/* src/images/cobertes
    rm -r Obres-VPR
fi
