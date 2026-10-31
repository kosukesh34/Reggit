import { expect, test } from '@playwright/test';
const liffPath = process.env.E2E_LIFF_PATH ?? '/liff';
const demoPath = '/demo/liff';
async function expectShell(page: import('@playwright/test').Page) {
  await expect(page.locator('.app-shell')).toBeVisible({ timeout: 30_000 });
  await expect(page.getByRole('navigation', { name: '主要ナビゲーション' })).toBeVisible();
}
test.describe('Skip と共通の LIFF UI', () => {
  test('デモのホームから、設定された全メニューに移動できる', async ({ page }) => {
    await page.goto(`${demoPath}#/home`);
    await expectShell(page);
    await expect(page.getByRole('navigation', { name: 'ホームメニュー' }).getByRole('link', { name: '予約', exact: true })).toBeVisible();
    await page.getByRole('navigation', { name: 'ホームメニュー' }).getByRole('link', { name: 'スタンプ', exact: true }).click();
    await expect(page).toHaveURL(/#\/stamp/);
    await expect(page.getByRole('heading', { name: '来店スタンプラリー' })).toBeVisible();
  });
  test('クーポン利用後は履歴に移り、バーコードを表示する', async ({ page }) => {
    await page.goto(`${demoPath}#/coupon`);
    await page.getByRole('button', { name: '使う', exact: true }).first().click();
    await page.getByRole('button', { name: '使用する', exact: true }).click();
    await expect(page.getByRole('dialog').getByRole('figure')).toBeVisible();
    await page.getByRole('dialog').getByRole('button', { name: '閉じる', exact: true }).last().click();
    await page.getByRole('tab', { name: '履歴', exact: true }).click();
    await expect(page.getByText('利用済み', { exact: true })).toBeVisible();
  });
  test('整理券の発行とキャンセルを確認できる', async ({ page }) => {
    await page.goto(`${demoPath}#/queue`);
    await page.getByRole('button', { name: '人数を増やす' }).click();
    await page.getByRole('button', { name: '整理券を発行' }).click();
    await expect(page.getByText('2名', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: '整理券をキャンセル', exact: true }).click();
    await page.getByRole('button', { name: 'キャンセルする', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'ご来店の受付' })).toBeVisible();
  });
  test('商品詳細からカートに追加して内容を確認できる', async ({ page }) => {
    await page.goto(`${demoPath}#/mobileOrder`);
    await page.getByRole('button', { name: '季節のおすすめの詳細' }).click();
    await page.getByRole('button', { name: 'カートに追加', exact: true }).click();
    await page.getByRole('button', { name: '注文を確認', exact: true }).click();
    await expect(page.getByRole('heading', { name: '注文内容', exact: true })).toBeVisible();
    await expect(page.getByText('×1', { exact: true })).toBeVisible();
  });
  test('実 LIFF は旧テーマ・ビルダーの CSS を読み込まない', async ({ page }) => {
    // 実 LIFF は LINE ログインへ移動するため、SDK を止めて自前のページの読み込み完了（ログイン案内）まで待つ
    await page.route('https://static.line-scdn.net/liff/**', (route) => route.abort());
    const response = await page.goto(`${liffPath}#/home`);
    test.skip(response?.status() === 404, 'E2E_LIFF_PATH に有効な店舗 LIFF を指定してください');
    await expect(page.getByRole('button', { name: 'LINEでログインする' })).toBeVisible();
    const hrefs = await page.locator('link[rel="stylesheet"]').evaluateAll((nodes) => nodes.map((node) => (node as HTMLLinkElement).href));
    expect(hrefs.some((href) => /(?:member-card-design|builder-component|system-ui-customization|liff-fixed-ui)\.css/.test(href))).toBe(false);
  });
});
