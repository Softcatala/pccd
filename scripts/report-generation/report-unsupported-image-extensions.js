#!/usr/bin/env node
/**
 * Reports images with unsupported file extensions.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import path from "node:path";
import { readdir } from "node:fs/promises";
import { pathToFileURL } from "node:url";
import { lines, readReportData, writeReport } from "./report-helpers.js";

const IGNORED_FILES = new Set([".picasa.ini"]);
const SUPPORTED_INPUT_IMAGE_EXTENSIONS = new Set([".gif", ".jpg", ".png"]);

const paremiesDirectory = path.join(import.meta.dirname, "../../images/paremies");
const cobertesDirectory = path.join(import.meta.dirname, "../../images/cobertes");

const listUnsupportedExtensions = async (sourceDirectory) => {
  const unsupportedFiles = [];

  const files = await readdir(sourceDirectory);
  for (const file of files) {
    if (IGNORED_FILES.has(file)) {
      continue;
    }

    // Flag extensions in uppercase too, to help with standardization.
    if (!SUPPORTED_INPUT_IMAGE_EXTENSIONS.has(path.extname(file))) {
      unsupportedFiles.push(file);
    }
  }

  return unsupportedFiles.join("\n");
};

const listUnsupportedDatabaseReferences = (fonts, imatges) => {
  const unsupportedFiles = [];

  for (const { Imatge } of fonts) {
    if (Imatge !== "" && !SUPPORTED_INPUT_IMAGE_EXTENSIONS.has(path.extname(Imatge))) {
      unsupportedFiles.push(`cobertes/${Imatge}`);
    }
  }
  for (const { Identificador } of imatges) {
    if (Identificador !== "" && !SUPPORTED_INPUT_IMAGE_EXTENSIONS.has(path.extname(Identificador))) {
      unsupportedFiles.push(`paremies/${Identificador}`);
    }
  }

  return lines(unsupportedFiles);
};

const run = async () => {
  const [fonts, imatges] = await Promise.all([
    readReportData("report_fonts.json"),
    readReportData("report_imatges.json"),
  ]);
  const [cobertes, paremies] = await Promise.all([
    listUnsupportedExtensions(cobertesDirectory),
    listUnsupportedExtensions(paremiesDirectory),
  ]);

  await Promise.all([
    writeReport("test_imatges_file_extensions.txt", `${cobertes}\n${paremies}\n`),
    writeReport("test_imatges_db_file_extensions.txt", listUnsupportedDatabaseReferences(fonts, imatges)),
  ]);
};

export { listUnsupportedDatabaseReferences, listUnsupportedExtensions };

if (process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
  await run();
}
