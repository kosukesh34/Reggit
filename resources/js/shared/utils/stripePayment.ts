declare const Stripe: ((key: string) => any) | undefined;

import { ApiClient } from './apiClient';

type StripeConfirmResult = {
    error?: { message?: string } | null;
    paymentIntent: { id: string; status: string };
};

export class StripePayment {
    stripe: any;
    elements: any;
    paymentElement: any;
    clientSecret: string | null;
    apiClient: ApiClient;

    constructor() {
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.clientSecret = null;
        this.apiClient = new ApiClient();
        void this.init();
    }

    async init(): Promise<void> {
        if (typeof Stripe === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = () => this.setupStripe();
            document.head.appendChild(script);
        } else {
            this.setupStripe();
        }
    }

    setupStripe(): void {
        const stripeMeta = document.querySelector('meta[name="stripe-key"]');
        const stripeKey = stripeMeta instanceof HTMLMetaElement ? stripeMeta.content : null;
        if (!stripeKey) {
            // eslint-disable-next-line no-console
            console.error('Stripe key not found. Please set STRIPE_KEY in .env file.');
            return;
        }
        if (typeof Stripe === 'function') {
            this.stripe = Stripe(stripeKey);
        }
    }

    async createPaymentIntent(orderId: number | string): Promise<any> {
        const result = await this.apiClient.post('/api/payment/create-intent', {
            order_id: orderId,
        });
        if (!result.success) {
            throw new Error(result.error || 'Payment intent creation failed');
        }
        const data = result.data as { clientSecret?: string; error?: string };
        if (data.clientSecret) {
            this.clientSecret = data.clientSecret;
            return data;
        }
        throw new Error(data.error || 'Payment intent creation failed');
    }

    async setupPaymentForm(containerId: string, _amount: number, orderId: number | string): Promise<void> {
        if (!this.stripe) {
            await this.init();
            await new Promise((resolve) => setTimeout(resolve, 500));
        }
        const { clientSecret } = await this.createPaymentIntent(orderId);
        this.clientSecret = clientSecret;
        this.elements = this.stripe.elements({
            clientSecret,
            appearance: { theme: 'stripe' },
        });
        this.paymentElement = this.elements.create('payment');
        this.paymentElement.mount(`#${containerId}`);
        void clientSecret;
    }

    async confirmPayment(orderId: number | string): Promise<any> {
        if (!this.stripe || !this.clientSecret) {
            throw new Error('Stripe not initialized');
        }
        const { error, paymentIntent } = await this.stripe.confirmPayment({
            elements: this.elements,
            confirmParams: { return_url: `${window.location.origin}/order-success` },
            redirect: 'if_required',
        }) as StripeConfirmResult;

        if (error) {
            throw error;
        }
        if (paymentIntent.status !== 'succeeded') {
            return { success: false, status: paymentIntent.status };
        }

        const result = await this.apiClient.post('/api/payment/confirm', {
            payment_intent_id: paymentIntent.id,
            order_id: orderId,
        });
        if (!result.success) {
            throw new Error(result.error || 'Payment confirmation failed');
        }
        return result.data;
    }
}
