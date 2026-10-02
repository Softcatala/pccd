import { analyze } from "@moderation-api/unicode-spoofing";
import { lines, readReportData, writeReport } from "./report-helpers.js";

const modismes = await readReportData("report_modismes_sospitosos.json");
const report = lines(modismes.filter((modisme) => analyze(modisme).spoofed));

await writeReport("test_intl_modismes_sospitosos.txt", report);
