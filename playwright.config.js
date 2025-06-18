const { defineConfig } = require("@playwright/test");
require("dotenv").config();

module.exports = defineConfig({
    use: {
        baseURL: process.env.BASE_URL,
    },
});
