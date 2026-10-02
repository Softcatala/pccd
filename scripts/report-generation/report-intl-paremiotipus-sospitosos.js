import { analyze } from "@moderation-api/unicode-spoofing";
import { displayName, lines, readReportData, writeReport } from "./report-helpers.js";

const paremiotipus = await readReportData("report_paremiotipus.json");
const report = lines(paremiotipus.filter(({ value }) => analyze(value).spoofed).map(displayName));

await writeReport("test_intl_paremiotipus_sospitosos.txt", report);
