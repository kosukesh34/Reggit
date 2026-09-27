import assert from 'node:assert/strict';
import test from 'node:test';

import { formatRankUpHistoryHtml } from './rankHistoryFormatter.ts';

test('formatRankUpHistoryHtml renders an empty state for unsupported values', () => {
    assert.match(formatRankUpHistoryHtml(null), /まだランクアップ履歴がありません/);
    assert.match(formatRankUpHistoryHtml([]), /まだランクアップ履歴がありません/);
});

test('formatRankUpHistoryHtml renders labels and dates', () => {
    const html = formatRankUpHistoryHtml([{
        achieved_at: '2026/09/27',
        from_rank_label: 'シルバー',
        to_rank_label: 'ゴールド',
    }]);

    assert.match(html, /シルバー/);
    assert.match(html, /ゴールド/);
    assert.match(html, /2026\/09\/27/);
});

test('formatRankUpHistoryHtml escapes untrusted API values', () => {
    const html = formatRankUpHistoryHtml([{
        achieved_at: '<script>alert(1)</script>',
        from_rank_label: '<b>Bronze</b>',
        to_rank_label: 'Gold & Platinum',
    }]);

    assert.doesNotMatch(html, /<script>|<b>/);
    assert.match(html, /&lt;script&gt;/);
    assert.match(html, /Gold &amp; Platinum/);
});
