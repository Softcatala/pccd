#!/usr/bin/env node
/**
 * Optimizes images by compressing and converting to web-friendly formats.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { execFileSync } from "node:child_process";
import fs from "node:fs";
import path from "node:path";
import sharp from "sharp";

const JPEG_QUALITY = 80;
const PNG_QUALITY = 80;
const IMAGE_WIDTH = 500;
const IGNORED_FILES = new Set([".picasa.ini"]);

// External dependencies for image optimization.
// TODO: consider removing these packages to reduce system dependencies (no npm equivalents currently available).
const GIF2WEBP = "gif2webp";
const GIFSICLE = "gifsicle";

const paremiesDirectory = path.join(import.meta.dirname, "../../images/paremies");
const paremiesTargetDirectory = path.join(import.meta.dirname, "../../docroot/img/imatges");
const cobertesDirectory = path.join(import.meta.dirname, "../../images/cobertes");
const cobertesTargetDirectory = path.join(import.meta.dirname, "../../docroot/img/obres");

const createAvifImage = async (sourceFile, targetFile, width) => {
  const { dir, name } = path.parse(targetFile);
  const targetFileAvif = path.join(dir, `${name}.avif`);

  try {
    await sharp(sourceFile).resize({ width, withoutEnlargement: true }).toFormat("avif").toFile(targetFileAvif);
  } catch (error) {
    console.error(`Error while processing ${sourceFile} to AVIF: ${error.message}`);
  }
};

const processPng = async (sourceFile, targetFile, width) => {
  try {
    await sharp(sourceFile)
      .resize({ width, withoutEnlargement: true })
      .png({
        palette: true,
        quality: PNG_QUALITY,
        compressionLevel: 9,
        effort: 10,
      })
      .toFile(targetFile);
  } catch (error) {
    console.error(`Error processing PNG ${sourceFile}: ${error.message}`);
    fs.copyFileSync(sourceFile, targetFile);
  }

  await createAvifImage(sourceFile, targetFile, width);
};

const processJpg = async (sourceFile, targetFile, width) => {
  try {
    await sharp(sourceFile)
      .resize({ width, withoutEnlargement: true })
      .jpeg({
        quality: JPEG_QUALITY,
        mozjpeg: true,
      })
      .toFile(targetFile);
  } catch (error) {
    console.error(`Error processing JPEG ${sourceFile}: ${error.message}`);
    fs.copyFileSync(sourceFile, targetFile);
  }

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

  execFileSync(GIF2WEBP, ["-q", "100", "-mt", "-m", "6", "-o", targetFileWebp, targetFile]);
};

const processFile = async ({ file, sourceDirectory, targetDirectory, width }) => {
  const sourceFile = path.join(sourceDirectory, file);
  const targetFile = path.join(targetDirectory, file);

  // Process file only once.
  if (fs.existsSync(targetFile)) {
    return;
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
  }
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

    await processFile({ file, sourceDirectory, targetDirectory, width });
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
