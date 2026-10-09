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

function browser(storageFailure = null) {
    const calls = [];
    const storage = new Map([['device-token:shop:device', 'dummy-device-token']]);
    const context = vm.createContext({ URL, URLSearchParams, console,
        localStorage: { getItem: (key) => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: (key) => storage.delete(key) },
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
    context.window.localStorage = context.localStorage;
    if (storageFailure === 'getter') {
        const denied = () => { throw new DOMException('Storage access denied', 'SecurityError'); };
        Object.defineProperty(context.window, 'localStorage', { get: denied });
        Object.defineProperty(context, 'localStorage', { get: denied });
    } else if (storageFailure === 'write') {
        context.localStorage.setItem = () => { throw new DOMException('Storage full', 'QuotaExceededError'); };
    }
    context.window.liff.getDecodedIDToken = () => ({ sub: 'U' + 'a'.repeat(32) });
    vm.runInContext(bundle.outputFiles[0].text, context);
    return { api: context.api, calls, context, storage };
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

for (const failure of ['getter', 'write']) {
    test(`storage ${failure} failure does not interrupt LINE authentication or session clearing`, async () => {
        const { api, calls } = browser(failure);
        const client = new api.ApiClient();
        assert.equal((await client.post('/api/member', {})).success, true);
        assert.equal(calls[0].config.headers['X-Line-User-Id'], 'U' + 'a'.repeat(32));
        assert.doesNotThrow(() => client.clearPersistedLineUserId());
        assert.equal(client.getPersistedLineUserId(), null);
    });

    test(`storage ${failure} failure retains newly registered device credentials in this session`, async () => {
        const { api, calls, context } = browser(failure);
        assert.doesNotThrow(() => api.ApiClient.saveDeviceToken('device', 'new-device-token'));
        await new api.ApiClient().post('/api/member', {});
        assert.equal(calls[0].config.headers['X-Device-Token'], 'new-device-token');
        context.window.__PROJECT_KEY__ = 'other-shop';
        assert.equal(api.ApiClient.getDeviceToken('device'), '');
        context.window.__PROJECT_KEY__ = 'shop';
        assert.equal(api.ApiClient.getDeviceToken('other-device'), '');
    });
}

test('a successful device-token write replaces the failed-write fallback and restores persisted reads', () => {
    const { api, context, storage } = browser('write');
    api.ApiClient.saveDeviceToken('device', 'session-token');
    assert.equal(api.ApiClient.getDeviceToken('device'), 'session-token');
    context.localStorage.setItem = (key, value) => storage.set(key, value);
    api.ApiClient.saveDeviceToken('device', 'persisted-token');
    assert.equal(storage.get('device-token:shop:device'), 'persisted-token');
    storage.set('device-token:shop:device', 'rotated-token');
    assert.equal(api.ApiClient.getDeviceToken('device'), 'rotated-token');
});
