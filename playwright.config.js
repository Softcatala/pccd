import { defineConfig } from "@playwright/test";
import { loadEnvFile } from "node:process";

loadEnvFile();

export default defineConfig({
  use: {
    baseURL: process.env.BASE_URL,
  },
});
