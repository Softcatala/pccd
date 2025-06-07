const { chromium } = require("@playwright/test");
const dotenv = require("dotenv");
const fs = require("node:fs");

dotenv.config();

const extractNumber = (text, regex) => {
    const [, extractedNumber] = regex.exec(text);
    return Number(extractedNumber.replace(".", ""));
};

const getCurrentYearMonth = () => {
    const date = new Date();
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    return `${year}${month < 10 ? "0" + month : month}`;
};

const dataPath = `${__dirname}/../tests/playwright/data/data.json`;
const historicPath = `${__dirname}/../tests/playwright/data/historic/data-${getCurrentYearMonth()}.json`;
const data = JSON.parse(fs.readFileSync(dataPath, "utf8"));

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();

    await page.goto(process.env.BASE_URL);

    data.homepageFirstParemiotipus = await page.locator("form[role=search] ol li").first().textContent();

    const footerText = await page.locator("body > footer p").first().textContent();
    data.paremiotipusNumber = extractNumber(footerText, /([\d.]+)\sparemiotipus/);
    data.fitxesNumber = extractNumber(footerText, /([\d.]+)\sfitxes/);
    data.fontsNumber = extractNumber(footerText, /([\d.]+)\sfonts/);
    data.informantsNumber = extractNumber(footerText, /([\d.]+)\sinformants/);

    await page.goto(`${process.env.BASE_URL}/?mode=&cerca=fera&mostra=10`);
    let content = await page.locator("form[role=search] > p").first().textContent();
    data.searchFeraNumberOfResults = extractNumber(content, /trobat ([\d.]+) paremiotipus per a/);

    await page.goto(`${process.env.BASE_URL}/?mode=&cerca=fera&variant&mostra=10`);
    content = await page.locator("form[role=search] > p").first().textContent();
    data.searchFeraWithVariantsNumberOfResults = extractNumber(content, /trobat ([\d.]+) paremiotipus per a/);

    await page.goto(`${process.env.BASE_URL}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era`);
    content = await page.locator(".description").textContent();
    data.paremiotipusQuiNoVulguiPolsNumberOfEntries = extractNumber(content, /([\d.]+)\srecurrències/);
    data.paremiotipusQuiNoVulguiPolsNumberOfVariants = extractNumber(content, /en ([\d.]+)\svariants/);

    await page.goto(
        `${process.env.BASE_URL}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982`,
    );
    const obra = await page.locator("article").textContent();
    data.obraFolkloreCatalunyaNumberOfEntries = extractNumber(
        obra,
        /Aquesta obra té ([\d.]+) fitxes a la base de dades/,
    );
    data.obraFolkloreCatalunyaNumberOfEntriesCollected = extractNumber(obra, /de les quals ([\d.]+) estan recollides/);
    const dataString = JSON.stringify(data, undefined, 2) + "\n";
    fs.writeFileSync(dataPath, dataString, "utf8");
    fs.writeFileSync(historicPath, dataString, "utf8");

    await browser.close();
})();
