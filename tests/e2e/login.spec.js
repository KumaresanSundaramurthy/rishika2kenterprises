import { test, expect } from '@playwright/test';

// Reset the storage state for login tests so we start logged out
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Login Page', () => {
  test('should load the login page correctly', async ({ page }) => {
    await page.goto('/portal');
    await expect(page).toHaveTitle(/Login/i);
    await expect(page.locator('#UserName')).toBeVisible();
    await expect(page.locator('#UserPassword')).toBeVisible();
    await expect(page.locator('#lrSubmit')).toBeVisible();
  });

  test('should display error message on invalid credentials', async ({ page }) => {
    await page.goto('/portal');

    // Enter incorrect credentials
    await page.fill('#UserName', 'invalid_user@example.com');
    await page.fill('#UserPassword', 'wrong_password_123');

    // Click submit
    await page.click('#lrSubmit');

    // We should remain on the portal/login page or get redirected with flash error
    await page.waitForURL('**/portal');

    // Verify error message container is visible
    const alert = page.locator('.lr-alerts');
    await expect(alert).toBeVisible();
  });
});
