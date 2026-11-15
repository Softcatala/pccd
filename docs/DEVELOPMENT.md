# Development

This document covers local development, testing, profiling, and the content update/release workflow.

## Common commands

Build assets:

```sh
node scripts/build-assets.js
```

**Note**: Compiled assets in `docroot/css/` and `docroot/js/`, and optimized SVGs in `docroot/img/`, are committed to git. Remember to rebuild and commit them when modifying source files in `src/css/`, `src/js/`, or SVG images.

Code quality checks:

```sh
npm run check
```

Run the full test suite:

```sh
npm test
```

Notes:

- `BASE_URL` can be overridden for tests that target a web server (e.g. Playwright): `BASE_URL=https://pccd.dites.cat npm test`
- If e2e tests fail after data changes, run `node tests/playwright/refresh-test-data.js`
- To run validation over many pages, use `npm run validate:sitemap-urls`
- If Lighthouse audits crash, try specifying `CHROME_PATH`:
  `CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" npm run validate:lighthouse`

Third-party admin scripts:

`src/third_party/apc.php` and `src/third_party/opcache-gui.php` are downloaded from upstream by the Composer `update-third-party` script, which runs after `composer install` and `composer update`. Local fixes to these files live in `patches/` and are applied automatically by the same script. If an upstream change makes a patch fail, update the patch and commit it together with the regenerated files.

Profiling (dev only, Apache-based):

```sh
docker compose build --build-arg profiler=spx && docker compose up
docker compose build --build-arg profiler=xhprof && docker compose up
```

Profiler reports are available in `/admin/` (admin password is set in `.env`).

Test production setup locally (FPM + Nginx):

```sh
docker compose -f docker-compose.fpm.yml up --build
```

## Updating the content and creating a new release

### Prerequisites

- Docker

Install system dependencies for database conversion and image processing:

- **Linux (Debian-based)**: `sudo apt-get install -y $(cat apt_dev_deps.txt)`
- **Linux (Alpine)**: `apk add $(cat apk_dev_deps.txt)`
- **macOS (Homebrew)**: `brew bundle install`

Then install npm dependencies:

```sh
npm ci
```

### Release workflow

Put the MS Access database (database.accdb) and any image archives (Cobertes.zip, Imatges.zip, Obres-VPR.zip) in the
root directory, then run:

```sh
scripts/release.sh
```

This single command:

1. Decompresses and optimizes images (if zip files are present)
2. Converts the MS Access database to MariaDB
3. Rebuilds containers and waits for the database to be healthy
4. Runs the installation script
5. Exports the database, runs tests, and generates reports

Options:

- `scripts/release.sh --skip-images` - Skip image processing
- `scripts/release.sh --skip-tests` - Skip running tests
- `scripts/release.sh --skip-reports` - Skip report generation

Then commit and push:

```sh
git add . && git commit -m 'new release' && git push
npm run export:code
```

### Docker-based release (Windows / cross-platform)

The release can also run entirely inside Docker using the build container:

```sh
docker compose -f docker-compose.build.yml run build npm ci
docker compose -f docker-compose.build.yml run build scripts/release.sh
```
