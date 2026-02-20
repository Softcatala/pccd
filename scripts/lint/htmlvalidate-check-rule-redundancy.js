#!/usr/bin/env node
/**
 * Checks for redundant html-validate rules already defined in presets.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { configPresets } from "html-validate";
import console from "node:console";
import process from "node:process";

import localConfig from "../../.htmlvalidate.json" with { type: "json" };

const getPresetRules = (presetName) => {
  const presetKey = `html-validate:${presetName}`;
  const preset = configPresets[presetKey];

  if (!preset) {
    throw new Error(`Preset ${presetKey} not found`);
  }

  return preset.rules || {};
};

try {
  const customRules = localConfig.rules || {};
  const customRuleNames = Object.keys(customRules);

  if (customRuleNames.length === 0) {
    console.log("No custom rules defined to check for redundancy");
    process.exit(0);
  }

  // Get extended rulesets.
  const extendsConfig = localConfig.extends || [];

  // Extract preset names from extends (remove html-validate: prefix).
  const presetNames = extendsConfig
    .filter((extension) => extension.startsWith("html-validate:"))
    .map((extension) => extension.replace("html-validate:", ""));

  if (presetNames.length === 0) {
    console.log("No html-validate presets to check against");
    process.exit(0);
  }

  console.log(`Fetching rules from presets: ${presetNames.join(", ")}`);

  // Fetch all preset rules.
  const extendedRules = {};
  const presetSources = {};

  for (const presetName of presetNames) {
    try {
      const presetRules = getPresetRules(presetName);

      // Track which preset each rule comes from.
      for (const ruleName of Object.keys(presetRules)) {
        if (!presetSources[ruleName]) {
          presetSources[ruleName] = [];
        }
        presetSources[ruleName].push(`html-validate:${presetName}`);
      }

      // Merge rules (later presets override earlier ones).
      Object.assign(extendedRules, presetRules);
    } catch (error) {
      console.log(`Warning: ${error.message}`);
    }
  }

  // Find redundant rules.
  const redundant = customRuleNames.filter((ruleName) => {
    const customValue = customRules[ruleName];
    const extendedValue = extendedRules[ruleName];

    if (typeof extendedValue !== "undefined") {
      const sources = presetSources[ruleName] || [];
      const sourceText = sources.join(" and ");

      // Compare rule values.
      const customString = JSON.stringify(customValue);
      const extendedString = JSON.stringify(extendedValue);

      if (customString === extendedString) {
        console.log(`${ruleName}: identical to ${sourceText} (${extendedString})`);
        return true;
      }

      console.log(`${ruleName}: overrides ${sourceText} (extended: ${extendedString}, yours: ${customString})`);
    }

    return false;
  });

  if (redundant.length === 0) {
    console.log("No redundant html-validate rules found");
    console.log(
      `${customRuleNames.length} custom rules checked against ${Object.keys(extendedRules).length} extended rules`,
    );
  } else {
    console.log(`${redundant.length} redundant rules found`);
    process.exit(1);
  }
} catch (error) {
  console.log("Error checking html-validate rule redundancy:", error.message);
  process.exit(1);
}
