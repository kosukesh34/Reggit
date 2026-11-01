import { expect, test } from '@playwright/test';
import { loginAdmin } from './support/adminAuth';

test('店舗管理者がパスワードとパスキーでログインしてダッシュボードを表示できる', async ({ page }) => {
  await loginAdmin(page, 'store');

  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);
  await expect(page.getByRole('heading', { name: 'ダッシュボード' })).toBeVisible();
});
