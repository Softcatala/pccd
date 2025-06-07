const crypto = require("node:crypto");
const fs = require("node:fs");
const path = require("node:path");

const findDuplicateImages = function () {
    const directoryPath = path.join(__dirname, "/../src/images/paremies/");
    const files = {};
    const duplicates = [];

    // Read directory and gather file information.
    for (const file of fs.readdirSync(directoryPath)) {
        const filePath = path.join(directoryPath, file);
        const stat = fs.statSync(filePath);

        if (stat.isFile()) {
            const fileSize = stat.size;

            // Group files by size as a quick pre-check.
            if (!files[fileSize]) {
                files[fileSize] = [];
            }
            files[fileSize].push(filePath);
        }
    }

    // Compare files with the same size.
    for (const size of Object.keys(files)) {
        const sameSizeFiles = files[size];

        if (sameSizeFiles.length > 1) {
            const hashes = {};

            for (const file of sameSizeFiles) {
                const fileBuffer = fs.readFileSync(file);
                const hash = crypto.createHash("md5").update(fileBuffer).digest("hex");

                if (!hashes[hash]) {
                    hashes[hash] = [];
                }
                hashes[hash].push(file);
            }

            // Check for duplicates.
            for (const duplicateGroup of Object.values(hashes)) {
                if (duplicateGroup.length > 1) {
                    duplicates.push(duplicateGroup);
                }
            }
        }
    }

    // Format output.
    let output = "";
    for (const group of duplicates) {
        output += "\n" + group.map((file) => path.basename(file)).join("\n") + "\n";
    }

    return output.trim();
};

console.log(findDuplicateImages());
