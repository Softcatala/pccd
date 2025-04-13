const fs = require("node:fs");
const path = require("node:path");

const paremiesDirectory = path.join(__dirname, "../src/images/paremies");
const cobertesDirectory = path.join(__dirname, "../src/images/cobertes");

const listUnsupportedExtensions = function (sourceDirectory) {
    const supportedExtensions = new Set([".gif", ".jpg", ".png"]);
    const unsupportedFiles = [];

    const files = fs.readdirSync(sourceDirectory);
    for (const file of files) {
        if (file === ".picasa.ini") {
            continue;
        }

        const extension = path.extname(file);
        if (!supportedExtensions.has(extension)) {
            unsupportedFiles.push(file);
        }
    }

    return unsupportedFiles.join("\n");
};

console.log(listUnsupportedExtensions(cobertesDirectory));
console.log(listUnsupportedExtensions(paremiesDirectory));
