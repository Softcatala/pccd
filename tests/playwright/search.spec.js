import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

const formatNumber = (number) => new Intl.NumberFormat("ca-ES").format(number);
const extractNumber = (text, regex) => Number(regex.exec(text)[1].replace(".", ""));
const gotoSearch = (page, url) => page.goto(url, { waitUntil: "domcontentloaded" });
const resultsSummary = (page) => page.getByRole("search").locator("p").first();

test.describe("Search", () => {
  test("wildcard search returns all results", async ({ page }) => {
    await gotoSearch(page, "/");
    const footerText = await page.locator("body > footer p").first().textContent();

    const nParemiotipus = extractNumber(footerText, /([\d.]+)\sparemiotipus/);

    await gotoSearch(page, "/?mode=&cerca=*&variant=&mostra=10");
    await expect(resultsSummary(page)).toContainText(`${formatNumber(nParemiotipus)} paremiotipus`);
  });

  test(`"fera" returns ${data.searchFeraNumberOfResults} results`, async ({ page }) => {
    await gotoSearch(page, "/?mode=&cerca=fera&mostra=10");
    await expect(resultsSummary(page)).toContainText(`${formatNumber(data.searchFeraNumberOfResults)} paremiotipus`);
  });

  test(`"fera" with variants returns ${data.searchFeraWithVariantsNumberOfResults} results`, async ({ page }) => {
    await gotoSearch(page, "/?mode=&cerca=fera&variant&mostra=10");
    await expect(resultsSummary(page)).toContainText(
      `${formatNumber(data.searchFeraWithVariantsNumberOfResults)} paremiotipus`,
    );
  });

  test(`"Val més un boig conegut que un savi per conèixer" returns exactly 1 result`, async ({ page }) => {
    await gotoSearch(
      page,
      "/?mode=&cerca=Val+m%C3%A9s+un+boig+conegut+que+un+savi+per+con%C3%A8ixer&variant=&mostra=10",
    );
    await expect(resultsSummary(page)).toContainText("1 paremiotipus");
  });

  test(`"asdfasdf" returns no results`, async ({ page }) => {
    await gotoSearch(page, "/?mode=&cerca=asdfasdf&variant=&mostra=10");
    await expect(page.getByRole("search").getByText(/cap resultat coincident/)).toBeVisible();
  });
});
