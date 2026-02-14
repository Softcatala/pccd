# Architecture

This document describes the architectural principles and design decisions for the Paremiologia catalana comparada digital (PCCD) project.

## Core Philosophy

This project prioritizes **simplicity, performance, and long-term maintainability** over architectural complexity. As a data-heavy linguistic database focused on content display and search, procedural approaches often work better than complex architectural patterns.

## Technology Stack

### Backend

- **PHP** - Server-side application logic
  - No framework - pure PHP with utility functions
  - Modern language features (typed properties, enums, named arguments)
  - Latest stable PHP version preferred if it helps improving code quality
  - PDO for DB access (no ORM)
- **MariaDB** - Database
  - Removed previous MySQL support to simplify encoding support
  - Latest LTS release supported, previous versions likely to work but usually untested
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

### Asset Management

**Compiled assets are committed to version control.** Source files in `src/css/` and `src/js/` are built into `docroot/css/` and `docroot/js/` via esbuild and lightningcss.

**Rationale**: Simplifies deployment by eliminating build steps in production, reduces runtime dependencies (no Node.js/build tools needed in containers), ensures reproducibility (exact deployed code is in git), and enables faster deployments.

**Trade-offs**: Requires rebuilding assets before committing source changes (see DEVELOPMENT.md) and pollutes git history with minified code diffs, making pull request reviews less readable.

### Script Language Conventions

- **Node.js (ESM)** - Default choice for scripts
  - Build tools (bundling, asset processing)
  - Validation and testing (crawling, Lighthouse audits)
  - File system operations
  - HTTP requests and API interactions
  - Text processing and data manipulation
  - **Prefer Node.js over sed/awk/perl** for anything beyond trivial one-liners
- **Bash** - System and environment operations only
  - Docker commands
  - Database dumps and migrations
  - Archive extraction
  - CI/CD pipeline tasks
  - Simple glue logic and command orchestration
  - **Version requirement**: Requires GNU Bash 4.0+. macOS ships with bash 3.2, so Brewfile includes `brew "bash"` to install a modern version.
  - **Portability requirement**: Scripts must be compatible with both macOS (BSD utilities) and Linux (GNU utilities)
  - **Keep it simple**: Prefer portable shell patterns across macOS and Linux. For complex text processing, use Node.js instead
- **PHP** - Database-dependent operations
  - Report generation requiring SQL queries
  - Data integrity checks and content analysis
  - Runs inside web container with database access

**Rationale**: Node.js scripts are more maintainable, testable, and portable than complex bash/sed/awk scripts. Bash should be reserved for operations where shell execution is the natural fit (Docker, git, system commands).

### Shell Script Portability

Bash scripts require GNU Bash 4.0+ and work across macOS (BSD utilities) and Linux (GNU utilities).

**Guidelines for new scripts**:

1. **Prefer Node.js over bash** for anything beyond simple command orchestration
2. **Never add** utilities with portability issues: `awk`/`gawk`, `perl`, `ruby`, `python` (for scripting - use Node.js instead)
3. **Avoid sed/xargs when possible** - If the logic is complex enough to need sed, consider using Node.js instead
4. **Keep bash scripts simple** - Only use for Docker/git operations and glue logic

**Portable patterns currently used**:

- `sed -i'.backup'` - Works on both BSD and GNU sed (creates `.backup` file as side-effect, which is cleaned up immediately)
- `grep -E`, `grep -F`, `grep -o` - Extended regex, fixed strings, and only-matching are standard across both
- `find ... -print0 | xargs -0` - Null-delimited output/input, standard across both
- `xargs -r` - `-r` (`--no-run-if-empty`) is a GNU extension. On GNU/Linux, it prevents running the command when input is empty. On macOS/BSD, `xargs` already skips execution on empty input, and `-r` is accepted as a compatibility no-op.
- Standard POSIX utilities without GNU-specific extensions

**Utilities to avoid**:

- `awk`/`gawk` - Different implementations (BSD awk vs GNU awk), use Node.js instead
- `readlink -f` - GNU-only, not available on BSD/macOS
- `date -d` - GNU-only date arithmetic, use Node.js instead
- `sed -r` - GNU-only, use `sed -E` (portable extended regex) if needed
- `grep -P` - Perl regex, GNU-only, use `grep -E` or Node.js instead
- `stat` - Completely different flags between BSD/GNU, use Node.js `fs.stat()` instead
- `perl`, `python`, `ruby` - Extra language dependencies, use Node.js (already required)

**Commands currently used in scripts**:

All commands are either:

- Standard shell builtins
- Standard POSIX utilities
- System dependencies documented in `Brewfile` and `apt_dev_deps.txt`

### Image Processing

**Input formats**: `.jpg`, `.png`, `.gif` only

**Optimization pipeline** (during release):

1. Resize to target dimensions
2. Lossy palette quantization (for PNG) and optimization
3. Lossless compression
4. Generate modern format variants (AVIF, and WebP for animated GIFs with alpha)

**Output**: `.jpg`/`.png` → `.avif`, `.gif` → `.webp`

Source images reside in `images/` and are built into `docroot/img/` by `scripts/install/optimize-images.js`.

### Code Quality

**Standard tooling**: PHPStan (level 9), Psalm, ESLint, Prettier, Stylelint, ShellCheck, and other industry-standard linters enforce code quality. See `package.json` and `composer.json` for the complete list.

**Notable decisions**:

- **PHPStan custom rules** (`pereorga/phpstan-rules`) - Project-specific quality checks beyond standard analysis
- **Meta-linting** - Scripts validate that linting configurations are complete and non-redundant
- **Multi-version CI** - Tests run across multiple PHP/Node.js versions and operating systems (Debian, Ubuntu, Alpine)

**Data integrity reports** (`scripts/report-generation/`) - Custom PHP scripts perform deep content validation:

- Link validation for broken URLs in bibliography and sources
- Duplicate detection using Levenshtein distance and `Spoofchecker`
- Consistency checks for accentuation, image references, and linguistic variant relationships
- Asset integrity validation for missing or orphaned images

These reports run offline via `npm run generate:reports` for manual data maintenance.

### Code Style Conventions

**Tone and readability**:

- **No emojis** - Never use emojis in code, comments, commit messages, or documentation
- **No SCREAMING CASE** - Avoid all-caps text in prose, comments, and user-facing messages. Exception: constants and environment variables follow language conventions (e.g., `MYSQL_ROOT_PASSWORD`, `const MAX_RETRIES`)
- **Professional tone** - Keep language clear, direct, and technical without informal expressions

**Rationale**: Emojis and all-caps text reduce professionalism and accessibility. Code should be readable in any environment (terminal, IDE, documentation generators) and accessible to screen readers.

## Design Principles

### Simplicity Over Abstraction

**Principle**: Avoid unnecessary frameworks and abstractions. Use procedural code when appropriate.

**Implementation**:

- Only a few PHP classes in the entire codebase
- No runtime Composer dependencies (beyond PHP extensions)
- No JavaScript or CSS frameworks (libraries acceptable for specific use cases, e.g., `simple-datatables` for table functionality, `chart.js` for report visualization)

**Rationale**: Complex architectural patterns add cognitive overhead without providing value for a data-display application. Minimizing dependencies simplifies maintenance and updates. While frameworks dictate application architecture and control flow, targeted libraries that solve specific problems without imposing structural constraints are acceptable.

### Right Tool for the Job

**Principle**: Choose the best language/tool for each specific task.

**Script organization**:

- Node.js for file processing, HTTP requests, and build tools
- Bash for Docker operations and system commands
- PHP for database-dependent operations

### Minimize System Dependencies

**Principle**: Prefer npm/Composer packages over system binaries to simplify tooling.

- Prefer npm/Composer packages and language built-in APIs when functionality is equivalent
- Keep unavoidable system dependencies minimal and explicitly documented
- `composer.phar` is committed to the repository to keep tooling reproducible across dev and CI, without requiring Composer installation

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

### Dependency Versioning

**Principle**: Pin versions as tightly as practical to ensure reproducible builds. Dependencies are actively kept at their latest stable release (or LTS for MariaDB) via `scripts/update_deps.sh`.

**npm (exact versions)**: All dependencies use exact versions (e.g., `1.2.3`, no `^` or `~` prefix).

**Composer (caret ranges)**: Dev dependencies use standard caret ranges (e.g., `^2.1`).

**MariaDB (pinned patch version)**: The Docker image is pinned to an exact patch release (e.g., `11.8.6-noble`).

**Docker base images (mixed)**: Most images pin to specific releases (e.g., `alpine:3.23`). Edge/testing images intentionally float (`alpine:edge`) to catch compatibility issues early with upcoming versions.

## Application Architecture

### Routing

**Application-level routing without web server rewrites.** The `route_request()` function in `docroot/index.php` parses URL paths and populates `$_GET` parameters directly in PHP.

**URL patterns**:

- `/p/{slug}` → paremiotipus page (`$_GET['paremiotipus']`)
- `/obra/{slug}` → book page (`$_GET['obra']`)
- `/og/{slug}.png` → dynamic OG image generation
- Static pages (`/fonts`, `/credits`, `/llibres`, etc.) determined by array lookup in `PageRenderer::STATIC_PAGE_NAMES`

**Rationale**: Makes routing portable across Apache, Nginx, and FrankenPHP without maintaining separate web server configuration files. All routing logic lives in PHP. Using `$_GET` provides backward compatibility with legacy query string URLs (`?paremiotipus=`, `?obra=`).

### Page Rendering

**Template rendering via output buffering.** Pages render through a three-step process:

1. `ob_start()` initiates output buffering
2. `require` loads the page file (e.g., `src/pages/paremiotipus.php`)
3. `ob_get_clean()` captures output as a string

A single template file (`src/templates/main.php`) wraps all page content. Page metadata (title, description, OpenGraph tags) is configured via static methods on `PageRenderer` during page execution.

**Rationale**: Enables polymorphic rendering without a templating engine while maintaining separation between content generation and layout.

### Data Access

**PDO with readonly data classes.** Database rows map automatically to objects via `PDO::FETCH_CLASS`:

- `Obra`, `ParemiotipusVariant`, `ParemiotipusImage` are `readonly` classes
- Private properties set via constructor, exposed through getter methods
- Rendering logic embedded in data classes (e.g., `ParemiotipusVariant::renderBody()`)

Single PDO connection per request, cached by `get_db()`.

**Rationale**: Avoids ORM complexity while gaining type safety through readonly classes. Direct PDO usage provides full control over queries and performance characteristics.

## Data Ingestion

The canonical working dataset is maintained outside the runtime database and is converted/imported during install/update:

- **Database conversion**: a Microsoft Access source (`.accdb`) is converted into SQL for MariaDB.
- **Images**: source images live under `images/` and are optimized/transcoded into `docroot/img/` during release builds.

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

**Build context and `.dockerignore`**:

All Dockerfiles use the project root (`.`) as their build context. The `.dockerignore` file filters what gets sent to the Docker daemon during `docker build`:

- Files/directories in `.dockerignore` are excluded from the build context and cannot be used in `COPY` commands
- Volume mounts are unaffected—containers using volumes get full project access at runtime, including ignored files
- The current `.dockerignore` entries (e.g., `.git`, `node_modules`, `vendor`) are safe because no Dockerfile attempts to `COPY` them

This setup significantly speeds up builds by excluding large directories. For example, a source `images/` directory can be excluded when:

- Development/build containers access it via volume mounts
- Production containers (`fpm`, `nginx`) only copy optimized output from `docroot/`

### CI/CD Pipeline

**Multi-stage validation** (`.gitlab-ci.yml`):

1. **Code Quality**: Parallel linting and analysis across multiple PHP and Node.js versions
2. **Testing**: Multiple OS variants (Debian, Ubuntu, Alpine)
3. **Build**: Create and tag Docker images
4. **Deploy**: Manual approval required for production deployments

## Performance

### Caching Strategy

**Multi-level caching**:

- **Browser**: 1-year immutable cache for static assets, 15 minutes for HTML pages
- **APCu**: 64MB shared memory cache
  - Wrapped in `cache_get()` helper with callback pattern
  - Graceful fallback when extension not loaded (executes callback directly)
  - Used for expensive operations: search results, display text lookups, and database-derived lookup tables
  - Callback pattern: `cache_get($key, fn() => expensive_operation())`
- **Opcache**: 32MB, no timestamp validation (safe in immutable containers)

### Performance Philosophy

- **Minimal complexity**: No Varnish, CDN, or unnecessary caching layers (the slowest pages load in <100ms)
- **HTTP request reduction**: CSS/JavaScript inlined to minimize round trips
- **Link prefetching**: JavaScript-based prefetching on hover
- **Lazy loading**: HTML-only (no JavaScript required)
- **Compression**: Brotli/gzip (Apache/Nginx) or zstd/gzip (FrankenPHP)
- **No runtime overhead**: No HTML minification or excessive compression during request handling
- **Pragmatic approach**: Microoptimizations welcome when they don't add complexity or maintenance burden

## Project Structure

```text
/.docker/              # Container definitions and server configuration
/data/                 # Report inputs/outputs, historical snapshots and database date
/docroot/              # Document root (publicly served files)
/install/db/           # Database SQL dumps
/scripts/              # Build and deployment automation
  /install/            # Database initialization
  /lint/               # Code quality checks
  /report-generation/  # Data integrity reports
  /validate/           # Runtime validation
/src/                  # Server-side source code
  /css/                # Stylesheet source
  /js/                 # Client-side JavaScript source
  /pages/              # Page request handlers
  /reports/            # Data analysis scripts
  /templates/          # PHP templates
  /third_party/        # Third-party scripts (APCu/Opcache GUIs for profiling)
/tests/                # Automated test suites
/tmp/                  # Temporary files (validation output, test artifacts)
```

## Offline Reports

Some data quality reports are computationally expensive or require additional system dependencies. These run outside via `npm run generate:reports` and are used for manual data maintenance.

**Report types**:

- **Link validation** - checks for broken URLs in books, sources, and images (uses PHP `ext-curl`)
- **Duplicate detection** - finds similar or confusable entries using Levenshtein distance, `Spoofchecker`, and `Transliterator` (uses PHP `ext-intl`)
- **Image integrity** - validates JPEG/PNG files (uses `jpeginfo`, `pngcheck`)
- **Grammar checking** - flags grammatically incorrect sentences (uses `@pccd/lt-filter`)
