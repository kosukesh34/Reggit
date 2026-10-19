import assert from 'node:assert/strict';
import { test } from 'node:test';
import { build } from 'esbuild';
import vm from 'node:vm';

const bundle = await build({
    entryPoints: ['resources/js/admin/unsavedFormGuard.ts'],
    bundle: true,
    write: false,
    format: 'iife',
    globalName: 'Unsaved',
    platform: 'neutral',
});

const context = vm.createContext({});
vm.runInContext(bundle.outputFiles[0].text, context);
const { isSettingsLikeAction, shouldTrackUnsavedForm } = context.Unsaved;

function fakeForm({
    action = '',
    method = 'post',
    inContentBody = true,
    editable = true,
    classes = [],
    attributes = {},
} = {}) {
    return {
        hasAttribute: (name) => Object.prototype.hasOwnProperty.call(attributes, name),
        classList: { contains: (name) => classes.includes(name) },
        closest: (selector) => (selector === '.content-body' && inContentBody ? {} : null),
        getAttribute: (name) => {
            if (name === 'method') return method;
            if (name === 'action') return action;
            return attributes[name] ?? null;
        },
        querySelector: () => (editable ? {} : null),
    };
}

test('settings-like actions cover login security and password change', () => {
    assert.equal(isSettingsLikeAction('/admin/default/settings/basic'), true);
    assert.equal(isSettingsLikeAction('/admin/login-security'), true);
    assert.equal(isSettingsLikeAction('/admin/change-password'), true);
    assert.equal(isSettingsLikeAction('/admin/default/rfm/settings'), true);
    assert.equal(isSettingsLikeAction('/admin/passkey/remote-enroll'), false);
    assert.equal(isSettingsLikeAction('/admin/coupons'), false);
});

test('shouldTrackUnsavedForm tracks settings forms and opt-in', () => {
    assert.equal(shouldTrackUnsavedForm(fakeForm({
        action: '/admin/default/settings/basic',
    })), true);
    assert.equal(shouldTrackUnsavedForm(fakeForm({
        action: '/admin/login-security',
    })), true);
    assert.equal(shouldTrackUnsavedForm(fakeForm({
        action: '/admin/coupons',
        attributes: { 'data-unsaved-warn': '' },
    })), true);
    assert.equal(shouldTrackUnsavedForm(fakeForm({
        action: '/admin/default/settings/basic',
        editable: false,
    })), false);
    assert.equal(shouldTrackUnsavedForm(fakeForm({
        action: '/admin/default/settings/basic',
        classes: ['delete-form'],
    })), false);
    assert.equal(shouldTrackUnsavedForm(fakeForm({
        action: '/admin/default/settings/basic',
        attributes: { 'data-no-unsaved-warn': '' },
    })), false);
});
