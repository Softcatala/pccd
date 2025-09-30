import { test, describe } from "node:test";
import assert from "node:assert";
import { Buffer } from "node:buffer";
import { mkdir, writeFile, rm } from "node:fs/promises";
import path from "node:path";
import { tmpdir } from "node:os";
import process from "node:process";

// Import the function we want to test
// Note: We'll need to refactor the original file to export the function
import { findDuplicateImages } from "../../scripts/report-generation/report-duplicate-images.js";

// Skip tests on Node v20 since Object.groupBy is not available
const MIN_NODE_VERSION = 21;
const nodeVersion = Number.parseInt(process.versions.node, 10);
const shouldSkip = nodeVersion < MIN_NODE_VERSION;

describe("findDuplicateImages", { skip: shouldSkip }, () => {
  let testDirectory = "";

  // Setup: Create a temporary directory for testing
  test.beforeEach(async () => {
    testDirectory = path.join(tmpdir(), "test-duplicate-images-" + Date.now());
    await mkdir(testDirectory, { recursive: true });
  });

  // Cleanup: Remove test directory after each test
  test.afterEach(async () => {
    await rm(testDirectory, { recursive: true, force: true });
  });

  test("should detect duplicate images with same content", async () => {
    const imageContent = Buffer.from("fake image content");

    // Create two identical files
    await writeFile(path.join(testDirectory, "image1.jpg"), imageContent);
    await writeFile(path.join(testDirectory, "image2.jpg"), imageContent);

    const result = await findDuplicateImages(testDirectory);

    assert.match(result, /image1\.jpg/);
    assert.match(result, /image2\.jpg/);
  });

  test("should not report unique images as duplicates", async () => {
    const imageContent1 = Buffer.from("fake image content 1");
    const imageContent2 = Buffer.from("fake image content 2");

    // Create two different files
    await writeFile(path.join(testDirectory, "unique1.jpg"), imageContent1);
    await writeFile(path.join(testDirectory, "unique2.jpg"), imageContent2);

    const result = await findDuplicateImages(testDirectory);

    assert.strictEqual(result, "");
  });

  test("should handle empty directory", async () => {
    const result = await findDuplicateImages(testDirectory);

    assert.strictEqual(result, "");
  });

  test("should group multiple duplicates correctly", async () => {
    const content1 = Buffer.from("content A");
    const content2 = Buffer.from("content B");

    // Create two groups of duplicates
    await writeFile(path.join(testDirectory, "a1.jpg"), content1);
    await writeFile(path.join(testDirectory, "a2.jpg"), content1);
    await writeFile(path.join(testDirectory, "b1.jpg"), content2);
    await writeFile(path.join(testDirectory, "b2.jpg"), content2);

    const result = await findDuplicateImages(testDirectory);

    // Should contain both groups
    assert.match(result, /a1\.jpg/);
    assert.match(result, /a2\.jpg/);
    assert.match(result, /b1\.jpg/);
    assert.match(result, /b2\.jpg/);
  });
});
