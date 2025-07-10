const fs = require("node:fs");
const path = require("node:path");

const SoftcatalaSinonimsFile = path.join(__dirname, "../../sinonims-cat/dict/sinonims.txt");

fs.readFile(SoftcatalaSinonimsFile, "utf8", (error, data) => {
    if (error) {
        console.error("Error reading file:", error);
        return;
    }

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
        const multiWordTerms = words.map((word) => word.trim()).filter((word) => word.includes(" "));

        // Add to result if there are any multi-word terms in the line.
        if (multiWordTerms.length > 0) {
            result.push(multiWordTerms.join("\n"));
        }
    }

    // Join groups with a new line.
    console.log(result.join("\n\n"));
});
