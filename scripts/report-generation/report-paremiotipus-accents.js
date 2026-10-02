import { lines, readReportData, writeReport } from "./report-helpers.js";

const accents = await readReportData("report_paremiotipus_accents.json");
await writeReport("test_paremiotipus_accents.txt", lines(accents));
