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

const PNG_QUALITY = 80;
const IMAGE_WIDTH = 500;
const IGNORED_FILES = new Set([".picasa.ini"]);

// External dependencies for image optimization.
// TODO: consider removing these packages to reduce system dependencies (no npm equivalents currently available).
const GIF2WEBP = "gif2webp";
const GIFSICLE = "gifsicle";
const JPEGOPTIM = "jpegoptim";
const OXIPNG = "oxipng";

const paremiesDirectory = path.join(import.meta.dirname, "../../images/paremies");
const paremiesTargetDirectory = path.join(import.meta.dirname, "../../docroot/img/imatges");
const cobertesDirectory = path.join(import.meta.dirname, "../../images/cobertes");
const cobertesTargetDirectory = path.join(import.meta.dirname, "../../docroot/img/obres");

const isCommandAvailable = (command) => {
  const result = spawnSync(command, ["--version"], { stdio: "ignore" });
  return result.status === 0;
};

const OXIPNG_AVAILABLE = isCommandAvailable(OXIPNG);
if (!OXIPNG_AVAILABLE) {
  console.log("oxipng is not available, skipping it for PNG optimization.");
}

const resizeImage = async (sourceFile, targetFile, width) => {
  try {
    const metadata = await sharp(sourceFile).metadata();

    if (metadata.width > width) {
      await sharp(sourceFile).resize({ width }).toFile(targetFile);
    }
  } catch (error) {
    console.error(`Error while resizing ${sourceFile}: ${error.message}`);
  }

  if (!fs.existsSync(targetFile)) {
    // Use original file.
    fs.copyFileSync(sourceFile, targetFile);
  }
};

const createAvifImage = async (sourceFile, targetFile, width) => {
  const { dir, name } = path.parse(targetFile);
  const targetFileAvif = path.join(dir, `${name}.avif`);

  // Process file only once.
  if (fs.existsSync(targetFileAvif)) {
    return;
  }

  try {
    await sharp(sourceFile).resize({ width, withoutEnlargement: true }).toFormat("avif").toFile(targetFileAvif);
  } catch (error) {
    console.error(`Error while processing ${sourceFile} to AVIF: ${error.message}`);
  }
};

const processPng = async (sourceFile, targetFile, width) => {
  await resizeImage(sourceFile, targetFile, width);

  // Optimize with palette quantization (uses libimagequant if available).
  const temporaryFile = `${targetFile}.tmp.png`;
  try {
    await sharp(targetFile)
      .png({
        palette: true,
        quality: PNG_QUALITY,
        compressionLevel: 9,
      })
      .toFile(temporaryFile);

    fs.renameSync(temporaryFile, targetFile);
  } catch (error) {
    console.warn(`Warning: Palette optimization failed for ${targetFile}: ${error.message}`);
    console.warn("Continuing with oxipng optimization");
    if (fs.existsSync(temporaryFile)) {
      fs.unlinkSync(temporaryFile);
    }
  }

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
  const { dir, name } = path.parse(targetFile);
  const targetFileWebp = path.join(dir, `${name}.webp`);

  // Process file only once.
  if (fs.existsSync(targetFileWebp)) {
    return;
  }

  execFileSync(GIF2WEBP, ["-q", "100", "-mt", "-m", "6", "-o", targetFileWebp, targetFile]);
};

const resizeAndOptimizeImagesBulk = async (sourceDirectory, targetDirectory, width) => {
  if (!fs.existsSync(targetDirectory)) {
    fs.mkdirSync(targetDirectory, { recursive: true });
  }

  const files = fs.readdirSync(sourceDirectory);
  for (const file of files) {
    if (IGNORED_FILES.has(file)) {
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

  const sourceFileSet = new Set(sourceFiles.map((file) => path.parse(file).name));

  for (const targetFile of targetFiles) {
    const targetFileBaseName = path.parse(targetFile).name;

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
