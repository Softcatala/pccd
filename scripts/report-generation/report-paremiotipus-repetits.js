import {
  displayName,
  findSimilarParemiotipus,
  lines,
  readReportData,
  writeReportWithNewContent,
} from "./report-helpers.js";

const paremiotipus = await readReportData("report_paremiotipus.json");
const similarPairs = await findSimilarParemiotipus(paremiotipus);
const report = lines(similarPairs.flatMap(([first, second]) => [displayName(first), displayName(second), ""]));

await writeReportWithNewContent("test_repetits.txt", "test_repetits_new.txt", report);
