# Architecture

This document describes the architectural principles and design decisions for the Paremiologia catalana comparada digital (PCCD) project.

## Core Philosophy

This project prioritizes **simplicity, performance, and long-term maintainability** over architectural complexity. As a data-heavy linguistic database focused on content display and search, procedural approaches often work better than complex architectural patterns.

## Technology Stack

### Backend

- **PHP** - Server-side application logic
  - No framework - pure PHP with utility functions
  - Modern language features (typed properties, enums, named arguments)
  - Latest stable PHP version preferred if it improves code quality
  - PDO for DB access (no ORM)
- **MariaDB** - Database
  - Removed MySQL support to simplify modern encoding support
  - Latest LTS release usually supported
- **Web server** - Three configurations available:
  - **Apache with mod_php** - Development
  - **Nginx + PHP-FPM** - Production
  - **FrankenPHP** - Alternative setup

### Frontend

- **Vanilla JavaScript (ESM)** - Transpiled for browser compatibility, minimal usage
- **Modern CSS** - Transpiled for broad browser support, page-specific bundles

### Build Tools

- **esbuild** - Fast JavaScript bundling and transpiling
- **lightningcss** - CSS processing and minification
- **sharp** - Image processing and optimization

### Script Language Conventions

Scripts are organized by the most appropriate tool for each task:

- **Node.js (ESM)** - Default choice for scripts
  - Build tools (bundling, asset processing)
  - Validation and testing (crawling, Lighthouse audits)
  - File system operations
  - HTTP requests and API interactions
- **Bash** - System and environment operations
  - Docker commands
  - Database dumps and migrations
  - Archive extraction
  - CI/CD pipeline tasks
- **PHP** - Database-dependent operations
  - Report generation requiring SQL queries
  - Data integrity checks and content analysis
  - Runs inside web container with database access

### Image Processing

**Input formats**: `.jpg`, `.png`, `.gif` only

**Optimization pipeline** (during release):

1. Resize to target dimensions
2. Lossy palette quantization (for PNG) and optimization
3. Lossless compression
4. Generate modern format variants (AVIF, and WebP for animated GIFs with alpha)

**Output**: `.jpg`/`.png` → `.avif`, `.gif` → `.webp`

Source images reside in `src/images/` and are built into `docroot/img/` by `scripts/install/optimize-images.js`.

### Code Quality

The project enforces code quality through **standard tools** supplemented by **custom rules** when necessary:

**PHP**:

- PHPStan at level 9 with custom rules (`pereorga/phpstan-rules`)
- Psalm, Rector, PHP-CS-Fixer, PHPCS
- PHPUnit
- `composer-normalize` for strict `composer.json` formatting

**JavaScript/Node**:

- ESLint with multiple plugins (unicorn, perfectionist...)
- Prettier, Stylelint + additional rulesets

**HTML**:

- HTML Tidy
- HTMLHint
- HTML-Validate

**System**:

- ShellCheck, shfmt, Hadolint, yamllint, xmllint
- ls-lint for file naming conventions

**Data Quality**:

Custom PHP reports (in `scripts/report-generation/`) perform deep integrity checks on the database content, such as:

- **Link validation**: Checks for broken URLs in bibliography and sources
- **Duplicate detection**: Uses Levenshtein distance and `Spoofchecker` to find similar or confusable linguistic entries
- **Consistency**: Validates accentuation, image references, and relationship between linguistic variants
- **Asset integrity**: Identifies missing or orphaned images

These reports are primarily used for manual data maintenance and consistency audits.

**Meta-linting**: Custom scripts validate that linting configs (HTMLHint, HTML-Validate, Stylelint) are complete and non-redundant.

**Testing**:

- PHPUnit
- Node.js built-in test runner
- Playwright (end-to-end)
- Lighthouse (performance and best practices audits)
- SSL certificate expiration monitoring

Some tools run in parallel via `npm run check:code` and are executed in CI/CD.

**Multi-version CI**: Tests and development tools are validated across multiple PHP and Node.js versions, plus OS variants (Debian, Ubuntu).

## Design Principles

### Simplicity Over Abstraction

**Principle**: Avoid unnecessary frameworks and abstractions. Use procedural code when appropriate.

**Implementation**:

- Only a few PHP classes in the entire codebase
- No runtime Composer dependencies (beyond PHP extensions)
- No JavaScript or CSS frameworks

**Rationale**: Complex architectural patterns add cognitive overhead without providing value for a data-display application. Minimizing dependencies simplifies maintenance and updates.

### Right Tool for the Job

**Principle**: Choose the best language/tool for each specific task.

**Script organization**:

- Node.js for file processing, HTTP requests, and build tools
- Bash for Docker operations and system commands
- PHP for database-dependent operations

### Minimize System Dependencies

**Principle**: Prefer npm/Composer packages over system binaries to simplify tooling.

**Rationale**:

- System dependencies require installation on every environment
- Different package managers (apt, brew) need separate maintenance
- Version incompatibilities across operating systems
- npm packages are cross-platform and versioned in `package.json`

**Strategy**:

1. Prefer npm/Composer packages when functionality is equivalent
2. Use language built-in APIs when possible
3. Document why remaining system dependencies are necessary

**Current system dependencies** (see `Brewfile` and `apt_dev_deps.txt`):

Image optimization:

- `oxipng`, `jpegoptim`, `gifsicle`, `gif2webp`

Image validation:

- `jpeginfo`, `pngcheck` - validate JPEG/PNG file integrity

Development tools:

- `curl` - HTTP requests in shell scripts
- `jq` - JSON parsing in shell scripts

Linters and formatters:

- `shellcheck`
- `shfmt`
- `hadolint`
- `yamllint`

Data tooling:

- `mdbtools` - database conversion pipeline
- `icu-devtools` - provides `uconv` for Unicode normalization during database conversion
- `7zip-standalone` - extract compressed image archives
- `default-jre-headless` - Java runtime for `@pccd/lt-filter` (LanguageTool wrapper)

## Data Ingestion

The canonical working dataset is maintained outside the runtime database and is converted/imported during install/update:

- **Database conversion**: a Microsoft Access source (`.accdb`) is converted into SQL for MariaDB.
- **Images**: source images live under `src/images/` and are optimized/transcoded into `docroot/img/` during release builds.

## Deployment

### Docker-First Development

**Principle**: Achieve development/production parity through containers.

**Implementation**:

- **Development**: Debian-based image (`.docker/dev.Dockerfile`) with Apache and volume mounts for live editing
- **Production (Nginx + FPM)**: Alpine-based setup for improved concurrency
  - Separate containers for nginx (`.docker/nginx.Dockerfile`) and PHP-FPM (`.docker/fpm.Dockerfile`)
  - Brotli compression via `nginx-mod-http-brotli`
  - Test locally with `docker compose -f docker-compose.fpm.yml up`
- **Production (FrankenPHP)**: Single-container alternative
  - Caddy web server with embedded PHP (`.docker/frankenphp.Dockerfile`)
  - zstd/gzip compression (no Brotli)
  - Test locally with `docker compose -f docker-compose.frankenphp.yml up`
- **Edge testing**: Use `.docker/fpm.edge.Dockerfile` to test with latest PHP on Alpine edge

### CI/CD Pipeline

**Multi-stage validation** (`.gitlab-ci.yml`):

1. **Code Quality**: Parallel linting and analysis across multiple PHP and Node.js versions
2. **Testing**: Multiple OS variants (Debian stable/testing, Ubuntu LTS/devel)
3. **Build**: Create and tag Docker images
4. **Deploy**: Manual approval required for production deployments

## Performance

### Caching Strategy

**Multi-level caching**:

- **Browser**: 1-year immutable cache for static assets, 15 minutes for HTML pages
- **APCu**: 64MB shared memory for searches and quick lookup data
- **Opcache**: 32MB, no timestamp validation (safe in immutable containers)

### Performance Philosophy

- **Minimal complexity**: No Varnish, CDN, or unnecessary caching layers (the slowest pages load in <100ms)
- **HTTP request reduction**: CSS/JavaScript inlined to minimize round trips
- **Link prefetching**: JavaScript-based prefetching on hover
- **Lazy loading**: HTML-only (no JavaScript required)
- **Compression**: Brotli/gzip (Nginx) or zstd/gzip (FrankenPHP)
- **No runtime overhead**: No HTML minification or excessive compression during request handling
- **Pragmatic approach**: Microoptimizations welcome when they don't add complexity or maintenance burden

## Project Structure

```text
/docroot/              # Apache web root (publicly served files)
/src/                  # Server-side source code
  /pages/              # Page request handlers
  /reports/            # Data analysis scripts
  /js/                 # Client-side JavaScript source
  /css/                # Stylesheet source
  /templates/          # PHP templates
/scripts/              # Build and deployment automation
  /install/            # Database initialization
  /validate/           # Runtime validation
  /lint/               # Code quality checks
  /report-generation/  # Data integrity reports
/tests/                # Automated test suites
/.docker/              # Container definitions and server configuration
/install/db/           # Database SQL dumps
```

## Offline Reports

Some data quality reports are computationally expensive or require additional system dependencies. These run outside via `npm run generate:reports` and are used for manual data maintenance.

**Report types**:

- **Link validation** - checks for broken URLs in books, sources, and images (uses PHP `ext-curl`)
- **Duplicate detection** - finds similar or confusable entries using Levenshtein distance, `Spoofchecker`, and `Transliterator` (uses PHP `ext-intl`)
- **Image integrity** - validates JPEG/PNG files (uses `jpeginfo`, `pngcheck`)
- **Grammar checking** - flags grammatically incorrect sentences (uses `@pccd/lt-filter`)
