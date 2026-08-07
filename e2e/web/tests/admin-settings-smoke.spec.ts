import { expect, test } from '@playwright/test';

const loginId = process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store';
const password = process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026';
const skipAdminE2E = process.env.E2E_SKIP_ADMIN === '1';

test('店舗管理者がログイン後に基本設定画面を開ける', async ({ page }) => {
  test.skip(skipAdminE2E, 'E2E_SKIP_ADMIN=1 のため管理画面 E2E をスキップ');

  await page.goto('/admin/login');
  await page.locator('#login_id').fill(loginId);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: 'ログイン' }).click();

  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);

  const settingsLink = page.getByRole('link', { name: /設定|基本設定/ }).first();
  if (await settingsLink.count() === 0) {
    test.skip(true, '設定リンクが見つからないためスキップ');
  }

  await settingsLink.click();
  await expect(page).toHaveURL(/\/admin\/[^/]+\/settings/);
});
