import { displayName, groupConfusables, lines, readReportData, writeReport } from "./report-helpers.js";

const paremiotipus = await readReportData("report_paremiotipus.json");
const confusables = [];

for (const group of groupConfusables(paremiotipus, (record) => record.value)) {
  for (const record of group) {
    confusables.push(displayName(record));
  }
  confusables.push("");
}

await writeReport("test_intl_paremiotipus_repetits.txt", lines(confusables));
