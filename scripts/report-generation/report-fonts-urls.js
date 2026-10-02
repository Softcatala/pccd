import { readReportData, writeReport } from "./report-helpers.js";
import { checkUrls } from "./report-url-checker.js";

const fonts = await readReportData("report_fonts.json");
const report = await checkUrls({
  records: fonts,
  getUrl: ({ URL }) => URL.trim(),
  getLabel: ({ Identificador }) => `Identificador ${Identificador}`,
});

await writeReport("test_fonts_url.txt", report);
