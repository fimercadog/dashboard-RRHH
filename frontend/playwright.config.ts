import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: false,
  retries: 0,
  workers: 1,
  reporter: "list",
  use: {
    baseURL: "http://localhost:3000",
    trace: "on-first-retry",
    screenshot: "only-on-failure",
  },
  projects: [
    {
      name: "setup",
      testMatch: /auth\.setup\.ts/,
    },
    {
      name: "chromium",
      use: {
        ...devices["Desktop Chrome"],
        storageState: "tests/e2e/.auth/admin.json",
      },
      dependencies: ["setup"],
      testIgnore: /auth\.setup\.ts|permissions\.spec\.ts/,
    },
    {
      name: "chromium-permissions",
      use: { ...devices["Desktop Chrome"] },
      testMatch: /permissions\.spec\.ts/,
    },
    {
      name: "public-web",
      use: { ...devices["Desktop Chrome"] },
      testMatch: /public-web\.spec\.ts/,
    },
  ],
});
