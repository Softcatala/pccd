import { isMainThread, parentPort, workerData } from "node:worker_threads";
import levenshtein from "fastest-levenshtein";

export const findSimilarPairsInRange = (values, start, end) => {
  const matches = [];

  for (let i = start; i < end; i += 1) {
    const value1 = values[i];
    for (let j = i + 1; j < values.length; j += 1) {
      const value2 = values[j];
      const length1 = value1.length;
      const length2 = value2.length;

      if (Math.abs(length1 - length2) >= 12) {
        continue;
      }

      const similarity = 1 - levenshtein.distance(value1, value2) / Math.max(length1, length2);
      if (similarity >= 0.8) {
        matches.push([i, j]);
      }
    }
  }

  return matches;
};

if (!isMainThread) {
  const { values, start, end } = workerData;
  parentPort.postMessage(findSimilarPairsInRange(values, start, end));
}
