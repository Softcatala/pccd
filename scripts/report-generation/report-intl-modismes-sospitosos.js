import { analyze } from "@moderation-api/unicode-spoofing";
import { describeSpoofing, hasAnySignal, lines, readReportData, writeReport } from "./report-helpers.js";

const modismes = await readReportData("report_modismes_sospitosos.json");
const report = lines(
  modismes.flatMap((modisme) => {
    const result = analyze(modisme);
    return hasAnySignal(result) ? [modisme, ...describeSpoofing(result)] : [];
  }),
);

await writeReport("test_intl_modismes_sospitosos.txt", report);
