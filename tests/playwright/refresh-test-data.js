import process, { loadEnvFile } from "node:process";
import { chromium } from "@playwright/test";
import { writeFile } from "node:fs/promises";

import data from "../../data/playwright/data.json" with { type: "json" };

const SINGLE_DIGIT_THRESHOLD = 10;
const JSON_INDENT_SPACES = 2;

loadEnvFile();

const extractNumber = (text, regex) => {
  const match = regex.exec(text);
  return Number(match.groups.number.replace(".", ""));
};

const getCurrentYearMonth = () => {
  const date = new Date();
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  return `${year}${month < SINGLE_DIGIT_THRESHOLD ? "0" + month : month}`;
};

const dataPath = `${import.meta.dirname}/../../data/playwright/data.json`;
const historicPath = `${import.meta.dirname}/../../data/playwright/historic/data-${getCurrentYearMonth()}.json`;

const browser = await chromium.launch();
const page = await browser.newPage();

await page.goto(process.env.BASE_URL);

data.homepageFirstParemiotipus = await page.locator("form[role=search] ol li").first().textContent();

const footerText = await page.locator("body > footer p").first().textContent();
data.paremiotipusNumber = extractNumber(footerText, /(?<number>[\d.]+)\sparemiotipus/u);
data.fitxesNumber = extractNumber(footerText, /(?<number>[\d.]+)\sfitxes/u);
data.fontsNumber = extractNumber(footerText, /(?<number>[\d.]+)\sfonts/u);
data.informantsNumber = extractNumber(footerText, /(?<number>[\d.]+)\sinformants/u);

await page.goto(`${process.env.BASE_URL}/?mode=&cerca=fera&mostra=10`);
let content = await page.locator("form[role=search] > p").first().textContent();
data.searchFeraNumberOfResults = extractNumber(content, /trobat (?<number>[\d.]+) paremiotipus per a/u);

await page.goto(`${process.env.BASE_URL}/?mode=&cerca=fera&variant&mostra=10`);
content = await page.locator("form[role=search] > p").first().textContent();
data.searchFeraWithVariantsNumberOfResults = extractNumber(content, /trobat (?<number>[\d.]+) paremiotipus per a/u);

await page.goto(`${process.env.BASE_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`);
content = await page.locator(".description").textContent();
data.paremiotipusQuiNoVulguiPolsNumberOfEntries = extractNumber(content, /(?<number>[\d.]+)\srecurrències/u);
data.paremiotipusQuiNoVulguiPolsNumberOfVariants = extractNumber(content, /en (?<number>[\d.]+)\svariants/u);

await page.goto(
  `${process.env.BASE_URL}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982`,
);
const obra = await page.locator("article").textContent();
data.obraFolkloreCatalunyaNumberOfEntries = extractNumber(
  obra,
  /Aquesta obra té (?<number>[\d.]+) fitxes a la base de dades/u,
);
data.obraFolkloreCatalunyaNumberOfEntriesCollected = extractNumber(
  obra,
  /de les quals (?<number>[\d.]+) estan recollides/u,
);
const dataString = JSON.stringify(data, null, JSON_INDENT_SPACES) + "\n";
await writeFile(dataPath, dataString, "utf8");
await writeFile(historicPath, dataString, "utf8");

await browser.close();
