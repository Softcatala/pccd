import { mkdtemp, readFile, rename, rm, writeFile } from "node:fs/promises";
import { availableParallelism } from "node:os";
import path from "node:path";
import { Worker } from "node:worker_threads";
import { skeleton } from "@moderation-api/unicode-spoofing";

export const projectRoot = path.resolve(import.meta.dirname, "../..");
const dataDirectory = path.join(projectRoot, "tmp");
const reportDirectory = path.join(projectRoot, "data/reports");
const modismeWordsExclude = new Set(["a", "amb", "de", "el", "els", "en", "i", "la", "les", "ni", "o", "per", "que"]);

const DIACRITICS_RE = /\p{M}/gu;
const UNICODE_BLOCK_RE = /[\u0100-\u7fff]/g;
const WORD_SPLIT_RE = /[a-z]+(?:['-][a-z]+)*'?/g;
const NEWLINE_RE = /\r?\n/;

export const lines = (values) => (values.length === 0 ? "" : `${values.join("\n")}\n`);

export const readReportData = async (filename) =>
  JSON.parse(await readFile(path.join(dataDirectory, filename), "utf8"));

export const writeReport = (filename, contents) => writeFile(path.join(reportDirectory, filename), contents);

export const getNewLinesContent = (previousContent, currentContent) => {
  if (!currentContent) {
    return "";
  }

  const previousLines = new Set(previousContent.split(NEWLINE_RE));
  return lines(currentContent.split(NEWLINE_RE).filter((line) => line !== "" && !previousLines.has(line)));
};

export const writeReportWithNewContent = async (filename, newFilename, contents) => {
  const temporaryDirectory = await mkdtemp(path.join(reportDirectory, ".report-"));
  const temporaryReport = path.join(temporaryDirectory, filename);
  const reportPath = path.join(reportDirectory, filename);

  try {
    await writeFile(temporaryReport, contents);
    const previousContent = await readFile(reportPath, "utf8").catch((error) => {
      if (error.code === "ENOENT") {
        return "";
      }
      throw error;
    });

    await writeFile(path.join(reportDirectory, newFilename), getNewLinesContent(previousContent, contents));
    await rename(temporaryReport, reportPath);
  } finally {
    await rm(temporaryDirectory, { recursive: true, force: true });
  }
};

export const displayName = (record) => record.display || record.value;

// Non-standard separators that stand in for the Catalan middle dot (punt volat)
// in the ela geminada (l·l), e.g. the typewriter "l.l" or the old "l,l".
const ELA_GEMINADA_SEPARATOR_RE = /[lL][.,:;][lL]/u;
const ELA_GEMINADA_SEPARATOR_FOLD_RE = /[lL][.,:;][lL]/gu;
const MIDDLE_DOT = "·";
const MIDDLE_DOT_VARIANT_RE = /[•‧ꞏ∙⋅]/u;
// A middle dot that is not flanked by two l's is either misplaced or stands in
// for an apostrophe in archaic spellings ("que·l"), so it is worth reporting.
const MISPLACED_MIDDLE_DOT_RE = /(?<![lL·])·|·(?![lL·])/u;

export const normalizeElaGeminada = (value) =>
  value.replace(ELA_GEMINADA_SEPARATOR_FOLD_RE, (match) => `${match[0]}${MIDDLE_DOT}${match[2]}`);

export const confusableKey = (value) => skeleton(normalizeElaGeminada(value));

export const findTypographyIssues = (value) => {
  const issues = [];
  if (ELA_GEMINADA_SEPARATOR_RE.test(value)) {
    issues.push("ela_geminada_separator");
  }
  if (MIDDLE_DOT_VARIANT_RE.test(value)) {
    issues.push("middle_dot_variant");
  }
  if (MISPLACED_MIDDLE_DOT_RE.test(value)) {
    issues.push("middle_dot_position");
  }
  return issues;
};

export const typographyReport = (values) => {
  const findingsByIssue = Map.groupBy(
    [...new Set(values)].flatMap((value) => findTypographyIssues(value).map((issue) => ({ issue, value }))),
    ({ issue }) => issue,
  );

  return lines(
    [...findingsByIssue.entries()].flatMap(([issue, findings]) => [
      `${issue}:`,
      ...findings.map(({ value }) => `    ${value}`),
      "",
    ]),
  );
};

export const groupConfusables = (records, getValue) =>
  [...Map.groupBy(records, (record) => confusableKey(getValue(record))).values()].filter((group) => group.length > 1);

export const hasAnySignal = ({ signals }) => Object.values(signals).some(Boolean);

export const describeSpoofing = ({ words, normalized }) => [
  ...words.flatMap(({ word, signals, scripts }) =>
    signals.map((signal) => {
      const scriptList = scripts.length > 0 ? ` [${scripts.join(", ")}]` : "";
      return `    ${signal}: ${word}${scriptList}`;
    }),
  ),
  `    normalized: ${normalized}`,
];

export const wordsForModismeReport = (value) => {
  const normalized = value.normalize("NFD").replace(DIACRITICS_RE, "").replace(UNICODE_BLOCK_RE, "").toLowerCase();

  const words = normalized.match(WORD_SPLIT_RE) ?? [];
  return new Set(words.filter((word) => !modismeWordsExclude.has(word)));
};

export const findSimilarParemiotipus = async (records) => {
  if (records.length === 0) {
    return [];
  }

  // Map each UTF-8 byte to one JS code unit so distance and length share units.
  const values = records.map(({ value }) => Buffer.from(value, "utf8").toString("latin1"));
  const workerCount = Math.min(availableParallelism(), 10, values.length);
  const chunkSize = Math.ceil(values.length / workerCount);
  const workerFile = new URL("./levenshtein-worker.js", import.meta.url);

  const chunks = await Promise.all(
    Array.from({ length: workerCount }, (_, index) => {
      const start = index * chunkSize;
      const end = Math.min(start + chunkSize, values.length);

      if (start >= end) {
        return [];
      }

      return new Promise((resolve, reject) => {
        const worker = new Worker(workerFile, { workerData: { values, start, end } });
        worker.once("message", resolve);
        worker.once("error", reject);
        worker.once("exit", (code) => {
          if (code !== 0) {
            reject(new Error(`Levenshtein worker exited with code ${code}`));
          }
        });
      });
    }),
  );

  return chunks.flat().map(([first, second]) => [records[first], records[second]]);
};
