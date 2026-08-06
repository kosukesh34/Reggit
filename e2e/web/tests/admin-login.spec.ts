import { expect, test } from '@playwright/test';

const loginId = process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store';
const password = process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026';

test('店舗管理者がログインしてダッシュボードを表示できる', async ({ page }) => {
  await page.goto('/admin/login');

  await page.locator('#login_id').fill(loginId);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: 'ログイン' }).click();

  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);
  await expect(page.getByRole('heading', { name: 'ダッシュボード' })).toBeVisible();
});
