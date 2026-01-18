# Development

This document covers local development, testing, profiling, and the content update/release workflow.

## Common commands

Build assets:

```bash
npm run build:assets
```

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

### Option 1: Native + Docker (Linux / macOS)

### Prerequisites: Linux (Debian-based)

```bash
xargs sudo apt-get install -y < apt_dev_deps.txt
```

You may want to set up Docker to be used with a non-root user.

### Prerequisites: Linux (Alpine)

```bash
apk add $(grep -v '^#' apk_dev_deps.txt | tr '\n' ' ')
```

The rest of dependencies can be installed using `npm`:

```bash
npm ci
```

### Prerequisites: macOS (Homebrew)

After installing [Homebrew](https://brew.sh/), run the following from the root directory to install all developer
dependencies:

```bash
brew bundle install && npm ci
```

### Procedure

**Part 1**: Update the database, add new images and build the container. Usually, new image files (Cobertes.zip,
Imatges.zip, Obres-VPR.zip) are provided. Put them in the root directory alongside the MS Access database
(database.accdb) before running the following (skip the first 2 commands if images have not been provided):

```bash
npm run decompress:images && npm run optimize:images && npm run convert:db
docker compose down --volumes && docker compose build --no-cache && docker compose up
```

**Part 2**: Install (in a separate shell, after the database has been initialized in **Part 1**)

```bash
npm run install:db
```

When this command finishes, the website should be available and run properly.

**Part 3**: Export the database, run tests and generate reports.

```bash
npm run export:db && npm run refresh:test-data && npm run test && npm run generate:reports && npm run sync:external-reports
```

Additionally, consider running tests on the FPM setup to verify production compatibility:

```bash
docker compose -f docker-compose.fpm.yml up --build
```

The code can now be pushed to both private and public repositories for deployment:

```bash
git add . && git commit -m 'new release' && git push
npm run export:code
```

### Option 2: Docker-based (Linux, macOS, Windows)

The whole process of updating a release could be run 100% inside Docker, although this is not regularly tested.

Example on Windows:

```batch
:: Start the build-specific container
docker compose -f docker-compose.build.yml up
docker compose -f docker-compose.build.yml run build /bin/bash -c "npm ci"
:: Run the processing commands within the build container
docker compose -f docker-compose.build.yml run build /bin/bash -c "npm run decompress:images && npm run optimize:images && npm run convert:db"
:: Remove existing container, in case it was already created before
docker compose down --volumes
:: Start the HTTP and MariaDB servers
docker compose up --build
:: Execute the installation script inside the web container
docker exec pccd-web scripts/install.sh
:: Export the updated database
docker exec pccd-mysql /usr/bin/mysqldump -uroot -pcontrasenyarootmysql --skip-dump-date --ignore-table=pccd.commonvoice pccd > install\db\db.sql
```
