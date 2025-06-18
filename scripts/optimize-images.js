const { execFileSync, spawnSync } = require("node:child_process");
const fs = require("node:fs");
const path = require("node:path");
const sharp = require("sharp");

const SIZE_THRESHOLD = 5000;
const PNG_MIN_QUALITY = 70;
const PNG_MAX_QUALITY = 95;
const IMAGE_WIDTH = 500;

// TODO: consider removing all these packages to reduce the number of dependencies.
const gif2webp = "gif2webp";
const gifsicle = "gifsicle";
const jpegoptim = "jpegoptim";
const oxipng = "oxipng";
const pngquant = "pngquant";

const paremiesDirectory = path.join(__dirname, "../src/images/paremies");
const paremiesTargetDirectory = path.join(__dirname, "../docroot/img/imatges");
const cobertesDirectory = path.join(__dirname, "../src/images/cobertes");
const cobertesTargetDirectory = path.join(__dirname, "../docroot/img/obres");

const isCommandAvailable = (command) => {
    const result = spawnSync(command, ["--version"], { stdio: "ignore" });
    return result.status === 0;
};

const OXIPNG_AVAILABLE = isCommandAvailable(oxipng);
if (!OXIPNG_AVAILABLE) {
    console.log("oxipng is not available, skipping it for PNG optimization.");
}

const fileSizeDifferenceBelowThreshold = function (file1, file2, threshold = SIZE_THRESHOLD) {
    const size1 = fs.statSync(file1).size;
    const size2 = fs.statSync(file2).size;
    return size1 - threshold <= size2;
};

const resizeImage = async function (sourceFile, targetFile, width) {
    try {
        const metadata = await sharp(sourceFile).metadata();

        if (metadata.width > width) {
            await sharp(sourceFile).resize({ width }).toFile(targetFile);
        }
    } catch (error) {
        console.error(`Error while resizing ${sourceFile}: ${error.message}`);
    }

    if (!fs.existsSync(targetFile) || fileSizeDifferenceBelowThreshold(sourceFile, targetFile)) {
        // Restore original file.
        fs.copyFileSync(sourceFile, targetFile);
    }
};

const createAvifImage = function (sourceFile, targetFile, width) {
    const targetFileAvif = path.format({
        dir: path.dirname(targetFile),
        ext: ".avif",
        name: path.basename(targetFile, path.extname(targetFile)),
    });

    // Process file only once.
    if (fs.existsSync(targetFileAvif)) {
        return;
    }

    sharp(sourceFile)
        .resize({ width, withoutEnlargement: true })
        .toFormat("avif")
        .toFile(targetFileAvif)
        .then(() => {
            if (fileSizeDifferenceBelowThreshold(sourceFile, targetFileAvif)) {
                fs.unlinkSync(targetFileAvif);
            }
        })
        .catch((error) => {
            console.error(`Error while processing ${sourceFile} to AVIF: ${error.message}`);
        });
};

const processPng = function (sourceFile, targetFile, width) {
    resizeImage(sourceFile, targetFile, width).then(() => {
        execFileSync(pngquant, [
            "--skip-if-larger",
            `--quality=${PNG_MIN_QUALITY}-${PNG_MAX_QUALITY}`,
            "--ext=.png",
            "--force",
            targetFile,
        ]);

        if (OXIPNG_AVAILABLE) {
            execFileSync(oxipng, ["--quiet", "-o3", "--strip", "safe", "--zopfli", targetFile]);
        }

        createAvifImage(sourceFile, targetFile, width);
    });
};

const processJpg = function (sourceFile, targetFile, width) {
    resizeImage(sourceFile, targetFile, width).then(() => {
        execFileSync(jpegoptim, ["--strip-all", "--quiet", targetFile]);
        createAvifImage(sourceFile, targetFile, width);
    });
};

const processGif = function (sourceFile, targetFile) {
    execFileSync(gifsicle, ["--no-warnings", "-O3", "--output", targetFile, sourceFile]);

    if (!fs.existsSync(targetFile) || fs.statSync(sourceFile).size <= fs.statSync(targetFile).size) {
        // Restore original file.
        fs.copyFileSync(sourceFile, targetFile);
    }

    // TODO: consider using AVIF, although animation support is limited (does not work on iPhone)
    // and special care needs to be taken to keep the alpha channel.
    const targetFileWebp = path.format({
        dir: path.dirname(targetFile),
        ext: ".webp",
        name: path.basename(targetFile, path.extname(targetFile)),
    });

    // Process file only once.
    if (fs.existsSync(targetFileWebp)) {
        return;
    }

    execFileSync(gif2webp, ["-q", "100", "-mt", "-m", "6", "-o", targetFileWebp, targetFile]);

    if (fileSizeDifferenceBelowThreshold(targetFile, targetFileWebp)) {
        fs.unlinkSync(targetFileWebp);

        // Try again with lossy compression.
        execFileSync(gif2webp, ["-mt", "-m", "6", "-lossy", "-o", targetFileWebp, targetFile]);

        if (fileSizeDifferenceBelowThreshold(targetFile, targetFileWebp)) {
            fs.unlinkSync(targetFileWebp);
        }
    }
};

const resizeAndOptimizeImagesBulk = function (sourceDirectory, targetDirectory, width) {
    if (!fs.existsSync(targetDirectory)) {
        fs.mkdirSync(targetDirectory, { recursive: true });
    }

    const files = fs.readdirSync(sourceDirectory);
    for (const file of files) {
        if (file === ".picasa.ini") {
            continue;
        }

        const sourceFile = path.join(sourceDirectory, file);
        const targetFile = path.join(targetDirectory, file);

        // Process file only once.
        if (fs.existsSync(targetFile)) {
            continue;
        }

        const extension = path.extname(file).toLowerCase();
        switch (extension) {
            case ".gif": {
                processGif(sourceFile, targetFile);
                break;
            }
            case ".jpg": {
                processJpg(sourceFile, targetFile, width);
                break;
            }
            case ".png": {
                processPng(sourceFile, targetFile, width);
                break;
            }
        }
    }
};

const deleteUnusedImages = function (targetDirectory, sourceDirectory) {
    const targetFiles = fs.readdirSync(targetDirectory);
    const sourceFiles = fs.readdirSync(sourceDirectory);

    // Create a set of base filenames (without extension) from the source directory.
    const sourceFileSet = new Set(sourceFiles.map((file) => path.basename(file, path.extname(file))));

    for (const targetFile of targetFiles) {
        const targetFileBaseName = path.basename(targetFile, path.extname(targetFile));

        // If the file doesn't exist in the source directory, delete it.
        if (!sourceFileSet.has(targetFileBaseName)) {
            const targetFilePath = path.join(targetDirectory, targetFile);
            fs.unlinkSync(targetFilePath);
            console.log(`Deleted: ${targetFilePath}`);
        }
    }
};

const main = async function () {
    await resizeAndOptimizeImagesBulk(cobertesDirectory, cobertesTargetDirectory, IMAGE_WIDTH);
    await resizeAndOptimizeImagesBulk(paremiesDirectory, paremiesTargetDirectory, IMAGE_WIDTH);

    // Delete images not present in the source directories.
    deleteUnusedImages(cobertesTargetDirectory, cobertesDirectory);
    deleteUnusedImages(paremiesTargetDirectory, paremiesDirectory);
};

main().catch((error) => {
    console.error(error);
});
