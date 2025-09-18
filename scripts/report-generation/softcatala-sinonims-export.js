import console from "node:console";
import { readFile } from "node:fs/promises";
import path from "node:path";

const SoftcatalaSinonimsFile = path.join(
  import.meta.dirname,
  "../../../sinonims-cat/dict/sinonims.txt",
);

try {
  const data = await readFile(SoftcatalaSinonimsFile, "utf8");

  // Extract lines and process each one.
  const lines = data.split("\n");
  const result = [];

  for (const line of lines) {
    // Remove text after #, category prefix, parentheses content and backslashes
    const cleanedLine = line
      .replace(/#.*/, "")
      .replace(/^-.*?:\s*/, "")
      .replaceAll(/\([^)]*\)/g, "")
      .replaceAll("\\", "");

    // Split by commas and filter terms with spaces.
    const words = cleanedLine
      .split(",")
      .map((word) => word.trim())
      .filter((word) => word.includes(" "));

    // Filter words that contain spaces and trim whitespace.
    const multiWordTerms = words
      .map((word) => word.trim())
      .filter((word) => word.includes(" "));

    // Add to result if there are any multi-word terms in the line.
    if (multiWordTerms.length > 0) {
      result.push(multiWordTerms.join("\n"));
    }
  }

  // Join groups with a new line.
  console.log(result.join("\n\n"));
} catch (error) {
  console.error("Error reading file:", error);
  throw error;
}
