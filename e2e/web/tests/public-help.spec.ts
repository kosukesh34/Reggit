import { expect, test } from '@playwright/test';

test('LPから未ログインで全機能のヘルプを検索・閲覧できる', async ({ page }) => {
  test.setTimeout(180_000);
  await page.goto('/');
  await expect(page.getByRole('heading', { level: 1 })).toContainText('いつものLINEで');
  await page.getByRole('navigation', { name: 'メインナビゲーション', exact: true })
    .getByRole('link', { name: 'ヘルプ', exact: true }).click();
  await expect(page).toHaveURL(/\/help$/);
  const articles = await page.locator('.help-list a').evaluateAll(nodes => nodes.map(node => (node as HTMLAnchorElement).href));
  expect(articles.length).toBe(80);
  for (const url of articles) {
    const response = await page.goto(url);
    expect(response?.status()).toBe(200);
    await expect(page.locator('.help-manual h1')).toBeVisible();
    const images = page.locator('.help-shot img');
    expect(await images.count()).toBeGreaterThan(0);
    for (const image of await images.all()) {
      await image.scrollIntoViewIfNeeded();
      await expect.poll(() => image.evaluate(node => (node as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
    }
  }
  await page.goto('/help');
  await page.getByRole('searchbox', { name: '知りたい機能を検索' }).fill('既存会員');
  await page.getByRole('button', { name: '検索', exact: true }).click();
  await page.getByRole('link', { name: /^スマレジの既存会員をアプリに連携する/ }).click();
  await expect(page).toHaveURL(/\/help\/existing-member-link$/);
  await expect(page.getByRole('link', { name: '全機能のヘルプ一覧へ戻る', exact: true })).toHaveAttribute('href', '/help');
  await page.getByRole('link', { name: '関連する操作のヘルプを読む', exact: true }).click();
  await expect(page).toHaveURL(/\/help\/feature-overview$/);
});

test('スマートフォンでもLPメニューから公開ヘルプと画像を開ける', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  await page.locator('.lp-mobile-nav summary').click();
  await page.getByRole('navigation', { name: 'モバイルナビゲーション', exact: true })
    .getByRole('link', { name: 'ヘルプ', exact: true }).click();
  await expect(page).toHaveURL(/\/help$/);
  await page.screenshot({ path: '/tmp/reggit-public-help-mobile-index.png', fullPage: true });
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
  await page.goto('/help/existing-member-link');
  await expect(page.getByRole('heading', { level: 1 })).toContainText('既存会員');
  await page.screenshot({ path: '/tmp/reggit-public-help-mobile-article.png', fullPage: true });
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
  const imageLink = page.locator('.help-shot__frame').first();
  const popupPromise = page.waitForEvent('popup');
  await imageLink.click();
  const popup = await popupPromise;
  await expect(popup).toHaveURL(/\/images\/help\/guide-app-existing\.png$/);
  await popup.close();
});
