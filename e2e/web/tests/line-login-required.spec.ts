import { expect, test } from '@playwright/test';

test('外部ブラウザでも未ログインなら内容を表示せずLINEログインを開始する', async ({ page }) => {
  await page.route('https://static.line-scdn.net/**', route => route.fulfill({ contentType: 'application/javascript', body: `window.liff = {
    init: async options => { window.testLoginOptions = options; },
    isLoggedIn: () => false,
    login: () => { window.testLoginCalled = true; }
  };` }));
  await page.goto('/liff');
  await expect(page.locator('#app-container')).toBeEmpty();
  await expect(page.locator('#liff-loading')).toBeVisible();
  const id = await page.locator('meta[name="line-liff-id"]').getAttribute('content');
  if (id) {
    await expect.poll(() => page.evaluate(() => (window as any).testLoginCalled)).toBe(true);
    expect(await page.evaluate(() => (window as any).testLoginOptions.withLoginOnExternalBrowser)).toBe(true);
  } else {
    await expect(page.locator('#liff-loading')).toContainText('LINE連携が設定されていません');
  }
  await page.waitForTimeout(5500);
  await expect(page.locator('#app-container')).toBeEmpty();
  await expect(page.locator('#liff-loading')).toBeVisible();
});

test('LINEログイン済みなら外部ブラウザでも画面を表示する', async ({ page }) => {
  await page.route('https://static.line-scdn.net/**', route => route.fulfill({ contentType: 'application/javascript', body: `window.liff = {
    init: async () => {}, isLoggedIn: () => true,
    getDecodedIDToken: () => ({ sub: 'U' + 'a'.repeat(32) }),
    getIDToken: () => 'test-token',
    permission: { query: async () => ({ state: 'granted' }) },
    getProfile: async () => ({ displayName: 'テスト' })
  };` }));
  await page.goto('/liff');
  const id = await page.locator('meta[name="line-liff-id"]').getAttribute('content');
  test.skip(!id, 'この店舗にはLIFF IDが未設定');
  await expect(page.locator('#app-container .app-shell')).toBeAttached();
  await expect(page.locator('#liff-loading')).toBeHidden();
});
