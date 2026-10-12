import { expect, test } from '@playwright/test';

test('LINE会員証は認証済みの追加URLを事前取得し、タップ時に外部ブラウザーで開く', async ({ page }) => {
  await page.route('**/liff/default', async route => {
    const response = await route.fetch();
    await route.fulfill({ response, body: (await response.text()).replace('<meta name="line-login-required" content="true">', '') });
  });
  await page.addInitScript(() => {
    for (const [key, value] of Object.entries({ __WALLET_PASS_ENABLED__: true, __IS_LIFF__: true, __LINE_USER_ID__: 'U00000000000000000000000000000042', __LIFF_READY__: true })) {
      Object.defineProperty(window, key, { configurable: true, get: () => value, set: () => {} });
    }
    Object.defineProperty(navigator, 'userAgent', { get: () => 'iPhone Line/16.0' });
    const state = window as unknown as { walletOpened?: { url: string; external: boolean }; liff: unknown };
    const mockLiff = { isInClient: () => true, isLoggedIn: () => true, openWindow: (input: { url: string; external: boolean }) => { state.walletOpened = input; } };
    Object.defineProperty(window, "liff", { configurable: true, get: () => mockLiff, set: () => {} });
  });
  let authenticated = false;
  await page.route('**/api/member-wallet/pass-url**', async route => {
    authenticated = route.request().headers()['x-line-user-id'] === 'U00000000000000000000000000000042';
    await route.fulfill({ json: { url: `${new URL(route.request().url()).origin}/wallet-add?pass_token=test-pass&project_key=default&type=apple` } });
  });
  await page.route('**/api/member-profile**', route => route.fulfill({ json: { is_member: true, member: { member_number: '123456', points: 10 }, profile: { name: 'Walletテスト' } } }));
  await page.goto('/liff/default#/membership');
  const button = page.getByRole('button', { name: 'Apple Walletに追加' });
  await expect(button).toBeEnabled();
  await expect(button).toHaveAttribute('data-wallet-open-url', /pass_token=test-pass/);
  await button.click();
  await expect.poll(() => page.evaluate(() => (window as unknown as { walletOpened?: unknown }).walletOpened)).toMatchObject({ external: true });
  const opened = await page.evaluate(() => (window as unknown as { walletOpened: { url: string } }).walletOpened.url);
  expect(new URL(opened).pathname).toBe('/wallet-add');
  expect(new URL(opened).searchParams.get('return_target')).toBe('line');
  expect(authenticated).toBe(true);
});

test('Wallet追加ページは自動起動後も再試行でき、LINEに戻れる', async ({ page }) => {
  let downloads = 0;
  await page.route('**/api/member-wallet/pass?**', async route => {
    downloads++;
    await route.fulfill({ status: 204 });
  });
  const origin = new URL(test.info().project.use.baseURL as string).origin;
  await page.goto('/wallet-add?' + new URLSearchParams({ wallet_url: `${origin}/api/member-wallet/pass?token=test`, return_target: 'line', return_url: 'https://liff.line.me/test-wallet', project_key: 'default' }));
  await expect.poll(() => downloads).toBe(1);
  await page.getByRole('button', { name: 'Apple Wallet に追加' }).click();
  await expect.poll(() => downloads).toBe(2);
  await expect(page.getByRole('link', { name: 'LINEに戻る' })).toHaveAttribute('href', 'https://liff.line.me/test-wallet');
});

test('決済完了ページはネイティブ復帰URLへ自動移動し、手動復帰も残す', async ({ page }) => {
  await page.goto('/mobile/payment/return?status=success&order_id=42');
  await expect(page.getByRole('link', { name: '注文画面へ戻る' })).toHaveAttribute('href', 'reggit://payment?status=success&order_id=42');
  await expect(page.getByRole('status')).toContainText('注文画面へ戻っています');
  expect(await page.locator('script').textContent()).toContain('window.location.replace');
});
