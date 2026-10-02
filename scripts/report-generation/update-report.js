import { readFile } from "node:fs/promises";

import { writeReportWithNewContent } from "./report-helpers.js";

const [reportFilename, newReportFilename, generatedFilename] = process.argv.slice(2);

if (!reportFilename || !newReportFilename || !generatedFilename) {
  throw new Error("Usage: update-report.js <report> <new-report> <generated-file>");
}

await writeReportWithNewContent(reportFilename, newReportFilename, await readFile(generatedFilename, "utf8"));
