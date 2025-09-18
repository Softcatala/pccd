#!/usr/bin/env node

import localConfig from "../../.htmlvalidate.json" with { type: "json" };
import https from "node:https";
import { execSync } from "node:child_process";
import console from "node:console";
import process from "node:process";

// Function to get installed html-validate version
const getHtmlValidateVersion = () => {
  try {
    const version = execSync("npm pkg get devDependencies.html-validate", {
      encoding: "utf8",
    })
      .trim()
      .replaceAll('"', "");

    if (version === "{}" || !version) {
      throw new Error("html-validate not found in devDependencies");
    }

    return version;
  } catch (error) {
    throw new Error(`Failed to get html-validate version: ${error.message}`);
  }
};

// Function to fetch preset rules from GitHub
const fetchPresetRules = (presetName) => {
  const version = getHtmlValidateVersion();
  const url = `https://raw.githubusercontent.com/html-validate/html-validate/refs/tags/v${version}/src/config/presets/${presetName}.ts`;

  return new Promise((resolve, reject) => {
    https
      .get(url, (response) => {
        let data = "";
        response.on("data", (chunk) => {
          data += chunk;
        });
        response.on("end", () => {
          try {
            // Extract the rules object from the TypeScript file
            const rulesMatch = data.match(
              /rules:\s*\{([\s\S]*?)\}\s*(?:,\s*)?\}/,
            );
            if (!rulesMatch) {
              reject(
                new Error(
                  `Could not find rules object in ${presetName} preset`,
                ),
              );
              return;
            }

            // Convert TypeScript rules to JavaScript object
            const rulesContent = rulesMatch[1];
            // Simple parsing - convert TS object literal to JSON-like format
            const rulesString = `{${rulesContent}}`;

            // Parse the TypeScript object literal into JSON
            // Replace single quotes with double quotes and handle TypeScript syntax
            const jsonString = rulesString
              .replaceAll("'", '"')
              .replaceAll(/(\w+):/g, '"$1":')
              .replaceAll(/,\s*\}/g, "}");

            const rules = JSON.parse(jsonString);

            resolve(rules);
          } catch (error) {
            reject(
              new Error(
                `Failed to parse rules from ${presetName}: ${error.message}`,
              ),
            );
          }
        });
      })
      .on("error", (error) => {
        reject(
          new Error(`Failed to fetch ${presetName} preset: ${error.message}`),
        );
      });
  });
};

try {
  const customRules = localConfig.rules || {};
  const customRuleNames = Object.keys(customRules);

  if (customRuleNames.length === 0) {
    console.log("✅ No custom rules defined to check for redundancy");
    process.exit(0);
  }

  // Get extended rulesets
  const extendsConfig = localConfig.extends || [];

  // Extract preset names from extends (remove html-validate: prefix)
  const presetNames = extendsConfig
    .filter((extension) => extension.startsWith("html-validate:"))
    .map((extension) => extension.replace("html-validate:", ""));

  if (presetNames.length === 0) {
    console.log("✅ No html-validate presets to check against");
    process.exit(0);
  }

  console.log(`🔍 Fetching rules from presets: ${presetNames.join(", ")}`);

  // Fetch all preset rules
  const extendedRules = {};
  const presetSources = {};

  for (const presetName of presetNames) {
    try {
      const presetRules = await fetchPresetRules(presetName);

      // Track which preset each rule comes from
      for (const ruleName of Object.keys(presetRules)) {
        if (!presetSources[ruleName]) {
          presetSources[ruleName] = [];
        }
        presetSources[ruleName].push(`html-validate:${presetName}`);
      }

      // Merge rules (later presets override earlier ones)
      Object.assign(extendedRules, presetRules);
    } catch (error) {
      console.log(`⚠️  Warning: ${error.message}`);
    }
  }

  // Find redundant rules
  const redundant = customRuleNames.filter((ruleName) => {
    const customValue = customRules[ruleName];
    const extendedValue = extendedRules[ruleName];

    if (extendedValue !== undefined) {
      const sources = presetSources[ruleName] || [];
      const sourceText = sources.join(" and ");

      // Compare rule values
      const customString = JSON.stringify(customValue);
      const extendedString = JSON.stringify(extendedValue);

      if (customString === extendedString) {
        console.log(
          `❌ ${ruleName}: identical to ${sourceText} (${extendedString})`,
        );
        return true;
      }

      console.log(
        `⚠️  ${ruleName}: overrides ${sourceText} (extended: ${extendedString}, yours: ${customString})`,
      );
    }
    return false;
  });

  if (redundant.length === 0) {
    console.log("✅ No redundant html-validate rules found");
    console.log(
      `📊 ${customRuleNames.length} custom rules checked against ${Object.keys(extendedRules).length} extended rules`,
    );
  } else {
    console.log(`❌ ${redundant.length} redundant rules found`);
  }
} catch (error) {
  console.log(
    "❌ Error checking html-validate rule redundancy:",
    error.message,
  );
  process.exit(1);
}
