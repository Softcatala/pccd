#!/usr/bin/env bash
#
# Check expected diff for different dockerfiles.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

# Change directory to project root
cd "$(dirname "$0")/../.."

# Function to compare files
compare_files() {
    local file1="$1"
    local file2="$2"
    local expected_diff_file="$3"

    local expected_diff
    expected_diff=$(< "${expected_diff_file}")

    # Generate the actual diff, keeping only lines starting with '-' or '+'
    local actual_diff
    actual_diff=$(diff -u "${file1}" "${file2}" | grep '^[+-]' | grep -v '^+++' | grep -v '^---')

    echo "Comparing ${file1} and ${file2}..."
    if [[ "${actual_diff}" == "${expected_diff}" ]]; then
        echo "Test passed!"
    else
        echo "Test failed for ${file1} and ${file2}."
        echo "Generated diff:"
        echo "${actual_diff}"
        exit 1
    fi
}

# Test cases
compare_files ".docker/alpine.dev.Dockerfile" ".docker/web-alpine.prod.Dockerfile" "tests/bash/data/alpine_dev_prod_diff.txt"
compare_files ".docker/alpine.dev.Dockerfile" ".docker/alpine.edge.Dockerfile" "tests/bash/data/alpine_dev_edge_diff.txt"
