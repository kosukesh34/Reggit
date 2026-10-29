import test from 'node:test';
import assert from 'node:assert/strict';
import { needsFriendshipPrompt } from './friendshipPrompt.ts';

const url = 'https://line.me/R/ti/p/@example';
const liffWith = (friendship, loggedIn = true) => ({ isLoggedIn: () => loggedIn, getFriendship: friendship });

test('友だち未追加・ブロック中のときだけ友だち追加を勧める', async () => {
  assert.equal(await needsFriendshipPrompt(liffWith(async () => ({ friendFlag: false })), url), true);
  assert.equal(await needsFriendshipPrompt(liffWith(async () => ({ friendFlag: true })), url), false);
});

test('判定できないときや追加先が無いときは勧めない', async () => {
  const notFriend = async () => ({ friendFlag: false });
  assert.equal(await needsFriendshipPrompt(liffWith(notFriend), ''), false);
  assert.equal(await needsFriendshipPrompt(liffWith(notFriend), 'javascript:alert(1)'), false);
  assert.equal(await needsFriendshipPrompt(liffWith(notFriend, false), url), false);
  assert.equal(await needsFriendshipPrompt(undefined, url), false);
  assert.equal(await needsFriendshipPrompt(liffWith(async () => { throw new Error('not linked'); }), url), false);
});
