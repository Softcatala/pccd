import console from "node:console";
import crypto from "node:crypto";
import { readdir, stat, readFile } from "node:fs/promises";
import path from "node:path";
import process from "node:process";

const findDuplicateImages = async function (customPath) {
  const directoryPath =
    customPath || path.join(import.meta.dirname, "/../../src/images/paremies/");
  const files = {};
  const duplicates = [];

  // Read directory and gather file information.
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
      // Group files by hash
      const fileHashes = await Promise.all(
        sameSizeFiles.map(async (file) => {
          const fileBuffer = await readFile(file);
          const hash = crypto
            .createHash("md5")
            .update(fileBuffer)
            .digest("hex");
          return { file, hash };
        }),
      );

      const hashedGroups = Object.groupBy(fileHashes, ({ hash }) => hash);

      // Check for duplicates.
      for (const duplicateGroup of Object.values(hashedGroups)) {
        if (duplicateGroup.length > 1) {
          duplicates.push(duplicateGroup.map(({ file }) => file));
        }
      }
    }
  }

  // Format output.
  let output = "";
  for (const group of duplicates) {
    output += "\n" + group.map((file) => path.basename(file)).join("\n") + "\n";
  }

  return output.trim();
};

// Export for testing
export { findDuplicateImages };

// Run when executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  console.log(await findDuplicateImages());
}
