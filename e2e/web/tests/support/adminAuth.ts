import { mkdirSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, type Page } from '@playwright/test';

export type AdminRole = 'store' | 'project' | 'system';

const accounts: Record<AdminRole, { loginPath: string; loginId: string; password: string }> = {
  store: {
    loginPath: '/admin/login',
    loginId: process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store',
    password: process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026',
  },
  project: { loginPath: '/admin/default/login', loginId: 'demo_proj', password: 'DemoProj#2026' },
  system: {
    loginPath: '/system-admin/login',
    loginId: process.env.E2E_SYSTEM_LOGIN_ID ?? 'demo_sys',
    password: process.env.E2E_SYSTEM_PASSWORD ?? 'DemoSys#2026',
  },
};

// ログイン状態と仮想パスキーは実行中だけ使う。scripts/e2e-web.sh が終了時に消す
const authDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../.auth');

export const adminStorageState = (role: AdminRole): string => resolve(authDir, `${role}.json`);
const passkeyFile = (role: AdminRole): string => resolve(authDir, `${role}-passkey.json`);

/**
 * 管理画面はパスワードの後にパスキーが必須。仮想認証器で登録・確認する（RP ID がホスト名になるため localhost で開く）。
 * 登録したパスキーは保存し、次のログインでは同じパスキーで確認する。
 */
export async function loginAdmin(page: Page, role: AdminRole): Promise<void> {
  const account = accounts[role];
  const client = await page.context().newCDPSession(page);
  await client.send('WebAuthn.enable');
  const { authenticatorId } = await client.send('WebAuthn.addVirtualAuthenticator', {
    options: {
      protocol: 'ctap2',
      transport: 'internal',
      hasResidentKey: true,
      hasUserVerification: true,
      isUserVerified: true,
      automaticPresenceSimulation: true,
    },
  });
  if (existsSync(passkeyFile(role))) {
    for (const credential of JSON.parse(readFileSync(passkeyFile(role), 'utf8'))) {
      await client.send('WebAuthn.addCredential', { authenticatorId, credential });
    }
  }

  await page.goto(account.loginPath);
  await page.getByLabel('ID', { exact: true }).fill(account.loginId);
  await page.getByLabel('パスワード', { exact: true }).fill(account.password);
  await page.getByRole('button', { name: 'ログイン', exact: true }).click();
  await page.waitForURL((url) => !/\/login(\/form)?$/.test(url.pathname));
  if (page.url().includes('/passkey/enroll')) {
    await page.getByLabel('端末名').fill('E2E');
    await page.getByRole('button', { name: 'パスキーを作成して続行' }).click();
  } else if (page.url().includes('/passkey/verify')) {
    // 確認画面は開くと自動でパスキー認証を始める。進まないときだけボタンを押す
    await page.waitForURL((url) => !url.pathname.includes('/passkey/'), { timeout: 3000 })
      .catch(() => page.getByRole('button', { name: 'パスキーで続行' }).click());
  }
  await expect(page).not.toHaveURL(/\/(passkey\/|login(\/form)?$)/);

  const { credentials } = await client.send('WebAuthn.getCredentials', { authenticatorId });
  mkdirSync(authDir, { recursive: true });
  writeFileSync(passkeyFile(role), JSON.stringify(credentials));
}

/** ログイン済みの店舗管理画面の URL 接頭辞（例: /admin/default） */
export async function adminPrefix(page: Page): Promise<string> {
  await page.goto('/admin/login');
  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);

  return new URL(page.url()).pathname.replace(/\/dashboard$/, '');
}
