#!/usr/bin/env node
/**
 * Checks the expiration date in the production certificate.
 *
 * Usage:
 *   ./validate-ssl-expiration.js [ENVIRONMENT_URL] [IP]
 *
 * The website URL and IP address can be passed as arguments.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

import { URL } from "node:url";
import console from "node:console";
import https from "node:https";
import process from "node:process";

const DAYS_IN_MILLISECONDS = 24 * 60 * 60 * 1000;
const WARNING_DAYS = 10;
const EXPECTED_ARGUMENTS = 2;

// Load .env file if it exists.
try {
  process.loadEnvFile();
} catch {
  // .env file doesn't exist or couldn't be read
}

/**
 * Shows the usage of this command.
 */
const usage = () => {
  console.log("Usage: ./validate-ssl-expiration.js [ENVIRONMENT_URL] [IP]\n");
  console.log("Optional arguments:");
  console.log("  ENVIRONMENT_URL       The website URL, without trailing slash (default: https://pccd.dites.cat)");
  console.log(
    "  IP                    The IP address to connect to. Use when you want to resolve the domain to a specific IP.",
  );
};

/**
 * Gets the SSL certificate expiration date.
 */
const getCertificateExpiration = (url, ip) =>
  new Promise((resolve, reject) => {
    const parsedUrl = new URL(url);
    const options = {
      host: ip || parsedUrl.hostname,
      port: 443,
      method: "HEAD",
      servername: parsedUrl.hostname,
    };

    const request = https.request(options, (response) => {
      const certificate = response.socket.getPeerCertificate();
      if (certificate && certificate.valid_to) {
        resolve(new Date(certificate.valid_to));
      } else {
        reject(new Error("Could not get certificate"));
      }
      response.resume();
    });

    request.on("error", (error) => {
      reject(error);
    });

    request.end();
  });

// Main execution.
const userArguments = process.argv.slice(EXPECTED_ARGUMENTS);

if (userArguments.length > EXPECTED_ARGUMENTS) {
  usage();
  process.exit(1);
}

const remoteEnvironmentUrl = userArguments[0] || "https://pccd.dites.cat";
let originIp = userArguments[1];

if (originIp === "origin") {
  originIp = process.env.ORIGIN_IP;
}

// Get the expiration date of the certificate.
const expirationDate = await getCertificateExpiration(remoteEnvironmentUrl, originIp);

// Calculate the difference in days.
const currentDate = new Date();
currentDate.setHours(0, 0, 0, 0);
const expirationDateDays = Math.floor((expirationDate - currentDate) / DAYS_IN_MILLISECONDS);

// Color output based on days remaining.
const isWarning = expirationDateDays < WARNING_DAYS;
const color = isWarning ? "\u001B[0;31m" : "\u001B[0;32m";
const nc = "\u001B[0m";

const location = originIp ? `${remoteEnvironmentUrl} (${originIp})` : remoteEnvironmentUrl;
console.log(`${color}${location} certificate expires in ${expirationDateDays} days.${nc}`);

process.exit(isWarning ? 1 : 0);
