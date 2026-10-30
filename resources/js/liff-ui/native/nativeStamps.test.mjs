import test from 'node:test';
import assert from 'node:assert/strict';
import { stampLayout } from './nativeStamps.ts';

test('押した枠は押したあと、残りは押す前の画像を使い、特典の位置に印を付ける', () => {
  const layout = stampLayout({ required_stamps: 4, current_stamps: 2, stamp_images: { 1: '/a.png' }, stamp_empty_images: { 1: '/x.png', 3: '/b.png' }, reward_milestones: [{ stamp_count: 3, description: 'クーポン' }, { stamp_count: 9, description: '範囲外' }, { stamp_count: 2, description: '' }] });
  assert.deepEqual(layout.pages[0], [
    { position: 1, filled: true, image: '/a.png', reward: false },
    { position: 2, filled: true, image: undefined, reward: false },
    { position: 3, filled: false, image: '/b.png', reward: true },
    { position: 4, filled: false, image: undefined, reward: false },
  ]);
  assert.deepEqual(layout.milestones, [{ count: 3, description: 'クーポン' }]);
  assert.equal(layout.pages.length, 1);
});

test('25個までは1行5個で全部、それより多いと1行10個で50個ずつページを分け、次に押すページから開く', () => {
  const small = stampLayout({ required_stamps: 25 });
  assert.deepEqual([small.columns, small.pages.length, small.pages[0].length], [5, 1, 25]);
  const many = stampLayout({ required_stamps: 100, current_stamps: 60 });
  assert.deepEqual([many.columns, many.pages.length, many.initialPage, many.pages[1][0].position, many.pages[1][many.pages[1].length - 1].position], [10, 2, 1, 51, 100]);
  assert.deepEqual([stampLayout({ required_stamps: 100, current_stamps: 100 }).initialPage, stampLayout({ required_stamps: 101 }).pages[2].length], [1, 1]);
  const row = stampLayout({ layout_style: 'row', required_stamps: 60, current_stamps: 30 });
  assert.deepEqual([row.columns, row.pages.length, row.pages[0].length], [undefined, 1, 60]);
});

test('知らないデザインと並べ方は標準にする', () => {
  const unknown = stampLayout({ design: 'toString', layout_style: 'constructor', required_stamps: 3 });
  assert.deepEqual([unknown.design, unknown.name], ['standard', 'grid']);
  assert.equal(stampLayout({ design: 'membership' }).design, 'membership');
});

test('次の特典は途中の特典、なければゴールを目標にし、貯まったら出さない', () => {
  const milestones = [{ stamp_count: 5, description: 'お菓子' }, { stamp_count: 10, description: 'ドリンク' }];
  assert.deepEqual(stampLayout({ required_stamps: 10, current_stamps: 6, reward_milestones: milestones }).nextReward, { count: 10, description: 'ドリンク', remaining: 4, goal: false });
  assert.deepEqual(stampLayout({ required_stamps: 10, current_stamps: 2, reward_milestones: milestones }).nextReward, { count: 5, description: 'お菓子', remaining: 3, goal: false });
  assert.deepEqual(stampLayout({ required_stamps: 8, current_stamps: 0 }).nextReward, { count: 8, description: '', remaining: 8, goal: true });
  assert.equal(stampLayout({ required_stamps: 8, current_stamps: 8 }).nextReward, undefined);
});
