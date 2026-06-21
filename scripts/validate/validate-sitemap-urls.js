#!/usr/bin/env node
/**
 * Runs multiple tests in all URLs in sitemap.txt.
 *
 * This script can take a few hours to complete.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { appendFile, readFile, unlink, writeFile } from "node:fs/promises";
import htmlhintPkg from "htmlhint";
const { HTMLHint } = htmlhintPkg;
import { HtmlValidate } from "html-validate";
import console from "node:console";
import { exec } from "node:child_process";
import path from "node:path";
import process from "node:process";
import { promisify } from "node:util";
import { randomUUID } from "node:crypto";

import htmlValidateConfig from "../../.htmlvalidate.json" with { type: "json" };
import htmlhintConfig from "../../.htmlhintrc.json" with { type: "json" };

const TIDY = "tidy";

const execAsync = promisify(exec);
const rootDirectory = path.join(import.meta.dirname, "../..");
const MAX_CONCURRENT = 10;
const EXIT_CODE_ERROR = 255;

const state = { httpErrorCount: 0 };

process.loadEnvFile();
if (!process.env.BASE_URL) {
  console.error("ERROR: BASE_URL variable is not set.");
  process.exit(EXIT_CODE_ERROR);
}

const baseUrl = process.env.BASE_URL;

/**
 * Converts a production URL to a local URL.
 */
const productionToLocalUrl = (url) => url.replace("https://pccd.dites.cat", baseUrl);

/**
 * Converts a local URL to a production URL.
 */
const localToProductionUrl = (url) => url.replace(baseUrl, "https://pccd.dites.cat");

/**
 * Gets the current date in Catalan format.
 */
const getCatalanDate = () => {
  const formatter = new Intl.DateTimeFormat("ca-ES", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
  return formatter.format(new Date()).replaceAll("’", "'").replaceAll(" del ", " de ");
};

/**
 * Validates URL using curl, htmlhint, html-validate, and Tidy HTML.
 */
const validateHtmlUrl = async (url, reportFiles) => {
  const { htmlErrorsFile, zeroFontsFile } = reportFiles;
  const pageId = randomUUID();
  const filename = path.join(rootDirectory, `tmp/page_${pageId}.html`);
  const localUrl = productionToLocalUrl(url);

  console.log(`Validating HTML of ${localUrl} (${filename})...`);

  // Download the HTML content.
  let response;
  try {
    response = await fetch(localUrl);
  } catch (error) {
    console.error(`ERROR: Failed to fetch ${localUrl}: ${error.message}`);
    await appendFile(htmlErrorsFile, `\nERROR: Failed to fetch ${localUrl}: ${error.message}\n`);
    state.httpErrorCount++;
    return;
  }

  if (!response.ok) {
    console.error(`ERROR: ${localUrl} returned status code HTTP ${response.status}.`);
    await appendFile(htmlErrorsFile, `\nERROR: ${localUrl} returned status code HTTP ${response.status}.\n`);
    state.httpErrorCount++;
    return;
  }

  const html = await response.text();
  await writeFile(filename, html);

  // Run htmlhint.
  const htmlhintResults = HTMLHint.verify(html, htmlhintConfig);
  if (htmlhintResults.length > 0) {
    const errors = htmlhintResults
      .map((result) => `line ${result.line}, col ${result.col}, ${result.message} (${result.rule.id})`)
      .join("\n");
    await appendFile(htmlErrorsFile, `\nError reported by htmlhint in ${localUrl}:\n${errors}\n`);
  }

  // Run html-validate.
  const htmlValidateInstance = new HtmlValidate(htmlValidateConfig);
  const htmlValidateResult = await htmlValidateInstance.validateString(html);
  if (!htmlValidateResult.valid) {
    const errors = htmlValidateResult.results[0].messages
      .map((message) => `${filename}:${message.line}:${message.column}: ${message.message} (${message.ruleId})`)
      .join("\n");
    await appendFile(htmlErrorsFile, `\nError reported by html-validate in ${localUrl}:\n${errors}\n`);
  }

  // Run tidy.
  try {
    await execAsync(`${TIDY} -config .tidyrc "${filename}"`, {
      cwd: rootDirectory,
      encoding: "utf8",
    });
  } catch (error) {
    if (error.stderr && error.stderr.trim()) {
      await appendFile(htmlErrorsFile, `\nError reported by tidy in ${localUrl}: ${error.stderr}\n`);
    }
  }

  // Check for "0 sources" entries
  if (html.includes('<div class="summary">')) {
    await appendFile(zeroFontsFile, `${localToProductionUrl(localUrl)}\n`);
  }

  // Clean up temporary file immediately to avoid file descriptor exhaustion.
  await unlink(filename);
};

/**
 * Process URLs in batches with concurrency limit.
 */
const processUrlsBatch = async (urls, options) => {
  const { concurrency, reportFiles } = options;
  for (let index = 0; index < urls.length; index += concurrency) {
    const batch = urls.slice(index, index + concurrency);
    await Promise.all(batch.map((url) => validateHtmlUrl(url, reportFiles)));
  }
};

// Main execution.
// Initialize report files.
const htmlErrorsFile = path.join(rootDirectory, "data/reports/test_html_errors.txt");
const zeroFontsFile = path.join(rootDirectory, "data/reports/test_zero_fonts.txt");

await writeFile(htmlErrorsFile, "");
const catalanDate = getCatalanDate();
await writeFile(zeroFontsFile, `Informe actualitzat el dia: ${catalanDate}\n`);

// Read sitemap URLs.
const sitemapContent = await readFile(path.join(rootDirectory, "docroot/sitemap.txt"), "utf8");
const urls = sitemapContent.trim().split("\n");

// Process all URLs.
await processUrlsBatch(urls, {
  concurrency: MAX_CONCURRENT,
  reportFiles: {
    htmlErrorsFile,
    zeroFontsFile,
  },
});

if (state.httpErrorCount > 0) {
  console.error(`Validation failed with ${state.httpErrorCount} HTTP errors.`);
} else {
  console.log("All URLs in the sitemap file returned HTTP 200.");
}

// Add final message if no zero fonts found.
const zeroFontsContent = await readFile(zeroFontsFile, "utf8");
if (zeroFontsContent.trim() === `Informe actualitzat el dia: ${catalanDate}`) {
  await appendFile(zeroFontsFile, "<em>No hi ha parèmies amb 0 fonts.</em>\n");
}

// Exit with error code if any errors were found.
const htmlErrorsContent = await readFile(htmlErrorsFile, "utf8");
if (state.httpErrorCount > 0 || htmlErrorsContent.trim() !== "") {
  process.exit(EXIT_CODE_ERROR);
}
