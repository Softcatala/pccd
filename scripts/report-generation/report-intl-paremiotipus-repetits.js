import { skeleton } from "@moderation-api/unicode-spoofing";
import { displayName, lines, readReportData, writeReport } from "./report-helpers.js";

const paremiotipus = await readReportData("report_paremiotipus.json");
const confusables = [];

for (let index = 1; index < paremiotipus.length; index += 1) {
  if (skeleton(paremiotipus[index - 1].value) === skeleton(paremiotipus[index].value)) {
    confusables.push(displayName(paremiotipus[index - 1]), displayName(paremiotipus[index]), "");
  }
}

await writeReport("test_intl_paremiotipus_repetits.txt", lines(confusables));
