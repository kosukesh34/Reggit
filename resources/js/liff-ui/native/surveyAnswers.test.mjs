import test from 'node:test';
import assert from 'node:assert/strict';
import { OTHER_KEY, answerPayload, isAnswered, npsTone } from './surveyAnswers.ts';

test('単一選択で「その他」を選ぶと、本文を「その他：」付きで送る', () => {
  assert.equal(answerPayload(OTHER_KEY, '  駐車場  '), 'その他：駐車場');
  assert.equal(answerPayload(OTHER_KEY, ' '), null);
  assert.equal(answerPayload('ランチ', '無視される'), 'ランチ');
});
test('複数選択は選んだ順を保ち、本文が空の「その他」を落とす', () => {
  assert.deepEqual(answerPayload(['ランチ', OTHER_KEY], 'テイクアウト'), ['ランチ', 'その他：テイクアウト']);
  assert.deepEqual(answerPayload(['ランチ', OTHER_KEY], ''), ['ランチ']);
  assert.equal(answerPayload([OTHER_KEY], ''), null);
  assert.equal(answerPayload([], ''), null);
});
test('未入力は未回答、0点は回答済みとして数える', () => {
  assert.equal(isAnswered(undefined, ''), false);
  assert.equal(isAnswered('', ''), false);
  assert.equal(isAnswered(0, ''), true);
});
test('NPS は 0〜6・7〜8・9〜10 で色分けする', () => {
  assert.deepEqual([0, 6, 7, 8, 9, 10].map(npsTone), ['detractor', 'detractor', 'passive', 'passive', 'promoter', 'promoter']);
});
