const { defineConfig } = require("@playwright/test");

module.exports = defineConfig({
  testDir: "./tests/playwright",
  timeout: 30_000,
  workers: process.env.CI ? 2 : 4,
  projects: [
    { name: "desktop", testMatch: /smoke\.spec\.js/, use: { browserName: "chromium", viewport: { width: 1440, height: 1000 } } },
    ...["chromium", "webkit"].flatMap(browserName => [360, 390].map(width => ({
      name: `mobile-${browserName}-${width}`,
      testMatch: /(?:mobile-.*|page-refresh)\.spec\.js/,
      use: { browserName, viewport: { width, height: 844 }, isMobile: true, hasTouch: true }
    })))
  ],
  fullyParallel: true,
  retries: process.env.CI ? 2 : 0,
  reporter: process.env.CI ? [["github"], ["html", { open: "never" }]] : "list",
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || "http://localhost:8080",
    trace: "on-first-retry"
  }
});
