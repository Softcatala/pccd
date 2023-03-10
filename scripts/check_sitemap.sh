#!/usr/bin/env bash
#
# Runs multiple tests in all URLs in sitemap(s).txt.
#
# URL of the environment can be passed as argument:
#   ./check_sitemap.sh http://localhost:8091
#
# Otherwise, http://localhost:8092 (default Apache port) is used as default.
# This script can take a few hours to complete.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/../"

if [[ -z $1 ]]; then
    REMOTE_ENVIRONMENT_URL="http://localhost:8092"
else
    REMOTE_ENVIRONMENT_URL="$1"
fi
readonly REMOTE_ENVIRONMENT_URL
export REMOTE_ENVIRONMENT_URL

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0") [--help] [ENVIRONMENT_URL]"
    echo "Run multiple tests in all URLs in the sitemap files."
    echo ""
    echo "  --help"
    echo "    Show this help and exit"
    echo "  ENVIRONMENT_URL       The website URL, without trailing slash (default: http://localhost:8092)"
}

if [[ -n $2 ]]; then
    usage
    exit 1
fi

cat /dev/null > tmp/test_html_errors.txt
echo -n "Informe actualitzat el dia:" > tmp/test_zero_fonts.txt
LC_TIME='ca_ES' date | cut -d"," -f2 | sed "s/de o/d'o/" | sed "s/de a/d'a/" >> tmp/test_zero_fonts.txt

##############################################################################
# Validates URL using curl, htmlhint, html-validate, Tidy HTML and grep.
#
# This is similar to the function available in scripts/validate_website.sh, but is simpler (e.g. it does not use
# webhint, linkinator or lighthouse). See that file for more details.
#
# Globals:
#   REMOTE_ENVIRONMENT_URL
# Arguments:
#   The URL to validate
##############################################################################
validate_html_url() {
    local -r page_id=$(uuidgen)
    local -r filename="tmp/page_${page_id}.html"
    local -r url=$(echo "$1" | sed -e "s|https://pccd.dites.cat|${REMOTE_ENVIRONMENT_URL}|")
    local error
    local status_code

    echo "Validating HTML of ${url} (${filename})..."

    status_code=$(curl -o "${filename}" --silent --write-out "%{http_code}" "${url}")
    if [[ ${status_code} != "200" ]]; then
        echo "ERROR: ${url} returned status code HTTP ${status_code}." >&2
        exit 255
    fi

    npx htmlhint "${filename}" || exit 255

    error=$(npx html-validate --config=.htmlvalidate.json "${filename}")
    # Fail when there is an error.
    # shellcheck disable=SC2181
    if [[ $? -ne 0 ]]; then
        echo "" >> tmp/test_html_errors.txt
        echo "Error reported by html-validate in ${url}: ${error}" >> tmp/test_html_errors.txt
        echo "" >> tmp/test_html_errors.txt
        exit 255
    fi
    # Otherwise, just print the warning message.
    if [[ -n ${error} ]]; then
        echo "" >> tmp/test_html_errors.txt
        echo "Error reported by html-validate in ${url}: ${error}" >> tmp/test_html_errors.txt
        echo "" >> tmp/test_html_errors.txt
    fi

    error=$(tidy -config .tidyrc "${filename}" 2>&1 > /dev/null)
    if [[ -n ${error} ]]; then
        # Log records with HTML issues for later analysis. This report is available in the admin section.
        echo "" >> tmp/test_html_errors.txt
        echo "Error reported by tidy in ${url}: ${error}" >> tmp/test_html_errors.txt
        echo "" >> tmp/test_html_errors.txt
    fi

    # Log records with "0 sources" for later analysis. This report is available in the admin section.
    if grep -q -F -m 1 "(0 fonts" "${filename}"; then
        echo "${url}" >> tmp/test_zero_fonts.txt
    fi
}
export -f validate_html_url

if strings "$(command -v xargs)" | grep -q -F -m 1 "FreeBSD"; then
    # The following is compatible with the xargs implementation (POSIX) included in macOS.
    xargs -n 1 -P 10 -S 512 -I {} bash -c 'validate_html_url "$@" || exit 255' _ {} < docroot/sitemap_all.txt
else
    # Let's assume this is GNU xargs.
    xargs -n 1 -P 10 -I {} bash -c 'validate_html_url "$@" || exit 255' _ {} < docroot/sitemap_all.txt
fi
echo "All URLs in the sitemap file returned HTTP 200."
find tmp/ -type f -name '*.html' -delete
