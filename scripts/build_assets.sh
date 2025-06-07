#!/usr/bin/env bash
#
# Minifies the assets.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")/.."

echo "Minifying src/js/app.js..."
npx terser src/js/app.js --compress --mangle > docroot/js/app.min.js
sdt_version=$(jq -r '.dependencies["simple-datatables"]' package.json | sed 's/^\^//')
sdt_dislaimer="/*! This bundle includes simple-datatables ${sdt_version} (https://github.com/fiduswriter/simple-datatables). License: https://www.gnu.org/licenses/lgpl-3.0.html */"
for file in src/js/pages/*.js; do
    echo "Minifying ${file}..."
    if [[ "${file}" == "src/js/pages/fonts.js" ]]; then
        echo "${sdt_dislaimer}" > docroot/js/pages/fonts.min.js
        echo "Bundling simple-datatables JS file..."
        npx terser node_modules/simple-datatables/dist/umd/simple-datatables.js --compress --mangle >> docroot/js/pages/fonts.min.js
        npx terser src/js/pages/fonts.js --compress --mangle >> docroot/js/pages/fonts.min.js
    else
        npx terser "${file}" --compress --mangle > "docroot/js/pages/$(basename "${file}" .js).min.js"
    fi
done

echo "Minifying src/css/base.css..."
npx lightningcss --minify --bundle src/css/base.css > docroot/css/base.min.css
for file in src/css/pages/*.css; do
    echo "Minifying ${file}..."
    if [[ "${file}" == "src/css/pages/fonts.css" ]]; then
        echo "Bundling simple-datatables CSS file..."
        echo "${sdt_dislaimer}" > docroot/css/pages/fonts.min.css
        npx lightningcss --minify --bundle node_modules/simple-datatables/dist/style.css >> docroot/css/pages/fonts.min.css
        npx lightningcss --minify --bundle "${file}" >> docroot/css/pages/fonts.min.css
    else
        npx lightningcss --minify --bundle "${file}" > "docroot/css/pages/$(basename "${file}" .css).min.css"
    fi
done

echo "Bundling Chart.js..."
cp node_modules/chart.js/dist/chart.umd.js docroot/admin/js/chart.min.js

npm run export:asset-sizes
