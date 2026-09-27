import assert from 'node:assert/strict';
import test from 'node:test';

import { renderProfileForm } from './profileFormRenderer.ts';

const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

test('renderProfileForm renders an escaped edit form from member settings', () => {
    const html = renderProfileForm({
        profile_field_settings: [
            { key: 'name', required: true },
            { key: 'email', label: '<メール>', description: '<script>alert(1)</script>' },
        ],
        profile: { name: '山田 太郎', email: '" onfocus="alert(1)' },
    }, null, {}, escapeHtml);

    assert.match(html, /id="profileEditForm"/);
    assert.match(html, /name="name_last" value="山田"/);
    assert.match(html, /name="name_first" value="太郎"/);
    assert.match(html, /&lt;メール&gt;/);
    assert.match(html, /&quot; onfocus=&quot;alert\(1\)/);
    assert.doesNotMatch(html, /<script>/);
});

test('renderProfileForm renders forced registration without a cancel action', () => {
    const html = renderProfileForm({
        profile_field_settings: [{ key: 'notification_preference', label: '通知' }],
        profile: {},
    }, null, { isRegister: true, fullScreen: true, forced: true, isNativeApp: true }, escapeHtml);

    assert.match(html, /id="registerForm"/);
    assert.match(html, /Push通知の受け取り範囲/);
    assert.match(html, /value="all" selected/);
    assert.doesNotMatch(html, />キャンセル</);
});

test('renderProfileForm groups address fields and preserves configured choices', () => {
    const html = renderProfileForm({
        profile_field_settings: [
            { key: 'postal_code', label: '郵便番号' },
            { key: 'address', label: '住所' },
            { key: 'interests', label: '興味', input_type: 'checkbox', values: ['料理', '<旅行>'] },
        ],
        profile: { postal_code: '100-0001', address: '東京都', interests: ['<旅行>'] },
    }, null, {}, escapeHtml);

    assert.match(html, /member-form-section--address/);
    assert.match(html, /住所を自動入力/);
    assert.match(html, /value="&lt;旅行&gt;" checked/);
    assert.doesNotMatch(html, /value="<旅行>"/);
});

test('renderProfileForm uses default fields for malformed settings', () => {
    const html = renderProfileForm({ profile_field_settings: null, profile: {} }, null, {}, escapeHtml);

    assert.match(html, /profileEdit_name_last/);
    assert.match(html, /profileEdit_postal_code/);
    assert.match(html, /profileEdit_birthday/);
});
