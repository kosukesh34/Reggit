import assert from 'node:assert/strict';
import test from 'node:test';

import {
    buildLiffEntryUrl,
    formatWalletErrorMessage,
    normalizeExternalUrl,
    resolveApiUrl,
    resolveAppOrigin,
    resolveGoogleWalletOpenUrl,
} from './walletUrlPolicy.ts';

test('buildLiffEntryUrl encodes the project key in liff.state', () => {
    const result = buildLiffEntryUrl('123-example', 'store one');
    const url = new URL(result);

    assert.equal(url.origin + url.pathname, 'https://liff.line.me/123-example');
    assert.equal(url.searchParams.get('liff.state'), 'project_key=store+one');
});

test('resolveAppOrigin prefers the current browser origin', () => {
    assert.equal(
        resolveAppOrigin({ origin: 'https://shop.example.com' }, 'https://api.example.com/api'),
        'https://shop.example.com',
    );
    assert.equal(resolveAppOrigin({ origin: 'null' }, 'https://api.example.com/api'), 'https://api.example.com');
});

test('resolveApiUrl preserves absolute URLs and resolves application paths', () => {
    assert.equal(resolveApiUrl('https://wallet.example.com/pass', 'https://shop.example.com'), 'https://wallet.example.com/pass');
    assert.equal(resolveApiUrl('api/member', 'https://shop.example.com'), 'https://shop.example.com/api/member');
});

test('normalizeExternalUrl upgrades non-local HTTP URLs only', () => {
    assert.equal(normalizeExternalUrl('http://example.com/pass'), 'https://example.com/pass');
    assert.equal(normalizeExternalUrl('http://localhost:8000/pass'), 'http://localhost:8000/pass');
    assert.equal(normalizeExternalUrl('javascript:alert(1)'), null);
});

test('resolveGoogleWalletOpenUrl accepts save and redirect URLs', () => {
    assert.equal(
        resolveGoogleWalletOpenUrl('http://pay.google.com/gp/v/save/token'),
        'https://pay.google.com/gp/v/save/token',
    );
    assert.equal(
        resolveGoogleWalletOpenUrl('http://example.com/google-wallet/save/token'),
        'https://example.com/google-wallet/save/token',
    );
});

test('formatWalletErrorMessage retains actionable errors and supplies a fallback', () => {
    assert.equal(formatWalletErrorMessage(new Error('外部ブラウザを開けません')), '外部ブラウザを開けません');
    assert.equal(formatWalletErrorMessage({}, 'Wallet の取得に失敗しました'), 'Wallet の取得に失敗しました');
});
