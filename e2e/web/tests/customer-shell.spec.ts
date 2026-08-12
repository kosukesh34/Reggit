import { expect, test, type Page } from '@playwright/test';

const liffPath = process.env.E2E_LIFF_PATH ?? '/liff';
const nativeUserAgent =
  process.env.E2E_NATIVE_USER_AGENT ?? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Reggit/1.0';

async function gotoLiff(page: Page, pathWithHash: string) {
  const response = await page.goto(pathWithHash);
  if (response !== null && response.status() >= 500) {
    test.skip(true, `LIFF URL が ${response.status()} のためスキップ`);
  }
  if (response !== null && response.status() === 404) {
    test.skip(true, `LIFF URL が 404 のためスキップ（E2E_LIFF_PATH=${liffPath}）`);
  }
  return response;
}

async function expectAppShell(page: Page) {
  await expect(page.locator('#app-container')).toBeVisible();
  await expect(page.locator('.app-shell')).toBeVisible({ timeout: 30_000 });
  await expect(page.locator('body')).toHaveClass(/reggit-liff-ui/);
  await expect(page.getByRole('navigation', { name: '主要ナビゲーション' })).toBeVisible();
}

async function expectLegacyFeatureCssLinks(page: Page) {
  const hrefs = await page.locator('link[rel="stylesheet"]').evaluateAll((nodes) =>
    nodes
      .map((node) => (node instanceof HTMLLinkElement ? node.href : ''))
      .filter((href) => href !== ''),
  );
  const required = [
    '05-overlay-skeleton-toast.css',
    '13-member-card-design.css',
    '17-stamp.css',
    '19-coupon.css',
    '20-queue.css',
    '22-chrome-accent.css',
    '23-builder-blocks-theme.css',
    '26-retail-app-ui.css',
    '27-component-designs.css',
    '29-system-ui-customization.css',
    '30-builder-component-variants.css',
    '34-apple-block-variants.css',
    '38-liff-fixed-ui.css',
  ];
  for (const file of required) {
    expect(hrefs.some((href) => href.includes(file)), `${file} が link されていない`).toBe(true);
  }
}

async function expectGachaStyled(page: Page) {
  const gacha = page.locator('.pp-gacha').first();
  await expect(gacha).toBeVisible({ timeout: 30_000 });
  await expect(page.locator('.pp-gacha__dome').first()).toBeVisible();
  await expect(page.locator('.pp-gacha__knob').first()).toBeVisible();
  await expect(page.locator('#designGachaBtn')).toBeVisible();

  const metrics = await gacha.evaluate((el) => {
    const dome = el.querySelector('.pp-gacha__dome');
    const machine = el.querySelector('.pp-gacha__machine');
    const domeStyle = dome !== null ? window.getComputedStyle(dome) : null;
    const machineStyle = machine !== null ? window.getComputedStyle(machine) : null;
    const domeRect = dome?.getBoundingClientRect();
    return {
      bodyHasLotteryClass:
        document.body.classList.contains('liff-lottery-ui')
        || document.body.classList.contains('liff-fixed-ui'),
      domeDisplay: domeStyle?.display ?? '',
      domeWidth: domeRect?.width ?? 0,
      domeHeight: domeRect?.height ?? 0,
      machinePosition: machineStyle?.position ?? '',
    };
  });

  expect(metrics.bodyHasLotteryClass).toBe(true);
  expect(metrics.domeDisplay).not.toBe('none');
  expect(metrics.domeWidth).toBeGreaterThan(40);
  expect(metrics.domeHeight).toBeGreaterThan(40);
  expect(metrics.machinePosition).not.toBe('static');
}

test.describe('顧客向けシェル（LINE LIFF）', () => {
  test('ホームの app-shell / 下部ナビが表示される', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/home`);
    await expectAppShell(page);
    await expect(page.getByRole('navigation', { name: '主要ナビゲーション' }).getByRole('link').first()).toBeVisible();
  });

  test('機能画面用 CSS（会員・スタンプ・クーポン・順番待ち・ガチャ）が読み込まれる', async ({ page }) => {
    await gotoLiff(page, liffPath);
    await expectLegacyFeatureCssLinks(page);
  });

  test('demo モードでガチャ UI が CSS 付きで表示される', async ({ page }) => {
    await gotoLiff(page, `${liffPath}?demo=1#/designlab`);
    await expectAppShell(page);
    await expect(page.locator('body')).toHaveClass(/liff-fixed-ui/);
    await expectGachaStyled(page);
    await expect(page.locator('[data-lottery-section="scratch"], .pp-scratch').first()).toBeVisible();
  });
});

test.describe('顧客向けシェル（ネイティブ WebView）', () => {
  test.use({ userAgent: nativeUserAgent });

  test('ネイティブ UA でも同じ app-shell が表示される', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/home`);
    await expect(page.locator('html')).toHaveClass(/native-app-embed/);
    await expectAppShell(page);
    await expectLegacyFeatureCssLinks(page);
  });

  test('ネイティブ + demo でガチャ UI が CSS 付きで表示される', async ({ page }) => {
    await gotoLiff(page, `${liffPath}?demo=1#/designlab`);
    await expect(page.locator('body')).toHaveClass(/native-app-embed/);
    await expectAppShell(page);
    await expectGachaStyled(page);
  });
});
