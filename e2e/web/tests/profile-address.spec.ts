import { buildSync } from 'esbuild';
import { expect, test, type Page } from '@playwright/test';

const script = buildSync({
  stdin: { contents: `
    import React from 'react';
    import { createRoot } from 'react-dom/client';
    import { NativeAddressFields } from './resources/js/liff-ui/native/NativeAddressFields';
    const fields = [{ key: 'postal_code', label: '郵便番号' }, { key: 'address', label: '住所' }];
    createRoot(document.getElementById('root')).render(<form><NativeAddressFields fields={fields}
      profile={{ address: window.initialAddress || '' }} errors={{}}
      lookup={code => fetch('/postal?code=' + code).then(response => { if (!response.ok) throw Error(); return response.json(); })} />
      <button>保存</button></form>);
  `, resolveDir: process.cwd(), loader: 'tsx' },
  bundle: true, write: false, format: 'iife', jsx: 'automatic',
  define: { 'process.env.NODE_ENV': '"production"' },
}).outputFiles[0].text;

const address = { full_address: '東京都千代田区千代田', pref_name: '東京都', city_name: '千代田区', town_name: '千代田' };
async function open(page: Page, initial = '') {
  await page.route('http://profile.test/', route => route.fulfill({ contentType: 'text/html', body: '<div id="root"></div>' }));
  await page.goto('http://profile.test/');
  await page.evaluate(value => { (window as any).initialAddress = value; }, initial);
  await page.addScriptTag({ content: script });
}

test('全角・ハイフン付き郵便番号から住所を補完し、番地を追記して保存できる', async ({ page }) => {
  await page.route('**/postal?code=1000001', route => route.fulfill({ json: { addresses: [address] } }));
  await open(page);
  await page.getByLabel('郵便番号', { exact: true }).fill('１００－０００１');
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue(address.full_address);
  await expect(page.getByLabel('郵便番号', { exact: true })).toHaveValue('1000001');
  await page.getByLabel('住所', { exact: true }).fill(`${address.full_address}1-1\n建物名101号室`);
  const data = await page.locator('form').evaluate(form => Object.fromEntries(new FormData(form as HTMLFormElement)));
  expect(data.address).toBe(`${address.full_address}1-1\n建物名101号室`);
});

test('入力済み住所は上書きせず、選んだ候補を反映する', async ({ page }) => {
  await page.route('**/postal?code=1000001', route => route.fulfill({ json: { addresses: [address] } }));
  await open(page, '既存の住所101号室');
  await page.getByLabel('郵便番号', { exact: true }).fill('1000001');
  await expect(page.getByRole('button', { name: address.full_address })).toBeVisible();
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue('既存の住所101号室');
  await page.getByRole('button', { name: address.full_address }).click();
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue(address.full_address);
});

test('複数候補は選択してから住所に反映する', async ({ page }) => {
  const second = { ...address, full_address: '東京都千代田区丸の内', town_name: '丸の内' };
  await page.route('**/postal?code=1000001', route => route.fulfill({ json: { addresses: [address, second] } }));
  await open(page);
  await page.getByLabel('郵便番号', { exact: true }).fill('1000001');
  await page.getByRole('button', { name: second.full_address }).click();
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue(second.full_address);
});

test('古い検索結果が新しい郵便番号の住所を上書きしない', async ({ page }) => {
  let finish: () => void = () => {};
  const hold = new Promise<void>(resolve => { finish = resolve; });
  let requested: () => void = () => {};
  const started = new Promise<void>(resolve => { requested = resolve; });
  await page.route('**/postal?code=1000001', async route => { requested(); await hold; await route.fulfill({ json: { addresses: [address] } }); });
  const next = { ...address, full_address: '福岡県福岡市中央区天神' };
  await page.route('**/postal?code=8100001', route => route.fulfill({ json: { addresses: [next] } }));
  await open(page);
  await page.getByLabel('郵便番号', { exact: true }).fill('1000001');
  await started;
  await page.getByLabel('郵便番号', { exact: true }).fill('8100001');
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue(next.full_address);
  finish();
  await page.waitForTimeout(100);
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue(next.full_address);
});

test('検索失敗後も住所を手入力でき、再試行できる', async ({ page }) => {
  await page.route('**/postal?code=1000001', route => route.fulfill({ status: 503, body: '' }));
  await open(page);
  await page.getByLabel('郵便番号', { exact: true }).fill('1000001');
  await expect(page.getByRole('status')).toContainText('手入力');
  await page.getByLabel('住所', { exact: true }).fill('手入力の住所');
  await expect(page.getByLabel('住所', { exact: true })).toHaveValue('手入力の住所');
  await expect(page.getByRole('button', { name: '住所を自動入力' })).toBeEnabled();
});
