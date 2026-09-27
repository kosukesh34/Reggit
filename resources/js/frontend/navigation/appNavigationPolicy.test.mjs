import assert from 'node:assert/strict';
import test from 'node:test';

import {
    normalizeOrderMode,
    resolveAppTab,
    resolveDesignerPreview,
    resolveTabFromParsedHash,
    resolveTabFromSearch,
    tabForDesignerPreview,
} from './appNavigationPolicy.ts';

test('resolveAppTab accepts known, aliased, and numbered link tabs only', () => {
    assert.equal(resolveAppTab('order'), 'products');
    assert.equal(resolveAppTab('link_42'), 'link_42');
    assert.equal(resolveAppTab('javascript:alert(1)'), null);
});

test('resolveTabFromSearch supports direct and encoded LIFF state tabs', () => {
    assert.equal(resolveTabFromSearch('?tab=member'), 'member');
    assert.equal(resolveTabFromSearch('?liff.state=%3Ftab%3Dvisit'), 'queue');
    assert.equal(resolveTabFromSearch('?tab=invalid'), null);
});

test('resolveTabFromParsedHash maps detail routes to their owning tab', () => {
    assert.equal(resolveTabFromParsedHash({ kind: 'product', id: '1' }), 'products');
    assert.equal(resolveTabFromParsedHash({ kind: 'reservation', step: 'new' }), 'reservations');
    assert.equal(resolveTabFromParsedHash({ kind: 'unknown', raw: 'bad' }), null);
});

test('navigation settings normalize unsupported values and preview input', () => {
    assert.equal(normalizeOrderMode('mobile_only'), 'mobile_only');
    assert.equal(normalizeOrderMode('invalid'), 'both');
    assert.deepEqual(resolveDesignerPreview('?designer_live_preview=1&designer_source=member_card&designer_embed=1'), {
        source: 'member_card', embed: true, sample: false,
    });
    assert.equal(resolveDesignerPreview('?designer_live_preview=1&designer_source=invalid').source, null);
    assert.equal(tabForDesignerPreview('member_card'), 'member');
    assert.equal(tabForDesignerPreview('invalid'), 'home');
});
