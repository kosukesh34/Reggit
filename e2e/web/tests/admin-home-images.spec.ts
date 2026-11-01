import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { adminPrefix, adminStorageState } from './support/adminAuth';

test.use({ storageState: adminStorageState('store') });

// アイコンの文字が名前に入るため、表示文字で探す
const saveButton = (page: Page) => page.getByRole('button').filter({ hasText: /^\s*保存\s*$/ });
const image = readFileSync(resolve('mobile/reggit-skip/Sources/Reggit/Resources/Module.xcassets/DemoStoreHero.imageset/demo-store-hero.png'));

test('ホーム画像を2枚追加し、APIと管理画面に反映して削除できる', async ({ page }) => {
  test.skip(process.env.E2E_SKIP_ADMIN === '1', 'E2E_SKIP_ADMIN=1 のため管理画面 E2E をスキップ');

  const projectKey = (await adminPrefix(page)).split('/')[2];
  const settingsPath = `/admin/${projectKey}/settings/app-design/branding`;
  const configPath = `/api/v1/app-config?project_key=${encodeURIComponent(projectKey)}`;
  await page.goto(settingsPath);
  await expect(page.getByRole('heading', { name: 'アプリデザイン' })).toBeVisible();
  await page.getByRole('tab', { name: 'ホーム・ヘッダー' }).click();

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
    await saveButton(page).click();
    await expect(page.getByText('店舗名・画像を更新しました').first()).toBeVisible();
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
    await page.getByRole('tab', { name: 'ホーム・ヘッダー' }).click();
    const items = page.locator('.home-image-item');
    if (await items.count() >= initialUrls.length + 2) {
      await items.nth(initialUrls.length).locator('input[name="home_image_remove[]"]').check();
      await items.nth(initialUrls.length + 1).locator('input[name="home_image_remove[]"]').check();
      await saveButton(page).click();
      await expect(page.getByText('店舗名・画像を更新しました').first()).toBeVisible();
      const response = await page.request.get(configPath);
      const config = await response.json();
      expect(config.home.image_urls).toEqual(initialUrls);
    }
  }
});
