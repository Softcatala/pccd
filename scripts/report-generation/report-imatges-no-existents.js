import { access, stat } from "node:fs/promises";
import path from "node:path";
import { lines, projectRoot, readReportData, writeReport } from "./report-helpers.js";

const fileExists = async (filename) => {
  try {
    await access(filename);
    return (await stat(filename)).isFile();
  } catch {
    return false;
  }
};

const imatges = await readReportData("report_imatges.json");
const fonts = await readReportData("report_fonts.json");
const imagesDirectory = path.join(projectRoot, "docroot/img/imatges");
const bookImagesDirectory = path.join(projectRoot, "docroot/img/obres");
const missingImages = [];

for (const { Identificador } of imatges) {
  if (Identificador && !(await fileExists(path.join(imagesDirectory, Identificador)))) {
    missingImages.push(`paremies/${Identificador}`);
  }
}
for (const { Imatge } of fonts) {
  if (Imatge && !(await fileExists(path.join(bookImagesDirectory, Imatge)))) {
    missingImages.push(`cobertes/${Imatge}`);
  }
}

await writeReport("test_imatges_no_existents.txt", lines(missingImages));
