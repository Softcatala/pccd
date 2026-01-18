import { defineConfig } from "@playwright/test";
import process, { loadEnvFile } from "node:process";

loadEnvFile();

export default defineConfig({
  use: {
    baseURL: process.env.BASE_URL,
    browserName: "chromium",
  },
});
