#!/usr/bin/env node
/**
 * Checks that the npm-based lint scripts run in the GitLab CI npm template.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import fs from "node:fs";
import path from "node:path";

import packageJson from "../../package.json" with { type: "json" };

const projectDirectory = process.cwd();
const ciFile = fs.readFileSync(path.join(projectDirectory, ".gitlab-ci.yml"), "utf8");

const getPackageBinaries = (packageName) => {
  const packageFile = path.join(projectDirectory, "node_modules", packageName, "package.json");
  const packageManifest = JSON.parse(fs.readFileSync(packageFile, "utf8"));

  if (typeof packageManifest.bin === "string") {
    return [packageName.split("/").pop()];
  }

  return Object.keys(packageManifest.bin || {});
};

const dependencyNames = [
  ...Object.keys(packageJson.dependencies || {}),
  ...Object.keys(packageJson.devDependencies || {}),
];
const npmBinaries = new Set(dependencyNames.flatMap((packageName) => getPackageBinaries(packageName)));

const npmLintScripts = Object.entries(packageJson.scripts || {})
  .filter(([name, command]) => {
    if (typeof command !== "string" || !name.startsWith("lint:")) {
      return false;
    }

    const executable = command.trim().split(/\s/, 1)[0];
    return executable === "node" || npmBinaries.has(executable);
  })
  .map(([name]) => name);

const ciLines = ciFile.split("\n");
const templateStart = ciLines.indexOf(".check_code_npm_template:");
if (templateStart === -1) {
  console.error("Could not find .check_code_npm_template in .gitlab-ci.yml");
  process.exit(1);
}

const templateLines = [];
for (const line of ciLines.slice(templateStart)) {
  if (templateLines.length > 0 && /^\S/.test(line)) {
    break;
  }
  templateLines.push(line);
}

const ciLintScripts = new Set(
  templateLines
    .join("\n")
    .matchAll(/^\s+- npm run (lint:[a-z0-9-]+)\s*$/gmu)
    .map((match) => match[1]),
);
const missingScripts = npmLintScripts.filter((script) => !ciLintScripts.has(script));

if (missingScripts.length > 0) {
  console.error("These npm-based lint scripts are missing from .check_code_npm_template:");
  for (const script of missingScripts) {
    console.error(`- ${script}`);
  }
  process.exit(1);
}

console.log(`All ${npmLintScripts.length} npm-based lint scripts run in .check_code_npm_template`);
