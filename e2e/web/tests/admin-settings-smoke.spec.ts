import { expect, test } from '@playwright/test';
import { adminPrefix, adminStorageState } from './support/adminAuth';

test.use({ storageState: adminStorageState('store') });

const skipAdminE2E = process.env.E2E_SKIP_ADMIN === '1';

test('店舗管理者がログイン後に基本設定画面を開ける', async ({ page }) => {
  test.skip(skipAdminE2E, 'E2E_SKIP_ADMIN=1 のため管理画面 E2E をスキップ');

  await adminPrefix(page);

  // アイコンの文字が名前に入るため、表示文字で探す
  await page.getByRole('navigation', { name: 'メインメニュー' }).getByRole('link').filter({ hasText: /^\s*設定\s*$/ }).click();
  await expect(page).toHaveURL(/\/admin\/[^/]+\/settings/);
});
