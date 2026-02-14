# Development

This document covers local development, testing, profiling, and the content update/release workflow.

## Common commands

Build assets:

```bash
npm run build:assets
```

**Note**: Compiled assets in `docroot/css/` and `docroot/js/` are committed to git. Remember to rebuild and commit compiled assets when modifying source files in `src/css/` or `src/js/`.

Code quality checks:

```bash
npm run check:code
```

Run the full test suite:

```bash
npm test
```

Notes:

- `BASE_URL` can be overridden for tests that target a web server (e.g. Playwright): `BASE_URL=https://pccd.dites.cat npm test`
- If e2e tests fail after data changes, run `npm run refresh:test-data`
- To run validation over many pages, use `npm run validate:sitemap-urls`
- If Lighthouse audits crash, try specifying `CHROME_PATH`:
  `CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" npm run validate:lighthouse`

Profiling (dev only, Apache-based):

```bash
docker compose build --build-arg profiler=spx && docker compose up
docker compose build --build-arg profiler=xhprof && docker compose up
```

Profiler reports are available in `/admin/` (admin password is set in `.env`).

Test production setup locally (FPM + Nginx):

```bash
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

```bash
npm ci
```

### Release workflow

Put the MS Access database (database.accdb) and any image archives (Cobertes.zip, Imatges.zip, Obres-VPR.zip) in the
root directory, then run:

```bash
npm run release
```

This single command:

1. Decompresses and optimizes images (if zip files are present)
2. Converts the MS Access database to MariaDB
3. Rebuilds containers and waits for the database to be healthy
4. Runs the installation script
5. Exports the database, runs tests, and generates reports

Options:

- `npm run release -- --skip-images` - Skip image processing
- `npm run release -- --skip-tests` - Skip running tests
- `npm run release -- --skip-reports` - Skip report generation

Then commit and push:

```bash
git add . && git commit -m 'new release' && git push
npm run export:code
```

### Docker-based release (Windows / cross-platform)

The release can also run entirely inside Docker using the build container:

```bash
docker compose -f docker-compose.build.yml run build npm ci
docker compose -f docker-compose.build.yml run build npm run release
```
