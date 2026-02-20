#!/usr/bin/env node
/**
 * Finds duplicate images by comparing file hashes.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { readFile, readdir, stat } from "node:fs/promises";
import console from "node:console";
import crypto from "node:crypto";
import path from "node:path";
import process from "node:process";

/**
 * Finds duplicate images by comparing MD5 hashes of files with the same size.
 *
 * @param {string} [customPath] - Optional custom directory path to search.
 * @returns {Promise<string>} Newline-separated list of duplicate image groups.
 */
const findDuplicateImages = async (customPath) => {
  const directoryPath = customPath || path.join(import.meta.dirname, "/../../images/paremies/");
  const files = {};
  const duplicates = [];

  for (const file of await readdir(directoryPath)) {
    const filePath = path.join(directoryPath, file);
    const fileStat = await stat(filePath);

    if (fileStat.isFile()) {
      const fileSize = fileStat.size;

      // Group files by size as a quick pre-check.
      if (!files[fileSize]) {
        files[fileSize] = [];
      }
      files[fileSize].push(filePath);
    }
  }

  // Compare files with the same size.
  for (const sameSizeFiles of Object.values(files)) {
    if (sameSizeFiles.length > 1) {
      // Group files by hash.
      const fileHashes = await Promise.all(
        sameSizeFiles.map(async (file) => {
          const fileBuffer = await readFile(file);
          const hash = crypto.createHash("md5").update(fileBuffer).digest("hex");
          return { file, hash };
        }),
      );

      const hashedGroups = Object.groupBy(fileHashes, ({ hash }) => hash);

      for (const duplicateGroup of Object.values(hashedGroups)) {
        if (duplicateGroup.length > 1) {
          duplicates.push(duplicateGroup.map(({ file }) => file));
        }
      }
    }
  }

  let output = "";
  for (const group of duplicates) {
    output += "\n" + group.map((file) => path.basename(file)).join("\n") + "\n";
  }

  return output.trim();
};

// Export for testing.
export { findDuplicateImages };

// Run when executed directly.
if (import.meta.url === `file://${process.argv[1]}`) {
  console.log(await findDuplicateImages());
}
