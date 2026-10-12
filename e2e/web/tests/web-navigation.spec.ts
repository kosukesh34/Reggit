import { expect, test, type Locator, type Page } from '@playwright/test';

async function checkBack(control: Locator) {
  await expect(control).toBeVisible();
  await expect(control.locator('svg')).toHaveCount(1);
  const box = await control.boundingBox();
  expect(box?.height).toBeGreaterThanOrEqual(44);
  await expect(control).toHaveCSS('border-radius', '12px');
}

async function login(page: Page, system = false) {
  await page.goto(system ? '/system-admin/login' : '/admin/default/login');
  await page.getByLabel('ID', { exact: true }).fill(system ? 'demo_sys' : 'demo_proj');
  await page.getByLabel('パスワード', { exact: true }).fill(system ? 'DemoSys#2026' : 'DemoProj#2026');
  await page.getByRole('button', { name: 'ログイン', exact: true }).click();
  await page.waitForURL(url => !url.pathname.endsWith('/login'));
}

for (const width of [1280, 390]) {
  test(`公開ページの戻る操作・キーボード操作・表示幅 ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    for (const path of ['/help', '/help/existing-member-link', '/lp/register/email', '/admin/login', '/admin/default/login', '/admin/login/form', '/system-admin/login', '/admin/forgot-password', '/onboarding', '/legal/default/terms', '/legal/default/privacy']) {
      const response = await page.goto(path);
      expect(response?.status(), path).toBe(200);
      await checkBack(page.getByRole('navigation', { name: 'ページの移動' }).locator('.web-back'));
      expect(await page.evaluate(() => document.documentElement.scrollWidth), path).toBeLessThanOrEqual(width);
    }
    await page.goto('/help/existing-member-link');
    const back = page.getByRole('navigation', { name: 'ページの移動' }).getByRole('link', { name: 'ヘルプ一覧へ戻る' });
    await back.focus();
    await expect(back).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/help$/);
    await page.getByRole('navigation', { name: 'ページの移動' }).getByRole('link').click();
    await expect(page).toHaveURL(/\/$/);
  });

  test(`会員アプリのタブでは戻るを非表示にし、詳細・フォームから戻れる ${width}px`, async ({ page }) => {
    test.setTimeout(120_000);
    await page.setViewportSize({ width, height: 844 });
    const screens = ['home', 'membership', 'profile', 'more', 'more/link', 'more/transfer', 'more/withdraw', 'stamp', 'stamp/issue', 'coupon', 'queue', 'reservation', 'menu', 'mobileOrder', 'dineInOrder', 'orderHistory', 'gallery', 'news', 'storeSearch', 'terms', 'privacy', 'designlab', 'link_1', 'link_2', 'link_3'];
    for (const screen of screens) {
      await page.goto(`/demo/liff#/${screen}`);
      const back = page.getByRole('navigation', { name: 'ページの移動' }).locator('.web-back');
      const isChild = screen.startsWith('more/') || ['stamp/issue', 'terms', 'privacy'].includes(screen);
      await expect(page.locator('body')).toHaveClass(/liff-ui-ready/);
      await expect(page.getByText('画面を読み込んでいます...', { exact: true })).toBeHidden();
      if (isChild) await checkBack(back);
      else await expect(back).toHaveCount(0);
      if (screen === 'designlab') await page.getByRole('dialog', { name: '購入特典' }).getByRole('button', { name: '閉じて注文に戻る' }).click();
      if (!isChild) continue;
      await back.click();
      const parent = screen.startsWith('more/') || ['terms', 'privacy'].includes(screen) ? 'more' : screen === 'stamp/issue' ? 'stamp' : 'home';
      await expect(page).toHaveURL(new RegExp(`#/${parent}$`));
    }
    // Returning from a form must close it before leaving its feature.
    await page.goto('/demo/liff#/reservation');
    await page.getByRole('button', { name: '新しく予約する' }).click();
    const back = page.getByRole('navigation', { name: 'ページの移動' }).getByRole('button', { name: '前の画面へ戻る' });
    await checkBack(back);
    await back.click();
    await expect(page.getByRole('button', { name: '新しく予約する' })).toBeVisible();
    await expect(page).toHaveURL(/#\/reservation$/);
    await expect(back).toHaveCount(0);
    const tabs = page.getByRole('navigation', { name: '主要ナビゲーション' });
    const tabLinks = await tabs.getByRole('link').evaluateAll(links => links.map(link => link.getAttribute('href')).filter((href): href is string => !!href && href.startsWith('#/')));
    expect(tabLinks.length).toBeGreaterThan(1);
    for (const href of tabLinks) {
      await tabs.locator(`a[href="${href}"]`).click();
      await expect(page).toHaveURL(new RegExp(`${href}$`));
      await expect(page.getByRole('navigation', { name: 'ページの移動' })).toHaveCount(0);
    }
  });
}

test('店舗管理の全機能・設定・作成・詳細画面で戻り先と共通デザインを確認', async ({ page }) => {
  test.setTimeout(240_000);
  await login(page);
  const paths = ['orders', 'products', 'products/create', 'members', 'member-tags', 'rfm', 'reservations', 'reservations/create', 'stamps', 'stamps/create', 'coupons', 'coupons/create', 'coupons/bulk-issue', 'announcements', 'announcements/create', 'announcements/delivery', 'after-registration-deliveries', 'after-registration-deliveries/create', 'queue', 'qr-codes', 'stores', 'audit-logs', 'analytics', 'analysis-deliveries', 'gallery/items', 'gallery/categories', 'help', 'help/existing-member-link', 'settings'];
  const settings = ['basic', 'features', 'profile', 'ranks', 'app-design/member-card', 'admin-design', 'home', 'menu', 'advanced', 'reservations', 'queue', 'coupons', 'lottery', 'announcements', 'wallet', 'payments', 'existing-member-link', 'staff', 'liff-connection', 'liff-designer', 'app-design/branding'];
  const details = new Set<string>();
  for (const path of [...paths, ...settings.map(path => `settings/${path}`)]) {
    const response = await page.goto(`/admin/default/${path}`);
    expect(response?.status(), path).toBe(200);
    const back = page.getByRole('navigation', { name: 'ページの移動' }).first().locator('.web-back');
    await checkBack(back);
    expect(await back.getAttribute('href')).toContain('/admin/default/');
    if (['orders', 'products', 'members', 'reservations', 'coupons', 'stamps', 'accounts'].includes(path)) {
      for (const href of await page.locator('.content-body a[href]').evaluateAll(nodes => nodes.map(node => (node as HTMLAnchorElement).href))) {
        if (/\/admin\/default\/(orders|products|members|reservations|coupons|stamps|accounts)\/\d+(\/edit|\/redemptions|\/grant-qr)?$/.test(href)) details.add(href);
      }
    }
  }
  expect(details.size).toBeGreaterThan(5);
  for (const href of details) {
    const response = await page.goto(href);
    expect(response?.status(), href).toBe(200);
    await checkBack(page.getByRole('navigation', { name: 'ページの移動' }).first().locator('.web-back'));
  }
  await page.setViewportSize({ width: 390, height: 844 });
  for (const path of ['settings/basic', 'products/create', 'coupons/create', 'members', 'help']) {
    await page.goto(`/admin/default/${path}`, { waitUntil: 'domcontentloaded' });
    const back = page.getByRole('navigation', { name: 'ページの移動' }).first().locator('.web-back');
    await checkBack(back);
    await expect(page.locator('#sidebarOverlay')).toHaveCSS('pointer-events', 'none');
    if (path === 'settings/basic') {
      await page.getByRole('button', { name: 'メニューを開く', exact: true }).click();
      await expect(page.locator('#sidebarOverlay')).toHaveCSS('pointer-events', 'auto');
      await page.locator('#sidebarOverlay').click({ position: { x: 380, y: 100 } });
      await expect(page.locator('#sidebarOverlay')).toHaveCSS('pointer-events', 'none');
      await back.click();
      await expect(page).toHaveURL(/\/admin\/default\/settings$/);
    } else await back.click({ trial: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth), path).toBeLessThanOrEqual(390);
  }
});

test('システム管理の全機能・編集・プレビュー画面を統一', async ({ page }) => {
  test.setTimeout(120_000);
  await login(page, true);
  const details = new Set<string>();
  for (const path of ['projects', 'accounts', 'accounts/create', 'audit-logs', 'notices', 'help-articles', 'help-articles/create']) {
    const response = await page.goto(`/system-admin/${path}`);
    expect(response?.status(), path).toBe(200);
    await checkBack(page.getByRole('navigation', { name: 'ページの移動' }).locator('.web-back'));
    const links = await page.locator('.content-body a[href]').evaluateAll(nodes => nodes.map(node => (node as HTMLAnchorElement).href));
    for (const href of links) if (/\/system-admin\/(projects|accounts|help-articles)\/\d+\/(edit|preview)$/.test(href)) details.add(href);
  }
  for (const href of details) {
    const response = await page.goto(href);
    expect(response?.status(), href).toBe(200);
    await checkBack(page.getByRole('navigation', { name: 'ページの移動' }).locator('.web-back'));
  }
});
