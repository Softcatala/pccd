# Paremiologia catalana comparada digital (PCCD) [![License: AGPL v3](docs/img/agpl-v3.svg)](https://www.gnu.org/licenses/agpl-3.0) [![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php)](https://www.php.net/) [![Node.js 22.16.0+](https://img.shields.io/badge/Node.js-22.16.0+-339933?logo=node.js)](https://nodejs.org/)

This is the source code of [Paremiologia catalana comparada digital](https://pccd.dites.cat/) website.

## Installation

1. Copy `.env.sample` to `.env`.

2. Build the container using the default Debian-based image:

```bash
docker compose up
```

When the database has finished importing, the website should be available at <http://localhost:8092>, depending on your
`.env` file.

Note: If you don't have a database, you can copy `data/db/schema.sql` and `data/db/schema_init_sample.sql` files to
`install/db/`. That will import an empty database and should allow you to browse the website locally.

## Technology stack

<a href="https://www.php.net/"><img src="docs/img/php.svg" alt="PHP" width="50"></a>
<a href="https://developer.mozilla.org/docs/Web/JavaScript"><img src="docs/img/javascript.svg" alt="JavaScript" width="50"></a>
<a href="https://developer.mozilla.org/docs/Web/CSS"><img src="docs/img/css.svg" alt="CSS" width="50"></a>
<a href="https://mariadb.org/"><img src="docs/img/mariadb.svg" alt="MariaDB" width="50"></a>
<a href="https://github.com/mdbtools/mdbtools"><img src="docs/img/mdbtools.png" alt="MDB Tools" width="50"></a>

## Development requirements

- PHP 8.4+
- Node.js 22.16.0+

## Development

This project was originally developed by a human, before LLMs were available. LLMs have since been used to assist with code improvements and documentation.

## Documentation

- [Architecture](docs/ARCHITECTURE.md) - Design principles and technical decisions
- [Development](docs/DEVELOPMENT.md) - Development workflow (tests, profiling, releases)

## Contributing

For details on contributing to this repository, see the contributing guidelines:

- [English version](docs/CONTRIBUTING.md)
- [Versió en català](docs/CONTRIBUTING_CA.md)

## Copyright & license

Copyright (c) Pere Orga Esteve <pere@orga.cat>, 2020.

Copyright (c) Víctor Pàmies i Riudor <vpamies@gmail.com>, 2020.

This source code is licensed under the GNU Affero General Public License, version 3 or later, as
detailed in the [LICENSE](LICENSE) file or available at <https://www.gnu.org/licenses/agpl-3.0.html>.

Note that the database and media files are not distributed with this repository. For more details about PCCD, visit <https://pccd.dites.cat/>.

### Bundled dependencies

This repository includes:

- [Chart.js](https://www.chartjs.org/), licensed under the [MIT License](https://github.com/chartjs/Chart.js/blob/master/LICENSE.md).
- [Composer](https://getcomposer.org/), licensed under the [MIT License](https://github.com/composer/composer/blob/main/LICENSE).
- [Roboto font](https://github.com/googlefonts/roboto), licensed under the [Apache License Version 2.0](src/fonts/LICENSE).
- [simple-datatables](https://github.com/fiduswriter/simple-datatables), licensed under the
  [LGPL Version 3](https://github.com/fiduswriter/simple-datatables/blob/main/LICENSE).

### Related projects

The following tools were originally developed as part of this project and are now maintained in separate repositories:

- [@pccd/lt-filter](https://www.npmjs.com/package/@pccd/lt-filter) - A command-line tool for filtering Catalan sentences using LanguageTool. It separates grammatically or orthographically incorrect sentences from correct ones. Licensed under the LGPL 2.1 or later.
- [pereorga/phpstan-rules](https://packagist.org/packages/pereorga/phpstan-rules) - Custom opinionated rules for PHPStan. Licensed under the MIT License.
