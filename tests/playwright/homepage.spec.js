import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

const DEFAULT_PAGER_SIZE = 10;
const MIN_DATE_LENGTH = "1 de maig de 1949".length;
const MAX_DATE_LENGTH = "31 de desembre de 2025".length;
const footer = (page) => page.locator("body > footer p").first();

const formatNumber = (number) => new Intl.NumberFormat("ca-ES").format(number);

test.describe("Homepage", () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ height: 720, width: 1280 });
    await page.goto("/");
  });

  test("has correct title", async ({ page }) => {
    await expect(page).toHaveTitle(data.homepageTitle);
  });

  test("the server sets Brotli or Zstd compression", async ({ page }) => {
    const response = await page.request.get("/", {
      headers: { "Accept-Encoding": "gzip, deflate, br, zstd" },
    });
    expect(response.headers()["content-encoding"]).toMatch(/^(?:br|zstd)$/);
  });

  test("has correct projecte link", async ({ page }) => {
    await page.getByRole("link", { name: "Projecte" }).click();
    await expect(page).toHaveURL(/projecte/);
  });

  test("pager displays 10 rows by default", async ({ page }) => {
    await expect(page.locator("form[role=search] ol li")).toHaveCount(DEFAULT_PAGER_SIZE);
  });

  test(`first record is "${data.homepageFirstParemiotipus}"`, async ({ page }) => {
    await expect(page.locator("form[role=search] ol li").first()).toHaveText(data.homepageFirstParemiotipus);
  });

  test('block of text containing "Ajudeu-nos a millorar" is visible', async ({ page }) => {
    await expect(page.getByText("Ajudeu-nos a millorar", { exact: false })).toBeVisible();
  });

  test('block of text containing "Un projecte de:" is visible', async ({ page }) => {
    await expect(page.getByText("Un projecte de:", { exact: true })).toBeVisible();
  });

  test('block of text containing "Un projecte de:" is in the view port', async ({ page }) => {
    await expect(page.getByText("Un projecte de:", { exact: true })).toBeInViewport();
  });

  test('block of text containing "Última actualització" is visible', async ({ page }) => {
    await expect(page.getByText("Última actualització", { exact: false })).toBeVisible();
  });

  test('block of text containing "Última actualització" is not in the view port', async ({ page }) => {
    await expect(page.getByText("Última actualització:", { exact: false })).not.toBeInViewport();
  });

  test("has last updated date set", async ({ page }) => {
    const footerText = await footer(page).textContent();
    const updatedDate = /actualització: (.+)/.exec(footerText)[1].trim();
    expect(updatedDate.length).toBeGreaterThanOrEqual(MIN_DATE_LENGTH);
  });

  test("last updated date does not exceed maximum length", async ({ page }) => {
    const footerText = await footer(page).textContent();
    const updatedDate = /actualització: (.+)/.exec(footerText)[1].trim();
    expect(updatedDate.length).toBeLessThanOrEqual(MAX_DATE_LENGTH);
  });

  test("last updated date has month written properly in catalan", async ({ page }) => {
    const footerText = await footer(page).textContent();
    const updatedDate = /actualització: (.+)/.exec(footerText)[1].trim();
    expect(updatedDate).toMatch(/gener|febrer|març|abril|maig|juny|juliol|agost|setembre|octubre|novembre|desembre/);
  });

  test(`has ${data.paremiotipusNumber} paremiotipus`, async ({ page }) => {
    await expect(footer(page)).toContainText(`${formatNumber(data.paremiotipusNumber)} paremiotipus`);
  });

  test(`has ${data.fitxesNumber} fitxes`, async ({ page }) => {
    await expect(footer(page)).toContainText(`${formatNumber(data.fitxesNumber)} fitxes`);
  });

  test(`has ${data.fontsNumber} fonts`, async ({ page }) => {
    await expect(footer(page)).toContainText(`${formatNumber(data.fontsNumber)} fonts`);
  });

  test(`has ${data.informantsNumber} informants`, async ({ page }) => {
    await expect(footer(page)).toContainText(`${formatNumber(data.informantsNumber)} informants`);
  });
});
