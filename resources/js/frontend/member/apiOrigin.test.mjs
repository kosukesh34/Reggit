import assert from 'node:assert/strict';
import { test } from 'node:test';
import { build } from 'esbuild';
import vm from 'node:vm';

const bundle = await build({ stdin: {
    contents: `export { ApiClient } from './resources/js/shared/utils/apiClient';
               export { reggitApi } from './resources/js/liff-ui/services/reggitApi';
               export { parseStampGrant } from './resources/js/liff-ui/utils/stampGrant';`,
    resolveDir: process.cwd(), loader: 'ts',
}, bundle: true, write: false, format: 'iife', globalName: 'api', platform: 'browser' });

function browser() {
    const calls = [];
    const storage = new Map([['device-token:shop:device', 'dummy-device-token']]);
    const context = vm.createContext({ URL, URLSearchParams, console,
        localStorage: { getItem: (key) => storage.get(key), setItem() {}, removeItem() {} },
        document: { querySelector: () => ({ getAttribute: () => 'dummy-csrf' }) },
        window: { location: { origin: 'https://shop.example', search: '' },
            __PROJECT_KEY__: 'shop', __API_TOKEN__: 'dummy-bearer',
            __MOBILE_DEVICE_INFO__: { device_identifier: 'device' },
            liff: { isLoggedIn: () => true, getIDToken: () => 'dummy-line-token' },
        },
        fetch: async (url, config) => {
            calls.push({ url, config });
            return { ok: true, headers: { get: () => 'application/json' }, json: async () => ({ success: true }) };
        },
    });
    vm.runInContext(bundle.outputFiles[0].text, context);
    return { api: context.api, calls };
}

test('external absolute and protocol-relative URLs are rejected before fetch', async () => {
    const { api, calls } = browser();
    for (const url of ['https://untrusted.example/api/stamps/grant/123', '//untrusted.example/api/stamps/grant/123']) {
        const result = await new api.ApiClient().post(url, {});
        assert.equal(result.success, false);
    }
    assert.equal(calls.length, 0);
});

test('same-origin requests retain CSRF, bearer, LINE and device credentials', async () => {
    const { api, calls } = browser();
    for (const url of ['/api/member', 'https://shop.example/api/member']) {
        await new api.ApiClient().post(url, {});
    }
    assert.equal(calls.length, 2);
    const headers = calls[0].config.headers;
    assert.equal(headers['X-CSRF-TOKEN'], 'dummy-csrf');
    assert.equal(headers.Authorization, 'Bearer dummy-bearer');
    assert.equal(headers['X-Line-Id-Token'], 'dummy-line-token');
    assert.equal(headers['X-Device-Token'], 'dummy-device-token');
});

test('a signed-path QR must belong to the application origin', async () => {
    const { api, calls } = browser();
    assert.equal(api.parseStampGrant('https://untrusted.example/api/stamps/grant/123'), null);
    assert.equal(api.parseStampGrant('/api/stamps/grant/123?signature=dummy').kind, 'signed');
    await assert.rejects(api.reggitApi.addStamp('shop', 'https://untrusted.example/api/stamps/grant/123'), /利用できません/);
    assert.equal(calls.length, 0);
});
