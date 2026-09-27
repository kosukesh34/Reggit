import assert from 'node:assert/strict';
import test from 'node:test';

import {
    normalizeMemberPayload,
    normalizeWalletSummary,
    walletSummariesEqual,
} from './memberPayload.ts';

test('normalizeMemberPayload accepts flat and nested API shapes', () => {
    assert.deepEqual(normalizeMemberPayload({ is_member: true, points: 10 }), { is_member: true, points: 10 });
    assert.deepEqual(normalizeMemberPayload({ data: { is_member: false } }), { is_member: false });
    assert.deepEqual(normalizeMemberPayload({ data: { member: { is_member: true, id: 3 } } }), { is_member: true, id: 3 });
});

test('normalizeMemberPayload rejects payloads without membership state', () => {
    assert.equal(normalizeMemberPayload(null), null);
    assert.equal(normalizeMemberPayload({ member: { id: 3 } }), null);
});

test('normalizeWalletSummary returns finite numeric values', () => {
    assert.deepEqual(
        normalizeWalletSummary({ points: '12', stamps_total: Number.NaN, pending_lottery_count: null }),
        { points: 12, stamps_total: 0, pending_lottery_count: 0 },
    );
});

test('walletSummariesEqual compares every displayed value', () => {
    const summary = { points: 12, stamps_total: 3, pending_lottery_count: 1 };
    assert.equal(walletSummariesEqual(summary, { ...summary }), true);
    assert.equal(walletSummariesEqual(summary, { ...summary, points: 13 }), false);
    assert.equal(walletSummariesEqual(null, summary), false);
});
