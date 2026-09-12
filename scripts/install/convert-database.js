#!/usr/bin/env node
/**
 * Converts the MS Access database to MariaDB.
 *
 * Requires mdbtools (mdb-schema, mdb-export).
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import fs from "node:fs";
import path from "node:path";
import readline from "node:readline";
import { spawnSync } from "node:child_process";

const rootDirectory = path.join(import.meta.dirname, "../..");
const temporaryDirectory = path.join(rootDirectory, "data/db");
const installDatabaseFile = path.join(rootDirectory, "install/db/db.sql");

// Source tables exported from MS Access.
const TABLES = ["00_PAREMIOTIPUS", "00_FONTS", "00_IMATGES", "00_EDITORIA", "00_OBRESVPR", "00_EQUIVALENTS", "RML"];

// Tables dropped before import (source + app-managed).
const DROP_TABLES = [...TABLES, "common_paremiotipus", "paremiotipus_display", "pccd_is_installed"];

// Tables that get WIDTH/HEIGHT columns after data load.
const IMAGE_DIMENSION_TABLES = ["00_FONTS", "00_IMATGES", "00_OBRESVPR"];

/**
 * Prints CLI usage.
 */
const usage = () => {
  console.log(`Usage: node scripts/install/convert-database.js [DATABASE_FILENAME]

Optional arguments:
  DATABASE_FILENAME     The MS Access database file (default: database.accdb)`);
};

/**
 * Runs a command with stdout written to a file (overwrite or append).
 *
 * @param {string} filePath
 * @param {string} command
 * @param {{ argv: string[], append?: boolean }} options
 */
const runToFile = (filePath, command, { argv, append = false }) => {
  const fd = fs.openSync(filePath, append ? "a" : "w");
  try {
    const result = spawnSync(command, argv, {
      stdio: ["ignore", fd, "inherit"],
    });
    if (result.error) {
      throw result.error;
    }
    if (result.status !== 0) {
      throw new Error(`Command failed (${result.status}): ${command} ${argv.join(" ")}`);
    }
  } finally {
    fs.closeSync(fd);
  }
};

/**
 * Applies Unicode NFKC normalization to a text file, then restores ellipsis
 * characters (NFKC turns … into ..., so we map them back). Streams line-by-line
 * to handle large files.
 */
const normalizeFileNfkc = async (inputPath, outputPath) => {
  const rl = readline.createInterface({
    input: fs.createReadStream(inputPath, { encoding: "utf8" }),
    crlfDelay: Infinity,
  });

  const out = fs.createWriteStream(outputPath, { encoding: "utf8" });

  for await (const line of rl) {
    out.write(`${line.normalize("NFKC").replaceAll("...", "…")}\n`);
  }

  await new Promise((resolve, reject) => {
    out.on("error", reject);
    out.end(resolve);
  });
};

/**
 * Builds install/db/db.sql from an MS Access database.
 *
 * @param {string} databaseFile Absolute path to the .accdb file
 */
const convertDatabase = async (databaseFile) => {
  const appendSql = (sql) => {
    fs.appendFileSync(installDatabaseFile, `${sql}\n`);
  };

  const appendTableSchema = (table) => {
    runToFile(installDatabaseFile, "mdb-schema", {
      argv: ["--no-indexes", "--no-relations", "-T", table, databaseFile, "mysql"],
      append: true,
    });
  };

  const appendTableData = (table) => {
    runToFile(installDatabaseFile, "mdb-export", {
      argv: ["-I", "mysql", databaseFile, table],
      append: true,
    });
  };

  // Export schema and language table so breaking changes are easy to monitor.
  runToFile(path.join(temporaryDirectory, "msaccess_schema.sql"), "mdb-schema", {
    argv: [databaseFile, "mysql"],
  });
  runToFile(path.join(temporaryDirectory, "equivalents_dump.sql"), "mdb-export", {
    argv: ["--insert=mysql", "--batch-size=1", databaseFile, "00_EQUIVALENTS"],
  });

  fs.writeFileSync(installDatabaseFile, "");

  // mdb-export can dump dates as 1900-01-00 00:00:00; this mode was needed on MySQL.
  appendSql("SET sql_mode = ALLOW_INVALID_DATES;");

  for (const table of DROP_TABLES) {
    appendSql(`DROP TABLE IF EXISTS ${table};`);
  }

  for (const table of TABLES) {
    appendTableSchema(table);
  }

  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN ACCEPCIO varchar (2);");

  for (const table of TABLES) {
    appendTableData(table);
  }

  for (const table of IMAGE_DIMENSION_TABLES) {
    appendSql(`ALTER TABLE ${table} ADD COLUMN WIDTH int NOT NULL DEFAULT 0;`);
    appendSql(`ALTER TABLE ${table} ADD COLUMN HEIGHT int NOT NULL DEFAULT 0;`);
  }

  // Indexes for search, informant counts, and obra references.
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD PRIMARY KEY (Id);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (PAREMIOTIPUS);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (MODISME);");
  // AUTOR: informant counts on every page (cached by APCu).
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (AUTOR);");
  // ID_FONT: reference counts on the obra page and in reports.
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (ID_FONT);");

  // Full-text indexes for the search combinations.
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, SINONIM);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, EQUIVALENT);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, SINONIM);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, EQUIVALENT);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, SINONIM, EQUIVALENT);");
  appendSql("ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS, MODISME, SINONIM, EQUIVALENT);");

  // Multilingual.
  appendSql("ALTER TABLE RML ADD INDEX (PAREMIOTIPUS);");

  // Remaining tables are small; indexes have negligible size impact.
  appendSql("ALTER TABLE 00_FONTS ADD INDEX (Identificador);");
  appendSql("ALTER TABLE 00_IMATGES ADD INDEX (PAREMIOTIPUS);");
  appendSql("ALTER TABLE 00_EDITORIA ADD INDEX (CODI);");

  // App-managed tables.
  // Top 10000 paremiotipus display.
  appendSql("CREATE TABLE common_paremiotipus(Paremiotipus varchar (255), Compt int, INDEX (Compt));");
  // PAREMIOTIPUS is preprocessed for sorting; Display keeps the original value and
  // speeds up get_paremiotipus_count() in PHP.
  appendSql("CREATE TABLE paremiotipus_display(Paremiotipus varchar (255) PRIMARY KEY, Display varchar (255));");

  const temporaryFile = path.join(temporaryDirectory, "db_temp.sql");
  await normalizeFileNfkc(installDatabaseFile, temporaryFile);
  fs.renameSync(temporaryFile, installDatabaseFile);
};

const main = async () => {
  const userArguments = process.argv.slice(2);

  if (userArguments.includes("-h") || userArguments.includes("--help")) {
    usage();
    process.exit(0);
  }

  if (userArguments.length > 1) {
    usage();
    process.exit(1);
  }

  const databaseFileName = userArguments[0] ?? "database.accdb";
  const databaseFile = path.resolve(rootDirectory, databaseFileName);

  if (!fs.existsSync(databaseFile)) {
    console.error(`Error: Database file '${databaseFile}' not found.`);
    process.exit(1);
  }

  await convertDatabase(databaseFile);
};

await main();
