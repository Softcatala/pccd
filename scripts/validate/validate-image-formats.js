#!/usr/bin/env node
/**
 * Checks image extensions and formats.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { readdir, writeFile } from "node:fs/promises";
import console from "node:console";
import path from "node:path";
import sharp from "sharp";

const IGNORED_FILES = new Set([".picasa.ini"]);
const EXTENSION_TO_FORMAT = {
  avif: "avif",
  gif: "gif",
  jpg: "jpeg",
  png: "png",
  webp: "webp",
};

const rootDirectory = path.join(import.meta.dirname, "../..");
const imageDirectory = path.join(rootDirectory, "images");
const outputExtensionsFile = path.join(rootDirectory, "data/reports/test_imatges_extensions.txt");
const outputFormatFile = path.join(rootDirectory, "data/reports/test_imatges_format.txt");

const formatOutput = (title, results) => {
  if (results.length === 0) {
    return "";
  }
  return `${title}\n=============================\n${results.join("\n")}\n=============================\n\n`;
};

const checkImages = async (category) => {
  const extensionResults = [];
  const integrityResults = [];
  const categoryPath = path.join(imageDirectory, category);

  let files;
  try {
    files = await readdir(categoryPath);
  } catch {
    files = [];
  }

  for (const file of files) {
    if (IGNORED_FILES.has(file)) {
      continue;
    }

    const extension = path.extname(file).slice(1).toLowerCase();
    const expectedFormat = EXTENSION_TO_FORMAT[extension];
    const filePath = path.join(categoryPath, file);

    try {
      const image = sharp(filePath);

      // 1. Check Extension vs Actual Format.
      const metadata = await image.metadata();
      if (metadata.format !== expectedFormat) {
        extensionResults.push(`${file} has format '${metadata.format || "unknown"}'`);
      }

      // 2. Check Integrity.
      await image.stats();
    } catch (error) {
      integrityResults.push(`${file}: ${error.message}`);
    }
  }

  return {
    extensionsContent: formatOutput(category, extensionResults),
    integrityContent: formatOutput(category, integrityResults),
  };
};

console.log("Checking image extensions and formats...");

const categories = ["cobertes", "paremies"];
let finalExtensionsContent = "";
let finalIntegrityContent = "";

for (const category of categories) {
  const { extensionsContent, integrityContent } = await checkImages(category);
  finalExtensionsContent += extensionsContent;
  finalIntegrityContent += integrityContent;
}

await writeFile(outputExtensionsFile, finalExtensionsContent);
await writeFile(outputFormatFile, finalIntegrityContent);

console.log("Image validation finished.");
