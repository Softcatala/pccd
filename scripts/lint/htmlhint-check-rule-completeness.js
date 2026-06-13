#!/usr/bin/env node
/**
 * Checks if all available HTMLHint rules are configured.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import pkg from "htmlhint";
const { HTMLHint } = pkg;
import console from "node:console";
import process from "node:process";

import config from "../../.htmlhintrc.json" with { type: "json" };

try {
  const configuredRules = Object.keys(config).toSorted();
  const availableRules = Object.keys(HTMLHint.rules).toSorted();
  const missingRules = availableRules.filter((rule) => !Object.hasOwn(config, rule));
  const extraRules = configuredRules.filter((rule) => !availableRules.includes(rule));

  if (missingRules.length === 0 && extraRules.length === 0) {
    console.log("All HTMLHint rules are configured");
    console.log(`${configuredRules.length}/${availableRules.length} rules configured`);
  } else {
    if (missingRules.length > 0) {
      console.log(`Missing ${missingRules.length} HTMLHint rules:`);
      for (const rule of missingRules) {
        console.log(`   - ${rule}`);
      }
    }

    if (extraRules.length > 0) {
      console.log(`${extraRules.length} unknown rules in config:`);
      for (const rule of extraRules) {
        console.log(`   - ${rule}`);
      }
    }

    console.log(`${configuredRules.length - extraRules.length}/${availableRules.length} valid rules configured`);
    process.exit(1);
  }
} catch (error) {
  console.log("Error checking HTMLHint rule completeness:", error.message);
  process.exit(1);
}
