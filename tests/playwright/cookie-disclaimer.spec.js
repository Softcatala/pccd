import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

test.describe("Cookie disclaimer", () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ height: 720, width: 1280 });
    await page.goto("/");
  });

  test("message is visible", async ({ page }) => {
    await expect(page.getByText(data.cookieMessage, { exact: true })).toBeVisible();
  });

  test("message is in the view port", async ({ page }) => {
    await expect(page.getByText(data.cookieMessage, { exact: true })).toBeInViewport();
  });

  test("clicking accept button removes the message, and the message is not visible after reloading", async ({
    page,
  }) => {
    await page.locator("#cookie-banner").getByRole("button").click();
    await expect(page.getByText(data.cookieMessage, { exact: true })).toHaveCount(0);

    // Reload the page and check that the message is not visible.
    await page.reload();
    await expect(page.getByText(data.cookieMessage, { exact: true })).toBeHidden();
  });
});
