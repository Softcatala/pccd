#!/usr/bin/env node
/**
 * Checks for redundant ESLint rules already defined in presets.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import js from "@eslint/js";

import config from "../../eslint.config.js";

const presetEntries = new Map([[js.configs.recommended, "js.configs.recommended"]]);

const isEqual = (left, right) => JSON.stringify(left) === JSON.stringify(right);

const previousRules = new Map();
const redundancies = [];

const addGlobalRules = (rules, origin) => {
  for (const [ruleName, ruleValue] of Object.entries(rules)) {
    previousRules.set(ruleName, { value: ruleValue, origin });
  }
};

const describeEntry = (entry, index) =>
  entry.files ? `entry #${index + 1} (files: ${entry.files.join(", ")})` : `entry #${index + 1} (global rules)`;

for (const [index, entry] of config.entries()) {
  if (!entry || !entry.rules) {
    continue;
  }

  const presetOrigin = presetEntries.get(entry);
  if (presetOrigin) {
    if (!entry.files) {
      addGlobalRules(entry.rules, presetOrigin);
    }
    continue;
  }

  const entryLabel = describeEntry(entry, index);
  for (const [ruleName, ruleValue] of Object.entries(entry.rules)) {
    const previous = previousRules.get(ruleName);
    if (previous && isEqual(previous.value, ruleValue)) {
      redundancies.push({
        ruleName,
        entryLabel,
        origin: previous.origin,
      });
    }
  }

  if (!entry.files) {
    addGlobalRules(entry.rules, entryLabel);
  }
}

if (redundancies.length === 0) {
  console.log("No redundant ESLint rules found");
  process.exit(0);
}

console.log(`${redundancies.length} redundant ESLint rules found`);
for (const redundancy of redundancies) {
  console.log(`${redundancy.ruleName}: redundant in ${redundancy.entryLabel} (already set by ${redundancy.origin})`);
}
process.exit(1);
