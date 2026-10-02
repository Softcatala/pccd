import { readdir } from "node:fs/promises";
import path from "node:path";
import { lines, projectRoot, readReportData, writeReport } from "./report-helpers.js";

const ignoredFiles = new Set([".picasa.ini"]);
const supportedExtensions = new Set(["gif", "jpg", "png"]);
const [imatges, fonts, llibres] = await Promise.all([
  readReportData("report_imatges.json"),
  readReportData("report_fonts.json"),
  readReportData("report_llibres.json"),
]);
const imageDirectory = path.join(projectRoot, "docroot/img/imatges");
const bookImageDirectory = path.join(projectRoot, "docroot/img/obres");
const imageIdentifiers = new Set(imatges.map(({ Identificador }) => Identificador));
const bookImages = new Set([...fonts, ...llibres].map(({ Imatge }) => Imatge));
const unreferencedImages = [];

for (const [directory, references] of [
  [imageDirectory, imageIdentifiers],
  [bookImageDirectory, bookImages],
]) {
  for (const filename of await readdir(directory)) {
    if (ignoredFiles.has(filename) || !supportedExtensions.has(path.extname(filename).slice(1))) {
      continue;
    }
    if (!references.has(filename)) {
      unreferencedImages.push(filename);
    }
  }
}

await writeReport("test_imatges_no_referenciades.txt", lines(unreferencedImages));
