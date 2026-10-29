import test from 'node:test';
import assert from 'node:assert/strict';
import { lineQuotaCheck } from './lineQuota.ts';

test('残りに収まるときは使う通数と残りを伝える', () => {
  const result = lineQuotaCheck(120, 1, { used: 1000, limit: 1200 });
  assert.equal(result.over, false);
  assert.match(result.message, /約120通.*残りは200通/);
});

test('クーポン付きは1人2通で数え、残りを超えると警告する', () => {
  const result = lineQuotaCheck(120, 2, { used: 1000, limit: 1200 });
  assert.equal(result.over, true);
  assert.match(result.message, /約240通.*残りは200通.*約40通は送れず/);
  assert.equal(lineQuotaCheck(1, 1, { used: 1500, limit: 1200 }).over, true);
});

test('上限のないプランでは警告しない', () => {
  assert.deepEqual(lineQuotaCheck(5000, 2, { used: 99999, limit: null }), { message: 'この配信でLINEを約10,000通使います（上限なし）。', over: false });
});
