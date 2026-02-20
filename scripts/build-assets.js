#!/usr/bin/env node
/**
 * Minifies the assets.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

/* eslint-disable no-bitwise -- lightningcss requires bitshift format */

import * as esbuild from "esbuild";
import { Features, bundle } from "lightningcss";
import { copyFile, readdir, stat, writeFile } from "node:fs/promises";
import console from "node:console";
import path from "node:path";

const rootDirectory = path.join(import.meta.dirname, "..");
const JSON_INDENT = 2;

const JS_TARGET = "es2020";

// Browser version targets for CSS.
const CHROME_VERSION = 90;
const FIREFOX_VERSION = 88;
const SAFARI_VERSION = 14;
const VERSION_SHIFT = 16;

const CSS_TARGETS = {
  chrome: CHROME_VERSION << VERSION_SHIFT,
  firefox: FIREFOX_VERSION << VERSION_SHIFT,
  safari: SAFARI_VERSION << VERSION_SHIFT,
};

/**
 * Bundles and minifies JavaScript assets with esbuild.
 */
const buildJavaScript = async () => {
  console.log("Bundling and minifying JavaScript assets with esbuild...");

  const jsFiles = ["src/js/app.js"];
  const pagesDirectory = "src/js/pages";
  const pageFiles = await readdir(path.join(rootDirectory, pagesDirectory));

  for (const file of pageFiles) {
    if (file.endsWith(".js")) {
      jsFiles.push(path.join(pagesDirectory, file));
    }
  }

  await esbuild.build({
    entryPoints: jsFiles,
    bundle: true,
    minify: true,
    target: JS_TARGET,
    outdir: "docroot/js",
    outbase: "src/js",
    outExtension: { ".js": ".min.js" },
  });
};

/**
 * Bundles and minifies CSS assets with lightningcss.
 */
const buildCSS = async () => {
  console.log("Bundling and minifying CSS assets with lightningcss...");

  const cssFiles = ["src/css/base.css"];
  const pagesDirectory = "src/css/pages";
  const pageFiles = await readdir(path.join(rootDirectory, pagesDirectory));

  for (const file of pageFiles) {
    if (file.endsWith(".css")) {
      cssFiles.push(path.join(pagesDirectory, file));
    }
  }

  for (const file of cssFiles) {
    const { code } = bundle({
      filename: path.join(rootDirectory, file),
      minify: true,
      targets: CSS_TARGETS,
      exclude: Features.FontFamilySystemUi,
    });

    const outputPath = file.replace("src/css/", "docroot/css/").replace(".css", ".min.css");

    const fullOutputPath = path.join(rootDirectory, outputPath);
    await writeFile(fullOutputPath, code);
  }
};

/**
 * Copies Chart.js bundle.
 */
const copyChartJS = async () => {
  console.log("Bundling Chart.js...");

  await copyFile(
    path.join(rootDirectory, "node_modules/chart.js/dist/chart.umd.js"),
    path.join(rootDirectory, "docroot/admin/js/chart.min.js"),
  );
};

/**
 * Gets file sizes recursively from a directory.
 */
const getFileSizes = async (directory) => {
  const fileSizes = [];
  const files = await readdir(directory, { recursive: true });

  for (const file of files) {
    const filePath = path.join(directory, file);
    try {
      const stats = await stat(filePath);
      if (stats.isFile()) {
        fileSizes.push({
          path: file,
          size: stats.size,
        });
      }
    } catch {
      // Skip files that can't be read.
    }
  }

  return fileSizes;
};

/**
 * Exports asset sizes to a JSON report.
 */
const exportAssetSizes = async () => {
  console.log("Exporting asset sizes...");

  const jsDirectory = path.join(rootDirectory, "docroot/js");
  const cssDirectory = path.join(rootDirectory, "docroot/css");

  const jsSizes = await getFileSizes(jsDirectory);
  const cssSizes = await getFileSizes(cssDirectory);

  const report = {
    js: jsSizes,
    css: cssSizes,
  };

  const outputFile = path.join(rootDirectory, "data/assets/assets-sizes.json");
  await writeFile(outputFile, JSON.stringify(report, null, JSON_INDENT) + "\n");

  console.log(`Asset sizes report written to ${outputFile}`);
};

// Run all build tasks in parallel.
await Promise.all([buildJavaScript(), buildCSS(), copyChartJS()]);

// Export asset sizes after build completes.
await exportAssetSizes();
