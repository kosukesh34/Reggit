import { expect, test } from '@playwright/test';
import { build } from 'esbuild';
import path from 'node:path';

let toastScript: string;
let dialogScript: string;
let sheetScript: string;

test.beforeAll(async () => {
  // Execute the actual source in Chromium, with no production data or external requests.
  async function bundle(file: string, globalName: string) {
    const result = await build({
      entryPoints: [path.resolve(process.cwd(), file)],
      bundle: true, write: false, format: 'iife', globalName,
    });
    return result.outputFiles[0].text;
  }
  toastScript = await bundle('resources/js/shared/utils/toast.ts', 'ReviewToast');
  dialogScript = await bundle('resources/js/shared/utils/confirmDialog.ts', 'ReviewDialog');
  const sheet = await build({
    stdin: {
      contents: `import React from 'react';
        import { createRoot } from 'react-dom/client';
        import { BottomSheet } from './resources/js/liff-ui/components/ui/BottomSheet';
        export function mount() {
          const root = createRoot(document.getElementById('root'));
          root.render(React.createElement(BottomSheet, { open: true, title: '詳細確認', onClose: () => root.unmount() }, React.createElement('button', null, '最後の操作')));
        }`,
      resolveDir: process.cwd(),
    },
    bundle: true, write: false, format: 'iife', globalName: 'ReviewSheet',
  });
  sheetScript = sheet.outputFiles[0].text;
});

test('@component ボトムシートのフォーカスを循環し閉じると元へ戻す', async ({ page }) => {
  await page.setContent('<button id="opener">開く</button><div id="root"></div>');
  await page.addScriptTag({ content: sheetScript });
  await page.locator('#opener').focus();
  await page.evaluate(() => (window as any).ReviewSheet.mount());
  const dialog = page.getByRole('dialog', { name: '詳細確認' });
  await expect(dialog).toBeVisible();
  await page.keyboard.press('Shift+Tab');
  await expect(page.getByRole('button', { name: '最後の操作' })).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(dialog.locator('.sheet__header button')).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(dialog).toHaveCount(0);
  await expect(page.locator('#opener')).toBeFocused();
});

test('@component 通知メッセージ内の商品名をHTMLとして実行しない', async ({ page }) => {
  await page.setContent('<main>Review fixture</main>');
  await page.addScriptTag({ content: toastScript });
  const name = '<img src="data:image/png,invalid" onerror="document.body.dataset.reviewXss=\'executed\'">';
  await page.evaluate((message) => {
    (window as any).ReviewToast.ToastManager.success(message, 60_000);
  }, `${name}をカートに追加しました`);

  await expect.soft(page.locator('.toast span')).toHaveText(`${name}をカートに追加しました`);
  await expect.soft(page.locator('.toast img')).toHaveCount(0);
  await expect.soft(page.locator('body')).not.toHaveAttribute('data-review-xss', 'executed');
});

test('@component 確認ダイアログをキーボードと支援技術で操作できる', async ({ page }) => {
  await page.setContent('<button id="opener">削除</button>');
  await page.addScriptTag({ content: dialogScript });
  await page.locator('#opener').focus();
  await page.evaluate(() => {
    void (window as any).ReviewDialog.ConfirmDialog.show('削除しますか？', '削除の確認');
  });

  await expect.soft(page.getByRole('dialog', { name: '削除の確認' })).toBeVisible();
  await expect.soft(page.getByRole('button', { name: 'キャンセル' })).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(page.locator('.confirm-dialog')).toHaveCount(0);
  await expect(page.locator('#opener')).toBeFocused();
});

for (const width of [390, 1280]) {
  test(`デモのホームからその他へ移動でき横スクロールが発生しない (${width}px)`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    const response = await page.goto('/liff#/home');
    expect(response?.status()).toBe(200);
    await page.getByRole('button', { name: '会員証を見る' }).click();
    await page.getByRole('button', { name: 'マイページを見る' }).click();
    await expect(page).toHaveURL(/#\/more$/);
    await expect(page.getByRole('heading', { name: 'すべてのサービス', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'ポイント', exact: true })).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    expect(overflow).toBe(false);
  });
}

test('実店舗のホームで受付がない利用者にサンプルの待ち番号を表示しない', async ({ page }) => {
  await page.goto('/admin/login');
  await page.locator('#login_id').fill(process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store');
  await page.locator('#password').fill(process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026');
  await page.getByRole('button', { name: 'ログイン', exact: true }).click();
  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);
  const href = await page.getByRole('link', { name: /店舗アプリを開く/ }).getAttribute('href');
  expect(href).toBeTruthy();

  // Deterministic empty queue; read-only browser flow, no queue changes or notifications.
  for (const endpoint of ['me', 'current']) {
    await page.route(`**/api/queue/${endpoint}*`, route => route.fulfill({
      json: { queue_entry: null, waiting_count: 0, estimated_wait_minutes: 0 },
    }));
  }
  await page.goto(href!);
  await expect(page).toHaveURL(/\/liff\/[^/?#]+.*#\/home/);
  await expect(page.getByRole('navigation', { name: '主要ナビゲーション' })).toBeVisible();
  await expect(page.getByText('現在 12番・あと5組', { exact: true })).toHaveCount(0);
});
