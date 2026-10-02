import { lines } from "./report-helpers.js";

const ignoredHttpStatusCodes = new Set(["200", "301", "302", "307", "308"]);
const userAgent =
  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36";

const requestStatusCodes = async (urls, method = "HEAD") => {
  const responseCodes = new Map();
  let nextIndex = 0;

  const requestNext = async () => {
    while (nextIndex < urls.length) {
      const url = urls[nextIndex++];
      try {
        const response = await fetch(url, {
          method,
          headers: { "user-agent": userAgent },
          redirect: "manual",
          signal: AbortSignal.timeout(3000),
        });
        responseCodes.set(url, String(response.status));
        await response.body?.cancel().catch(() => {});
      } catch (error) {
        responseCodes.set(url, `ERROR: ${error.message}`);
      }
    }
  };

  await Promise.all(Array.from({ length: Math.min(4, urls.length) }, requestNext));
  return responseCodes;
};

const isHttpUrl = (value) => value.startsWith("http://") || value.startsWith("https://");
const isValidHttpUrl = (value) => {
  if (!isHttpUrl(value) || /\s/.test(value)) {
    return false;
  }

  try {
    const { protocol, hostname } = new URL(value);
    return (protocol === "http:" || protocol === "https:") && hostname !== "";
  } catch {
    return false;
  }
};

export const checkUrls = async ({
  records,
  getUrl,
  getLabel,
  method = "HEAD",
  skipEmpty = true,
  validate = false,
  emptyMessage,
}) => {
  const uniqueUrls = [
    ...new Set(records.map(getUrl).filter((url) => url !== "" && isHttpUrl(url) && (!validate || isValidHttpUrl(url)))),
  ];
  const statuses = await requestStatusCodes(uniqueUrls, method);
  const seen = new Set();
  const output = [];

  for (const record of records) {
    const url = getUrl(record);
    if (skipEmpty && url === "") {
      continue;
    }
    if (url === "" && emptyMessage) {
      output.push(emptyMessage(record));
      continue;
    }
    if (validate && url !== "") {
      if (seen.has(url)) {
        continue;
      }
      seen.add(url);
    }

    if (!isHttpUrl(url) || (validate && !isValidHttpUrl(url))) {
      if (validate || url !== "") {
        output.push(`URL no vàlida${validate ? " o amb caràcters especials" : ""} (${getLabel(record)}): ${url}`);
      }
      continue;
    }

    if (!validate && seen.has(url)) {
      continue;
    }
    if (!validate) {
      seen.add(url);
    }

    const status = statuses.get(url) ?? "ERROR: URL is invalid";
    if (!ignoredHttpStatusCodes.has(status)) {
      output.push(`HTTP ${status} (${getLabel(record)}): ${url}`);
    }
  }

  return lines(output);
};
