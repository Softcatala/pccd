import { readReportData, writeReport } from "./report-helpers.js";
import { checkUrls } from "./report-url-checker.js";

const imatges = await readReportData("report_imatges.json");
const report = await checkUrls({
  records: imatges,
  getUrl: ({ URL_ENLLAÇ }) => URL_ENLLAÇ,
  getLabel: ({ Identificador }) => `Identificador ${Identificador}`,
  validate: true,
});

await writeReport("test_imatges_url_enllac.txt", report);
