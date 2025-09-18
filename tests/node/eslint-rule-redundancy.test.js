import { test, describe } from "node:test";
import assert from "node:assert";
import { mkdir, writeFile, rm } from "node:fs/promises";
import path from "node:path";
import { tmpdir } from "node:os";

import { checkRuleRedundancy } from "../../scripts/lint/eslint-check-rule-redundancy.js";

describe("checkRuleRedundancy", () => {
  let testDirectory = "";

  test.beforeEach(async () => {
    testDirectory = path.join(tmpdir(), "test-eslint-rules-" + Date.now());
    await mkdir(testDirectory, { recursive: true });
  });

  test.afterEach(async () => {
    await rm(testDirectory, { recursive: true, force: true });
  });

  test("should detect redundant rules that match extended configs", async () => {
    const configContent = `
export default [
  js.configs.recommended,
  {
    rules: {
      "no-unused-vars": "error",
      "no-undef": "error"
    }
  }
];`;

    const configPath = path.join(testDirectory, "eslint.config.js");
    await writeFile(configPath, configContent);

    const result = await checkRuleRedundancy(configPath);

    assert.strictEqual(result.error, undefined);
    assert.ok(result.redundant.length > 0);
    assert.ok(result.redundant.some((item) => item.rule === "no-unused-vars"));
  });

  test("should detect rule overrides", async () => {
    const configContent = `
export default [
  js.configs.recommended,
  {
    rules: {
      "no-unused-vars": "warn",
      "custom-rule": "error"
    }
  }
];`;

    const configPath = path.join(testDirectory, "eslint.config.js");
    await writeFile(configPath, configContent);

    const result = await checkRuleRedundancy(configPath);

    assert.strictEqual(result.error, undefined);
    // no-unused-vars should be in overrides since we changed it from "error" to "warn"
    const override = result.overrides.find(
      (item) => item.rule === "no-unused-vars",
    );
    assert.ok(override);
    assert.strictEqual(override.custom, "warn");
  });

  test("should handle config files without rules section", async () => {
    const configContent = `
export default [
  js.configs.recommended
];`;

    const configPath = path.join(testDirectory, "eslint.config.js");
    await writeFile(configPath, configContent);

    const result = await checkRuleRedundancy(configPath);

    assert.strictEqual(
      result.error,
      "Could not find rules section in config file",
    );
    assert.strictEqual(result.redundant.length, 0);
    assert.strictEqual(result.overrides.length, 0);
  });

  test("should handle empty rules section", async () => {
    const configContent = `
export default [
  js.configs.recommended,
  {
    rules: {}
  }
];`;

    const configPath = path.join(testDirectory, "eslint.config.js");
    await writeFile(configPath, configContent);

    const result = await checkRuleRedundancy(configPath);

    assert.strictEqual(result.error, undefined);
    assert.strictEqual(result.redundant.length, 0);
    assert.strictEqual(result.overrides.length, 0);
  });

  test("should handle complex rule configurations", async () => {
    const configContent = `
export default [
  js.configs.recommended,
  {
    rules: {
      "no-unused-vars": ["error", { "argsIgnorePattern": "^_" }],
      "quotes": ["error", "double"]
    }
  }
];`;

    const configPath = path.join(testDirectory, "eslint.config.js");
    await writeFile(configPath, configContent);

    const result = await checkRuleRedundancy(configPath);

    assert.strictEqual(result.error, undefined);
    // These should be overrides since they have different configurations
    const unusedVariablesOverride = result.overrides.find(
      (item) => item.rule === "no-unused-vars",
    );
    assert.ok(unusedVariablesOverride);
  });
});
