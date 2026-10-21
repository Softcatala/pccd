import { groupConfusables, lines, readReportData, writeReport } from "./report-helpers.js";

const modismes = await readReportData("report_modismes.json");
const groupedModismes = Map.groupBy(modismes, ({ PAREMIOTIPUS }) => PAREMIOTIPUS);
const confusables = [];

for (const paremiotipusGroup of groupedModismes.values()) {
  const candidates = paremiotipusGroup.filter(({ MODISME }) => MODISME !== "");
  for (const group of groupConfusables(candidates, ({ MODISME }) => MODISME)) {
    for (const { MODISME } of group) {
      confusables.push(MODISME);
    }
    confusables.push("");
  }
}

await writeReport("test_intl_modismes_repetits.txt", lines(confusables));
