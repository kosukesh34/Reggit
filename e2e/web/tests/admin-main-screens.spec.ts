import { expect, test } from '@playwright/test';
import { adminPrefix, adminStorageState } from './support/adminAuth';

test.use({ storageState: adminStorageState('store') });

test('店舗管理のメニューと設定配下の画面を開ける', async ({ page }) => {
  test.skip(process.env.E2E_SKIP_ADMIN === '1', 'E2E_SKIP_ADMIN=1');

  const projectPrefix = await adminPrefix(page);
  const menuScreens = await page.locator('.sidebar-nav a.nav-item').evaluateAll((links, prefix) =>
    links
      .map((link) => ({ label: link.textContent?.trim() ?? '', path: new URL((link as HTMLAnchorElement).href).pathname }))
      .filter((link) => link.path.startsWith(`${prefix}/`) && link.label !== ''),
    projectPrefix,
  );
  expect(menuScreens.length).toBeGreaterThan(5);

  const settingsResponse = await page.goto(`${projectPrefix}/settings`);
  expect(settingsResponse?.ok()).toBe(true);
  const settingScreens = await page.locator('main a[href]').evaluateAll((links, prefix) =>
    links
      .map((link) => ({ label: link.textContent?.trim() ?? '', path: new URL((link as HTMLAnchorElement).href).pathname }))
      .filter((link) => link.path.startsWith(`${prefix}/settings/`) && link.label !== ''),
    projectPrefix,
  );

  const screens = [...new Map([...menuScreens, ...settingScreens].map((screen) => [screen.path, screen])).values()];
  for (const screen of screens) {
    await test.step(`${screen.label}: ${screen.path}`, async () => {
      const response = await page.goto(screen.path);
      expect(response?.ok(), `${screen.path}: HTTP 応答`).toBe(true);
      await expect(page.locator('main.main-content h2').first()).toBeVisible();
      await expect(page.locator('main.main-content')).not.toContainText('Internal Server Error');
    });
  }
});
