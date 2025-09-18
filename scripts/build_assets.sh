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

echo "Bundling and minifying JavaScript assets with esbuild..."
npx esbuild src/js/app.js src/js/pages/*.js \
  --bundle \
  --minify \
  --outdir=docroot/js \
  --outbase=src/js \
  --out-extension:.js=.min.js

echo "Bundling and minifying CSS assets with esbuild..."
npx esbuild src/css/base.css src/css/pages/*.css \
  --bundle \
  --minify \
  --external:/img/* \
  --outdir=docroot/css \
  --outbase=src/css \
  --out-extension:.css=.min.css

echo "Bundling Chart.js..."
cp node_modules/chart.js/dist/chart.umd.js docroot/admin/js/chart.min.js

npm run export:asset-sizes
