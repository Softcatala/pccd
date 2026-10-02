import { readReportData, writeReport } from "./report-helpers.js";
import { checkUrls } from "./report-url-checker.js";

const imatges = await readReportData("report_imatges.json");
const report = await checkUrls({
  records: imatges,
  getUrl: ({ URL_IMATGE }) => URL_IMATGE.trim(),
  getLabel: ({ Identificador }) => `Identificador ${Identificador}`,
});

await writeReport("test_imatges_url_imatge.txt", report);
