import assert from 'node:assert/strict';
import test from 'node:test';

import { buildProfilePayload } from './profilePayload.ts';

function reader({ values = {}, checked = {}, checkedLists = {} } = {}) {
    return {
        value: (name) => values[name],
        checkedValue: (name) => checked[name],
        checkedValues: (name) => checkedLists[name] ?? [],
    };
}

test('buildProfilePayload joins split name and kana fields', () => {
    const payload = buildProfilePayload(
        [{ key: 'name' }, { key: 'kana' }],
        reader({ values: { name_last: ' 山田 ', name_first: '太郎', kana_last: 'ヤマダ', kana_first: '' } }),
    );

    assert.deepEqual(payload, { name: '山田 太郎', kana: 'ヤマダ' });
});

test('buildProfilePayload normalizes text and empty values', () => {
    const payload = buildProfilePayload(
        [{ key: 'email' }, { key: 'birthday' }, { key: 'missing' }],
        reader({ values: { email: ' user@example.com ', birthday: '' } }),
    );

    assert.deepEqual(payload, { email: 'user@example.com', birthday: null });
});

test('buildProfilePayload collects checked values and choice fields', () => {
    const payload = buildProfilePayload(
        [
            { key: 'interests', input_type: 'checkbox' },
            { key: 'sex', input_type: 'radio' },
            { key: 'mail_receive_flag', input_type: 'selectbox' },
        ],
        reader({
            values: { mail_receive_flag: '0' },
            checked: { sex: ' female ' },
            checkedLists: { 'interests[]': [' food ', '', 'travel'] },
        }),
    );

    assert.deepEqual(payload, {
        interests: ['food', 'travel'],
        sex: 'female',
        mail_receive_flag: '0',
    });
});

test('buildProfilePayload emits null for an absent sex selection', () => {
    assert.deepEqual(buildProfilePayload([{ key: 'sex', input_type: 'radio' }], reader()), { sex: null });
});
