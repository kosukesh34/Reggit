import assert from 'node:assert/strict';
import test from 'node:test';
import { formatPointHistoryHtml } from './pointHistoryFormatter.ts';

const escapeHtml = (value) => value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

test('point history formatter renders a stable empty state', () => {
    assert.match(formatPointHistoryHtml(null, escapeHtml), /ポイント履歴がありません/);
    assert.match(formatPointHistoryHtml([], escapeHtml), /ポイント履歴がありません/);
});

test('point history formatter normalizes numeric values and states', () => {
    const html = formatPointHistoryHtml([
        { points: '1200', created_at: '2026-09-27T00:00:00+09:00', description: '購入' },
        { points: -300, created_at: 'invalid', description: '利用' },
    ], escapeHtml);
    assert.match(html, /member-point-history-earned/);
    assert.match(html, /\+1,200pt/);
    assert.match(html, /member-point-history-used/);
    assert.match(html, /-300pt/);
});

test('point history formatter escapes untrusted descriptions', () => {
    const html = formatPointHistoryHtml([{ points: 1, description: '<img src=x onerror=bad()>' }], escapeHtml);
    assert.doesNotMatch(html, /<img/);
    assert.match(html, /&lt;img src=x onerror=bad\(\)&gt;/);
});
