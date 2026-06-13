import { expect, test } from "@playwright/test";

import data from "../../data/playwright/data.json" with { type: "json" };

test.describe("Obra", () => {
  let extractedNumber = "";
  test(`"Folklore de Catalunya. Cançoner" has ${data.obraFolkloreCatalunyaNumberOfEntries} entries, of which ${data.obraFolkloreCatalunyaNumberOfEntriesCollected} has been collected`, async ({
    page,
  }) => {
    await page.goto("/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982");
    const obra = await page.locator("article").textContent();
    let match = /Aquesta obra té (?<number>[\d.]+) fitxes a la base de dades/u.exec(obra);
    extractedNumber = match.groups.number;

    expect(Number(extractedNumber.replace(".", ""))).toBe(data.obraFolkloreCatalunyaNumberOfEntries);

    match = /de les quals (?<number>[\d.]+) estan recollides/u.exec(obra);
    extractedNumber = match.groups.number;
    expect(Number(extractedNumber.replace(".", ""))).toBe(data.obraFolkloreCatalunyaNumberOfEntriesCollected);
  });

  test('"Folklore de Catalunya. Cançoner" includes a preloaded image with type and media attributes"', async ({
    page,
  }) => {
    const response = await page.goto(
      "/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Cançoner%2C_3a_ed._1982",
    );
    const linkHeader = response.headers().link;

    expect(linkHeader).toMatch(
      /<[^>]+>; rel=preload; as=image; type=image\/(?:avif|webp|jpeg|png|gif); media="\(min-width: \d+px\)"/u,
    );
  });
});
