import assert from 'node:assert/strict';
import test from 'node:test';
import { resolvePostalAddress, validatePostalCode } from './postalAddressPolicy.ts';

test('postal code validation normalizes separators and accepts exactly seven digits', () => {
    assert.deepEqual(validatePostalCode('100－0001'), { digits: '1000001', errorMessage: null });
    assert.deepEqual(validatePostalCode('12'), { digits: '12', errorMessage: '郵便番号を3桁以上入力してください。' });
    assert.deepEqual(validatePostalCode('1234'), { digits: '1234', errorMessage: '郵便番号は7桁で入力してください。' });
    assert.deepEqual(validatePostalCode('12345678'), { digits: '12345678', errorMessage: '郵便番号は7桁で入力してください。' });
});

test('postal address resolver supports snake and camel case API shapes', () => {
    assert.deepEqual(resolvePostalAddress({
        full_address: '東京都千代田区丸の内1',
        pref_name: '東京都',
        city_name: '千代田区',
        town_name: '丸の内',
        block_name: '1',
    }), {
        fullAddress: '東京都千代田区丸の内1',
        prefecture: '東京都',
        city: '千代田区',
        street: '丸の内1',
    });
    assert.equal(resolvePostalAddress({ fullAddress: '' }), null);
    assert.equal(resolvePostalAddress(null), null);
});
