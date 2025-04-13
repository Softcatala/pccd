/* eslint-env browser */
const { expect, test } = require("@playwright/test");

test.describe("Homepage <picture> tag", () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ height: 720, width: 1280 });
        await page.goto("/");
    });

    test("homepage has a <picture> element", async ({ page }) => {
        await expect(page.locator("picture")).toHaveCount(1);
    });

    test("the picture includes an AVIF file", async ({ page }) => {
        const pictureSrcset = await page.locator("picture source").getAttribute("srcset");
        expect(pictureSrcset).toMatch(/\.avif/);
    });

    test("the server sets the correct image/avif content type for the AVIF file", async ({ page }) => {
        const avifUrl = await page.evaluate(() => {
            const sourceElement = document.querySelector("picture source[type='image/avif']");
            return sourceElement ? sourceElement.srcset : undefined;
        });

        const response = await page.request.get(avifUrl);
        expect(response.headers()["content-type"]).toBe("image/avif");
    });

    test("all files in the picture element have the correct Cache-Control header", async ({ page }) => {
        const urls = await page.evaluate(() => {
            const sources = document.querySelectorAll("picture source");
            return [...sources].map((source) => source.srcset);
        });

        for (const url of urls) {
            const response = await page.request.get(url);
            expect(response.headers()["cache-control"]).toBe("public, max-age=31536000, immutable");
        }
    });

    test("all files in the picture element have the correct Strict-Transport-Security header", async ({ page }) => {
        const urls = await page.evaluate(() => {
            const sources = document.querySelectorAll("picture source");
            return [...sources].map((source) => source.srcset);
        });

        for (const url of urls) {
            const response = await page.request.get(url);
            expect(response.headers()["strict-transport-security"]).toBe("max-age=31536000");
        }
    });

    test("homepage includes a preloaded image with type and media attributes", async ({ page }) => {
        const response = await page.goto("/");
        const linkHeader = response.headers().link;

        expect(linkHeader).toMatch(
            /<[^>]+>; rel=preload; as=image; type=image\/(avif|webp|jpeg|png|gif); media="\(min-width: \d+px\)"/,
        );
    });
});

test.describe("SVG in <img> tags", () => {
    test.beforeEach(async ({ page }) => {
        await page.setViewportSize({ height: 720, width: 1280 });
        await page.goto("/");
    });

    test("homepage has <img> tags with SVG files", async ({ page }) => {
        const svgImagesCount = await page.locator('img[src$=".svg"]').count();
        expect(svgImagesCount).toBeGreaterThan(0);
    });

    test("the server sends correct type and sets Brotli compression for SVG files", async ({ page }) => {
        const svgUrls = await page.evaluate(() => {
            return [...document.querySelectorAll('img[src$=".svg"]')].map((img) => img.src);
        });

        for (const url of svgUrls) {
            const response = await page.request.get(url);
            expect(response.headers()["content-type"]).toBe("image/svg+xml; charset=utf-8");
            expect(response.headers()["content-encoding"]).toBe("br");
        }
    });
});
