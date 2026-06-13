import { defineConfig } from "@playwright/test";
import process from "node:process";

process.loadEnvFile();
if (!process.env.BASE_URL) {
  throw new Error("BASE_URL variable is not set.");
}

export default defineConfig({
  use: {
    baseURL: process.env.BASE_URL,
    browserName: "chromium",
  },
});
