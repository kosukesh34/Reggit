declare const Stripe: ((key: string) => any) | undefined;

import { ApiClient } from './apiClient';

type StripeConfirmResult = {
    error?: { message?: string } | null;
    paymentIntent?: { id: string; status: string } | null;
};

export class StripePayment {
    stripe: any;
    elements: any;
    paymentElement: any;
    clientSecret: string | null;
    apiClient: ApiClient;
    private initPromise: Promise<void> | null;

    constructor() {
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.clientSecret = null;
        this.apiClient = new ApiClient();
        this.initPromise = null;
        void this.init();
    }

    async init(): Promise<void> {
        if (this.stripe !== null) {
            return;
        }
        if (this.initPromise !== null) {
            await this.initPromise;
            return;
        }
        this.initPromise = new Promise<void>((resolve) => {
            const finish = (): void => {
                this.setupStripe();
                resolve();
            };
            if (typeof Stripe === 'undefined') {
                const existing = document.querySelector('script[data-stripe-js="true"]');
                if (existing instanceof HTMLScriptElement) {
                    existing.addEventListener('load', finish, { once: true });
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.async = true;
                script.dataset.stripeJs = 'true';
                script.onload = finish;
                document.head.appendChild(script);
                return;
            }
            finish();
        });
        await this.initPromise;
    }

    setupStripe(): void {
        if (this.stripe !== null) {
            return;
        }
        const stripeMeta = document.querySelector('meta[name="stripe-key"]');
        const stripeKey = stripeMeta instanceof HTMLMetaElement ? stripeMeta.content.trim() : '';
        if (stripeKey === '') {

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
        if (result.success === false) {
            throw new Error(result.error || 'Payment intent creation failed');
        }
        const data = result.data as { clientSecret?: string; error?: string };
        if (data.clientSecret) {
            this.clientSecret = data.clientSecret;
            return data;
        }
        throw new Error(data.error || 'Payment intent creation failed');
    }

    destroyPaymentForm(): void {
        try {
            if (this.paymentElement !== null && typeof this.paymentElement.destroy === 'function') {
                this.paymentElement.destroy();
            }
        } catch (_e) {
            // destroy はベストエフォート
        }
        this.paymentElement = null;
        this.elements = null;
        this.clientSecret = null;
    }

    async setupPaymentForm(containerId: string, _amount: number, orderId: number | string): Promise<void> {
        await this.init();
        if (this.stripe === null) {
            throw new Error('Stripe の初期化に失敗しました。STRIPE_KEY を確認してください');
        }
        this.destroyPaymentForm();
        const { clientSecret } = await this.createPaymentIntent(orderId);
        this.clientSecret = clientSecret;
        this.elements = this.stripe.elements({
            clientSecret,
            appearance: { theme: 'stripe' },
        });
        this.paymentElement = this.elements.create('payment');
        const mountNode = document.getElementById(containerId);
        if (mountNode === null) {
            throw new Error('決済フォームの表示領域が見つかりません');
        }
        mountNode.replaceChildren();
        this.paymentElement.mount(`#${containerId}`);
    }

    buildReturnUrl(orderId: number | string): string {
        const url = new URL(window.location.href);
        url.searchParams.set('order_id', String(orderId));
        return url.toString();
    }

    async confirmPayment(orderId: number | string): Promise<any> {
        if (!this.stripe || !this.clientSecret || !this.elements) {
            throw new Error('Stripe not initialized');
        }
        const { error, paymentIntent } = await this.stripe.confirmPayment({
            elements: this.elements,
            confirmParams: { return_url: this.buildReturnUrl(orderId) },
            redirect: 'if_required',
        }) as StripeConfirmResult;

        if (error) {
            throw error;
        }
        if (paymentIntent === null || paymentIntent === undefined) {
            throw new Error('決済の確認結果を取得できませんでした');
        }
        if (paymentIntent.status !== 'succeeded') {
            return { success: false, status: paymentIntent.status };
        }

        return this.confirmOnServer(paymentIntent.id, orderId);
    }

    async confirmOnServer(paymentIntentId: string, orderId: number | string): Promise<any> {
        const result = await this.apiClient.post('/api/payment/confirm', {
            payment_intent_id: paymentIntentId,
            order_id: orderId,
        });
        if (result.success === false) {
            throw new Error(result.error || 'Payment confirmation failed');
        }
        return result.data;
    }
}
