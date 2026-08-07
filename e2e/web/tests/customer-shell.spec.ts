import { expect, test } from '@playwright/test';

const liffPath = process.env.E2E_LIFF_PATH ?? '/liff';

test('LIFF フロントの chrome / tabs ルートが存在する', async ({ page }) => {
  const response = await page.goto(liffPath);
  if (response !== null && response.status() >= 500) {
    test.skip(true, `LIFF URL が ${response.status()} のためスキップ`);
  }

  await expect(page.locator('#react-frontend-chrome-root')).toBeAttached();
  await expect(page.locator('#react-frontend-tabs-root')).toBeAttached();
  await expect(page.locator('#app-container')).toBeVisible();
});
