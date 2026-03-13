#!/usr/bin/env node
/**
 * Exports synonyms from Softcatalà dictionary format.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import console from "node:console";
import path from "node:path";
import { readFile } from "node:fs/promises";

const SoftcatalaSinonimsFile = path.join(import.meta.dirname, "../../../sinonims-cat/dict/sinonims.txt");

try {
  const data = await readFile(SoftcatalaSinonimsFile, "utf8");

  // Extract lines and process each one.
  const lines = data.split("\n");
  const result = [];

  for (const line of lines) {
    // Remove text after #, category prefix, parentheses content and backslashes.
    const cleanedLine = line
      .replace(/#.*/u, "")
      .replace(/^-.*?:\s*/u, "")
      .replaceAll(/\([^)]*\)/gu, "")
      .replaceAll("\\", "");

    // Split by commas, trim whitespace and filter terms with spaces.
    const multiWordTerms = cleanedLine
      .split(",")
      .map((word) => word.trim())
      .filter((word) => word.includes(" "));

    // Add to result if there are any multi-word terms in the line.
    if (multiWordTerms.length > 0) {
      result.push(multiWordTerms.join("\n"));
    }
  }

  // Join groups with a new line.
  console.log(result.join("\n\n"));
} catch (error) {
  console.error("Error reading file:", error);
  throw error;
}
