import test from 'node:test';
import assert from 'node:assert/strict';
import { localDay, pickPopup, popupLink, popupSeenKey, shouldShowPopup } from './promoPopupPolicy.ts';
import { launchParamFromUrl, withSurveyLaunchQuery } from '../../shared/utils/launchParam.ts';
import { parseStoredSource } from '../../shared/utils/acquisitionSourcePolicy.ts';

test('表示回数の設定どおりにポップアップを出す', () => {
  const today = localDay(new Date(2026, 9, 27));
  assert.equal(today, '2026-10-27');
  for (const frequency of ['once', 'daily', 'every_open']) assert.equal(shouldShowPopup(frequency, null, today), true);
  assert.equal(shouldShowPopup('once', '2026-10-01', today), false);
  assert.equal(shouldShowPopup('daily', today, today), false);
  assert.equal(shouldShowPopup('daily', '2026-10-26', today), true);
  assert.equal(shouldShowPopup('every_open', today, today), true);
});

test('優先度順の先頭から出せる1件を選び、リンクは安全な形だけ開く', () => {
  const popups = [{ id: 1, frequency: 'once', version: 5 }, { id: 2, frequency: 'daily' }, { id: 3, frequency: 'every_open' }];
  const seen = { '1:5': '2026-10-01', '2:0': '2026-10-27' };
  assert.equal(popupSeenKey(popups[0]), '1:5');
  assert.equal(pickPopup(popups, '2026-10-27', new Set(), (p) => seen[popupSeenKey(p)] ?? null)?.id, 3);
  assert.equal(pickPopup(popups, '2026-10-27', new Set([3]), (p) => seen[popupSeenKey(p)] ?? null), undefined);
  assert.deepEqual(popupLink('/coupon'), { kind: 'app', path: '/coupon' });
  assert.deepEqual(popupLink('https://example.com'), { kind: 'external', url: 'https://example.com' });
  for (const unsafe of ['//evil.example.com', 'javascript:alert(1)', 'http://example.com', null]) assert.deepEqual(popupLink(unsafe), { kind: 'none' });
});

test('起動URLとliff.stateから値を取り出す', () => {
  assert.equal(launchParamFromUrl('https://example.com/liff?src=a1b2c3d4e5', 'src'), 'a1b2c3d4e5');
  assert.equal(launchParamFromUrl('https://example.com/liff?liff.state=' + encodeURIComponent('project_key=demo&tab=survey&survey=12'), 'survey'), '12');
  assert.equal(launchParamFromUrl('https://example.com/liff?liff.state=' + encodeURIComponent('/?tab=home&src=ffffffffff'), 'src'), 'ffffffffff');
  assert.equal(launchParamFromUrl('https://example.com/liff#/survey?survey=3', 'survey'), '3');
  assert.equal(launchParamFromUrl('not a url', 'src'), '');
});

test('アンケートの起動URLは開く対象を画面のパスに移す', () => {
  assert.equal(withSurveyLaunchQuery('/survey', 'https://example.com/liff?tab=survey&survey=5'), '/survey?survey=5');
  assert.equal(withSurveyLaunchQuery('/survey', 'https://example.com/liff?liff.state=' + encodeURIComponent('project_key=demo&tab=survey&survey=8')), '/survey?survey=8');
  assert.equal(withSurveyLaunchQuery('/survey', 'https://example.com/liff?tab=survey&survey=x'), '/survey');
  assert.equal(withSurveyLaunchQuery('/coupon', 'https://example.com/liff?survey=5'), '/coupon');
});

test('流入経路の記録は同じプロジェクトで30日以内のものだけ使う', () => {
  const now = Date.UTC(2026, 9, 28);
  const stored = (projectKey, daysAgo, code = 'a1b2c3d4e5') => JSON.stringify({ code, projectKey, savedAt: now - daysAgo * 86_400_000 });
  assert.equal(parseStoredSource(stored('demo', 3), 'demo', now), 'a1b2c3d4e5');
  assert.equal(parseStoredSource(stored('other', 3), 'demo', now), undefined);
  assert.equal(parseStoredSource(stored('demo', 31), 'demo', now), undefined);
  assert.equal(parseStoredSource(stored('demo', 1, 'not-a-code'), 'demo', now), undefined);
  assert.equal(parseStoredSource('a1b2c3d4e5', 'demo', now), undefined);
  assert.equal(parseStoredSource(null, 'demo', now), undefined);
});
