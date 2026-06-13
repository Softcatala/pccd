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
import { exec } from "node:child_process";
import path from "node:path";
import { promisify } from "node:util";
import sharp from "sharp";

// TODO: consider removing these packages to reduce the number of system dependencies.
const GREP = "grep";
const GIFSICLE = "gifsicle";
const JPEGINFO = "jpeginfo";
const PNGCHECK = "pngcheck";

const IGNORED_FILES = new Set([".picasa.ini"]);
const EXTENSION_TO_FORMAT = {
  avif: "avif",
  gif: "gif",
  jpg: "jpeg",
  png: "png",
  webp: "webp",
};

const execAsync = promisify(exec);
const rootDirectory = path.join(import.meta.dirname, "../..");
const imageDirectory = path.join(rootDirectory, "images");
const outputExtensionsFile = path.join(rootDirectory, "data/reports/test_imatges_extensions.txt");
const outputFormatFile = path.join(rootDirectory, "data/reports/test_imatges_format.txt");

const checkImageExtensions = async (category) => {
  const results = [];
  const categoryPath = path.join(imageDirectory, category);
  const files = await readdir(categoryPath).catch(() => []);

  for (const file of files) {
    if (IGNORED_FILES.has(file)) {
      continue;
    }

    const extension = path.extname(file).slice(1);
    const expectedFormat = EXTENSION_TO_FORMAT[extension];
    const filePath = path.join(categoryPath, file);

    try {
      const { format } = await sharp(filePath).metadata();
      if (format !== expectedFormat) {
        results.push(`${file} has format '${format || "unknown"}'`);
      }
    } catch (error) {
      results.push(`${file}: ${error.message}`);
    }
  }

  let content = `${category}\n=============================\n`;
  content += results.join("\n");
  content += "\n=============================\n\n";

  return content;
};

const checkImageIntegrity = async (category) => {
  const relativePath = path.join("images", category);
  const results = [];

  const toolConfigs = [
    { cmd: `${JPEGINFO} -c "${relativePath}"/*.jpg | ${GREP} -w ERROR`, output: "stdout" },
    { cmd: `${PNGCHECK} -q "${relativePath}"/*.png`, output: "stdout" },
    { cmd: `${GIFSICLE} --info "${relativePath}"/*.gif`, output: "stderr", checkErrorStderr: true },
  ];

  for (const { cmd, output, checkErrorStderr } of toolConfigs) {
    try {
      const result = await execAsync(cmd, { cwd: rootDirectory });
      if (result[output]) {
        results.push(result[output]);
      }
    } catch (error) {
      if (checkErrorStderr && error.stderr) {
        results.push(error.stderr);
      }
    }
  }

  return results.join("");
};

console.log("Checking image extensions and formats...");
const categories = ["cobertes", "paremies"];
let extensionsContent = "";
let formatContent = "";
for (const category of categories) {
  extensionsContent += await checkImageExtensions(category);
  formatContent += await checkImageIntegrity(category);
}

await writeFile(outputExtensionsFile, extensionsContent);
await writeFile(outputFormatFile, formatContent);

console.log("Image validation finished.");
