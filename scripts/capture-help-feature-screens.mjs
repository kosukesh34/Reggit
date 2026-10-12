import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, access } from 'node:fs/promises';

const base = process.env.HELP_BASE_URL ?? 'http://127.0.0.1:8091';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) throw new Error('Capture requires a local demo environment');
const guides = JSON.parse(await readFile('database/seeders/data/help-screen-guides.json', 'utf8'));
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
const page = await context.newPage();
await mkdir('public/images/help', { recursive: true });
async function login(system = false) {
  await context.clearCookies();
  await page.goto(`${base}${system ? '/system-admin/login' : '/admin/login'}`);
  await page.locator('#login_id').fill(system ? 'demo_sys' : 'demo_proj');
  await page.locator('#password').fill(system ? 'DemoSys#2026' : 'DemoProj#2026');
  await page.getByRole('button', { name: 'ログイン', exact: true }).click();
  await page.waitForURL(url => system ? url.pathname === '/system-admin/projects' : url.pathname.endsWith('/dashboard'));
}
try {
  await login();
  const prefix = new URL(page.url()).pathname.replace(/\/dashboard$/, '');
  const unique = [...new Map(Object.values(guides).map(g => [g.image, g])).values()];
  for (const guide of unique.filter(g => !g.path.startsWith('system:'))) {
    await capture(guide, prefix);
  }
  await login(true);
  for (const guide of unique.filter(g => g.path.startsWith('system:'))) {
    await capture(guide, prefix);
  }
} finally {
  await browser.close();
}
async function capture(guide, prefix) {
  if (process.env.HELP_CAPTURE_RESUME === "1") {
    try { await access(`public/images/help/guide-${guide.image}.png`); return; } catch {}
  }
  const mobile = guide.path.startsWith('app:');
  await page.setViewportSize(mobile ? { width: 390, height: 844 } : { width: 1440, height: 1000 });
  const path = mobile ? `/demo/liff#${guide.path.slice(4)}`
    : guide.path.startsWith('system:') ? `/system-admin${guide.path.slice(7)}`
      : ['/admin/login', '/onboarding'].includes(guide.path) ? guide.path : `${prefix}${guide.path}`;
  if (guide.path === '/admin/login') await context.clearCookies();
  const response = await page.goto(`${base}${path}`);
  if (response && response.status() >= 400) throw new Error(`${path}: HTTP ${response.status()}`);
  await expect(page.locator('body')).not.toContainText('画面を読み込んでいます', { timeout: 30000 });
  await page.locator('main:visible, .native-screen:visible, h1:visible, h2:visible, .page-title:visible').first().waitFor({ state: 'visible' });
  await page.evaluate(() => document.fonts.ready);
  // Capture after images have loaded, without triggering business operations.
  await page.evaluate(async () => Promise.all([...document.images].map(img => img.complete ? undefined : new Promise(resolve => { img.onload = resolve; img.onerror = resolve; }))));
  await page.evaluate(() => {
    const banner = document.createElement('div');
    banner.textContent = '操作説明用のデモ画面です。氏名・件数・設定値は例です。';
    banner.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:100000;background:#fff4cc;color:#332b00;padding:9px;text-align:center;font-size:12px';
    document.body.append(banner);
    const target = document.querySelector('.native-form, .native-screen, .content-header, .page-header, .card');
    if (target) target.style.outline = '3px solid #e11d48';
  });
  await page.screenshot({ path: `public/images/help/guide-${guide.image}.png` });
  console.log(`Captured guide-${guide.image}`);
  if (guide.path === '/admin/login') await login();
}
