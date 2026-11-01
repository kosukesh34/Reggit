import { expect, test } from '@playwright/test';
import { adminPrefix, adminStorageState } from './support/adminAuth';

test.describe('システム管理', () => {
  test.use({ storageState: adminStorageState('system') });

  test('全記事のプレビューで手順と画面画像を表示できる', async ({ page }) => {
    test.setTimeout(180_000);
    await page.goto('/system-admin/help-articles');
    const previews = await page.getByRole('link', { name: 'プレビュー', exact: true }).evaluateAll(nodes => nodes.map(node => (node as HTMLAnchorElement).href));
    expect(previews.length).toBeGreaterThanOrEqual(80);
    for (const url of previews) {
      await page.goto(url);
      await expect(page.locator('.help-manual h1')).toBeVisible();
      const images = page.locator('.help-shot img');
      // User-created text-only articles are also valid.
      for (const image of await images.all()) {
        await image.scrollIntoViewIfNeeded();
        await expect.poll(() => image.evaluate(node => (node as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
        await expect(image.locator('..')).toHaveAttribute('target', '_blank');
      }
    }
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(previews[0]);
    await expect(page.locator('.help-manual')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
  });
});

test.describe('店舗管理', () => {
  test.use({ storageState: adminStorageState('store') });

  test('店舗ヘルプの全機能に画像と店舗別リンクがありスマートフォンでも読める', async ({ page }) => {
    test.setTimeout(180_000);
    const prefix = await adminPrefix(page);
    await page.goto(`${prefix}/help`);
    const articles = await page.locator('.help-list a').evaluateAll(nodes => nodes.map(node => (node as HTMLAnchorElement).href));
    expect(articles.length).toBeGreaterThanOrEqual(80);

    for (const url of articles) {
      await page.goto(url);
      await expect(page.locator('.help-manual h1')).toBeVisible();
      const links = page.locator('.help-markdown a').filter({ hasNot: page.locator('img') });
      expect(await links.count()).toBeGreaterThan(0);
      const hrefs = await links.evaluateAll(nodes => nodes.map(node => node.getAttribute('href') ?? ''));
      expect(hrefs.every(href => href.startsWith('/') || href.startsWith('#'))).toBeTruthy();
      expect(hrefs.every(href => !href.includes('{project_key}'))).toBeTruthy();
      expect(hrefs.filter(href => href.startsWith('/admin/') && href !== '/admin/login')
        .every(href => href.startsWith(`${prefix}/`))).toBeTruthy();
      const images = page.locator('.help-shot img');
      expect(await images.count()).toBeGreaterThan(0);
      for (const image of await images.all()) {
        await image.scrollIntoViewIfNeeded();
        await expect.poll(() => image.evaluate(node => (node as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
      }
    }

    await page.goto(`${prefix}/help/existing-member-link`);
    await expect(page.getByRole('link', { name: '対象画面を開く', exact: true }))
      .toHaveAttribute('href', `/liff/${prefix.split('/').pop()}#/more/link`);
    await expect(page.locator('.help-markdown ol').last()).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.reload();
    await expect.poll(async () => {
      const box = await page.locator('#sidebar').boundingBox();
      return box === null ? 0 : box.x + box.width;
    }).toBeLessThanOrEqual(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
  });
});
