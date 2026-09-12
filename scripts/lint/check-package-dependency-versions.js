#!/usr/bin/env node
/**
 * Checks that package dependencies use exact versions.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import path from "node:path";
import { readFile } from "node:fs/promises";

const rootDirectory = path.join(import.meta.dirname, "../..");
const packageFile = process.argv[2] ?? path.join(rootDirectory, "package.json");
const dependencySections = ["dependencies", "devDependencies", "optionalDependencies", "peerDependencies"];
const rangePattern = /[\^~><*]/;

const packageJson = JSON.parse(await readFile(packageFile, "utf8"));
const invalidDependencies = [];

for (const section of dependencySections) {
  const dependencies = Object.entries(packageJson[section] ?? {});
  for (const [name, version] of dependencies) {
    if (typeof version !== "string" || rangePattern.test(version)) {
      invalidDependencies.push(`${section}.${name}: ${version}`);
    }
  }
}

if (invalidDependencies.length > 0) {
  console.error("Package dependencies must use exact versions:");
  for (const dependency of invalidDependencies) {
    console.error(`- ${dependency}`);
  }
  process.exit(1);
}

console.log("All package dependencies use exact versions");
