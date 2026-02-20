import process, { loadEnvFile } from "node:process";
import { defineConfig } from "@playwright/test";

loadEnvFile();

export default defineConfig({
  use: {
    baseURL: process.env.BASE_URL,
    browserName: "chromium",
  },
});
