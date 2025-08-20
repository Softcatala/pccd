const { defineConfig } = require("@playwright/test");
const { loadEnvFile } = require("node:process");

loadEnvFile();

module.exports = defineConfig({
  use: {
    baseURL: process.env.BASE_URL,
  },
});
