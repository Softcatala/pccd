const { expect, test } = require("@playwright/test");
const fs = require("node:fs");
const path = require("node:path");

const data = JSON.parse(fs.readFileSync(path.resolve(__dirname, "data/data.json"), "utf8"));

test.describe("Cookie disclaimer", () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ height: 720, width: 1280 });
        await page.goto("/");
    });

    test("message is visible", async ({ page }) => {
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeVisible();
    });

    test("message is in the view port", async ({ page }) => {
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeInViewport();
    });

    test("clicking accept button removes the message, and the message is not visible after reloading", async ({
        page,
    }) => {
        await page.click("#cookie-banner button");
        await expect(page.locator(`text=${data.cookieMessage}`)).toHaveCount(0);

        // Reload the page and check that the message is not visible.
        await page.reload();
        await expect(page.locator(`text=${data.cookieMessage}`)).toBeHidden();
    });
});
