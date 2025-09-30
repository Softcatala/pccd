import { expect, test } from "@playwright/test";

const TOP_100_COUNT = 100;
const TOP_10000_COUNT = 10000;

test.describe("Top paremiotipus", () => {
  test("top 100 has 100 entries", async ({ page }) => {
    await page.goto("/top100");
    const nRecords = await page.locator("main ol li").count();
    expect(nRecords).toEqual(TOP_100_COUNT);
  });

  test("top 10000 has 10000 entries", async ({ page }) => {
    await page.goto("/top10000");
    const nRecords = await page.locator("main ol li").count();
    expect(nRecords).toEqual(TOP_10000_COUNT);
  });

  test("top 100 has unique text in each <li> tag", async ({ page }) => {
    await page.goto("/top100");
    const liElements = page.locator("main ol li");
    const texts = await liElements.allTextContents();

    // Use a Set to store unique texts.
    const uniqueTexts = new Set(texts);

    // The size of the Set should be equal to the number of <li> elements if all texts are unique.
    expect(uniqueTexts.size).toEqual(await liElements.count());
  });

  test("top 10000 has unique text in each <li> tag", async ({ page }) => {
    await page.goto("/top10000");
    const liElements = page.locator("main ol li");
    const texts = await liElements.allTextContents();

    const uniqueTexts = new Set(texts);
    expect(uniqueTexts.size).toEqual(await liElements.count());
  });
});
