#!/usr/bin/env bash
#
# Runs multiple tests in all URLs in sitemap.txt.
#
# This script can take a few hours to complete.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/.."

##############################################################################
# Shows the help of this command.
#
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: ./$(basename "$0")"
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

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
# Converts a local URL to a production URL.
#
# Globals:
#   BASE_URL
# Arguments:
#   Local URL
# Outputs:
#   Production URL
##############################################################################
local_to_production_url() {
    echo "$1" | sed -e "s|${BASE_URL}|https://pccd.dites.cat|"
}
export -f local_to_production_url

cat /dev/null > tmp/test_html_errors.txt
echo -n "Informe actualitzat el dia:" > tmp/test_zero_fonts.txt
LC_TIME='ca_ES' date | cut -d"," -f2 | sed "s/de o/d'o/" | sed "s/de a/d'a/" >> tmp/test_zero_fonts.txt

##############################################################################
# Validates URL using curl, htmlhint, html-validate, Tidy HTML and grep.
#
# This is similar to the function available in scripts/validate_website.sh, but is simpler (e.g. it does not use
# linkinator or lighthouse). See that file for more details.
#
# Arguments:
#   The URL to validate
##############################################################################
validate_html_url() {
    local -r page_id=$(uuidgen)
    local -r filename="tmp/page_${page_id}.html"
    local -r url=$(production_to_local_url "$1")
    local error
    local status_code

    echo "Validating HTML of ${url} (${filename})..."

    status_code=$(curl -o "${filename}" --silent --write-out "%{http_code}" "${url}")
    if [[ ${status_code} != "200" ]]; then
        echo "ERROR: ${url} returned status code HTTP ${status_code}." >&2
        exit 255
    fi

    npx htmlhint --config .htmlhintrc.json "${filename}" || exit 255

    error=$(npx html-validate --config=.htmlvalidate.json "${filename}")
    if [[ -n ${error} ]]; then
        echo "" >> tmp/test_html_errors.txt
        echo "Error reported by html-validate in ${url}: ${error}" >> tmp/test_html_errors.txt
        echo "" >> tmp/test_html_errors.txt
    fi

    error=$(tidy -config .tidyrc "${filename}" 2>&1 > /dev/null)
    if [[ -n ${error} ]]; then
        echo "" >> tmp/test_html_errors.txt
        echo "Error reported by tidy in ${url}: ${error}" >> tmp/test_html_errors.txt
        echo "" >> tmp/test_html_errors.txt
    fi

    # Log entries with "0 sources". This report is available in the admin section.
    if grep -q -F -m 1 '<div class="summary">' "${filename}"; then
        local_to_production_url "${url}" >> tmp/test_zero_fonts.txt
    fi
}
export -f validate_html_url

if strings "$(command -v xargs)" | grep -q -F -m 1 "FreeBSD"; then
    # Compatible with the xargs implementation available in Mac.
    xargs -n 1 -P 10 -S 512 -I {} bash -c 'validate_html_url "$@" || exit 255' _ {} < docroot/sitemap.txt
else
    # Compatible with GNU xargs.
    xargs -n 1 -P 10 -I {} bash -c 'validate_html_url "$@" || exit 255' _ {} < docroot/sitemap.txt
fi

if [[ $(wc -l < tmp/test_zero_fonts.txt) -lt 2 ]]; then
    echo "<em>No hi ha parèmies amb 0 fonts.</em>" >> tmp/test_zero_fonts.txt
fi

echo "All URLs in the sitemap file returned HTTP 200."
find tmp/ -type f -name '*.html' -delete
