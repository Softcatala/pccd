#!/usr/bin/env node
/**
 * Optimizes images by compressing and converting to web-friendly formats.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import fs from "node:fs";
import path from "node:path";
import sharp from "sharp";

const JPEG_QUALITY = 80;
const PNG_QUALITY = 80;
const WEBP_QUALITY = 80;
const IMAGE_WIDTH = 500;
const IGNORED_FILES = new Set([".picasa.ini"]);

const GIF_OPTIONS = {
  colours: 256,
  dither: 0,
  effort: 10,
  interFrameMaxError: 0,
  interPaletteMaxError: 0,
  keepDuplicateFrames: true,
  reuse: true,
};

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

const processGif = async (sourceFile, targetFile) => {
  const sourceSize = fs.statSync(sourceFile).size;
  const optimized = await sharp(sourceFile, { animated: true }).gif(GIF_OPTIONS).toBuffer();
  const [sourceMetadata, optimizedMetadata] = await Promise.all([
    sharp(sourceFile, { animated: true }).metadata(),
    sharp(optimized, { animated: true }).metadata(),
  ]);
  const sourcePages = sourceMetadata.pages ?? 1;
  const optimizedPages = optimizedMetadata.pages ?? 1;
  const output =
    sourcePages === optimizedPages && optimized.length < sourceSize ? optimized : fs.readFileSync(sourceFile);

  fs.writeFileSync(targetFile, output);

  // TODO: consider using AVIF instead, although animation and alpha channel
  // should be preserved, and looks like the tooling is not there yet.
  const { dir, name } = path.parse(targetFile);
  const targetFileWebp = path.join(dir, `${name}.webp`);

  await sharp(targetFile, { animated: true })
    .webp({ quality: WEBP_QUALITY, effort: 6, minSize: true })
    .toFile(targetFileWebp);
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
      await processGif(sourceFile, targetFile);
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

    // Keep files that still exist in the source directory.
    if (sourceFileSet.has(targetFileBaseName)) {
      continue;
    }

    const targetFilePath = path.join(targetDirectory, targetFile);
    fs.unlinkSync(targetFilePath);
    console.log(`Deleted: ${targetFilePath}`);
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
