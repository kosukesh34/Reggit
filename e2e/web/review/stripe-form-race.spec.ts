import { expect, test } from '@playwright/test';
import { build } from 'esbuild';

let script: string;
test.beforeAll(async () => {
  const result = await build({
    entryPoints: ['resources/js/shared/utils/stripePayment.ts'],
    bundle: true, write: false, format: 'iife', globalName: 'PaymentReview',
  });
  script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
  await page.route('**/*', route => route.abort());
  await page.setContent('<meta name="stripe-key" content="pk_initial"><div id="card" role="status"></div>');
  await page.evaluate(() => {
    const state = window as any;
    state.confirmations = [];
    state.requests = new Map();
    state.Stripe = (key: string) => ({
      elements: () => ({ create: () => ({
        mount: (selector: string) => { document.querySelector(selector)!.textContent = key; },
        destroy: () => { document.getElementById('card')!.textContent = ''; },
      }) }),
      confirmCardPayment: async (secret: string) => {
        state.confirmations.push(secret);
        return { paymentIntent: { id: secret, status: 'succeeded' } };
      },
    });
  });
  await page.addScriptTag({ content: script });
  await page.evaluate(async () => {
    const state = window as any;
    state.payment = new state.PaymentReview.StripePayment();
    await state.payment.init();
    state.payment.apiClient.post = (url: string, body: { order_id: number | string }) => {
      if (url.endsWith('/confirm')) return Promise.resolve({ success: true, data: { success: true, order_id: body.order_id } });
      return new Promise(resolve => state.requests.set(String(body.order_id), resolve));
    };
    state.start = (id: number) => { state['setup' + id] = state.payment.setupPaymentForm('card', id * 100, id); };
    state.respond = (id: number) => state.requests.get(String(id))({ success: true, data: { clientSecret: `secret_${id}`, publishableKey: `pk_${id}` } });
  });
});

test('@component late old intent cannot overwrite or charge the current order form', async ({ page }) => {
  await page.evaluate(() => (window as any).start(1));
  await expect.poll(() => page.evaluate(() => (window as any).requests.has('1'))).toBe(true);
  await page.evaluate(() => { (window as any).payment.destroyPaymentForm(); (window as any).start(2); });
  await expect.poll(() => page.evaluate(() => (window as any).requests.has('2'))).toBe(true);
  await page.evaluate(async () => { (window as any).respond(2); await (window as any).setup2; });
  await expect(page.getByRole('status')).toHaveText('pk_2');
  await page.evaluate(async () => { (window as any).respond(1); await (window as any).setup1; });
  await expect(page.getByRole('status')).toHaveText('pk_2');
  await page.evaluate(() => (window as any).payment.confirmPayment(2));
  expect(await page.evaluate(() => (window as any).confirmations)).toEqual(['secret_2']);
});

test('@component closing during intent creation never remounts the card', async ({ page }) => {
  await page.evaluate(() => (window as any).start(1));
  await expect.poll(() => page.evaluate(() => (window as any).requests.has('1'))).toBe(true);
  await page.evaluate(async () => {
    const state = window as any;
    state.payment.destroyPaymentForm();
    state.respond(1);
    await state.setup1;
  });
  await expect(page.getByRole('status')).toBeEmpty();
  expect(await page.evaluate(() => (window as any).payment.clientSecret)).toBeNull();
});
