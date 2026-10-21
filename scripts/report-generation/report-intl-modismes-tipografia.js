import { readReportData, typographyReport, writeReport } from "./report-helpers.js";

const modismes = await readReportData("report_modismes.json");

await writeReport("test_intl_modismes_tipografia.txt", typographyReport(modismes.map(({ MODISME }) => MODISME)));
