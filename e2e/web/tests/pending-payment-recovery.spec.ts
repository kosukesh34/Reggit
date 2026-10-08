import { build } from 'esbuild';
import { expect, test, type Page } from '@playwright/test';

const script = (await build({
  stdin: { contents: `
    import React from 'react';
    import { createRoot } from 'react-dom/client';
    import { MemoryRouter } from 'react-router-dom';
    import { NativeProductsPage } from './resources/js/liff-ui/native/NativeProductsPage';
    createRoot(document.getElementById('root')).render(<MemoryRouter><NativeProductsPage
      dineIn={false} image="" storeId="1" venueName="Test" /></MemoryRouter>);
  `, resolveDir: process.cwd(), loader: 'tsx' },
  bundle: true, write: false, format: 'iife', jsx: 'automatic',
  define: { 'process.env.NODE_ENV': '"production"' },
  plugins: [{ name: 'runtime-fixtures', setup(build) {
    build.onResolve({ filter: /RuntimeDataContext$/ }, () => ({ path: 'runtime', namespace: 'fixture' }));
    build.onResolve({ filter: /\/nativeConfig$/ }, () => ({ path: 'config', namespace: 'fixture' }));
    build.onLoad({ filter: /.*/, namespace: 'fixture' }, args => ({ resolveDir: process.cwd(), contents: args.path === 'config'
      ? 'export const useNativeConfig = () => ({ config: {} });'
      : `import { useEffect, useState } from 'react';
        export function useRuntimeData() {
          const [number, setNumber] = useState(window.memberNumber);
          useEffect(() => { window.switchMember = setNumber; }, []);
          return { mode: 'live', member: { number }, commerce: { items: [] }, createOrder: async () => ({}) };
        }` }));
  } }],
})).outputFiles[0].text;

async function open(page: Page, saved: Record<string, string>) {
  await page.route('http://payment.test/', route => route.fulfill({ contentType: 'text/html', body: '<div id="root"></div>' }));
  await page.route('**/api/payment/methods', route => route.fulfill({ json: { methods: ['card', 'cash'] } }));
  await page.goto('http://payment.test/');
  await page.evaluate(items => {
    Object.assign(window, { __PROJECT_KEY__: 'default', memberNumber: 'A' });
    for (const [key, value] of Object.entries(items)) localStorage.setItem(key, value);
  }, saved);
  await page.addScriptTag({ content: script });
}

for (const status of [404, 200]) {
  test(`古い未決済注文を解除してカタログへ戻れる (${status === 404 ? '別会員・削除済み' : 'キャンセル済み'})`, async ({ page }) => {
    await page.route('**/api/orders/41', route => route.fulfill({ status, json: status === 404
      ? { error: '注文の取得に失敗しました' } : { id: 41, status: 'cancelled', payment_method: 'stripe' } }));
    await open(page, { 'reggit-payment:default': '41' });
    await expect(page.getByRole('heading', { name: 'お支払い待ちの注文' })).toHaveCount(0);
    await expect(page.getByPlaceholder('商品・サービスを検索')).toBeVisible();
    expect(await page.evaluate(() => localStorage.getItem('reggit-payment:default'))).toBeNull();
  });
}

test('通信失敗では未決済参照を保持し、復旧後に確認できる', async ({ page }) => {
  let failed = true;
  await page.route('**/api/orders/41', route => route.fulfill({ status: failed ? 503 : 200, json: failed
    ? { error: '一時的な通信失敗' } : { id: 41, status: 'confirmed', payment_method: 'stripe', order_number: '101' } }));
  await open(page, { 'reggit-payment:default:A': '41' });
  await expect(page.getByRole('heading', { name: 'お支払い待ちの注文' })).toBeVisible();
  expect(await page.evaluate(() => localStorage.getItem('reggit-payment:default:A'))).toBe('41');
  failed = false;
  await page.getByRole('button', { name: 'お支払い状況を確認' }).click();
  await expect(page.getByRole('heading', { name: 'ご注文を受け付けました' })).toBeVisible();
  await expect(page.getByText('受付ID 101')).toBeVisible();
});

test('会員切替後に古い確認応答が届いても新しい会員の注文を消さない', async ({ page }) => {
  let finish: () => void = () => {};
  const hold = new Promise<void>(resolve => { finish = resolve; });
  let requested: () => void = () => {};
  const started = new Promise<void>(resolve => { requested = resolve; });
  await page.route('**/api/orders/41', async route => { requested(); await hold; await route.fulfill({ status: 404, json: { error: '見つかりません' } }); });
  let secondRequested: () => void = () => {};
  const secondStarted = new Promise<void>(resolve => { secondRequested = resolve; });
  await page.route('**/api/orders/42', route => { secondRequested(); return route.fulfill({ json: { id: 42, status: 'pending', payment_method: 'stripe' } }); });
  await page.route('**/api/payment/checkout/confirm', route => route.fulfill({ status: 409, json: { message: '決済が完了していません' } }));
  await open(page, { 'reggit-payment:default:A': '41', 'reggit-payment:default:B': '42' });
  await started;
  await page.getByRole('button', { name: 'お支払い状況を確認' }).click();
  await page.evaluate(() => (window as any).switchMember('B'));
  await secondStarted;
  await expect.poll(() => page.evaluate(() => localStorage.getItem('reggit-payment:default:B'))).toBe('42');
  // Observe B's confirmation before releasing A's delayed response.
  await expect.poll(() => page.evaluate(() => localStorage.getItem('reggit-payment:default:A'))).toBe('41');
  finish();
  await page.waitForTimeout(100);
  await expect(page.getByRole('heading', { name: 'お支払い待ちの注文' })).toBeVisible();
  expect(await page.evaluate(() => localStorage.getItem('reggit-payment:default:B'))).toBe('42');
});
