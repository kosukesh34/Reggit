import assert from 'node:assert/strict';
import { test } from 'node:test';
import { build } from 'esbuild';
import vm from 'node:vm';

const bundle = await build({ entryPoints: ['resources/js/payment/card.ts'], bundle: true, write: false, format: 'iife', platform: 'browser' });

async function screen(result) {
    let submit;
    let confirmed;
    let redirect;
    const button = { disabled: true };
    const error = { textContent: '' };
    const form = { dataset: { clientSecret: 'secret', successUrl: '/return?status=success' }, addEventListener: (_, handler) => { submit = handler; } };
    const card = { mount: (selector) => assert.equal(selector, '#card-element'), on: (name, callback) => { if (name === 'ready') callback(); } };
    class Meta { content = 'pk_test'; }
    const context = vm.createContext({
        console, URL, HTMLMetaElement: Meta,
        document: { querySelector: (selector) => ({ '#card-payment-form': form, '#pay-button': button, '#payment-error': error, 'meta[name="stripe-key"]': new Meta() })[selector] },
        window: { location: { assign: (url) => { redirect = url; } } },
        Stripe: () => ({ elements: () => ({ create: (type, options) => { assert.equal(type, 'card'); assert.deepEqual(JSON.parse(JSON.stringify(options)), { hidePostalCode: true }); return card; } }), confirmCardPayment: async (secret, options) => { confirmed = { secret, options }; return result; } }),
    });
    vm.runInContext(bundle.outputFiles[0].text, context);
    await new Promise(setImmediate);
    await submit({ preventDefault() {} });
    return { button, error, confirmed, redirect, card };
}

test('card-only payment submits without collecting billing details and returns to the app', async () => {
    const state = await screen({ paymentIntent: { status: 'succeeded' } });
    assert.equal(state.confirmed.secret, 'secret');
    assert.deepEqual(Object.keys(state.confirmed.options), ['payment_method']);
    assert.deepEqual(Object.keys(state.confirmed.options.payment_method), ['card']);
    assert.equal(state.redirect, '/return?status=success');
    assert.equal(state.button.disabled, true);
});

test('failed payments stay on the form and allow retry', async () => {
    const state = await screen({ error: { message: 'カードを確認してください' } });
    assert.equal(state.redirect, undefined);
    assert.equal(state.error.textContent, 'カードを確認してください');
    assert.equal(state.button.disabled, false);
});
