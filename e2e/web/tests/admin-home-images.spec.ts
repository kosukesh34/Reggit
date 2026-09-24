import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { expect, test } from '@playwright/test';

const loginId = process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store';
const password = process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026';
const image = readFileSync(resolve('mobile/reggit-skip/Sources/Reggit/Resources/Module.xcassets/DemoStoreHero.imageset/demo-store-hero.png'));

test('ホーム画像を2枚追加し、APIと管理画面に反映して削除できる', async ({ page }) => {
  test.skip(process.env.E2E_SKIP_ADMIN === '1', 'E2E_SKIP_ADMIN=1 のため管理画面 E2E をスキップ');

  await page.goto('/admin/login');
  await page.locator('#login_id').fill(loginId);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: 'ログイン' }).click();
  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);

  const projectKey = new URL(page.url()).pathname.split('/')[2];
  const settingsPath = `/admin/${projectKey}/settings/home`;
  const configPath = `/api/v1/app-config?project_key=${encodeURIComponent(projectKey)}`;
  await page.goto(settingsPath);
  await expect(page.getByRole('heading', { name: 'ホーム設定' })).toBeVisible();

  const initialConfigResponse = await page.request.get(configPath);
  expect(initialConfigResponse.ok()).toBe(true);
  const initialConfig = await initialConfigResponse.json();
  const initialUrls: string[] = initialConfig.home.image_urls;
  test.skip(initialUrls.length > 8, '追加可能なホーム画像が2枚未満');

  const files = [1, 2].map((number) => ({
    name: `e2e-home-${number}.png`,
    mimeType: 'image/png',
    buffer: image,
  }));

  try {
    await page.locator('#home_images').setInputFiles(files);
    await page.getByRole('button', { name: '保存する' }).click();
    await expect(page.getByText('ホーム設定を更新しました').first()).toBeVisible();
    await expect(page.locator('.home-image-item')).toHaveCount(initialUrls.length + 2);

    const response = await page.request.get(configPath);
    expect(response.ok()).toBe(true);
    const config = await response.json();
    const urls: string[] = config.home.image_urls;
    expect(urls).toHaveLength(initialUrls.length + 2);
    expect(urls.slice(0, initialUrls.length)).toEqual(initialUrls);
    expect(config.home.image_url).toBe(urls[0]);
    expect(urls.slice(-2).every((url) => url.includes('/storage/home_images/'))).toBe(true);
  } finally {
    await page.goto(settingsPath);
    const items = page.locator('.home-image-item');
    if (await items.count() >= initialUrls.length + 2) {
      await items.nth(initialUrls.length).locator('input[name="home_image_remove[]"]').check();
      await items.nth(initialUrls.length + 1).locator('input[name="home_image_remove[]"]').check();
      await page.getByRole('button', { name: '保存する' }).click();
      await expect(page.getByText('ホーム設定を更新しました').first()).toBeVisible();
      const response = await page.request.get(configPath);
      const config = await response.json();
      expect(config.home.image_urls).toEqual(initialUrls);
    }
  }
});
