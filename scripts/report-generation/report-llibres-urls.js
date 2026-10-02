import { readReportData, writeReport } from "./report-helpers.js";
import { checkUrls } from "./report-url-checker.js";

const llibres = await readReportData("report_llibres.json");
const report = await checkUrls({
  records: llibres,
  getUrl: ({ URL }) => URL,
  getLabel: ({ Títol }) => Títol,
  method: "GET",
  skipEmpty: false,
  emptyMessage: ({ Títol }) => `URL buida (Identificador ${Títol})`,
});

await writeReport("test_llibres_url.txt", report);
