import { expect, test } from '@playwright/test';

const loginId = process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store';
const password = process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026';

test('クーポン配信画面と対象者プレビューAPIが連携する', async ({ page }) => {
  test.skip(process.env.E2E_SKIP_ADMIN === '1', 'E2E_SKIP_ADMIN=1');

  await page.goto('/admin/login');
  await page.locator('#login_id').fill(loginId);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: 'ログイン' }).click();
  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);

  const projectPrefix = new URL(page.url()).pathname.replace(/\/dashboard$/, '');
  const response = await page.goto(`${projectPrefix}/coupons/bulk-issue`);
  expect(response?.ok()).toBe(true);

  await expect(page.getByRole('heading', { name: 'クーポン配信' })).toBeVisible();
  await expect(page.getByText('全会員', { exact: true })).toBeVisible();
  await expect(page.getByText('条件で絞る', { exact: true })).toBeVisible();

  const previewResponse = await page.request.get(
    `${projectPrefix}/coupons/delivery-preview?delivery_type=immediate_all`,
  );
  expect(previewResponse.ok()).toBe(true);
  const preview = await previewResponse.json();
  expect(preview).toEqual(
    expect.objectContaining({
      count: expect.any(Number),
      total: expect.any(Number),
      is_event: false,
    }),
  );
  expect(preview.count).toBeLessThanOrEqual(preview.total);

  const membersResponse = await page.request.get(
    `${projectPrefix}/coupons/delivery-preview-members?delivery_type=immediate_all`,
  );
  expect(membersResponse.ok()).toBe(true);
  await expect(membersResponse.json()).resolves.toEqual(
    expect.objectContaining({
      members: expect.any(Array),
      total: expect.any(Number),
      error: null,
    }),
  );
});
