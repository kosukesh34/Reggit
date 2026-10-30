import test from 'node:test';
import assert from 'node:assert/strict';
import { needsFriendshipPrompt, openFriendshipWindow } from './friendshipPrompt.ts';

const liffWith = (friendship, loggedIn = true) => ({ isLoggedIn: () => loggedIn, getFriendship: friendship });

test('友だち未追加・ブロック中のときだけ友だち追加を勧める', async () => {
  assert.equal(await needsFriendshipPrompt(liffWith(async () => ({ friendFlag: false }))), true);
  assert.equal(await needsFriendshipPrompt(liffWith(async () => ({ friendFlag: true }))), false);
});

test('判定できないときは勧めない', async () => {
  const notFriend = async () => ({ friendFlag: false });
  assert.equal(await needsFriendshipPrompt(liffWith(notFriend, false)), false);
  assert.equal(await needsFriendshipPrompt(undefined), false);
  assert.equal(await needsFriendshipPrompt(liffWith(async () => { throw new Error('not linked'); })), false);
});

test('LINE 内では LINE の友だち追加の小窓を開き、開けないときは false を返す', async () => {
  let opened = 0;
  const liff = (inClient, request) => ({ isInClient: () => inClient, requestFriendship: request });
  assert.equal(await openFriendshipWindow(liff(true, async () => { opened += 1; })), true);
  assert.equal(opened, 1);
  assert.equal(await openFriendshipWindow(liff(false, async () => { opened += 1; })), false);
  assert.equal(opened, 1);
  assert.equal(await openFriendshipWindow(liff(true, async () => { throw new Error('subwindowOpen is not allowed in this LIFF app'); })), false);
  assert.equal(await openFriendshipWindow(undefined), false);
});
