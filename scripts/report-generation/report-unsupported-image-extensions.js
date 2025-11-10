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
import { readdir } from "node:fs/promises";
import path from "node:path";
import process from "node:process";

const paremiesDirectory = path.join(
  import.meta.dirname,
  "../../src/images/paremies",
);
const cobertesDirectory = path.join(
  import.meta.dirname,
  "../../src/images/cobertes",
);

const listUnsupportedExtensions = async (
  sourceDirectory,
  supportedExtensions = new Set([".gif", ".jpg", ".png"]),
) => {
  const unsupportedFiles = [];

  const files = await readdir(sourceDirectory);
  for (const file of files) {
    if (file === ".picasa.ini") {
      continue;
    }

    const extension = path.extname(file);
    if (!supportedExtensions.has(extension)) {
      unsupportedFiles.push(file);
    }
  }

  return unsupportedFiles.join("\n");
};

// Export for testing
export { listUnsupportedExtensions };

// Run when executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  console.log(await listUnsupportedExtensions(cobertesDirectory));
  console.log(await listUnsupportedExtensions(paremiesDirectory));
}
