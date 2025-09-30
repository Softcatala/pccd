#!/usr/bin/env node
/**
 * Minifies the assets.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

/* eslint-disable no-magic-numbers, no-bitwise */

import console from "node:console";
import { copyFile, mkdir, readdir, writeFile } from "node:fs/promises";
import path from "node:path";
import * as esbuild from "esbuild";
import { bundle, Features } from "lightningcss";

const rootDirectory = path.join(import.meta.dirname, "..");

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
    target: "es2020",
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
      targets: {
        chrome: 90 << 16,
        firefox: 88 << 16,
        safari: 14 << 16,
      },
      exclude: Features.FontFamilySystemUi,
    });

    const outputPath = file
      .replace("src/css/", "docroot/css/")
      .replace(".css", ".min.css");

    const fullOutputPath = path.join(rootDirectory, outputPath);
    await mkdir(path.dirname(fullOutputPath), { recursive: true });
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

// Run all build tasks.
await buildJavaScript();
await buildCSS();
await copyChartJS();
