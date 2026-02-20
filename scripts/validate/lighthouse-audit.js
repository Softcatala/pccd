#!/usr/bin/env node
/**
 * Audits a running website with Google Lighthouse.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

// eslint-disable-next-line n/no-extraneous-import -- chrome-launcher is a transitive dependency from lighthouse
import * as chromeLauncher from "chrome-launcher";
import console from "node:console";
import lighthouse from "lighthouse";
import process from "node:process";

const EXIT_CODE_ERROR = 255;
const PERFECT_SCORE = 100;
const PERFORMANCE_SCORE_ACCEPTABLE = 90;
const SEO_SCORE_MISSING_META = 92;
const ACCESSIBILITY_SCORE_MINOR = 99;

const URLS = [
  "/",
  "/p/A_Agramunt_comerciants_i_a_T%C3%A0rrega_comediants",
  "/p/A_Abrera%2C_garses",
  "/p/Cel_rogent%2C_pluja_o_vent",
  "/p/Tal_far%C3%A0s%2C_tal_trobar%C3%A0s",
  "/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Can%C3%A7oner%2C_3a_ed._1982",
  "/obra/Carol%2C_Roser_%281978-2021%29%3A_Frases_fetes_dels_Pa%C3%AFsos_Catalans",
  "/fonts",
];

const DEVICES = ["desktop", "mobile", "tablet", "small-mobile", "experimental"];

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

const getLighthouseConfig = (device) => {
  const config = {
    extends: "lighthouse:default",
    settings: {
      onlyCategories: ["accessibility", "best-practices", "performance", "seo"],
      formFactor: device === "desktop" ? "desktop" : "mobile",
      screenEmulation: {
        mobile: device !== "desktop",
        disabled: device === "desktop",
      },
    },
  };

  switch (device) {
    case "mobile": {
      config.settings.screenEmulation.width = 360;
      config.settings.screenEmulation.height = 640;
      config.settings.screenEmulation.deviceScaleFactor = 2;

      break;
    }
    case "small-mobile": {
      config.settings.screenEmulation.width = 320;
      config.settings.screenEmulation.height = 568;
      config.settings.screenEmulation.deviceScaleFactor = 2;

      break;
    }
    case "tablet": {
      config.settings.screenEmulation.width = 768;
      config.settings.screenEmulation.height = 1024;
      config.settings.screenEmulation.deviceScaleFactor = 2;

      break;
    }
    default: {
      break;
    }
  }

  return config;
};

const auditUrl = async (url, baseUrl, device = "desktop") => {
  console.log(`Running Lighthouse audit for ${url} on ${device}...`);

  const chrome = await chromeLauncher.launch({
    chromePath: process.env.CHROME_PATH,
    chromeFlags: ["--headless", "--no-sandbox", "--disable-dev-shm-usage"],
  });

  try {
    const options = {
      port: chrome.port,
      output: "json",
    };
    const runnerResult = await lighthouse(url, options, getLighthouseConfig(device));
    // lhr is the Lighthouse Result object.
    const { lhr } = runnerResult;

    let allScoresPerfect = true;
    for (const category of Object.values(lhr.categories)) {
      const score = Math.floor(category.score * PERFECT_SCORE);

      if (score !== PERFECT_SCORE) {
        // Check for acceptable, non-perfect scores.
        // Performance can fluctuate.
        if (
          (category.id === "performance" && url === `${baseUrl}/fonts`) ||
          (category.id === "performance" && score > PERFORMANCE_SCORE_ACCEPTABLE) ||
          (category.id === "seo" &&
            score === SEO_SCORE_MISSING_META &&
            (url.startsWith(`${baseUrl}/p/`) || url.startsWith(`${baseUrl}/obra/`))) ||
          (device === "small-mobile" &&
            category.id === "accessibility" &&
            score === ACCESSIBILITY_SCORE_MINOR &&
            url === `${baseUrl}/`)
        ) {
          // Ignore acceptable score.
          continue;
        }

        allScoresPerfect = false;
        console.error(`[${device.toUpperCase()}] ${url}\n   Category '${category.title}' failed with score ${score}`);
      }
    }

    if (allScoresPerfect) {
      console.log(`All essential audits score 100% for ${url} on ${device}.`);
    } else {
      console.error(`   Re-run with: npx lighthouse "${url}" --view --chrome-flags="--headless"`);
      process.exitCode = EXIT_CODE_ERROR;
    }
  } finally {
    await chrome.kill();
  }
};

try {
  for (const url of URLS) {
    for (const device of DEVICES) {
      await auditUrl(`${process.env.BASE_URL}${url}`, process.env.BASE_URL, device);
    }
  }

  if (!process.exitCode) {
    console.log("\nAll audits finished OK :)");
  }
} catch (error) {
  console.error("An unexpected error occurred during Lighthouse audit:", error);
  process.exit(EXIT_CODE_ERROR);
}
