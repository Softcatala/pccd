const { expect, test } = require("@playwright/test");
const fs = require("node:fs");
const path = require("node:path");

const data = JSON.parse(fs.readFileSync(path.resolve(__dirname, "data/data.json"), "utf8"));

test.describe("Paremiotipus", () => {
    let extractedNumber = "";
    test(`"Qui no vulgui pols, que no vagi a l'era" has ${data.paremiotipusQuiNoVulguiPolsNumberOfEntries} records`, async ({
        page,
    }) => {
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const results = await page.locator(".description").textContent();

        [, extractedNumber] = /([\d.]+)\srecurrències/.exec(results);
        const nEntries = Number(extractedNumber.replace(".", ""));
        expect(nEntries).toBe(data.paremiotipusQuiNoVulguiPolsNumberOfEntries);
    });

    test(`"Qui no vulgui pols, que no vagi a l'era" has ${data.paremiotipusQuiNoVulguiPolsNumberOfVariants} variants`, async ({
        page,
    }) => {
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const results = await page.locator(".description").textContent();

        [, extractedNumber] = /en ([\d.]+)\svariants/.exec(results);
        const nVariants = Number(extractedNumber.replace(".", ""));
        expect(nVariants).toBe(data.paremiotipusQuiNoVulguiPolsNumberOfVariants);
    });

    test('"Qui no vulgui pols, que no vagi a l\'era" includes a twitter:image meta tag with a valid image URL', async ({
        page,
        baseURL,
    }) => {
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const twitterImage = await page.locator('meta[name="twitter:image"]').getAttribute("content");
        expect(twitterImage).toBeTruthy();

        // Extract the path from the full URL and create a new URL with baseURL.
        const originalUrl = new URL(twitterImage);
        const imagePath = originalUrl.pathname;
        const testImageUrl = new URL(imagePath, baseURL).toString();

        const response = await page.request.get(testImageUrl);
        expect(response.status()).toBe(200);
        expect(response.headers()["content-type"]).toContain("image");
    });
    test('"Qui no vulgui pols, que no vagi a l\'era" includes an og:image meta tag with a valid image URL', async ({
        page,
        baseURL,
    }) => {
        await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const ogImage = await page.locator('meta[property="og:image"]').getAttribute("content");
        expect(ogImage).toBeTruthy();

        // Extract the path from the full URL and create a new URL with baseURL.
        const originalUrl = new URL(ogImage);
        const imagePath = originalUrl.pathname;
        const testImageUrl = new URL(imagePath, baseURL).toString();

        const response = await page.request.get(testImageUrl);
        expect(response.status()).toBe(200);
        expect(response.headers()["content-type"]).toContain("image");
    });

    test('"Qui no vulgui pols, que no vagi a l\'era" includes a preloaded image with type and media attributes"', async ({
        page,
    }) => {
        const response = await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
        const linkHeader = response.headers().link;

        expect(linkHeader).toMatch(
            /<[^>]+>; rel=preload; as=image; type=image\/(avif|webp|jpeg|png|gif); media="\(min-width: \d+px\)"/,
        );
    });

    test(`"Val més un boig conegut que un savi per conèixer" has CV audio`, async ({ page }) => {
        await page.goto("/p/Val_m%C3%A9s_un_boig_conegut_que_un_savi_per_con%C3%A8ixer");
        const commonVoicePath = await page.locator("#commonvoice audio").first().getAttribute("src");
        expect(commonVoicePath).toContain(".mp3");
    });

    test(`"—Què hem de fer? —Vendre la casa i anar de lloguer" page has correct title`, async ({ page }) => {
        await page.goto("/p/Qu%C3%A8_hem_de_fer%3F_%E2%80%94Vendre_la_casa_i_anar_de_lloguer");
        const pageTitle = await page.title();
        expect(pageTitle).toBe("—Què hem de fer? —Vendre la casa i anar de lloguer | PCCD");
    });
});
