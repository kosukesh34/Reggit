import assert from 'node:assert/strict';
import { test } from 'node:test';
import { build } from 'esbuild';
import vm from 'node:vm';

const bundle = await build({ entryPoints: ['resources/js/shared/utils/stripePayment.ts'], bundle: true, write: false, format: 'iife', globalName: 'PaymentReview', platform: 'browser' });
function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}
async function fixture() {
    const mounts = [];
    const confirmations = [];
    const requests = new Map();
    const node = { replaceChildren() {} };
    class Meta { content = 'pk_initial'; }
    const context = vm.createContext({ console, URL, HTMLMetaElement: Meta,
        document: { querySelector: selector => selector === 'meta[name="stripe-key"]' ? new Meta() : null, getElementById: () => node },
        window: { location: { href: 'https://example.test/' } },
        Stripe: key => ({ elements: () => ({ create: () => ({ mount: () => mounts.push(key), destroy() {} }) }),
            confirmCardPayment: async secret => { confirmations.push(secret); return { paymentIntent: { id: secret, status: 'succeeded' } }; } }),
    });
    vm.runInContext(bundle.outputFiles[0].text, context);
    const payment = new context.PaymentReview.StripePayment();
    await payment.init();
    payment.apiClient.post = (url, body) => {
        if (url.endsWith('/confirm')) return Promise.resolve({ success: true, data: { success: true, order_id: body.order_id } });
        const request = deferred();
        requests.set(String(body.order_id), request);
        return request.promise;
    };
    const respond = id => requests.get(String(id)).resolve({ success: true, data: { clientSecret: `secret_${id}`, publishableKey: `pk_${id}` } });
    return { payment, requests, respond, mounts, confirmations };
}

test('an older intent response cannot overwrite a newer order form', async () => {
    const { payment, respond, mounts, confirmations } = await fixture();
    const old = payment.setupPaymentForm('card', 100, 1);
    await new Promise(setImmediate);
    payment.destroyPaymentForm();
    const current = payment.setupPaymentForm('card', 200, 2);
    await new Promise(setImmediate);
    respond(2);
    await current;
    respond(1);
    await old;
    assert.equal(payment.clientSecret, 'secret_2');
    assert.deepEqual(mounts, ['pk_2']);
    await payment.confirmPayment(2);
    assert.deepEqual(confirmations, ['secret_2']);
});

test('closing while intent creation is pending leaves the form destroyed', async () => {
    const { payment, respond, mounts } = await fixture();
    const pending = payment.setupPaymentForm('card', 100, 1);
    await new Promise(setImmediate);
    payment.destroyPaymentForm();
    respond(1);
    await pending;
    assert.equal(payment.clientSecret, null);
    assert.equal(payment.paymentElement, null);
    assert.deepEqual(mounts, []);
});

test('a form cannot charge an intent for a different order', async () => {
    const { payment, respond, confirmations } = await fixture();
    const pending = payment.setupPaymentForm('card', 100, 1);
    await new Promise(setImmediate);
    respond(1);
    await pending;
    await assert.rejects(payment.confirmPayment(2));
    assert.deepEqual(confirmations, []);
});


test('closing during Stripe initialization prevents intent creation', async () => {
    const { payment, requests, mounts } = await fixture();
    const initialization = deferred();
    payment.init = () => initialization.promise;
    const pending = payment.setupPaymentForm('card', 100, 1);
    payment.destroyPaymentForm();
    initialization.resolve();
    assert.equal(await pending, false);
    assert.equal(requests.size, 0);
    assert.deepEqual(mounts, []);
});

test('a superseded setup failure does not fail the current form', async () => {
    const { payment, requests, respond } = await fixture();
    const old = payment.setupPaymentForm('card', 100, 1);
    await new Promise(setImmediate);
    const current = payment.setupPaymentForm('card', 200, 2);
    await new Promise(setImmediate);
    respond(2);
    assert.equal(await current, true);
    requests.get('1').resolve({ success: false, error: 'Old intent rejected' });
    assert.equal(await old, false);
    assert.equal(payment.clientSecret, 'secret_2');
});
