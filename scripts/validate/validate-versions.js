#!/usr/bin/env node
/**
 * Checks that Docker images and GitHub release pins are up to date.
 *
 * Docker tags are compared by digest against a reference tag (for example,
 * `latest` or `lts`). GitHub release pins are compared against the latest
 * release tag. This script never changes any file.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import fs from "node:fs";
import path from "node:path";

const rootDirectory = path.join(import.meta.dirname, "../..");
const filePatterns = [".docker/*Dockerfile", "docker-compose*.yml"];

const dockerRules = [
  {
    matchImages: ["alpine"],
    matchFiles: [".docker/fpm.Dockerfile"],
    referenceTag: "latest",
  },
  {
    matchImages: ["mariadb"],
    referenceTag: "lts",
  },
  {
    matchImages: ["php"],
    matchArgs: ["PHP_IMAGE_TAG"],
    referenceTag: "apache",
  },
  {
    matchImages: ["nginx"],
    referenceTag: "mainline-alpine-slim",
  },
  {
    matchImages: ["frankenphp"],
    matchFiles: [".docker/frankenphp.Dockerfile"],
    referenceTag: "alpine",
  },
];

const githubReleaseRules = [
  {
    owner: "mlocati",
    repo: "docker-php-extension-installer",
    pins: [
      {
        path: ".docker/dev.Dockerfile",
        line: "ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION={version}",
      },
    ],
  },
];

const versionTagPattern = /^\d/u;
const composeFilePattern = /(?:^|[/\\])(?:docker-)?compose[^/\\]*\.ya?ml$/iu;
const dockerfileNamePattern = /(?:^|[/\\])(?:Dockerfile(?:\.[^/\\]+)?|[^/\\]+\.Dockerfile|Containerfile)$/iu;
const dockerArchitecture =
  {
    arm: "arm",
    arm64: "arm64",
    ia32: "386",
    ppc64: "ppc64le",
    s390x: "s390x",
    x64: "amd64",
  }[process.arch] ?? process.arch;

const dockerDigestCache = new Map();
const githubReleaseCache = new Map();

/**
 * Expands simple glob patterns such as `.docker/*Dockerfile`.
 */
const expandFilePatterns = (patterns) => {
  const files = new Set();

  for (const pattern of patterns) {
    const absolute = path.join(rootDirectory, pattern);
    const directory = path.dirname(absolute);
    const base = path.basename(absolute);
    const starIndex = base.indexOf("*");

    if (starIndex === -1) {
      if (fs.existsSync(absolute)) {
        files.add(absolute);
      }
      continue;
    }

    const prefix = base.slice(0, starIndex);
    const suffix = base.slice(starIndex + 1);
    for (const name of fs.readdirSync(directory)) {
      if (name.startsWith(prefix) && name.endsWith(suffix)) {
        files.add(path.join(directory, name));
      }
    }
  }

  return [...files].sort();
};

const parseImageReference = (reference) => {
  const trimmed = reference.trim().replace(/^["']|["']$/gu, "");
  if (!trimmed || trimmed.startsWith("$") || trimmed.includes("${")) {
    return undefined;
  }

  const withoutDigest = trimmed.split("@")[0] ?? trimmed;
  const lastSlash = withoutDigest.lastIndexOf("/");
  const lastColon = withoutDigest.lastIndexOf(":");
  if (lastColon === -1 || lastColon < lastSlash) {
    return undefined;
  }

  const image = withoutDigest.slice(0, lastColon);
  const tag = withoutDigest.slice(lastColon + 1);
  if (!image || !tag) {
    return undefined;
  }

  return { image, tag };
};

/**
 * Extracts pinned image tags from `FROM` lines and `image:` lines.
 */
const scanDockerfile = (file, content) => {
  const pins = [];
  const args = new Map();

  for (const line of content.split("\n")) {
    const trimmed = line.trim();

    const argMatch = /^ARG\s+(?<name>[A-Za-z_][A-Za-z0-9_]*)=(?<value>\S+)\s*$/u.exec(trimmed);
    if (argMatch?.groups?.name && argMatch.groups.value) {
      args.set(argMatch.groups.name, argMatch.groups.value);
      continue;
    }

    const fromMatch = /^FROM(?:\s+--platform=\S+)?\s+(?<ref>\S+)(?:\s+AS\s+\S+)?\s*$/iu.exec(trimmed);
    const ref = fromMatch?.groups?.ref;
    if (!ref) {
      continue;
    }

    const variableMatch = /^(?<image>[^:$]+):\$\{(?<arg>[A-Za-z_][A-Za-z0-9_]*)\}$/u.exec(ref);
    if (variableMatch?.groups?.image && variableMatch.groups.arg) {
      const value = args.get(variableMatch.groups.arg);
      if (value) {
        pins.push({
          file,
          image: variableMatch.groups.image,
          tag: value,
          argName: variableMatch.groups.arg,
        });
      }
      continue;
    }

    const parsed = parseImageReference(ref);
    if (parsed) {
      pins.push({ file, ...parsed });
    }
  }

  return pins;
};

const scanCompose = (file, content) => {
  const pins = [];

  for (const line of content.split("\n")) {
    const match = /^\s*image:\s*(?<ref>\S+)\s*$/u.exec(line);
    const ref = match?.groups?.ref;
    if (!ref) {
      continue;
    }

    const parsed = parseImageReference(ref);
    if (parsed) {
      pins.push({ file, ...parsed });
    }
  }

  return pins;
};

const readPins = () => {
  const pins = [];

  for (const file of expandFilePatterns(filePatterns)) {
    const content = fs.readFileSync(file, "utf8");
    if (composeFilePattern.test(file)) {
      pins.push(...scanCompose(file, content));
    } else if (dockerfileNamePattern.test(file)) {
      pins.push(...scanDockerfile(file, content));
    }
  }

  return pins;
};

const imageMatches = (image, pattern) => image === pattern || image.split("/").pop() === pattern;

const ruleMatchesPin = (pin, rule) => {
  if (rule.matchFiles?.length && !rule.matchFiles.some((fragment) => pin.file.includes(fragment))) {
    return false;
  }

  if (rule.matchArgs?.length && (pin.argName === undefined || !rule.matchArgs.includes(pin.argName))) {
    return false;
  }

  if (rule.matchImages?.length && !rule.matchImages.some((pattern) => imageMatches(pin.image, pattern))) {
    return false;
  }

  return true;
};

const getTagDigest = (image, tag) => {
  const key = `${image}:${tag}`;
  const cached = dockerDigestCache.get(key);
  if (cached) {
    return cached;
  }

  const promise = (async () => {
    const repository = image.replace(/^docker\.io\//u, "");
    const repositoryPath = repository.includes("/") ? repository : `library/${repository}`;
    const url = `https://hub.docker.com/v2/repositories/${repositoryPath}/tags/${encodeURIComponent(tag)}/`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`Docker Hub returned ${response.status} for ${image}:${tag}`);
    }

    const data = await response.json();
    const architecture = data.images?.find(
      (entry) => entry.architecture === dockerArchitecture && entry.os === "linux",
    );
    return architecture?.digest ?? data.digest ?? "";
  })();

  dockerDigestCache.set(key, promise);
  return promise;
};

const relativePath = (file) => path.relative(rootDirectory, file);

const formatFiles = (files) => [...files].sort().map(relativePath).join(", ");

const checkDockerPins = async (pins) => {
  const groups = new Map();
  const problems = [];

  for (const pin of pins) {
    if (!versionTagPattern.test(pin.tag)) {
      continue;
    }

    const rule = dockerRules.find((candidate) => ruleMatchesPin(pin, candidate));
    if (!rule) {
      problems.push(`${relativePath(pin.file)}: no reference tag configured for ${pin.image}:${pin.tag}`);
      continue;
    }

    const key = `${pin.image}:${pin.tag}|${rule.referenceTag}`;
    const group = groups.get(key);
    if (group) {
      group.files.add(pin.file);
    } else {
      groups.set(key, { files: new Set([pin.file]), pin, rule });
    }
  }

  let checked = 0;
  for (const { files, pin, rule } of groups.values()) {
    try {
      const [currentDigest, referenceDigest] = await Promise.all([
        getTagDigest(pin.image, pin.tag),
        getTagDigest(pin.image, rule.referenceTag),
      ]);

      checked += 1;
      if (!currentDigest || !referenceDigest || currentDigest !== referenceDigest) {
        problems.push(
          `${pin.image}:${pin.tag} does not match ${pin.image}:${rule.referenceTag} (used in ${formatFiles(files)})`,
        );
      }
    } catch (error) {
      problems.push(`${formatFiles(files)}: ${error.message}`);
    }
  }

  return { checked, problems };
};

const getLatestGithubRelease = (owner, repo) => {
  const key = `${owner}/${repo}`;
  const cached = githubReleaseCache.get(key);
  if (cached) {
    return cached;
  }

  const promise = (async () => {
    const headers = {
      "Accept": "application/vnd.github+json",
      "User-Agent": "pccd-version-check",
    };
    const token = process.env.GITHUB_TOKEN ?? process.env.GH_TOKEN;
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`https://api.github.com/repos/${owner}/${repo}/releases/latest`, {
      headers,
    });
    if (!response.ok) {
      throw new Error(`GitHub returned ${response.status} for ${owner}/${repo}`);
    }

    const data = await response.json();
    if (!data.tag_name) {
      throw new Error(`No tag_name in latest release for ${owner}/${repo}`);
    }

    return data.tag_name.replace(/^v(?=\d)/u, "");
  })();

  githubReleaseCache.set(key, promise);
  return promise;
};

const escapeRegularExpression = (value) => value.replace(/[.*+?^${}()|[\]\\]/gu, "\\$&");

const readGithubPin = (file, lineTemplate) => {
  const absolute = path.join(rootDirectory, file);
  if (!fs.existsSync(absolute)) {
    throw new Error(`file not found: ${file}`);
  }

  const content = fs.readFileSync(absolute, "utf8");
  const pattern = lineTemplate
    .split("{version}")
    .map((part) => escapeRegularExpression(part))
    .join(String.raw`(\d+\.\d+\.\d+)`);
  const match = new RegExp(pattern, "u").exec(content);
  if (!match?.[1]) {
    throw new Error(`could not find the pinned version in ${file}`);
  }

  return match[1];
};

const checkGithubPins = async () => {
  const problems = [];
  let checked = 0;

  for (const release of githubReleaseRules) {
    let latest;
    try {
      latest = await getLatestGithubRelease(release.owner, release.repo);
    } catch (error) {
      problems.push(`${release.owner}/${release.repo}: ${error.message}`);
      continue;
    }

    for (const pin of release.pins) {
      try {
        const current = readGithubPin(pin.path, pin.line);
        checked += 1;
        if (current !== latest) {
          problems.push(`${pin.path}: ${release.owner}/${release.repo} is ${current}, latest is ${latest}`);
        }
      } catch (error) {
        problems.push(`${pin.path}: ${error.message}`);
      }
    }
  }

  return { checked, problems };
};

const main = async () => {
  const dockerResult = await checkDockerPins(readPins());
  const githubResult = await checkGithubPins();
  const problems = [...dockerResult.problems, ...githubResult.problems];
  const checked = dockerResult.checked + githubResult.checked;

  for (const problem of problems) {
    console.error(`ERROR: ${problem}`);
  }

  if (problems.length > 0) {
    console.error(`${problems.length} outdated or invalid version pin(s) found (${checked} checked).`);
    process.exitCode = 1;
    return;
  }

  if (checked === 0) {
    console.error("No version pins were checked.");
    process.exitCode = 1;
    return;
  }

  console.log(`All ${checked} version pins match their reference tags.`);
};

await main();
