import { chromium } from "@playwright/test";

import path from "node:path";
import { writeFile } from "node:fs/promises";

import data from "../../data/playwright/data.json" with { type: "json" };

process.loadEnvFile();
if (!process.env.BASE_URL) {
  throw new Error("BASE_URL variable is not set.");
}

const JSON_INDENT_SPACES = 2;
const baseUrl = process.env.BASE_URL;

const extractNumber = (text, regex) => {
  const match = regex.exec(text);
  return Number(match[1].replace(".", ""));
};

const getCurrentYearMonth = () => {
  const date = new Date();
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  return `${year}${month}`;
};

const dataPath = path.join(import.meta.dirname, "../../data/playwright/data.json");
const historicPath = path.join(
  import.meta.dirname,
  `../../data/playwright/historic/data-${getCurrentYearMonth()}.json`,
);

const browser = await chromium.launch();
try {
  const page = await browser.newPage();
  await page.goto(baseUrl);

  data.homepageFirstParemiotipus = await page.locator("form[role=search] ol li").first().textContent();

  const footerText = await page.locator("body > footer p").first().textContent();
  data.paremiotipusNumber = extractNumber(footerText, /([\d.]+)\sparemiotipus/);
  data.fitxesNumber = extractNumber(footerText, /([\d.]+)\sfitxes/);
  data.fontsNumber = extractNumber(footerText, /([\d.]+)\sfonts/);
  data.informantsNumber = extractNumber(footerText, /([\d.]+)\sinformants/);

  await page.goto(`${baseUrl}/?mode=&cerca=fera&mostra=10`);
  let content = await page.locator("form[role=search] > p").first().textContent();
  data.searchFeraNumberOfResults = extractNumber(content, /trobat ([\d.]+) paremiotipus per a/);

  await page.goto(`${baseUrl}/?mode=&cerca=fera&variant&mostra=10`);
  content = await page.locator("form[role=search] > p").first().textContent();
  data.searchFeraWithVariantsNumberOfResults = extractNumber(content, /trobat ([\d.]+) paremiotipus per a/);

  await page.goto(`${baseUrl}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`);
  content = await page.locator(".description").textContent();
  data.paremiotipusQuiNoVulguiPolsNumberOfEntries = extractNumber(content, /([\d.]+)\srecurrències/);
  data.paremiotipusQuiNoVulguiPolsNumberOfVariants = extractNumber(content, /en ([\d.]+)\svariants/);

  await page.goto(
    `${baseUrl}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982`,
  );
  const obra = await page.locator("article").textContent();
  data.obraFolkloreCatalunyaNumberOfEntries = extractNumber(obra, /Aquesta obra té ([\d.]+) fitxes a la base de dades/);
  data.obraFolkloreCatalunyaNumberOfEntriesCollected = extractNumber(obra, /de les quals ([\d.]+) estan recollides/);
  const dataString = JSON.stringify(data, undefined, JSON_INDENT_SPACES) + "\n";
  await writeFile(dataPath, dataString, "utf8");
  await writeFile(historicPath, dataString, "utf8");
} finally {
  await browser.close();
}
