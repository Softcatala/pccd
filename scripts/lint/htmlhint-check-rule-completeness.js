#!/usr/bin/env node

import config from "../../.htmlhintrc.json" with { type: "json" };
import console from "node:console";
import process from "node:process";
import { HTMLHint } from "htmlhint";

try {
  const configuredRules = Object.keys(config).toSorted();

  // Get all available htmlhint rules
  const availableRules = Object.keys(HTMLHint.rules).toSorted();

  // Find missing rules
  const missingRules = availableRules.filter(
    (rule) => !Object.hasOwn(config, rule),
  );

  // Find extra rules (shouldn't happen, but good to check)
  const extraRules = configuredRules.filter(
    (rule) => !availableRules.includes(rule),
  );

  // Report results
  if (missingRules.length === 0 && extraRules.length === 0) {
    console.log("✅ All HTMLHint rules are configured");
    console.log(
      `📊 ${configuredRules.length}/${availableRules.length} rules configured`,
    );
  } else {
    if (missingRules.length > 0) {
      console.log(`❌ Missing ${missingRules.length} HTMLHint rules:`);
      for (const rule of missingRules) {
        console.log(`   - ${rule}`);
      }
    }

    if (extraRules.length > 0) {
      console.log(`⚠️  ${extraRules.length} unknown rules in config:`);
      for (const rule of extraRules) {
        console.log(`   - ${rule}`);
      }
    }

    console.log(
      `📊 ${configuredRules.length - extraRules.length}/${availableRules.length} valid rules configured`,
    );
  }
} catch (error) {
  console.log("❌ Error checking HTMLHint rule completeness:", error.message);
  process.exit(1);
}
