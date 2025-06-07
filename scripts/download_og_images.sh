#!/usr/bin/env bash
#
# Downloads all OG images.
#
# This script is mostly used for developing and testing og.php.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/.."

# If BASE_URL variable is not set, load it from the .env file.
if [[ -z ${BASE_URL} ]]; then
    export "$(grep 'BASE_URL=' .env | xargs)"
    if [[ -z ${BASE_URL} ]]; then
        echo "ERROR: BASE_URL variable is not set." >&2
        exit 255
    fi
fi

readonly BASE_URL

##############################################################################
# Converts a production URL to a local URL.
#
# Globals:
#   BASE_URL
# Arguments:
#   Production URL
# Outputs:
#   Local URL
##############################################################################
production_to_local_url() {
    echo "$1" | sed -e "s|https://pccd.dites.cat|${BASE_URL}|"
}
export -f production_to_local_url

##############################################################################
# Downloads OG image of a URL.
#
# Arguments:
#   The URL to validate
##############################################################################
download_og_image() {
    local -r page_id=$(uuidgen)
    local -r filename="tmp/page_${page_id}.html"
    local -r url=$(production_to_local_url "$1")
    local status_code
    local image_url
    local local_image_url

    echo "Trying to fetch OG image from ${url} (${filename})..."

    # Download the HTML content of the page
    status_code=$(curl -o "${filename}" --silent --write-out "%{http_code}" "${url}")

    if [[ "${status_code}" -ne 200 ]]; then
        echo "Failed to download the page. HTTP status code: ${status_code}"
        return 1
    fi

    # Extract the og:image URL from the downloaded HTML file
    image_url=$(sed -n 's/.*property="og:image" content="\([^"]*\)".*/\1/p' "${filename}")

    local_image_url=$(production_to_local_url "${image_url}")

    if [[ -z "${local_image_url}" ]]; then
        echo "Failed to find og:image in ${url}."
        if echo "${url}" | grep -q '/p/'; then
            return 1
        else
            return 0
        fi
    fi

    # Download the image
    local -r image_filename="tmp/og/${page_id}.png"
    if ! curl -o "${image_filename}" --silent "${local_image_url}"; then
        echo "Failed to download the image."
        return 1
    fi

    echo "Image downloaded successfully: ${image_filename}"

}
export -f download_og_image

rm -rf tmp/og/
mkdir tmp/og/

if strings "$(command -v xargs)" | grep -q -F -m 1 "FreeBSD"; then
    # Compatible with the xargs implementation available in Mac.
    xargs -n 1 -P 10 -S 512 -I {} bash -c 'download_og_image "$@" || exit 255' _ {} < docroot/sitemap.txt
else
    # Compatible with GNU xargs.
    xargs -n 1 -P 10 -I {} bash -c 'download_og_image "$@" || exit 255' _ {} < docroot/sitemap.txt
fi
