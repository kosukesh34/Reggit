import { StripePayment } from '../shared/utils/stripePayment';

const form = document.querySelector<HTMLFormElement>('#card-payment-form');
const button = document.querySelector<HTMLButtonElement>('#pay-button');
const errorNode = document.querySelector<HTMLElement>('#payment-error');

async function setup(): Promise<void> {
    if (!form || !button || !errorNode) return;
    const payment = new StripePayment();
    await payment.init();
    if (!payment.stripe) throw new Error('決済画面を読み込めませんでした。時間をおいて再度お試しください。');
    const card = payment.stripe.elements().create('card', { hidePostalCode: true });
    card.mount('#card-element');
    card.on('ready', () => { button.disabled = false; });
    card.on('change', (event: { error?: { message: string } }) => {
        errorNode.textContent = event.error?.message ?? '';
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;
        errorNode.textContent = '';
        try {
            const result = await payment.stripe.confirmCardPayment(form.dataset.clientSecret, {
                payment_method: { card },
            });
            if (result.error) throw new Error(result.error.message || '決済に失敗しました。');
            if (result.paymentIntent?.status !== 'succeeded') throw new Error('決済が完了していません。再度お試しください。');
            window.location.assign(form.dataset.successUrl!);
        } catch (error) {
            errorNode.textContent = error instanceof Error ? error.message : '決済に失敗しました。';
            button.disabled = false;
        }
    });
}

void setup().catch((error: unknown) => {
    if (errorNode) errorNode.textContent = error instanceof Error ? error.message : '決済画面を読み込めませんでした。';
});
