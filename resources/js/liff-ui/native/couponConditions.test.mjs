import test from 'node:test';
import assert from 'node:assert/strict';
import { couponBadges, couponCombineNotice, couponConditionRows } from './couponConditions.ts';

test('併用区分・対象商品・最低金額をバッジにする', () => {
  assert.deepEqual(couponBadges({ combine_division: '1', target_products: ['カフェラテ'], min_order_amount: 1000 }), ['併用不可', '対象商品あり', '¥1,000以上']);
  assert.deepEqual(couponBadges({ combine_division: '3', target_products: [], min_order_amount: null }), ['併用OK']);
  assert.deepEqual(couponBadges({}), []);
});

test('詳細には対象商品と金額の範囲と併用の説明を出す', () => {
  assert.deepEqual(couponConditionRows({ combine_division: '2', target_products: ['カフェラテ', 'スコーン'], min_order_amount: 1000, max_order_amount: 5000 }), [
    { label: '対象商品', value: 'カフェラテ、スコーンのいずれかを含むお会計で使えます' },
    { label: 'お会計金額', value: '¥1,000〜¥5,000のお会計で使えます' },
    { label: '併用', value: '「併用OK」のクーポンとだけ一緒に使えます' },
  ]);
  assert.deepEqual(couponConditionRows({ max_order_amount: '3000' }), [{ label: 'お会計金額', value: '¥3,000以下のお会計で使えます' }]);
});

test('バーコード表示の併用注意は併用不可と一部併用可のときだけ出す', () => {
  assert.equal(couponCombineNotice({ combine_division: '1' }), 'このクーポンは他のクーポンと併用できません');
  assert.equal(couponCombineNotice({ combine_division: '2' }), '「併用OK」のクーポンとだけ併用できます');
  assert.equal(couponCombineNotice({ combine_division: '3' }), null);
  assert.equal(couponCombineNotice({}), null);
});
