import { expect, test } from '@playwright/test';

const liffPath = process.env.E2E_LIFF_PATH ?? '/liff';

const screens = [
  'home', 'membership', 'profile', 'mobileOrder', 'dineInOrder',
  'reservation', 'queue', 'coupon', 'stamp', 'point', 'menu',
  'gallery', 'news', 'storeSearch', 'purchaseHistory', 'orderHistory',
  'favorite', 'ticket', 'subscription', 'designlab', 'more',
  'support', 'contact', 'terms', 'settings',
];

test('顧客向けの主要画面をすべて開ける', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));

  for (const screen of screens) {
    await test.step(screen, async () => {
      const response = await page.goto(`${liffPath}?demo=1#/${screen}`);
      if (response !== null) expect(response.ok(), `${screen}: HTTP 応答`).toBe(true);
      await expect(page.locator('.app-shell'), `${screen}: アプリの枠`).toBeVisible();
      await expect(page.getByRole('main'), `${screen}: 画面本体`).toBeVisible();
      await expect(page.getByRole('alert').filter({ hasText: '画面を表示できませんでした' })).toHaveCount(0);
      await expect(page.getByRole('heading', { name: 'ページが見つかりません' })).toHaveCount(0);
      expect(errors, `${screen}: JavaScript エラー`).toEqual([]);
    });
  }
});
