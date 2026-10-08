import { expect, test } from '@playwright/test';
import { build } from 'esbuild';

let script: string;
test.beforeAll(async () => {
  const result = await build({
    stdin: {
      contents: `import React from 'react';
        import { createRoot } from 'react-dom/client';
        import { AppConfigProvider } from './resources/js/liff-ui/context/AppConfigContext';
        import { RuntimeDataProvider, useRuntimeData } from './resources/js/liff-ui/context/RuntimeDataContext';
        function Probe() {
          const runtime = useRuntimeData();
          return React.createElement('button', { onClick: () => runtime.saveSettings({}).then(() => { document.body.dataset.saved = 'yes'; }) }, '設定を保存');
        }
        export function mount() {
          createRoot(document.getElementById('root')).render(React.createElement(AppConfigProvider, null, React.createElement(RuntimeDataProvider, null, React.createElement(Probe))));
        }`,
      resolveDir: process.cwd(),
    },
    bundle: true, write: false, format: 'iife', globalName: 'StorageReview',
    define: { 'import.meta.env': '{}' },
  });
  script = result.outputFiles[0].text;
});

for (const failure of ['SecurityError', 'QuotaExceededError', 'null-state', 'array-state']) {
  test(`@component 保存領域の${failure}でも設定操作を継続できる`, async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setContent('<body class="liff-demo-data" data-app-mode="demo"><div id="root"></div></body>');
    await page.evaluate((failure) => {
      Object.defineProperty(window, 'localStorage', { configurable: true, value: {
        getItem: (key: string) => key.includes('runtime-data') ? (failure === 'null-state' ? 'null' : failure === 'array-state' ? '[]' : null) : null,
        setItem: () => { if (failure === 'SecurityError' || failure === 'QuotaExceededError') throw new DOMException('Storage unavailable', failure); },
      } });
    }, failure);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => (window as any).StorageReview.mount());
    await page.getByRole('button', { name: '設定を保存' }).click();
    await expect(page.locator('body')).toHaveAttribute('data-saved', 'yes');
    expect(errors).toEqual([]);
  });
}
