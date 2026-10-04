import test from 'node:test';
import assert from 'node:assert/strict';
import { pointQrTokenFromUrl } from './pointQrToken.ts';

const token = 'a'.repeat(48);
test('ポイントQRを通常URL、LIFF state、画面のクエリから復元する', () => {
    assert.equal(pointQrTokenFromUrl(`https://example.com/liff/shop?point_code=${token}`), token);
    const state = encodeURIComponent(`?project_key=shop&point_code=${token}`);
    assert.equal(pointQrTokenFromUrl(`https://liff.line.me/app?liff.state=${state}`), token);
    assert.equal(pointQrTokenFromUrl(`https://example.com/liff/shop#/membership?point_code=${token}`), token);
});
test('ポイントQR以外の値や不正URLを受け付けない', () => {
    assert.equal(pointQrTokenFromUrl('invalid'), '');
    assert.equal(pointQrTokenFromUrl('https://example.com/?point_code=bad'), '');
    assert.equal(pointQrTokenFromUrl('https://example.com/?point_code=' + 'a'.repeat(12)), '');
});
