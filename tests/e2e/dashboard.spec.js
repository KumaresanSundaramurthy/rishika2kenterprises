import { test, expect } from '@playwright/test';

test.describe('Dashboard Page', () => {
  test('should load the dashboard successfully using saved session', async ({ page }) => {
    // 1. Go to the dashboard page
    await page.goto('/dashboard');

    // 2. Verify we are on the dashboard (title or URL check)
    await expect(page).toHaveTitle(/Dashboard/i);
    await expect(page).toHaveURL(/.*dashboard/);

    // 3. Verify page header elements exist
    const pageHeader = page.locator('h4:has-text("Dashboard")');
    await expect(pageHeader).toBeVisible();

    // 4. Verify some metric cards are visible
    // "To Collect" statistics card
    const toCollectCard = page.locator('span:has-text("To Collect")');
    await expect(toCollectCard).toBeVisible();

    // "To Pay" statistics card
    const toPayCard = page.locator('span:has-text("To Pay")');
    await expect(toPayCard).toBeVisible();

    // 5. Verify the Sales chart canvas element exists
    const salesChart = page.locator('canvas#salesChart');
    await expect(salesChart).toBeVisible();
  });
});
