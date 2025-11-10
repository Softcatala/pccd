#!/usr/bin/env node
/**
 * Checks image extensions and formats.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { fileTypeFromFile } from "file-type";
import { exec } from "node:child_process";
import console from "node:console";
import { readdir, writeFile } from "node:fs/promises";
import path from "node:path";
import { promisify } from "node:util";

const execAsync = promisify(exec);
const rootDirectory = path.join(import.meta.dirname, "../..");
const imageDirectory = path.join(rootDirectory, "src/images");
const outputExtensionsFile = path.join(
  rootDirectory,
  "tmp/test_imatges_extensions.txt",
);
const outputFormatFile = path.join(
  rootDirectory,
  "tmp/test_imatges_format.txt",
);

const checkImageExtensions = async (category) => {
  const extensions = ["jpg", "png", "gif", "avif", "webp"];
  const results = [];

  for (const extension of extensions) {
    const files = await readdir(path.join(imageDirectory, category)).catch(
      () => [],
    );
    for (const file of files.filter((f) => f.endsWith(`.${extension}`))) {
      const filePath = path.join(imageDirectory, category, file);
      const fileType = await fileTypeFromFile(filePath);
      const mimeType = fileType?.mime;

      const expectedType = {
        jpg: "image/jpeg",
        png: "image/png",
        gif: "image/gif",
        avif: "image/avif",
        webp: "image/webp",
      }[extension];

      if (mimeType !== expectedType) {
        results.push(`${file} is '${mimeType || "unknown"}'`);
      }
    }
  }

  let content = `${category}\n=============================\n`;
  content += results.join("\n");
  content += "\n=============================\n\n";
  return content;
};

const checkImageIntegrity = async (category) => {
  const relativeCategoryPath = path.join("src/images", category);
  const results = [];

  try {
    const { stdout } = await execAsync(
      `jpeginfo -c "${relativeCategoryPath}"/*.jpg | grep -F 'ERROR' | grep -F -v 'OK'`,
      { cwd: rootDirectory },
    );
    if (stdout) {
      results.push(stdout);
    }
  } catch {
    // Ignore errors if no files match or jpeginfo fails
  }

  try {
    const { stdout } = await execAsync(
      `pngcheck "${relativeCategoryPath}"/*.png | grep -v 'OK:'`,
      { cwd: rootDirectory },
    );
    if (stdout) {
      results.push(stdout);
    }
  } catch {
    // Ignore errors
  }

  try {
    const { stderr } = await execAsync(
      `gifsicle --info "${relativeCategoryPath}"/*.gif`,
      { cwd: rootDirectory },
    );
    if (stderr) {
      results.push(stderr);
    }
  } catch (error) {
    // gifsicle exits with error code on warnings, so we check stderr
    if (error.stderr) {
      results.push(error.stderr);
    }
  }

  return results.join("");
};

// Main execution
console.log("Checking image extensions and formats...");
const categories = ["cobertes", "paremies"];
let extensionsContent = "";
let formatContent = "";

for (const category of categories) {
  extensionsContent += await checkImageExtensions(category);
  formatContent += await checkImageIntegrity(category);
}

await Promise.all([
  writeFile(outputExtensionsFile, extensionsContent),
  writeFile(outputFormatFile, formatContent),
]);

console.log("Image validation finished.");
