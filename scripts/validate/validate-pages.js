#!/usr/bin/env node
/**
 * Runs some tests and checks against a running website.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { HtmlValidate } from "html-validate";
import htmlhintPkg from "htmlhint";
const { HTMLHint } = htmlhintPkg;
import console from "node:console";
import { exec } from "node:child_process";
import path from "node:path";
import process from "node:process";
import { promisify } from "node:util";
import { writeFile } from "node:fs/promises";

import htmlValidateConfig from "../../.htmlvalidate.json" with { type: "json" };
import htmlhintConfig from "../../.htmlhintrc.json" with { type: "json" };

const TIDY = "tidy";

const execAsync = promisify(exec);
const rootDirectory = path.join(import.meta.dirname, "../..");

const EXIT_CODE_ERROR = 255;
const HTTP_STATUS_NOT_FOUND = 404;

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
 * Validates URL using curl, HTML Tidy, HTMLHint, and HTML-validate.
 */
const validateUrl = async (url, options = {}) => {
  const { skipTidy = false, skipHtmlhint = false, skipHtmlvalidate = false } = options;

  const outputFilename = path.join(rootDirectory, "tmp/page.html");

  console.log("");
  console.log(`Validating ${url}`);

  // Check HTTP status and download HTML.
  console.log("==============");
  console.log("fetch");
  console.log("==============");
  const response = await fetch(url, {
    headers: { "Accept-Encoding": "gzip, deflate, br" },
  });

  if (!response.ok) {
    console.error(`ERROR: Status code HTTP ${response.status}.`);
    process.exit(EXIT_CODE_ERROR);
  }

  console.log("No HTTP errors.");

  const html = await response.text();
  await writeFile(outputFilename, html);

  // HTML Tidy.
  if (!skipTidy) {
    console.log("==============");
    console.log("HTML Tidy");
    console.log("==============");
    try {
      await execAsync(`${TIDY} -config .tidyrc "${outputFilename}"`, {
        cwd: rootDirectory,
        encoding: "utf8",
      });
      console.log("No errors.");
    } catch (error) {
      if (error.stderr && error.stderr.trim()) {
        console.error(`ERROR reported in HTML Tidy: ${error.stderr}`);
        process.exit(EXIT_CODE_ERROR);
      }

      console.log("No errors.");
    }
  }

  // HTMLHint.
  if (!skipHtmlhint) {
    console.log("==============");
    console.log("htmlhint");
    console.log("==============");
    const htmlhintResults = HTMLHint.verify(html, htmlhintConfig);
    if (htmlhintResults.length > 0) {
      for (const result of htmlhintResults) {
        console.error(
          `${outputFilename}: line ${result.line}, col ${result.col}, ${result.message} (${result.rule.id})`,
        );
      }

      process.exit(EXIT_CODE_ERROR);
    }

    console.log("No htmlhint issues.");
  }

  // HTML-validate.
  if (!skipHtmlvalidate) {
    console.log("==============");
    console.log("html-validate");
    console.log("==============");
    const htmlValidateInstance = new HtmlValidate(htmlValidateConfig);
    const htmlValidateResult = await htmlValidateInstance.validateString(html);
    if (!htmlValidateResult.valid) {
      for (const message of htmlValidateResult.results[0].messages) {
        console.error(`${outputFilename}:${message.line}:${message.column}: ${message.message} (${message.ruleId})`);
      }

      process.exit(EXIT_CODE_ERROR);
    }

    console.log("No html-validate issues.");
  }
};

/**
 * Validates an HTTP 404 page.
 */
const validateUrl404 = async (url) => {
  const outputFilename = path.join(rootDirectory, "tmp/page.html");

  console.log("");
  console.log(`Validating 404 page ${url}...`);

  console.log("==============");
  console.log("curl");
  console.log("==============");

  const response = await fetch(url, {
    headers: { "Accept-Encoding": "gzip, deflate, br" },
  });

  if (response.status !== HTTP_STATUS_NOT_FOUND) {
    console.error(`Error: Status code HTTP ${response.status}.`);
    process.exit(EXIT_CODE_ERROR);
  }

  console.log("No HTTP errors.");

  const html = await response.text();
  await writeFile(outputFilename, html);
};

// Main execution.
// Check 404 pages.
await validateUrl404(`${process.env.BASE_URL}/p/A_Abrerasefserewrwe`);
await validateUrl404(`${process.env.BASE_URL}/asdfasdfsadfs`);

// Validate HTML.
await validateUrl(`${process.env.BASE_URL}/`);
await validateUrl(`${process.env.BASE_URL}/projecte`);
await validateUrl(`${process.env.BASE_URL}/top100`);
await validateUrl(`${process.env.BASE_URL}/llibres`);
await validateUrl(`${process.env.BASE_URL}/instruccions`);
await validateUrl(`${process.env.BASE_URL}/credits`);
await validateUrl(`${process.env.BASE_URL}/fonts`);
await validateUrl(`${process.env.BASE_URL}/p/A_Abrera%2C_donen_garses_per_perdius`);
await validateUrl(`${process.env.BASE_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`);
await validateUrl(`${process.env.BASE_URL}/obra/Pons_Lluch%2C_Josep_%281993%29%3A_Refranyer_menorqu%C3%AD`);
await validateUrl(`${process.env.BASE_URL}/?pagina=5147`);
await validateUrl(`${process.env.BASE_URL}/?mode=&cerca=ca%C3%A7a&variant=&mostra=10`);
await validateUrl(`${process.env.BASE_URL}/p/A_Adra%C3%A9n%2C_tanys`);
await validateUrl(`${process.env.BASE_URL}/p/A_Alaior%2C_mostren_la_panxa_per_un_guix%C3%B3_o_bot%C3%B3`);
await validateUrl(`${process.env.BASE_URL}/?mostra=-1`, {
  skipHtmlvalidate: true,
});

console.log("All validation tests finished OK :)");
