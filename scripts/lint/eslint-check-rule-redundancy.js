#!/usr/bin/env node

import console from "node:console";
import { readFile } from "node:fs/promises";
import process from "node:process";
import js from "@eslint/js";
import regexp from "eslint-plugin-regexp";
import unicorn from "eslint-plugin-unicorn";

const checkRuleRedundancy = async function (configPath = "./eslint.config.js") {
  // Read the config file as text to extract only explicitly defined rules
  const configText = await readFile(configPath, "utf8");
  const rulesSection = configText.match(/rules:\s*\{([\s\S]*?)\},?\s*\}/);

  if (!rulesSection) {
    return {
      error: "Could not find rules section in config file",
      redundant: [],
      overrides: [],
    };
  }

  // Extract rule names and values from the text
  const ruleMatches = rulesSection[1].match(/"([^"]+)":\s*([^,\n}]+)/g);
  const customRulesMap = {};
  if (ruleMatches) {
    for (const match of ruleMatches) {
      const [, ruleName, ruleValue] = match.match(/"([^"]+)":\s*([^,\n}]+)/);
      customRulesMap[ruleName] = ruleValue
        .trim()
        .replace(/,$/, "")
        .replaceAll('"', "");
    }
  }
  const customRules = Object.keys(customRulesMap);

  // Get extended configs
  const jsRules = js.configs.recommended.rules || {};
  const regexpRules = regexp.configs["flat/recommended"].rules || {};
  const unicornRules = unicorn.configs.recommended.rules || {};

  const extendedRules = {
    ...jsRules,
    ...regexpRules,
    ...unicornRules,
  };

  const redundant = [];
  const overrides = [];

  for (const rule of customRules) {
    const customValue = customRulesMap[rule];

    // Check which config provides this rule
    let source = "";
    if (Object.hasOwn(jsRules, rule)) {
      source = "@eslint/js recommended";
    } else if (Object.hasOwn(regexpRules, rule)) {
      source = "eslint-plugin-regexp flat/recommended";
    } else if (Object.hasOwn(unicornRules, rule)) {
      source = "eslint-plugin-unicorn recommended";
    }

    if (source) {
      const extendedValue = extendedRules[rule];

      if (JSON.stringify(customValue) === JSON.stringify(extendedValue)) {
        redundant.push({ rule, source, value: extendedValue });
      } else {
        overrides.push({
          rule,
          source,
          extended: extendedValue,
          custom: customValue,
        });
      }
    }
  }

  return { redundant, overrides, error: undefined };
};

// Export for testing
export { checkRuleRedundancy };

// Run when executed directly
if (import.meta.url === `file://${process.argv[1]}`) {
  try {
    const result = await checkRuleRedundancy();

    if (result.error) {
      console.log(`❌ ${result.error}`);
      process.exit(1);
    }

    for (const item of result.redundant) {
      console.log(
        `❌ ${item.rule}: identical to ${item.source} (${JSON.stringify(item.value)})`,
      );
    }

    for (const item of result.overrides) {
      console.log(
        `⚠️  ${item.rule}: overrides ${item.source} (extended: ${JSON.stringify(item.extended)}, yours: ${JSON.stringify(item.custom)})`,
      );
    }

    if (result.redundant.length === 0) {
      console.log("✅ No redundant ESLint rules found");
    } else {
      console.log(`❌ ${result.redundant.length} redundant rules found`);
    }
  } catch (error) {
    console.error(error);
    process.exit(1);
  }
}
