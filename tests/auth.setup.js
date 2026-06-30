import { test as setup, expect } from '@playwright/test';

const authFile = 'playwright/.auth/user.json';

setup('authenticate', async ({ page }) => {
  // 1. Go to the login page (appended to baseURL in playwright.config.js)
  await page.goto('/portal');

  // 2. Read test user credentials from environment variables
  const email = process.env.TEST_USER_EMAIL;
  const password = process.env.TEST_USER_PASSWORD;

  if (!email || !password) {
    throw new Error('TEST_USER_EMAIL and TEST_USER_PASSWORD must be defined in your .env file');
  }

  // 3. Fill in the login form fields
  await page.fill('#UserName', email);
  await page.fill('#UserPassword', password);

  // 4. Click the "Sign In" button and wait for it to process
  await page.click('#lrSubmit');

  // 5. Wait for the URL to change to the dashboard, indicating successful login
  await page.waitForURL('**/dashboard');

  // 6. Double check that the dashboard loads by checking for a header or sidebar element
  await expect(page).toHaveTitle(/Dashboard/i);

  // 7. Save the login state (cookies/local storage) to a file for reuse in E2E tests
  await page.context().storageState({ path: authFile });
});
