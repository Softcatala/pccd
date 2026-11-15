#!/usr/bin/env node
/**
 * Checks that the compiled assets are up to date with the sources.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { execFileSync } from "node:child_process";
import { createHash } from "node:crypto";
import { readdir, readFile, stat } from "node:fs/promises";
import path from "node:path";

const rootDirectory = path.join(import.meta.dirname, "../..");
const generatedPaths = [
  "docroot/css",
  "docroot/js",
  "docroot/admin/js/chart.min.js",
  "docroot/img",
  "data/assets/assets-sizes.json",
];
const svgDirectory = "docroot/img";

/**
 * Returns whether a file is an SVG.
 */
const isSvg = (file) => path.extname(file).toLowerCase() === ".svg";

/**
 * Lists all files under a generated path, optionally filtered.
 */
const listFiles = async (target, filter = () => true) => {
  const absolutePath = path.join(rootDirectory, target);
  const stats = await stat(absolutePath);

  if (!stats.isDirectory()) {
    return filter(target) ? [target] : [];
  }

  const files = [];
  for (const entry of await readdir(absolutePath, { withFileTypes: true })) {
    const relativePath = path.join(target, entry.name);
    if (entry.isDirectory()) {
      files.push(...(await listFiles(relativePath, filter)));
    } else if (filter(relativePath)) {
      files.push(relativePath);
    }
  }

  return files;
};

/**
 * Returns a map of file paths to content hashes.
 */
const getFileHashes = async () => {
  const hashes = new Map();

  for (const target of generatedPaths) {
    const files = await listFiles(target, target === svgDirectory ? isSvg : undefined);
    for (const file of files) {
      const contents = await readFile(path.join(rootDirectory, file));
      hashes.set(file, createHash("sha256").update(contents).digest("hex"));
    }
  }

  return hashes;
};

const hashesBeforeBuild = await getFileHashes();
execFileSync(process.execPath, [path.join(rootDirectory, "scripts/build-assets.js")], { stdio: "inherit" });
const hashesAfterBuild = await getFileHashes();

const changedFiles = [...new Set([...hashesBeforeBuild.keys(), ...hashesAfterBuild.keys()])]
  .filter((file) => hashesBeforeBuild.get(file) !== hashesAfterBuild.get(file))
  .sort();

if (changedFiles.length > 0) {
  console.error("Generated assets were out of date and have been rebuilt. Commit the changes:");
  for (const file of changedFiles) {
    console.error(`- ${file}`);
  }
  process.exit(1);
}

console.log("Assets are up to date");
