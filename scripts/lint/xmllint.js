#!/usr/bin/env node
/**
 * Validates XML files using xmllint-wasm (libxml2 compiled to WebAssembly).
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { readFile, readdir } from "node:fs/promises";
import path from "node:path";
import process from "node:process";

import { validateXML } from "xmllint-wasm";

const globXml = async (directory) => {
  const entries = await readdir(directory).catch(() => []);
  return entries.filter((f) => f.endsWith(".xml")).map((f) => (directory === "." ? f : path.join(directory, f)));
};

const directories = [".", "docroot"];
const results = await Promise.all(directories.map((directory) => globXml(directory)));
const files = results.flat();

if (files.length === 0) {
  process.exit(0);
}

const xmlFiles = await Promise.all(
  files.map(async (file) => ({
    fileName: path.basename(file),
    contents: await readFile(file, "utf8"),
  })),
);

const result = await validateXML({ xml: xmlFiles });

if (!result.valid) {
  for (const error of result.errors) {
    process.stderr.write(error.rawMessage + "\n");
  }
  process.exit(1);
}
