#!/usr/bin/env bash
#
# Automates the content release workflow.
# This script handles database conversion, image optimization, container setup,
# and testing.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/.."

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
  echo "Usage: ./$(basename "$0") [OPTIONS]"
  echo ""
  echo "Automates the content release workflow:"
  echo "  1. Decompress and optimize images (if zip files are present)"
  echo "  2. Convert the MS Access database"
  echo "  3. Rebuild and start containers (waits for DB to be healthy)"
  echo "  4. Run installation, tests, and generate reports"
  echo ""
  echo "Options:"
  echo "  --skip-images     Skip image decompression and optimization"
  echo "  --skip-tests      Skip running all tests"
  echo "  --skip-reports    Skip report generation"
  echo "  -h, --help        Show this help message"
}

SKIP_IMAGES=0
SKIP_TESTS=0
SKIP_REPORTS=0

while [[ $# -gt 0 ]]; do
  case $1 in
    --skip-images)
      SKIP_IMAGES=1
      shift
      ;;
    --skip-tests)
      SKIP_TESTS=1
      shift
      ;;
    --skip-reports)
      SKIP_REPORTS=1
      shift
      ;;
    -h | --help)
      usage
      exit 0
      ;;
    *)
      echo "Error: Unknown option $1"
      usage
      exit 1
      ;;
  esac
done

echo "=== PCCD Release Script ==="
echo ""

# Part 1: Process images and convert database
if [[ ${SKIP_IMAGES} -eq 0 ]]; then
  if ls ./*.zip 1> /dev/null 2>&1; then
    echo ">>> Decompressing images..."
    npm run decompress:images

    echo ">>> Optimizing images..."
    npm run optimize:images
  else
    echo ">>> No zip files found, skipping image processing"
  fi
else
  echo ">>> Skipping image processing (--skip-images)"
fi

if [[ ! -f database.accdb ]]; then
  echo "Error: database.accdb not found in root directory"
  exit 1
fi

echo ">>> Converting database..."
npm run convert:db

# Part 2: Rebuild and start containers
echo ">>> Stopping existing containers..."
docker compose down --volumes

echo ">>> Building and starting containers (waiting for DB to be healthy)..."
docker compose up -d --build --wait

echo ">>> Running installation..."
npm run install:db

# Part 3: Export, test, and generate reports
echo ">>> Exporting database..."
npm run export:db

echo ">>> Refreshing test data..."
npm run refresh:test-data

if [[ ${SKIP_REPORTS} -eq 0 ]]; then
  echo ">>> Generating reports..."
  npm run generate:reports
else
  echo ">>> Skipping report generation (--skip-reports)"
fi

if [[ ${SKIP_TESTS} -eq 0 ]]; then
  echo ">>> Stopping dev containers..."
  docker compose down --remove-orphans

  echo ">>> Starting production containers (fpm + nginx, reusing database volume)..."
  docker compose -f docker-compose.fpm.yml up -d --build --wait

  echo ">>> Running tests..."
  npm run test
fi

echo ""
echo "=== Release preparation complete ==="
echo ""
echo "Next steps:"
echo "  1. Review changes and reports"
echo "  2. Stop containers: docker compose down"
echo "  3. Commit: git add . && git commit -m 'new release'"
echo "  4. Push: git push"
echo "  5. Export to public repo: npm run export:code"
