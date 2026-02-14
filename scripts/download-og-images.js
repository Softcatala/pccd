#!/usr/bin/env node
/**
 * Downloads all OG images.
 *
 * This script is mostly used for developing and testing OG image rendering.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { mkdir, readFile, rm, writeFile } from "node:fs/promises";
import { Buffer } from "node:buffer";
import console from "node:console";
import path from "node:path";
import process from "node:process";
import { randomUUID } from "node:crypto";

const rootDirectory = path.join(import.meta.dirname, "..");
const MAX_CONCURRENT = 10;
const EXIT_CODE_ERROR = 255;

// Load .env file if it exists.
try {
  process.loadEnvFile();
} catch {
  // .env file doesn't exist or couldn't be read
}

if (!process.env.BASE_URL) {
  console.error("ERROR: BASE_URL variable is not set.");
  process.exit(EXIT_CODE_ERROR);
}

/**
 * Converts a production URL to a local URL.
 */
const productionToLocalUrl = (url, baseUrl) => url.replace("https://pccd.dites.cat", baseUrl);

/**
 * Downloads OG image of a URL.
 */
const downloadOgImage = async (url, baseUrl) => {
  const pageId = randomUUID();
  const filename = path.join(rootDirectory, `tmp/page_${pageId}.html`);
  const localUrl = productionToLocalUrl(url, baseUrl);

  console.log(`Trying to fetch OG image from ${localUrl} (${filename})...`);

  // Download the HTML content of the page.
  const response = await fetch(localUrl);
  if (!response.ok) {
    console.error(`Failed to download the page. HTTP status code: ${response.status}`);
    throw new Error(`HTTP ${response.status}`);
  }

  const html = await response.text();
  await writeFile(filename, html);

  // Extract the og:image URL from the downloaded HTML.
  const match = /property="og:image" content="(?<imageUrl>[^"]*)"/u.exec(html);
  if (!match) {
    console.error(`Failed to find og:image in ${localUrl}.`);
    if (url.includes("/p/")) {
      throw new Error("Missing og:image");
    }
    return;
  }

  const imageUrl = match.groups.imageUrl;
  const localImageUrl = productionToLocalUrl(imageUrl, baseUrl);

  // Download the image.
  const imageFilename = path.join(rootDirectory, `tmp/og/${pageId}.png`);
  const imageResponse = await fetch(localImageUrl);

  if (!imageResponse.ok) {
    console.error("Failed to download the image.");
    throw new Error(`HTTP ${imageResponse.status}`);
  }

  const imageBuffer = await imageResponse.arrayBuffer();
  await writeFile(imageFilename, Buffer.from(imageBuffer));

  console.log(`Image downloaded successfully: ${imageFilename}`);
};

/**
 * Process URLs in batches with concurrency limit.
 */
const processUrlsBatch = async (urls, baseUrl, concurrency) => {
  const results = [];

  for (let index = 0; index < urls.length; index += concurrency) {
    const batch = urls.slice(index, index + concurrency);
    const promises = batch.map(async (url) => {
      try {
        await downloadOgImage(url, baseUrl);
        return { url, success: true };
      } catch (error) {
        return { url, success: false, error: error.message };
      }
    });
    const batchResults = await Promise.all(promises);
    results.push(...batchResults);
  }

  return results;
};

// Main execution.
// Clean and create tmp/og directory.
await rm(path.join(rootDirectory, "tmp/og"), {
  recursive: true,
  force: true,
});
await mkdir(path.join(rootDirectory, "tmp/og"), { recursive: true });

// Read sitemap URLs.
const sitemapContent = await readFile(path.join(rootDirectory, "docroot/sitemap.txt"), "utf8");
const urls = sitemapContent.trim().split("\n");

// Process all URLs.
const results = await processUrlsBatch(urls, process.env.BASE_URL, MAX_CONCURRENT);

// Check for failures.
const failures = results.filter((result) => !result.success);
if (failures.length > 0) {
  console.error(`\n${failures.length} URL(s) failed:`);
  for (const failure of failures) {
    console.error(`  - ${failure.url}: ${failure.error}`);
  }
  process.exit(1);
}
