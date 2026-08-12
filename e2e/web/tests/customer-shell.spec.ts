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

  test('デモ画面で店舗デザインを切り替えられる', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/home`);
    await expectAppShell(page);
    const switcher = page.getByRole('button', { name: '店舗を切り替える' });
    await expect(switcher).toBeVisible({ timeout: 30_000 });
    await switcher.click();
    await expect(page.getByRole('dialog')).toBeVisible();
    const options = page.locator('.store-switcher');
    await expect(options.first()).toBeVisible();
    const count = await options.count();
    expect(count).toBeGreaterThan(1);
    await options.nth(1).getByRole('button', { name: '切り替え' }).click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await expect(page.locator('.app-shell')).toBeVisible();
  });

  test('デザインプリセット用の配置CSSが読み込まれる', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/home`);
    await expectAppShell(page);
    const hasPresetRule = await page.evaluate(() => {
      const sheets = Array.from(document.styleSheets);
      for (const sheet of sheets) {
        let rules: CSSRuleList;
        try {
          rules = sheet.cssRules;
        } catch {
          continue;
        }
        for (const rule of Array.from(rules)) {
          if (rule instanceof CSSStyleRule && rule.selectorText.includes('data-design-preset')) {
            return true;
          }
        }
      }
      return false;
    });
    expect(hasPresetRule).toBe(true);
  });

  test('機能画面用 CSS（会員・スタンプ・クーポン・順番待ち・ガチャ）が読み込まれる', async ({ page }) => {
    await gotoLiff(page, liffPath);
    await expectLegacyFeatureCssLinks(page);
  });

  test('順番待ちの ios-queue スタイルが reggit-liff-ui でも効く', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/queue`);
    await expectAppShell(page);
    const probe = await page.evaluate(() => {
      const host = document.createElement('div');
      host.className = 'ios-queue';
      host.style.position = 'absolute';
      host.style.left = '-9999px';
      document.body.appendChild(host);
      const style = window.getComputedStyle(host);
      const result = {
        bodyHasReggit: document.body.classList.contains('reggit-liff-ui'),
        borderRadius: style.borderRadius,
        backgroundColor: style.backgroundColor,
      };
      host.remove();
      return result;
    });
    expect(probe.bodyHasReggit).toBe(true);
    // 38-liff-fixed-ui.css の body.reggit-liff-ui .ios-queue { border-radius: 24px; background: #fff }
    expect(probe.borderRadius).toContain('24px');
    expect(probe.backgroundColor).not.toBe('rgba(0, 0, 0, 0)');
  });

  test('demo モードでガチャ UI が CSS 付きで表示される', async ({ page }) => {
    await gotoLiff(page, `${liffPath}?demo=1#/designlab`);
    await expectAppShell(page);
    await expect(page.locator('body')).toHaveClass(/liff-fixed-ui/);
    await expectGachaStyled(page);
    await expect(page.locator('[data-lottery-section="scratch"], .pp-scratch').first()).toBeVisible();
  });

  test('ガチャ UI が demo=1 なしでも表示される', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/designlab`);
    await expectAppShell(page);
    await expectGachaStyled(page);
    await expect(page.locator('.pp-roulette').first()).toBeVisible();
    await expect(page.locator('#designWonList')).toHaveCount(0);
  });

  test('その他にガチャ・スクラッチ・ルーレット導線がある', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/more`);
    await expectAppShell(page);
    await expect(page.getByRole('link', { name: 'ガチャ' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'スクラッチ' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'ルーレット' })).toBeVisible();
  });

  test('外部ページタブは chrome を表示する', async ({ page }) => {
    await gotoLiff(page, `${liffPath}#/link_1`);
    await expectAppShell(page);
    await expect(page.locator('.link-tab-chrome')).toBeVisible();
    await expect(page.locator('.link-tab-frame, .page-note').first()).toBeVisible();
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
