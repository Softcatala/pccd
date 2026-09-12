import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

test.describe("Obra", () => {
  test(`"Folklore de Catalunya. Cançoner" has ${data.obraFolkloreCatalunyaNumberOfEntries} entries, of which ${data.obraFolkloreCatalunyaNumberOfEntriesCollected} has been collected`, async ({
    page,
  }) => {
    await page.goto("/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982");
    const obra = await page.locator("article").textContent();
    const match = /Aquesta obra té ([\d.]+) fitxes a la base de dades/.exec(obra);

    expect(Number(match[1].replace(".", ""))).toBe(data.obraFolkloreCatalunyaNumberOfEntries);

    const collectedMatch = /de les quals ([\d.]+) estan recollides/.exec(obra);
    expect(Number(collectedMatch[1].replace(".", ""))).toBe(data.obraFolkloreCatalunyaNumberOfEntriesCollected);
  });

  test('"Folklore de Catalunya. Cançoner" includes a preloaded image with type and media attributes"', async ({
    page,
  }) => {
    const response = await page.goto(
      "/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982",
    );
    const linkHeader = response.headers().link;

    expect(linkHeader).toMatch(
      /<[^>]+>; rel=preload; as=image; type=image\/(?:avif|webp|jpeg|png|gif); media="\(width >= \d+px\)"/,
    );
  });
});
