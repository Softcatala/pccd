import { expect, test } from "@playwright/test";

const SAMPLES = [
  "/",
  "/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982",
  "/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era",
  "/p/Val_m%C3%A9s_un_boig_conegut_que_un_savi_per_con%C3%A8ixer",
  "/fonts",
];

test.describe("Console errors", () => {
  for (const url of SAMPLES) {
    test(`page "${url}" should have no console errors or uncaught exceptions`, async ({ page }) => {
      const errors = [];
      page.on("console", (message) => {
        if (message.type() === "error") {
          errors.push(message.text());
        }
      });
      page.on("pageerror", (exception) => {
        errors.push(exception.message);
      });

      await page.goto(url);

      // Wait for any potential async console errors that might happen shortly after load
      await page.waitForTimeout(500);

      expect(errors, `Found console errors or exceptions on ${url}:\n${errors.join("\n")}`).toEqual([]);
    });
  }
});
