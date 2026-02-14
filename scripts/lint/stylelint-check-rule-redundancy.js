#!/usr/bin/env node
/**
 * Checks for redundant Stylelint rules already defined in presets.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import console from "node:console";
import process from "node:process";
import stylelint from "stylelint";

import localConfig from "../../.stylelintrc.json" with { type: "json" };

try {
  const customRules = localConfig.rules || {};
  const customRuleNames = Object.keys(customRules);

  if (customRuleNames.length === 0) {
    console.log("No custom rules defined to check for redundancy");
    process.exit(0);
  }

  // Get the extended config without custom rules.
  // Create a temporary config with only the extends, no custom rules.
  const extendsOnlyConfig = {
    extends: localConfig.extends || [],
  };

  const resolvedExtendsConfig = await stylelint.resolveConfig(".", {
    config: extendsOnlyConfig,
  });

  if (!resolvedExtendsConfig) {
    console.log("Could not resolve extended stylelint config");
    process.exit(1);
  }

  const extendedRules = resolvedExtendsConfig.rules || {};

  // Find redundant rules.
  const redundant = customRuleNames.filter((ruleName) => {
    const customValue = customRules[ruleName];
    const extendedValue = extendedRules[ruleName];

    if (typeof extendedValue !== "undefined") {
      // Normalize values for comparison (stylelint accepts both array and scalar formats).
      const normalizeValue = (value) => {
        if (Array.isArray(value) && value.length === 1) {
          return value[0];
        }

        return value;
      };

      const normalizedCustom = normalizeValue(customValue);
      const normalizedExtended = normalizeValue(extendedValue);

      // Compare rule values.
      const customString = JSON.stringify(customValue);
      const extendedString = JSON.stringify(extendedValue);
      const normalizedCustomString = JSON.stringify(normalizedCustom);
      const normalizedExtendedString = JSON.stringify(normalizedExtended);

      if (customString === extendedString) {
        console.log(`${ruleName}: identical to extended config (${extendedString})`);
        return true;
      }

      if (normalizedCustomString === normalizedExtendedString && customString !== extendedString) {
        console.log(
          `${ruleName}: semantically identical to extended config (extended: ${extendedString}, yours: ${customString})`,
        );
        return true;
      }

      console.log(`${ruleName}: overrides extended config (extended: ${extendedString}, yours: ${customString})`);
    }

    return false;
  });

  if (redundant.length === 0) {
    console.log("No redundant stylelint rules found");
    console.log(`${customRuleNames.length} custom rules checked`);
  } else {
    console.log(`${redundant.length} redundant rules found`);
    process.exit(1);
  }
} catch (error) {
  console.log("Error checking stylelint rule redundancy:", error.message);
  process.exit(1);
}
