import { lines, readReportData, wordsForModismeReport, writeReport } from "./report-helpers.js";

const modismes = await readReportData("report_modismes.json");
const displayNames = new Map(modismes.map(({ PAREMIOTIPUS, Display }) => [PAREMIOTIPUS, Display]));
const wordsByParemiotipus = new Map();
let previousParemiotipus = "";
const output = [];

for (const { MODISME, PAREMIOTIPUS } of modismes) {
  if (MODISME === PAREMIOTIPUS) {
    continue;
  }

  const modismeWords = wordsForModismeReport(MODISME);
  const paremiotipusWords = wordsByParemiotipus.get(PAREMIOTIPUS) ?? wordsForModismeReport(PAREMIOTIPUS);
  wordsByParemiotipus.set(PAREMIOTIPUS, paremiotipusWords);
  if ([...modismeWords].some((word) => paremiotipusWords.has(word))) {
    continue;
  }

  if (previousParemiotipus !== PAREMIOTIPUS) {
    if (previousParemiotipus !== "") {
      output.push("");
    }
    output.push(`${displayNames.get(PAREMIOTIPUS) ?? PAREMIOTIPUS}:`);
  }
  output.push(`    ${MODISME}`);
  previousParemiotipus = PAREMIOTIPUS;
}

await writeReport("test_intl_modismes_molt_diferents.txt", lines(output));
