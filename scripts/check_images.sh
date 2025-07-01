#!/usr/bin/env bash
#
# Checks image extensions and formats.
# This script usually takes a few minutes, unless a lot of new images are added. In that case, it can take a long time.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")"

image_dir="../src/images"
output_file="../tmp/test_imatges_extensions.txt"

check_image_extensions() {
    local category=$1
    local extensions=("jpg" "png" "gif" "avif" "webp")
    echo "${category}" >> "${output_file}"
    echo "=============================" >> "${output_file}"
    for ext in "${extensions[@]}"; do
        for f in "${image_dir}/${category}"/*."${ext}"; do
            # Skip if no files are found for the pattern.
            [[ -e "${f}" ]] || continue

            local filetype
            filetype=$(file -b --mime-type "${f}")

            local expected_type=""
            case ${ext} in
                "jpg") expected_type="image/jpeg" ;;
                "png") expected_type="image/png" ;;
                "gif") expected_type="image/gif" ;;
                "avif") expected_type="image/avif" ;;
                "webp") expected_type="image/webp" ;;
                *) expected_type="Unknown" ;;
            esac

            if [[ "${filetype}" != "${expected_type}" ]]; then
                local filename
                filename=$(basename "${f}")
                echo "${filename} is '${filetype}'" >> "${output_file}"
            fi
        done
    done
    echo "=============================" >> "${output_file}"
    echo "" >> "${output_file}"
}

cat /dev/null > "${output_file}"
cat /dev/null > ../tmp/test_imatges_format.txt
categories=("cobertes" "paremies")
for category in "${categories[@]}"; do
    set -e
    check_image_extensions "${category}"
    # We use set +e to ignore potential errors in jpeginfo, pngcheck and gifsicle commands.
    set +e
    jpeginfo -c "${image_dir}"/"${category}"/*.jpg | grep -F 'ERROR' | grep -F -v 'OK' >> ../tmp/test_imatges_format.txt
    pngcheck "${image_dir}"/"${category}"/*.png | grep -v 'OK:' >> ../tmp/test_imatges_format.txt
    gifsicle --info "${image_dir}"/"${category}"/*.gif 2>> ../tmp/test_imatges_format.txt
done
