# Updating the content and creating a new release

## Option 1: Native + Docker (Linux / macOS)

### Prerequisites: Linux (Debian-based)

```bash
xargs sudo apt-get install -y < apt_packages.txt
```

You may want to set up Docker to be used with a non-root user.

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

### Prerequisites: Linux (Nix) / macOS (Nix)

Note: this is not regularly tested.

```bash
nix-shell
```

### Procedure

**Part 1**: Update the database, add new images and build the container. Usually, new image files (Cobertes.zip,
Imatges.zip, Obres-VPR.zip) are provided. Put them in the root directory alongside the MS Access database
(database.accdb) before running the following (skip the first 2 commands if images have not been provided):

```bash
npm run decompress:images && npm run optimize:images && npm run convert:db && npm run docker:build
```

**Part 2**: Install (in a separate shell, after the database has been initialized in **Part 1**)

```bash
npm run install:db
```

When this command finishes, the website should be available and run properly.

**Part 3**: Export the database, run tests and generate reports.

```bash
npm run prepare:deploy
```

Additionally, consider running tests (`npm test`) on the Alpine-based image too.

The code can now be pushed to both private and public repositories for deployment:

```bash
git add . && git commit -m 'new release' && git push
npm run export:code
```

## Option 2: Docker-based (Linux, macOS, Windows)

The whole process of updating a release could be run 100% inside Docker, although this is not regularly tested.

Example on Windows:

```batch
:: Disable .dockerignore to include everything in the build context
ren .dockerignore .dockerignore.disabled
:: Start the build-specific container
docker compose -f docker-compose-build.yml up
:: Run the processing commands within the build container
docker compose -f docker-compose-build.yml run build /bin/bash -c "npm run decompress:images && npm run optimize:images && npm run convert:db"
:: Restore .dockerignore
ren .dockerignore.disabled .dockerignore
:: Remove existing container, in case it was already created before
docker compose down --volumes
:: Start the HTTP and MariaDB servers
docker compose up --build
:: Execute the installation script inside the web container
docker exec pccd-web scripts/install.sh
:: Export the updated database
docker exec pccd-mysql /usr/bin/mysqldump -uroot -pcontrasenyarootmysql --skip-dump-date --ignore-table=pccd.commonvoice pccd > install\db\db.sql
```
