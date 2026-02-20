#!/usr/bin/env node
/**
 * Reports images that are below minimum width threshold.
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
import sharp from "sharp";

const IMAGE_MIN_WIDTH = 350;
const IGNORED_FILES = new Set([".picasa.ini"]);

const paremiesDirectory = path.join(import.meta.dirname, "../../images/paremies");
const cobertesDirectory = path.join(import.meta.dirname, "../../images/cobertes");

/**
 * Lists images that are below the minimum width threshold.
 *
 * @param {string} sourceDirectory - Directory to scan for images.
 * @param {number} [minimumWidth=IMAGE_MIN_WIDTH] - Minimum width threshold in pixels.
 * @returns {Promise<string>} Newline-separated list of small images with their widths.
 */
const listSmallImages = async (sourceDirectory, minimumWidth = IMAGE_MIN_WIDTH) => {
  const smallImages = [];
  const files = await readdir(sourceDirectory);

  for (const file of files) {
    if (IGNORED_FILES.has(file)) {
      continue;
    }

    const filePath = path.join(sourceDirectory, file);
    try {
      const { width } = await sharp(filePath).metadata();
      if (width < minimumWidth) {
        smallImages.push(`${file} (${width} px)`);
      }
    } catch (error) {
      smallImages.push(`Error while trying to open ${file}: ${error.message}`);
    }
  }

  return smallImages.join("\n");
};

const main = async () => {
  const cobertes = await listSmallImages(cobertesDirectory);
  console.log(cobertes);
  const paremies = await listSmallImages(paremiesDirectory);
  console.log(paremies);
};

try {
  await main();
} catch (error) {
  console.error(error);
  process.exit(1);
}
