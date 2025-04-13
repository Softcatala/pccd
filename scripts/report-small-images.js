const fs = require("node:fs");
const path = require("node:path");
const sharp = require("sharp");

const IMAGE_MIN_WIDTH = 350;
const paremiesDirectory = path.join(__dirname, "../src/images/paremies");
const cobertesDirectory = path.join(__dirname, "../src/images/cobertes");

const listSmallImages = async function (sourceDirectory, minimumWidth = IMAGE_MIN_WIDTH) {
    const smallImages = [];
    const files = fs.readdirSync(sourceDirectory);

    for (const file of files) {
        if (file === ".picasa.ini") {
            continue;
        }
        const filePath = path.join(sourceDirectory, file);
        try {
            const { width } = await sharp(filePath).metadata();
            if (width < minimumWidth) {
                smallImages.push(`${file} (${width} px)`);
            }
        } catch (error) {
            smallImages.push(`Error while trying to open ${file}: ${error.message}`);
        }
    }

    return smallImages.join("\n");
};

const main = async function () {
    const cobertes = await listSmallImages(cobertesDirectory);
    console.log(cobertes);
    const paremies = await listSmallImages(paremiesDirectory);
    console.log(paremies);
};

// Call main using a function expression
main().catch((error) => {
    console.error(error);
});
