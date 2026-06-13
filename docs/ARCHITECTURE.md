# Architecture

This document describes the architectural principles and design decisions for the Paremiologia catalana comparada digital (PCCD) project.

## Core Philosophy

This project prioritizes **simplicity, performance, and long-term maintainability** over architectural complexity. As a data-heavy linguistic database focused on content display and search, procedural approaches often work better than complex architectural patterns.

## Technology Stack

### Backend

- **PHP** - No framework, pure PHP with utility functions. Uses PDO for database access (no ORM). Modern features preferred.
- **MariaDB** - Database. Latest LTS preferred.
- **Web server** - Apache with mod_php (dev), Nginx + PHP-FPM (prod), or FrankenPHP.

### Frontend

- **Vanilla JavaScript (ESM)** - Minimal usage, transpiled.
- **Modern CSS** - Transpiled, page-specific bundles.

### Build Tools

- **esbuild** - JavaScript bundling and transpiling.
- **lightningcss** - CSS processing and minification.
- **sharp** - Image processing and optimization.

### Asset Management

Compiled assets in `docroot/css/` and `docroot/js/` are committed to version control. This simplifies deployment by eliminating build steps in production and ensures reproducibility. Source files in `src/css/` and `src/js/` must be rebuilt before committing changes (see DEVELOPMENT.md).

### Script Language Conventions

- **Node.js (ESM)** - Default for build tools, validation, testing, and complex text processing. Prefer Node.js over sed/awk/perl for anything beyond trivial one-liners.
- **POSIX sh** - Used for system operations (Docker, DB migrations, CI tasks). Target `/bin/sh` and maintain compatibility between macOS and Linux. Keep logic simple; use Node.js for anything complex.
- **PHP** - Used for database-dependent operations like report generation or data integrity checks.

Node.js is preferred for maintainability and portability. Shell scripts are reserved for operations where shell execution is a natural fit.

### Image Processing

**Input formats**: `.jpg`, `.png`, `.gif` only.

The optimization pipeline resizes, quantizes, and compresses images, generating modern variants (AVIF, and WebP for animated GIFs). Output formats are `.avif` and `.webp`.

### Code Quality

Standard tooling (PHPStan level 9, Psalm, ESLint, Prettier, Stylelint, ShellCheck) enforces code quality.

- **PHPStan custom rules** - Project-specific quality checks.
- **Data integrity reports** - Custom PHP scripts in `scripts/report-generation/` validate links, detect duplicates, and check asset integrity. These run offline via `npm run generate:reports`.

### Code Style Conventions

- **No emojis** - Never use emojis in code, comments, or documentation.
- **No SCREAMING CASE** - Avoid all-caps text in prose and messages (except for constants/env vars).
- **Professional tone** - Keep language clear, direct, and technical.

## Design Principles

### Simplicity Over Abstraction

Avoid unnecessary frameworks and abstractions. Use procedural code when appropriate.

- Only a few PHP classes in the codebase.
- No runtime Composer dependencies.
- No JavaScript or CSS frameworks; targeted libraries are acceptable (e.g., Chart.js).

### Right Tool for the Job

- Node.js: File processing, HTTP requests, build tools.
- Shell scripts: Docker operations and system commands.
- PHP: Database-dependent operations.

### Minimize System Dependencies

Prefer npm/Composer packages over system binaries. `composer.phar` is committed to the repository to ensure reproducibility.

Current system dependencies include tools for image optimization (gifsicle, jpegoptim), validation (jpeginfo, pngcheck), linting (shellcheck, shfmt), and data processing (mdbtools, icu-devtools).

### Dependency Versioning

- **npm**: Exact versions.
- **Composer**: Dev dependencies use caret ranges.
- **Docker images**: Application images pin to specific releases. CI edge-testing images may float.
- **System dependencies**: Not version-pinned.

## Application Architecture

### Routing

Application-level routing without web server rewrites. The `route_request()` function in `docroot/index.php` parses URL paths and populates `$_GET` parameters. This ensures portability across Apache, Nginx, and FrankenPHP.

### Page Rendering

Pages render via output buffering. A single template (`src/templates/main.php`) wraps all content. Page metadata is configured via static methods on `PageRenderer`.

### Data Access

Database rows map to `readonly` data classes via `PDO::FETCH_CLASS`. Direct PDO usage provides full control over queries without ORM complexity.

## Data Ingestion

The working dataset is converted from Microsoft Access (`.accdb`) to MariaDB. Source images in `images/` are optimized and transcoded into `docroot/img/`.

## Deployment

### Docker-First Development

- **Development**: Debian-based image with Apache and volume mounts.
- **Production (Nginx + FPM)**: Alpine-based setup for improved concurrency.
- **Production (FrankenPHP)**: Single-container alternative using Caddy.

Dockerfiles use the project root as build context. `.dockerignore` excludes large directories like `node_modules` or source `images/` to speed up builds.

### CI/CD Pipeline

Multi-stage validation in `.gitlab-ci.yml` includes code quality checks, testing across OS variants, building Docker images, and manual production deployment.

## Performance

- **Caching**: 1-year immutable cache for static assets, 15 minutes for HTML. APCu (64MB) and Opcache (32MB) are used for expensive operations and scripts.
- **HTTP requests**: CSS and JavaScript are inlined where appropriate to minimize round trips.
- **Optimization**: Lazy loading, link prefetching, and Brotli/zstd compression are employed.

The slowest pages load in under 100ms without complex caching layers like Varnish.

## Project Structure

```text
/.docker/              # Server configuration
/data/                 # Report data and historical snapshots
/docroot/              # Document root
/install/db/           # Database dumps
/scripts/              # Build and deployment automation
/src/                  # Server-side source code
/tests/                # Automated test suites
```

## Offline Reports

Expensive data quality reports run via `npm run generate:reports`.

- **Link validation** for books and sources.
- **Duplicate detection** using Levenshtein distance.
- **Image integrity** for JPEG/PNG files.
- **Grammar checking** using `@pccd/lt-filter`.
