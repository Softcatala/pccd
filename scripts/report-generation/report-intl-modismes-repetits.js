import { skeleton } from "@moderation-api/unicode-spoofing";
import { lines, readReportData, writeReport } from "./report-helpers.js";

const modismes = await readReportData("report_modismes.json");
const groupedModismes = Map.groupBy(modismes, ({ PAREMIOTIPUS }) => PAREMIOTIPUS);
const confusables = [];

for (const group of groupedModismes.values()) {
  for (let index = 1; index < group.length; index += 1) {
    if (group[index - 1].MODISME !== "" && skeleton(group[index - 1].MODISME) === skeleton(group[index].MODISME)) {
      confusables.push(group[index - 1].MODISME, group[index].MODISME, "");
    }
  }
}

await writeReport("test_intl_modismes_repetits.txt", lines(confusables));
