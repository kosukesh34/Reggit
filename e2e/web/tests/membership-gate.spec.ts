import { buildSync } from 'esbuild';
import { expect, test, type Page } from '@playwright/test';

const buildGate = (mode: 'production' | 'development') => buildSync({
  stdin: { contents: `
    import React, { useState } from 'react';
    import { createRoot } from 'react-dom/client';
    import { useMembershipGate } from './resources/js/frontend/hooks/useMembershipGate';
    const root = createRoot(document.getElementById('root'));
    window.unmountGate = () => root.unmount();
    function Gate() {
      const [settled, setSettled] = useState(false);
      useMembershipGate(() => { window.switches++; return true; }, () => {
        window.settles++;
        setSettled(true);
      });
      return <p role="status">{settled ? 'Ready' : 'Loading'}</p>;
    }
    root.render(window.strictGate ? <React.StrictMode><Gate /></React.StrictMode> : <Gate />);
  `, resolveDir: process.cwd(), loader: 'tsx' },
  bundle: true, write: false, format: 'iife', jsx: 'automatic',
  define: { 'process.env.NODE_ENV': JSON.stringify(mode) },
}).outputFiles[0].text;

const script = buildGate('production');
const strictScript = buildGate('development');

async function open(page: Page, setup: string, strict = false) {
  await page.route('http://gate.test/', route => route.fulfill({ contentType: 'text/html', body: '<div id="root"></div>' }));
  await page.goto('http://gate.test/');
  await page.addScriptTag({ content: `
    window.strictGate = ${strict};
    window.settles = 0; window.switches = 0; window.popups = 0;
    document.addEventListener('liff-member-open-login-popup', () => window.popups++);
    ${setup}
  ` });
  await page.addScriptTag({ content: strict ? strictScript : script });
}

const automatic = `
  let registrations = 0;
  window.__MEMBER_REGISTRATION_MODE__ = 'optional';
  window.app = { memberManager: {
    fetchMemberContext: async () => ({}),
    getMemberData: () => ({ is_member: false }),
    canAutoRegister: () => true,
    autoRegister: async () => {
      window.registrations = ++registrations;
      if (registrations > 1) return;
      await new Promise(resolve => { window.finishRegistration = resolve; });
    }
  } };
`;

test('LIFF準備完了が重なっても登録完了まで読み込みを維持する', async ({ page }) => {
  await open(page, automatic);
  await page.waitForFunction(() => (window as any).registrations === 1);
  await page.evaluate(() => document.dispatchEvent(new Event('liff-ready')));
  await expect(page.getByRole('status')).toHaveText('Loading');
  expect(await page.evaluate(() => (window as any).registrations)).toBe(1);
  await page.evaluate(() => (window as any).finishRegistration());
  await expect(page.getByRole('status')).toHaveText('Ready');
  expect(await page.evaluate(() => (window as any).settles)).toBe(1);
});

test('本人確認中に画面を離れてもログイン表示や完了通知を行わない', async ({ page }) => {
  await open(page, `
    window.__IS_LIFF__ = true;
    window.__MEMBER_REGISTRATION_MODE__ = 'force';
    window.app = { memberManager: {
      ensureIdentityReady: () => new Promise(resolve => { window.finishIdentity = resolve; })
    } };
  `);
  await page.waitForFunction(() => typeof (window as any).finishIdentity === 'function');
  await page.evaluate(async () => {
    (window as any).unmountGate();
    (window as any).finishIdentity();
    await Promise.resolve();
  });
  expect(await page.evaluate(() => ({ popups: (window as any).popups, settles: (window as any).settles })))
    .toEqual({ popups: 0, settles: 0 });
});

test('登録処理の例外を処理し、読み込みを終了する', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await open(page, automatic.replace('await new Promise(resolve => { window.finishRegistration = resolve; });', 'throw new Error("registration failed");'));
  await expect(page.getByRole('status')).toHaveText('Ready');
  expect(errors).toEqual([]);
  expect(await page.evaluate(() => (window as any).settles)).toBe(1);
});

test('アプリ待機中の画面離脱でポーリングを停止する', async ({ page }) => {
  await page.clock.install();
  await open(page, `
    window.appReads = 0;
    Object.defineProperty(window, 'app', { configurable: true, get: () => { window.appReads++; return undefined; } });
  `);
  await page.waitForFunction(() => (window as any).appReads > 0);
  await page.evaluate(() => (window as any).unmountGate());
  const reads = await page.evaluate(() => (window as any).appReads);
  await page.clock.runFor(200);
  expect(await page.evaluate(() => (window as any).appReads)).toBe(reads);
  expect(await page.evaluate(() => (window as any).settles)).toBe(0);
});


test('StrictModeの最初のeffect取消し後も登録と完了通知は一度だけ行う', async ({ page }) => {
  await open(page, automatic, true);
  await page.waitForFunction(() => (window as any).registrations === 1);
  await expect(page.getByRole('status')).toHaveText('Loading');
  await page.evaluate(() => (window as any).finishRegistration());
  await expect(page.getByRole('status')).toHaveText('Ready');
  expect(await page.evaluate(() => ({ registrations: (window as any).registrations, settles: (window as any).settles })))
    .toEqual({ registrations: 1, settles: 1 });
});

test('登録画面の準備中に離れた場合は登録画面を開かない', async ({ page }) => {
  await open(page, `
    window.registerOpens = 0;
    window.__MEMBER_REGISTRATION_MODE__ = 'force';
    window.app = {
      loadMemberTab: () => new Promise(resolve => { window.finishTab = resolve; }),
      memberManager: {
        getMemberData: () => ({ is_member: false }),
        navigateToRegisterPage: () => window.registerOpens++
      }
    };
  `);
  await page.waitForFunction(() => typeof (window as any).finishTab === 'function');
  await page.evaluate(async () => {
    (window as any).unmountGate();
    (window as any).finishTab();
    await Promise.resolve();
  });
  expect(await page.evaluate(() => ({ registerOpens: (window as any).registerOpens, settles: (window as any).settles })))
    .toEqual({ registerOpens: 0, settles: 0 });
});
