import { expect, test } from "@playwright/test";
import data from "./data/data.json" with { type: "json" };

const DEFAULT_PAGER_SIZE = 10;
const MIN_DATE_LENGTH = "1 de maig de 1949".length;
const MAX_DATE_LENGTH = "31 de desembre de 2025".length;

test.describe("Homepage", () => {
  let extractedNumber = "";
  let updatedDate = "";
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ height: 720, width: 1280 });
    await page.goto("/");
  });

  test("has correct title", async ({ page }) => {
    await expect(page).toHaveTitle(data.homepageTitle);
  });

  test("has correct projecte link", async ({ page }) => {
    await page.getByRole("link", { name: "Projecte" }).click();
    await expect(page).toHaveURL(/projecte/);
  });

  test("pager displays 10 rows by default", async ({ page }) => {
    const rows = await page.locator("form[role=search] ol li").count();
    expect(rows).toBe(DEFAULT_PAGER_SIZE);
  });

  test(`first record is "${data.homepageFirstParemiotipus}"`, async ({
    page,
  }) => {
    const firstRecord = await page
      .locator("form[role=search] ol li")
      .first()
      .textContent();
    expect(firstRecord).toBe(data.homepageFirstParemiotipus);
  });

  test('block of text containing "Ajudeu-nos a millorar" is visible', async ({
    page,
  }) => {
    await expect(page.locator("text=Ajudeu-nos a millorar")).toBeVisible();
  });

  test('block of text containing "Un projecte de:" is visible', async ({
    page,
  }) => {
    await expect(page.locator("text=Un projecte de:")).toBeVisible();
  });

  test('block of text containing "Un projecte de:" is in the view port', async ({
    page,
  }) => {
    await expect(page.locator("text=Un projecte de:")).toBeInViewport();
  });

  test('block of text containing "Última actualització" is visible', async ({
    page,
  }) => {
    await expect(page.locator("text=Última actualització")).toBeVisible();
  });

  test('block of text containing "Última actualització" is not in the view port', async ({
    page,
  }) => {
    await expect(
      page.locator("text=Última actualització:"),
    ).not.toBeInViewport();
  });

  test("has last updated date set", async ({ page }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, updatedDate] = /actualització: (.+)/.exec(footerText);
    expect(updatedDate.trim().length).toBeGreaterThanOrEqual(MIN_DATE_LENGTH);
  });

  test("last updated date does not exceed maximum length", async ({ page }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, updatedDate] = /actualització: (.+)/.exec(footerText);
    expect(updatedDate.trim().length).toBeLessThanOrEqual(MAX_DATE_LENGTH);
  });

  test("last updated date has month written properly in catalan", async ({
    page,
  }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, updatedDate] = /actualització: (.+)/.exec(footerText);
    expect(updatedDate.trim()).toMatch(
      /(gener|febrer|març|abril|maig|juny|juliol|agost|setembre|octubre|novembre|desembre)/,
    );
  });

  test(`has ${data.paremiotipusNumber} paremiotipus`, async ({ page }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, extractedNumber] = /([\d.]+)\sparemiotipus/.exec(footerText);
    const nParemiotipus = Number(extractedNumber.replace(".", ""));

    expect(nParemiotipus).toBe(data.paremiotipusNumber);
  });

  test(`has ${data.fitxesNumber} fitxes`, async ({ page }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, extractedNumber] = /([\d.]+)\sfitxes/.exec(footerText);
    const nFitxes = Number(extractedNumber.replace(".", ""));

    expect(nFitxes).toBe(data.fitxesNumber);
  });

  test(`has ${data.fontsNumber} fonts`, async ({ page }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, extractedNumber] = /([\d.]+)\sfonts/.exec(footerText);
    const nFonts = Number(extractedNumber.replace(".", ""));

    expect(nFonts).toBe(data.fontsNumber);
  });

  test(`has ${data.informantsNumber} informants`, async ({ page }) => {
    const footerText = await page
      .locator("body > footer p")
      .first()
      .textContent();

    [, extractedNumber] = /([\d.]+)\sinformants/.exec(footerText);
    const nInformants = Number(extractedNumber.replace(".", ""));

    expect(nInformants).toBe(data.informantsNumber);
  });
});
