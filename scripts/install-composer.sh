#!/bin/sh
#
# Downloads the latest stable Composer version.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -eu

cd "$(dirname "$0")/.."

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "${EXPECTED_CHECKSUM}" != "${ACTUAL_CHECKSUM}" ]; then
  rm -f composer-setup.php
  echo "Composer installer checksum verification failed." >&2
  exit 1
fi

php composer-setup.php --quiet --install-dir=. --filename=composer.phar
rm -f composer-setup.php

echo "Composer installed: $(./composer.phar --version)"
