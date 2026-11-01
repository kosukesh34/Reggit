import { defineConfig, devices } from '@playwright/test';

// Component checks run in CI; application flows use a seeded local environment.
export default defineConfig({
  testDir: './review',
  workers: 1,
  retries: 0,
  reporter: 'list',
  outputDir: 'test-results/security-review',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8090',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  projects: [{ name: 'chromium', use: {
    ...devices['Desktop Chrome'],
    ...(process.env.E2E_BROWSER_CHANNEL ? { channel: process.env.E2E_BROWSER_CHANNEL } : {}),
  } }],
});
