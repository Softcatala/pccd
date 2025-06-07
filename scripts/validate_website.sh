#!/usr/bin/env bash
#
# Runs some tests and checks against a running website.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e -o pipefail

cd "$(dirname "$0")"

##############################################################################
# Shows the help of this command.
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
    export "$(grep 'BASE_URL=' ../.env | xargs)"
    if [[ -z ${BASE_URL} ]]; then
        echo "ERROR: BASE_URL variable is not set." >&2
        exit 255
    fi
fi

readonly BASE_URL

##############################################################################
# Validates URL using curl, HTML Tidy, HTMLHint, Linkinator and HTML-validate.
# Arguments:
#   URL                  The URL to validate
# Optional arguments:
#   --skip-tidy          Skip HTML Tidy validation
#   --skip-htmlhint      Skip HTMLHint validation
#   --skip-linkinator    Skip Linkinator validation
#   --skip-htmlvalidate  Skip HTML-validate validation
#   --check-external     Check external links with Linkinator
##############################################################################
validate_url() {
    local -r URL=$1
    shift 1

    local SKIP_TIDY=""
    local SKIP_HTMLHINT=""
    local SKIP_LINKINATOR=""
    local SKIP_HTMLVALIDATE=""
    local CHECK_EXTERNAL=""

    while [[ $# -gt 0 ]]; do
        case $1 in
            --skip-tidy) SKIP_TIDY=true ;;
            --skip-htmlhint) SKIP_HTMLHINT=true ;;
            --skip-linkinator) SKIP_LINKINATOR=true ;;
            --skip-htmlvalidate) SKIP_HTMLVALIDATE=true ;;
            --check-external) CHECK_EXTERNAL=true ;;
            *)
                echo "Unknown parameter passed: $1"
                exit 1
                ;;
        esac
        shift
    done

    local -r OUTPUT_FILENAME=../tmp/page.html

    echo ""
    echo "Validating ${URL}"

    # curl is used to generated the HTML file, for the tools that only support local files. Additionally, HTTP status
    # code is also checked.
    echo "=============="
    echo "curl"
    echo "=============="
    local status_code
    set +e
    status_code=$(curl --compressed -o "${OUTPUT_FILENAME}" --silent --write-out "%{http_code}" "${URL}")
    if [[ ${status_code} != "200" ]]; then
        echo "ERROR: Status code HTTP ${status_code}." >&2
        exit 255
    else
        echo "No HTTP errors."
    fi
    set -e -o pipefail

    # HTACG HTML Tidy is an old tool written in C that checks code against modern standards. See .tidyrc file.
    if [[ -z ${SKIP_TIDY} ]]; then
        echo "=============="
        echo "HTML Tidy"
        echo "=============="
        local error
        set +e
        error=$(tidy -config ../.tidyrc "${OUTPUT_FILENAME}" 2>&1 > /dev/null)
        if [[ -n ${error} ]]; then
            echo "ERROR reported in HTML Tidy: ${error}" >&2
            exit 255
        else
            echo "No errors."
        fi
        set -e -o pipefail
    fi

    # HTMLHint is a fast static analysis tool for HTML. See the .htmlhintrc.json file for configuration.
    # Although this package is not actively maintained, it remains a better option than Markuplint, which is slow and
    # enforces too many rules by default.
    if [[ -z ${SKIP_HTMLHINT} ]]; then
        echo "=============="
        echo "htmlhint"
        echo "=============="
        npx htmlhint --config ../.htmlhintrc.json "${OUTPUT_FILENAME}"
    fi

    # Linkinator works well for checking that all links work properly.
    if [[ -z ${SKIP_LINKINATOR} ]]; then
        echo "=============="
        echo "linkinator"
        echo "=============="
        if [[ -n ${CHECK_EXTERNAL} ]]; then
            echo "Checking both internal and external links."
            npx linkinator "${URL}" --verbosity error
        else
            echo "Checking internal links only."
            npx linkinator "${URL}" --verbosity error --skip "^(?!${BASE_URL})"
        fi
    fi

    # HTML-validate is an offline HTML5 validator with strict parsing. Apart from parsing and content model validation
    # it also includes style, cosmetics, good practice and accessibility rules. See .htmlvalidate.json.
    if [[ -z ${SKIP_HTMLVALIDATE} ]]; then
        echo "=============="
        echo "html-validate"
        echo "=============="
        curl --fail --silent "${URL}" | npx html-validate --stdin --config=../.htmlvalidate.json
        echo "No html-validate issues."
    fi
}

##############################################################################
# Validates an HTTP 404 page.
# Arguments:
#   URL         The URL to validate
##############################################################################
validate_url_404() {
    local -r URL=$1
    local -r OUTPUT_FILENAME=../tmp/page.html

    echo ""
    echo "Validating 404 page ${URL}..."

    echo "=============="
    echo "curl"
    echo "=============="
    set +e
    local status_code
    status_code=$(curl --compressed -o "${OUTPUT_FILENAME}" --silent --write-out "%{http_code}" "${URL}")
    if [[ ${status_code} != "404" ]]; then
        echo "Error: Status code HTTP ${status_code}." >&2
        exit 255
    else
        echo "No HTTP errors."
    fi
    set -e -o pipefail
}

# Check 404 pages.
validate_url_404 "${BASE_URL}/p/A_Abrerasefserewrwe"
validate_url_404 "${BASE_URL}/asdfasdfsadfs"

# Validate HTML.
validate_url "${BASE_URL}/"
validate_url "${BASE_URL}/projecte"
validate_url "${BASE_URL}/top100"
validate_url "${BASE_URL}/llibres"
validate_url "${BASE_URL}/instruccions"
validate_url "${BASE_URL}/credits"
validate_url "${BASE_URL}/fonts"
validate_url "${BASE_URL}/p/A_Abrera%2C_donen_garses_per_perdius"
validate_url "${BASE_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era"
validate_url "${BASE_URL}/obra/Pons_Lluch%2C_Josep_%281993%29%3A_Refranyer_menorqu%C3%AD"
validate_url "${BASE_URL}/?pagina=5147"
validate_url "${BASE_URL}/?mode=&cerca=ca%C3%A7a&variant=&mostra=10"
validate_url "${BASE_URL}/p/A_Adra%C3%A9n%2C_tanys"
validate_url "${BASE_URL}/p/A_Alaior%2C_mostren_la_panxa_per_un_guix%C3%B3_o_bot%C3%B3"
validate_url "${BASE_URL}/?mostra=infinit" --skip-linkinator --skip-htmlvalidate

echo "All validation tests finished OK :)"
