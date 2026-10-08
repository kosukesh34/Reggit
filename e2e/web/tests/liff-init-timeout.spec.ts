import { buildSync } from 'esbuild';
import { expect, test } from '@playwright/test';

const script = buildSync({
  entryPoints: ['resources/js/shared/utils/liffInit.ts'],
  bundle: true,
  write: false,
  format: 'iife',
}).outputFiles[0].text;

test('LINE初期化が応答しなくても再試行でき、成功後は読み込みを終了する', async ({ page }) => {
  await page.clock.install();
  await page.setContent(`
    <meta name="line-login-required" content="true">
    <meta name="line-liff-id" content="test-liff">
    <div id="liff-loading"><div class="liff-loading-inner"><p class="liff-loading-text">読み込み中...</p></div></div>
    <script>
      let attempts = 0;
      window.liff = {
        init: () => ++attempts === 1 ? new Promise(() => {}) : Promise.resolve(),
        isLoggedIn: () => true,
        getDecodedIDToken: () => ({ sub: 'U' + 'a'.repeat(32) }),
        permission: { query: async () => ({ state: 'granted' }) },
        getProfile: async () => ({ displayName: 'テスト' })
      };
    </script>
  `);
  await page.addScriptTag({ content: script });
  await page.clock.runFor(15001);
  await expect(page.locator('#liff-login-retry-btn')).toBeVisible();
  await page.locator('#liff-login-retry-btn').click();
  await expect(page.locator('#liff-loading')).toHaveClass('liff-loading--hidden');
  expect(await page.evaluate(() => (window as any).__LIFF_READY__)).toBe(true);
});
