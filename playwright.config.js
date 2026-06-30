import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';

// Read from .env file
dotenv.config();

export default defineConfig({
  testDir: './tests',
  // Run tests sequentially to avoid database conflicts and session termination on the same user account
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:8080',
    trace: 'retain-on-failure',
  },
  projects: [
    // Setup phase: log in and save session
    {
      name: 'setup',
      testMatch: /auth\.setup\.js/,
    },
    // Testing phase: use the saved session to run E2E tests
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'playwright/.auth/user.json',
      },
      dependencies: ['setup'],
    },
  ],
});
