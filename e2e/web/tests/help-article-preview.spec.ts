import { expect, test } from '@playwright/test';

test('全記事のプレビューで手順と画面画像を表示できる', async ({ page }) => {
  await page.goto('/system-admin/login');
  await page.locator('#login_id').fill(process.env.E2E_SYSTEM_LOGIN_ID ?? 'demo_sys');
  await page.locator('#password').fill(process.env.E2E_SYSTEM_PASSWORD ?? 'DemoSys#2026');
  await page.getByRole('button', { name: 'ログイン', exact: true }).click();
  await page.waitForURL(url => !url.pathname.endsWith('/login'));
  await page.goto('/system-admin/help-articles');
  const previews = await page.getByRole('link', { name: 'プレビュー', exact: true }).evaluateAll(nodes => nodes.map(node => (node as HTMLAnchorElement).href));
  expect(previews.length).toBeGreaterThanOrEqual(10);
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
