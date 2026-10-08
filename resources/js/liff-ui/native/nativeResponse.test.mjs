import test from 'node:test';
import assert from 'node:assert/strict';
import { unwrapNativeResponse } from './nativeResponse.ts';
test('V1 device coupon envelopes expose their coupons', () => {
  assert.deepEqual(unwrapNativeResponse({ success: true, data: { coupons: [{ id: 7 }] } }), { coupons: [{ id: 7 }] });
});
test('flat membership and coupons keep their original shape', () => {
  const member = { is_member: true, member: { member_number: '000123' } };
  assert.equal(unwrapNativeResponse(member), member);
  const coupons = { coupons: [{ id: 7 }] };
  assert.equal(unwrapNativeResponse(coupons), coupons);
});
test('Laravel order paginators retain page metadata and their data array', () => {
  const page = { current_page: 1, data: [{ id: 9 }], last_page: 2 };
  assert.equal(unwrapNativeResponse(page), page);
});
test('application failures and missing responses never become success', () => {
  assert.throws(() => unwrapNativeResponse({ success: false, message: '利用できません' }), /利用できません/);
  assert.throws(() => unwrapNativeResponse(undefined), /応答/);
});
