import { expect, test } from "@playwright/test";

const TOP_100_COUNT = 100;
const TOP_10000_COUNT = 10000;

test.describe("Top paremiotipus", () => {
  test("top 100 has 100 entries", async ({ page }) => {
    await page.goto("/top100");
    await expect(page.locator("main ol li")).toHaveCount(TOP_100_COUNT);
  });

  test("top 10000 has 10000 entries", async ({ page }) => {
    await page.goto("/top10000");
    await expect(page.locator("main ol li")).toHaveCount(TOP_10000_COUNT);
  });

  test("top 100 has unique text in each <li> tag", async ({ page }) => {
    await page.goto("/top100");
    const liElements = page.locator("main ol li");
    const texts = await liElements.allTextContents();

    expect(new Set(texts).size).toBe(texts.length);
  });

  test("top 10000 has unique text in each <li> tag", async ({ page }) => {
    await page.goto("/top10000");
    const liElements = page.locator("main ol li");
    const texts = await liElements.allTextContents();

    expect(new Set(texts).size).toBe(texts.length);
  });
});
