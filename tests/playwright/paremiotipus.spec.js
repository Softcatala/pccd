import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

const HTTP_OK_STATUS = 200;
const formatNumber = (number) => new Intl.NumberFormat("ca-ES").format(number);

test.describe("Paremiotipus", () => {
  test(`"Qui no vulgui pols, que no vagi a l'era" has ${data.paremiotipusQuiNoVulguiPolsNumberOfEntries} records`, async ({
    page,
  }) => {
    await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
    await expect(page.locator(".description")).toContainText(
      `${formatNumber(data.paremiotipusQuiNoVulguiPolsNumberOfEntries)} recurrències`,
    );
  });

  test(`"Qui no vulgui pols, que no vagi a l'era" has ${data.paremiotipusQuiNoVulguiPolsNumberOfVariants} variants`, async ({
    page,
  }) => {
    await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
    await expect(page.locator(".description")).toContainText(
      `${formatNumber(data.paremiotipusQuiNoVulguiPolsNumberOfVariants)} variants`,
    );
  });

  test('"Qui no vulgui pols, que no vagi a l\'era" includes a Twitter card type', async ({ page }) => {
    await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
    await expect(page.locator('meta[name="twitter:card"]')).toHaveAttribute(
      "content",
      /^(summary|summary_large_image)$/,
    );
  });
  test('"Qui no vulgui pols, que no vagi a l\'era" includes an og:image meta tag with a valid image URL', async ({
    page,
    baseURL,
  }) => {
    await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
    const ogImage = page.locator('meta[property="og:image"]');
    await expect(ogImage).toHaveAttribute("content", /^https?:\/\//);
    const imageUrl = await ogImage.getAttribute("content");

    // Extract the path from the full URL and create a new URL with baseURL.
    const originalUrl = new URL(imageUrl);
    const imagePath = originalUrl.pathname;
    const testImageUrl = new URL(imagePath, baseURL);

    const response = await page.request.get(testImageUrl.href);
    expect(response.status()).toBe(HTTP_OK_STATUS);
    expect(response.headers()["content-type"]).toContain("image");
  });

  test('"Qui no vulgui pols, que no vagi a l\'era" includes a preloaded image with type and media attributes"', async ({
    page,
  }) => {
    const response = await page.goto("/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era");
    const linkHeader = response.headers().link;

    expect(linkHeader).toMatch(
      /<[^>]+>; rel=preload; as=image; type=image\/(?:avif|webp|jpeg|png|gif); media="\(width >= \d+px\)"/,
    );
  });

  test(`"Val més un boig conegut que un savi per conèixer" has CV audio`, async ({ page }) => {
    await page.goto("/p/Val_m%C3%A9s_un_boig_conegut_que_un_savi_per_con%C3%A8ixer");
    await expect(page.locator("#commonvoice audio").first()).toHaveAttribute("src", /\.mp3/);
  });

  test(`"—Què hem de fer? —Vendre la casa i anar de lloguer" page has correct title`, async ({ page }) => {
    await page.goto("/p/Qu%C3%A8_hem_de_fer%3F_%E2%80%94Vendre_la_casa_i_anar_de_lloguer");
    await expect(page).toHaveTitle("—Què hem de fer? —Vendre la casa i anar de lloguer | PCCD");
  });
});
