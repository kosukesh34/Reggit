import test from 'node:test';
import assert from 'node:assert/strict';
import { screenBack } from './screenBack.ts';

test('直接開いた画面でもアプリ内の親画面へ戻る', () => {
  for (const page of ['/more/link', '/more/transfer', '/more/withdraw']) assert.equal(screenBack(page).to, '/more');
  assert.equal(screenBack('/profile').to, '/membership');
  assert.equal(screenBack('/stamp/issue').to, '/stamp');
  for (const page of ['/terms', '/privacy']) assert.equal(screenBack(page).to, '/more');
  for (const page of ['/coupon', '/menu', '/news', '/link_1', '/queue', '/reservation', '/orderHistory', '/more', '/membership']) assert.equal(screenBack(page).to, '/home');
  assert.equal(screenBack('/home'), null);
  assert.equal(screenBack('/'), null);
});
