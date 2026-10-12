import test from 'node:test';
import assert from 'node:assert/strict';
import { screenBack } from './screenBack.ts';

test('直接開いた画面でもアプリ内の親画面へ戻る', () => {
  for (const page of ['/more/link', '/more/transfer', '/more/withdraw']) assert.equal(screenBack(page).to, '/more');
  assert.equal(screenBack('/stamp/issue').to, '/stamp');
  for (const page of ['/terms', '/privacy']) assert.equal(screenBack(page).to, '/more');
});

test('タブのルート画面では戻るボタンを表示しない', () => {
  for (const page of ['/', '/home', '/membership', '/profile', '/more', '/stamp', '/coupon', '/queue', '/reservation', '/menu', '/mobileOrder', '/dineInOrder', '/orderHistory', '/gallery', '/news', '/storeSearch', '/designlab', '/link_1', '/link_2', '/link_3']) {
    assert.equal(screenBack(page), null, page);
  }
});
