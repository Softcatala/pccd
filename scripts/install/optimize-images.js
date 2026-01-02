#!/usr/bin/env node
/**
 * Optimizes images by compressing and converting to web-friendly formats.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { execFileSync, spawnSync } from "node:child_process";
import console from "node:console";
import fs from "node:fs";
import path from "node:path";
import process from "node:process";
import sharp from "sharp";

const SIZE_THRESHOLD = 5000;
const PNG_MIN_QUALITY = 70;
const PNG_MAX_QUALITY = 95;
const IMAGE_WIDTH = 500;

// TODO: consider removing all these packages to reduce the number of dependencies.
const GIF2WEBP = "gif2webp";
const GIFSICLE = "gifsicle";
const JPEGOPTIM = "jpegoptim";
const OXIPNG = "oxipng";
const PNGQUANT = "pngquant";

const paremiesDirectory = path.join(import.meta.dirname, "../../src/images/paremies");
const paremiesTargetDirectory = path.join(import.meta.dirname, "../../docroot/img/imatges");
const cobertesDirectory = path.join(import.meta.dirname, "../../src/images/cobertes");
const cobertesTargetDirectory = path.join(import.meta.dirname, "../../docroot/img/obres");

const isCommandAvailable = (command) => {
  const result = spawnSync(command, ["--version"], { stdio: "ignore" });
  return result.status === 0;
};

const OXIPNG_AVAILABLE = isCommandAvailable(OXIPNG);
if (!OXIPNG_AVAILABLE) {
  console.log("oxipng is not available, skipping it for PNG optimization.");
}

const fileSizeDifferenceBelowThreshold = (file1, file2, threshold = SIZE_THRESHOLD) => {
  const size1 = fs.statSync(file1).size;
  const size2 = fs.statSync(file2).size;
  return size1 - threshold <= size2;
};

const resizeImage = async (sourceFile, targetFile, width) => {
  try {
    const metadata = await sharp(sourceFile).metadata();

    if (metadata.width > width) {
      await sharp(sourceFile).resize({ width }).toFile(targetFile);
    }
  } catch (error) {
    console.error(`Error while resizing ${sourceFile}: ${error.message}`);
  }

  if (!fs.existsSync(targetFile) || fileSizeDifferenceBelowThreshold(sourceFile, targetFile)) {
    // Restore original file.
    fs.copyFileSync(sourceFile, targetFile);
  }
};

const createAvifImage = async (sourceFile, targetFile, width) => {
  const targetFileAvif = path.format({
    dir: path.dirname(targetFile),
    ext: ".avif",
    name: path.basename(targetFile, path.extname(targetFile)),
  });

  // Process file only once.
  if (fs.existsSync(targetFileAvif)) {
    return;
  }

  try {
    await sharp(sourceFile).resize({ width, withoutEnlargement: true }).toFormat("avif").toFile(targetFileAvif);

    if (fileSizeDifferenceBelowThreshold(sourceFile, targetFileAvif)) {
      fs.unlinkSync(targetFileAvif);
    }
  } catch (error) {
    console.error(`Error while processing ${sourceFile} to AVIF: ${error.message}`);
  }
};

const processPng = async (sourceFile, targetFile, width) => {
  await resizeImage(sourceFile, targetFile, width);

  execFileSync(PNGQUANT, [
    "--skip-if-larger",
    `--quality=${PNG_MIN_QUALITY}-${PNG_MAX_QUALITY}`,
    "--ext=.png",
    "--force",
    targetFile,
  ]);

  if (OXIPNG_AVAILABLE) {
    execFileSync(OXIPNG, ["--quiet", "-o3", "--strip", "safe", "--zopfli", targetFile]);
  }

  await createAvifImage(sourceFile, targetFile, width);
};

const processJpg = async (sourceFile, targetFile, width) => {
  await resizeImage(sourceFile, targetFile, width);
  execFileSync(JPEGOPTIM, ["--strip-all", "--quiet", targetFile]);
  await createAvifImage(sourceFile, targetFile, width);
};

const processGif = (sourceFile, targetFile) => {
  execFileSync(GIFSICLE, ["--no-warnings", "-O3", "--output", targetFile, sourceFile]);

  if (!fs.existsSync(targetFile) || fs.statSync(sourceFile).size <= fs.statSync(targetFile).size) {
    // Restore original file.
    fs.copyFileSync(sourceFile, targetFile);
  }

  // TODO: consider using AVIF instead, although animation and alpha channel
  // should be preserved, and looks like the tooling is not there yet.
  const targetFileWebp = path.format({
    dir: path.dirname(targetFile),
    ext: ".webp",
    name: path.basename(targetFile, path.extname(targetFile)),
  });

  // Process file only once.
  if (fs.existsSync(targetFileWebp)) {
    return;
  }

  execFileSync(GIF2WEBP, ["-q", "100", "-mt", "-m", "6", "-o", targetFileWebp, targetFile]);

  if (fileSizeDifferenceBelowThreshold(targetFile, targetFileWebp)) {
    fs.unlinkSync(targetFileWebp);

    // Try again with lossy compression.
    execFileSync(GIF2WEBP, ["-mt", "-m", "6", "-lossy", "-o", targetFileWebp, targetFile]);

    if (fileSizeDifferenceBelowThreshold(targetFile, targetFileWebp)) {
      fs.unlinkSync(targetFileWebp);
    }
  }
};

const resizeAndOptimizeImagesBulk = async (sourceDirectory, targetDirectory, width) => {
  if (!fs.existsSync(targetDirectory)) {
    fs.mkdirSync(targetDirectory, { recursive: true });
  }

  const files = fs.readdirSync(sourceDirectory);
  for (const file of files) {
    if (file === ".picasa.ini") {
      continue;
    }

    const sourceFile = path.join(sourceDirectory, file);
    const targetFile = path.join(targetDirectory, file);

    // Process file only once.
    if (fs.existsSync(targetFile)) {
      continue;
    }

    const extension = path.extname(file).toLowerCase();
    switch (extension) {
      case ".gif": {
        processGif(sourceFile, targetFile);
        break;
      }
      case ".jpg": {
        await processJpg(sourceFile, targetFile, width);
        break;
      }
      case ".png": {
        await processPng(sourceFile, targetFile, width);
        break;
      }
      default: {
        break;
      }
    }
  }
};

const deleteUnusedImages = (targetDirectory, sourceDirectory) => {
  const targetFiles = fs.readdirSync(targetDirectory);
  const sourceFiles = fs.readdirSync(sourceDirectory);

  const sourceFileSet = new Set(sourceFiles.map((file) => path.basename(file, path.extname(file))));

  for (const targetFile of targetFiles) {
    const targetFileBaseName = path.basename(targetFile, path.extname(targetFile));

    // If the file doesn't exist in the source directory, delete it.
    if (!sourceFileSet.has(targetFileBaseName)) {
      const targetFilePath = path.join(targetDirectory, targetFile);
      fs.unlinkSync(targetFilePath);
      console.log(`Deleted: ${targetFilePath}`);
    }
  }
};

const main = async () => {
  await resizeAndOptimizeImagesBulk(cobertesDirectory, cobertesTargetDirectory, IMAGE_WIDTH);
  await resizeAndOptimizeImagesBulk(paremiesDirectory, paremiesTargetDirectory, IMAGE_WIDTH);

  // Delete images not present in the source directories.
  deleteUnusedImages(cobertesTargetDirectory, cobertesDirectory);
  deleteUnusedImages(paremiesTargetDirectory, paremiesDirectory);
};

try {
  await main();
} catch (error) {
  console.error(error);
  process.exit(1);
}
