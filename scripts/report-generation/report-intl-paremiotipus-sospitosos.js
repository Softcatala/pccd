import { analyze } from "@moderation-api/unicode-spoofing";
import { describeSpoofing, displayName, hasAnySignal, lines, readReportData, writeReport } from "./report-helpers.js";

const paremiotipus = await readReportData("report_paremiotipus.json");
const report = lines(
  paremiotipus.flatMap((record) => {
    const result = analyze(record.value);
    return hasAnySignal(result) ? [displayName(record), ...describeSpoofing(result)] : [];
  }),
);

await writeReport("test_intl_paremiotipus_sospitosos.txt", report);
