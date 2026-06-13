import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

test.describe("Search", () => {
  let extractedNumber = "";
  test("wildcard search returns all results", async ({ page }) => {
    await page.goto("/");
    const footerText = await page.locator("body > footer p").first().textContent();

    let match = /(?<number>[\d.]+)\sparemiotipus/u.exec(footerText);
    extractedNumber = match.groups.number;
    const nParemiotipus = Number(extractedNumber.replace(".", ""));

    await page.goto("/?mode=&cerca=*&variant=&mostra=10");
    const resultats = await page.locator("form[role=search] > p").first().textContent();

    match = /trobat (?<number>[\d.]+) paremiotipus per a/u.exec(resultats);
    extractedNumber = match.groups.number;
    const nResultats = Number(extractedNumber.replace(".", ""));

    expect(nResultats).toBe(nParemiotipus);
  });

  test(`"fera" returns ${data.searchFeraNumberOfResults} results`, async ({ page }) => {
    await page.goto("/?mode=&cerca=fera&mostra=10");
    const resultats = await page.locator("form[role=search] > p").first().textContent();

    const match = /trobat (?<number>[\d.]+) paremiotipus per a/u.exec(resultats);
    extractedNumber = match.groups.number;
    const nResultats = Number(extractedNumber.replace(".", ""));

    expect(nResultats).toBe(data.searchFeraNumberOfResults);
  });

  test(`"fera" with variants returns ${data.searchFeraWithVariantsNumberOfResults} results`, async ({ page }) => {
    await page.goto("/?mode=&cerca=fera&variant&mostra=10");
    const resultats = await page.locator("form[role=search] > p").first().textContent();

    const match = /trobat (?<number>[\d.]+) paremiotipus per a/u.exec(resultats);
    extractedNumber = match.groups.number;
    const nResultats = Number(extractedNumber.replace(".", ""));

    expect(nResultats).toBe(data.searchFeraWithVariantsNumberOfResults);
  });

  test(`"Val més un boig conegut que un savi per conèixer" returns exactly 1 result`, async ({ page }) => {
    await page.goto("/?mode=&cerca=Val+m%C3%A9s+un+boig+conegut+que+un+savi+per+con%C3%A8ixer&variant=&mostra=10");
    const resultats = await page.locator("form[role=search] > p").first().textContent();

    const match = /trobat (?<number>[\d.]+) paremiotipus per a/u.exec(resultats);
    extractedNumber = match.groups.number;
    const nResultats = Number(extractedNumber.replace(".", ""));

    expect(nResultats).toBe(1);
  });

  test(`"asdfasdf" returns no results`, async ({ page }) => {
    await page.goto("/?mode=&cerca=asdfasdf&variant=&mostra=10");
    const resultats = await page.locator("form[role=search] > p").first().textContent();
    expect(resultats.match(/cap resultat coincident/u)).toBeTruthy();
  });
});
