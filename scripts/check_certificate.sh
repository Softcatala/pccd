#!/usr/bin/env bash
#
# Checks the expiration date in the production certificate.
#
# Usage:
#   ./check_certificate.sh [ENVIRONMENT_URL] [IP]
#
# The website URL and IP address can be passed as arguments.
# By default, https://pccd.dites.cat is used and the IP is omitted. Pass "origin" as IP if you want to use ORIGIN_IP
# variable defined in your .env file.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

shopt -s expand_aliases

cd "$(dirname "$0")/.."

REMOTE_ENVIRONMENT_URL="${1:-https://pccd.dites.cat}"
readonly REMOTE_ENVIRONMENT_URL
export REMOTE_ENVIRONMENT_URL

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: ./$(basename "$0") [ENVIRONMENT_URL] [IP]"
    echo ""
    echo "Optional arguments:"
    echo "  ENVIRONMENT_URL       The website URL, without trailing slash (default: https://pccd.dites.cat)"
    echo "  IP                    The IP address to connect to. Use when you want to resolve the domain to a specific IP."
}

if [[ $# -gt 2 ]]; then
    usage
    exit 1
fi

# Call gdate from coreutils.
if command -v gdate > /dev/null; then
    alias date=gdate
fi

ORIGIN_IP="$2"
if [[ ${ORIGIN_IP} == 'origin' ]]; then
    export "$(grep 'ORIGIN_IP=' .env | xargs)"
fi
readonly ORIGIN_IP

# Get the expiration date of the certificate with curl.
if [[ -z ${ORIGIN_IP} ]]; then
    EXPIRATION_DATE=$(curl -v -I --stderr - "${REMOTE_ENVIRONMENT_URL}" | grep "expire date" | cut -d ":" -f 2- | xargs)
else
    DOMAIN=$(echo "${REMOTE_ENVIRONMENT_URL}" | awk -F/ '{print $3}')
    EXPIRATION_DATE=$(curl -v -I --stderr - --resolve "${DOMAIN}:443:${ORIGIN_IP}" "${REMOTE_ENVIRONMENT_URL}" | grep "expire date" | cut -d ":" -f 2- | xargs)
fi

# Convert the expiration date to seconds, using GNU date.
EXPIRATION_DATE_SECONDS=$(date -d "${EXPIRATION_DATE}" +%s)

# Get the current date.
CURRENT_DATE=$(date -d "$(date +%Y-%m-%d)" +%s)

# Calculate the difference in days.
EXPIRATION_DATE_DAYS=$(((EXPIRATION_DATE_SECONDS - CURRENT_DATE) / 86400))

# Exit with error if the certificate expires in less than 10 days.
if [[ ${EXPIRATION_DATE_DAYS} -lt 10 ]]; then
    COLOR='\033[0;31m'
    RET=1
else
    COLOR='\033[0;32m'
    RET=0
fi

NC='\033[0m'
if [[ -z ${ORIGIN_IP} ]]; then
    echo -e "${COLOR}${REMOTE_ENVIRONMENT_URL} certificate expires in ${EXPIRATION_DATE_DAYS} days.${NC}"
else
    echo -e "${COLOR}${REMOTE_ENVIRONMENT_URL} (${ORIGIN_IP}) certificate expires in ${EXPIRATION_DATE_DAYS} days.${NC}"
fi
exit "${RET}"
