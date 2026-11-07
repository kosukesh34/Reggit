import { chromium, expect } from '@playwright/test';
import { readFile, mkdir, access } from 'node:fs/promises';

const base = process.env.HELP_BASE_URL ?? 'http://127.0.0.1:8091';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) throw new Error('Capture requires a local demo environment');
const guides = JSON.parse(await readFile('database/seeders/data/help-screen-guides.json', 'utf8'));
// The innermost card that contains the heading.
const card = (name, selector = '.card') => page => page.locator(selector).filter({ has: page.locator(`:is(h1,h2,h3,h4):text-is("${name}")`) }).last();
const formWith = name => page => page.locator('form').filter({ has: page.getByRole('button', { name, exact: true }) }).last();
// Each screenshot frames the part of the screen the article tells the reader to operate first.
const targets = {
  'help-index': formWith('検索'),
  'admin-login': page => page.locator('form').filter({ has: page.locator('#login_id') }),
  dashboard: page => page.locator('.admin-store-switcher > summary'),
  stores: card('Reggitで利用する店舗'),
  features: card('利用する機能'),
  'app-membership': page => page.locator('.native-member-pass'),
  'profile-settings': card('プロフィール項目'),
  'app-profile': page => page.getByRole('heading', { name: '基本情報', exact: true }).locator('xpath=..'),
  members: card('検索・絞り込み'),
  'member-tags': card('新規タグ作成'),
  ranks: card('ランク一覧'),
  'qr-codes': card('ポイント付与QR'),
  'app-existing': page => page.locator('.native-form').first(),
  'existing-settings': card('連携の条件'),
  'app-transfer': page => page.getByRole('tab', { name: '引き継ぐ', exact: true }),
  'app-withdraw': page => page.locator('.native-form').first(),
  products: card('商品一覧'),
  'app-menu': page => page.locator('.native-product').first(),
  orders: card('注文一覧'),
  'app-order-history': page => page.locator('.native-orders'),
  payments: card('このプロジェクトのお支払い方法'),
  'app-reservation': page => page.locator('.native-reservation-row').first(),
  reservations: card('絞り込み'),
  'reservation-settings': card('受付日時と予約枠'),
  'app-queue': page => page.locator('.native-queue'),
  queue: card('待機中一覧'),
  'queue-settings': page => page.locator('.settings-page-content .card').first(),
  'basic-settings': page => page.getByRole('tablist').filter({ has: page.getByRole('tab', { name: '店舗端末' }) }),
  stamps: card('スタンプカード一覧'),
  coupons: card('クーポン一覧'),
  'app-coupon': page => page.locator('.native-coupon-card').first(),
  'coupon-bulk': page => page.locator('#delivery-audience'),
  'lottery-settings': card('購入特典の利用条件'),
  announcements: card('お知らせ一覧'),
  'announcement-delivery': page => page.locator('#delivery-audience'),
  'registration-deliveries': card('一覧'),
  'line-settings': page => page.locator('.alert').first(),
  analytics: card('期間'),
  rfm: card('RFMの判定基準'),
  'analysis-deliveries': page => page.locator('.card').filter({ has: page.getByRole('button', { name: '分析専用トークンを発行・再発行' }) }).last(),
  'home-settings': page => page.getByRole('tablist').filter({ has: page.getByRole('tab', { name: 'ヘッダー', exact: true }) }),
  branding: card('店舗名・ロゴ'),
  'member-card-settings': card('会員証の表示'),
  'liff-designer': page => page.getByText('LINEアプリの詳細デザイン', { exact: true }).first(),
  'admin-design': card('管理画面の配色'),
  'wallet-settings': card('Apple Wallet（iPhone）'),
  'staff-settings': card('スタッフを追加'),
  'audit-logs': card('管理操作ログ'),
  gallery: card('画像追加'),
  'app-contact': page => page.getByRole('button', { name: 'メニューを開く', exact: true }),
  'app-more': page => page.locator('.native-more'),
  'system-help': page => page.getByRole('link', { name: 'ヘルプ記事を書く' }),
  onboarding: card('基本情報'),
  'system-projects': card('プロジェクト一覧'),
  'system-accounts': card('プロジェクト管理者一覧'),
  'system-notices': page => page.getByRole('link', { name: 'お知らせを作成' }),
};
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
  await page.waitForURL(url => system ? url.pathname === '/system-admin/projects' : url.pathname.endsWith('/dashboard'), { waitUntil: 'domcontentloaded' });
}
try {
  await login();
  const prefix = new URL(page.url()).pathname.replace(/\/dashboard$/, '');
  const only = process.env.HELP_CAPTURE_ONLY?.split(',');
  const unique = [...new Map(Object.values(guides).map(g => [g.image, g])).values()].filter(g => !only || only.includes(g.image));
  const missing = unique.filter(g => !targets[g.image]).map(g => g.image);
  if (missing.length) throw new Error(`No highlight target: ${missing.join(', ')}`);
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
  // Lazy images outside the viewport never load, so stop waiting after a few seconds.
  await page.evaluate(async () => Promise.race([
    Promise.all([...document.images].map(img => img.complete ? undefined : new Promise(resolve => { img.onload = resolve; img.onerror = resolve; }))),
    new Promise(resolve => setTimeout(resolve, 5000)),
  ]));
  const target = targets[guide.image](page);
  await target.waitFor({ state: 'visible' });
  await target.evaluate(node => {
    const fits = node.getBoundingClientRect().height < window.innerHeight - 140;
    node.style.scrollMarginTop = '140px';
    node.style.scrollMarginBottom = '24px';
    node.scrollIntoView({ block: fits ? 'nearest' : 'start' });
  });
  await page.waitForTimeout(300);
  await target.evaluate(node => {
    const rect = node.getBoundingClientRect();
    const pad = 6;
    const top = Math.max(rect.top - pad, 2);
    const left = Math.max(rect.left - pad, 2);
    // LIFF screens change only the hash, so remove the frame left by the previous capture.
    document.getElementById('help-capture-frame')?.remove();
    const frame = document.createElement('div');
    frame.id = 'help-capture-frame';
    frame.setAttribute('aria-hidden', 'true');
    frame.style.cssText = `position:fixed;z-index:2147483647;pointer-events:none;box-sizing:border-box;border:4px solid #e11d48;border-radius:8px;top:${top}px;left:${left}px;width:${Math.min(rect.right + pad, window.innerWidth - 2) - left}px;height:${Math.min(rect.bottom + pad, window.innerHeight - 2) - top}px`;
    document.body.append(frame);
  });
  await page.screenshot({ path: `public/images/help/guide-${guide.image}.png` });
  console.log(`Captured guide-${guide.image}`);
  if (guide.path === '/admin/login') await login();
}
