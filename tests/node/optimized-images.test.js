import { describe, test } from "node:test";
import assert from "node:assert";
import path from "node:path";
import { readdir } from "node:fs/promises";

const SOURCE_COBERTES_DIR = "images/cobertes";
const SOURCE_PAREMIES_DIR = "images/paremies";
const OUTPUT_OBRES_DIR = "docroot/img/obres";
const OUTPUT_IMATGES_DIR = "docroot/img/imatges";

/**
 * Retrieves image files from a directory, excluding hidden files.
 *
 * @param {string} directory - The directory path to read.
 * @returns {Promise<string[]>} Array of file names.
 */
const getImageFiles = async (directory) => {
  const files = await readdir(directory);
  return files.filter((file) => !file.startsWith("."));
};

/**
 * Returns the expected optimized format extension for a given source file.
 * PNG and JPG files should have AVIF versions, GIF files should have WebP versions.
 *
 * @param {string} filename - The source file name.
 * @returns {string} The optimized format extension.
 */
const getOptimizedExtension = (filename) => {
  const extension = path.extname(filename).toLowerCase();
  if (extension === ".gif") {
    return ".webp";
  }
  return ".avif";
};

describe("Optimized images", () => {
  test("cobertes files should exist in obres output directory", async () => {
    const sourceFiles = await getImageFiles(SOURCE_COBERTES_DIR);
    const outputFiles = new Set(await getImageFiles(OUTPUT_OBRES_DIR));

    const missingFiles = sourceFiles.filter((file) => !outputFiles.has(file));

    assert.deepStrictEqual(missingFiles, [], `Missing files in ${OUTPUT_OBRES_DIR}:\n${missingFiles.join("\n")}`);
  });

  test("cobertes files should have optimized versions in obres", async () => {
    const sourceFiles = await getImageFiles(SOURCE_COBERTES_DIR);
    const outputFiles = new Set(await getImageFiles(OUTPUT_OBRES_DIR));

    const missingOptimized = sourceFiles.filter((file) => {
      const baseName = path.parse(file).name;
      const optimizedFile = baseName + getOptimizedExtension(file);
      return !outputFiles.has(optimizedFile);
    });

    assert.deepStrictEqual(
      missingOptimized,
      [],
      `Missing optimized versions in ${OUTPUT_OBRES_DIR}:\n${missingOptimized.join("\n")}`,
    );
  });

  test("paremies files should exist in imatges output directory", async () => {
    const sourceFiles = await getImageFiles(SOURCE_PAREMIES_DIR);
    const outputFiles = new Set(await getImageFiles(OUTPUT_IMATGES_DIR));

    const missingFiles = sourceFiles.filter((file) => !outputFiles.has(file));

    assert.deepStrictEqual(missingFiles, [], `Missing files in ${OUTPUT_IMATGES_DIR}:\n${missingFiles.join("\n")}`);
  });

  test("paremies files should have optimized versions in imatges", async () => {
    const sourceFiles = await getImageFiles(SOURCE_PAREMIES_DIR);
    const outputFiles = new Set(await getImageFiles(OUTPUT_IMATGES_DIR));

    const missingOptimized = sourceFiles.filter((file) => {
      const baseName = path.parse(file).name;
      const optimizedFile = baseName + getOptimizedExtension(file);
      return !outputFiles.has(optimizedFile);
    });

    assert.deepStrictEqual(
      missingOptimized,
      [],
      `Missing optimized versions in ${OUTPUT_IMATGES_DIR}:\n${missingOptimized.join("\n")}`,
    );
  });
});
