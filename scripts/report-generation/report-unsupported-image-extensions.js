#!/usr/bin/env node
/**
 * Reports images with unsupported file extensions.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import console from "node:console";
import path from "node:path";
import process from "node:process";
import { readdir } from "node:fs/promises";

const IGNORED_FILES = new Set([".picasa.ini"]);
const SUPPORTED_EXTENSIONS = new Set([".gif", ".jpg", ".png"]);

const paremiesDirectory = path.join(import.meta.dirname, "../../images/paremies");
const cobertesDirectory = path.join(import.meta.dirname, "../../images/cobertes");

const listUnsupportedExtensions = async (sourceDirectory) => {
  const unsupportedFiles = [];

  const files = await readdir(sourceDirectory);
  for (const file of files) {
    if (IGNORED_FILES.has(file)) {
      continue;
    }

    if (!SUPPORTED_EXTENSIONS.has(path.extname(file))) {
      unsupportedFiles.push(file);
    }
  }

  return unsupportedFiles.join("\n");
};

// Export for testing.
export { listUnsupportedExtensions };

// Run when executed directly.
if (import.meta.url === `file://${process.argv[1]}`) {
  console.log(await listUnsupportedExtensions(cobertesDirectory));
  console.log(await listUnsupportedExtensions(paremiesDirectory));
}
