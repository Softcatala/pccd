import { displayName, readReportData, typographyReport, writeReport } from "./report-helpers.js";

const paremiotipus = await readReportData("report_paremiotipus.json");

await writeReport("test_intl_paremiotipus_tipografia.txt", typographyReport(paremiotipus.map(displayName)));
