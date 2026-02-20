#!/usr/bin/env node
/**
 * Checks for filenames that differ only by case.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { readdir, stat } from "node:fs/promises";
import console from "node:console";
import path from "node:path";
import process from "node:process";

const rootDirectory = path.join(import.meta.dirname, "../..");
const directoriesToCheck = [path.join(rootDirectory, "images"), path.join(rootDirectory, "docroot/img")];

/**
 * Finds filenames that differ only by case
 */
const findCaseConflicts = (files) => {
  const conflicts = [];
  const lowerCaseMap = new Map();

  for (const file of files) {
    const lowerCase = file.toLowerCase();

    if (lowerCaseMap.has(lowerCase)) {
      const existing = lowerCaseMap.get(lowerCase);
      if (existing !== file) {
        conflicts.push([existing, file]);
      }
    } else {
      lowerCaseMap.set(lowerCase, file);
    }
  }

  return conflicts;
};

/**
 * Recursively gets all files in a directory
 */
const getAllFiles = async (directory, baseDirectory = directory) => {
  const files = [];

  try {
    const entries = await readdir(directory);

    for (const entry of entries) {
      const fullPath = path.join(directory, entry);
      const stats = await stat(fullPath);

      if (stats.isDirectory()) {
        files.push(...(await getAllFiles(fullPath, baseDirectory)));
      } else {
        // Store relative path from base directory.
        files.push(path.relative(baseDirectory, fullPath));
      }
    }
  } catch (error) {
    // Skip directories that don't exist or can't be read
    if (error.code !== "ENOENT" && error.code !== "EACCES") {
      throw error;
    }
  }

  return files;
};

// Main execution.
let hasConflicts = false;

for (const directory of directoriesToCheck) {
  const files = await getAllFiles(directory);

  if (files.length === 0) {
    continue;
  }

  const conflicts = findCaseConflicts(files);

  if (conflicts.length > 0) {
    hasConflicts = true;
    const relativePath = path.relative(rootDirectory, directory);
    console.log(`Found case-only conflicts in ${relativePath}:`);

    for (const [file1, file2] of conflicts) {
      console.log(`   - "${file1}" ↔ "${file2}"`);
    }
  }
}

if (hasConflicts) {
  console.log("\nThese files would conflict on case-sensitive filesystems (Linux)");
  process.exit(1);
} else {
  console.log("No case-only filename conflicts found");
  process.exit(0);
}
