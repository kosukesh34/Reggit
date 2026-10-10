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
    private formVersion = 0;
    private paymentOrderId: string | null = null;

    constructor() {
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.clientSecret = null;
        this.apiClient = new ApiClient();
        this.initPromise = null;
        void this.init().catch(() => { /* setupPaymentForm reports loading errors. */ });
    }

    async init(): Promise<void> {
        if (this.stripe !== null) {
            return;
        }
        if (this.initPromise !== null) {
            await this.initPromise;
            return;
        }
        this.initPromise = new Promise<void>((resolve, reject) => {
            const finish = (): void => {
                this.setupStripe();
                resolve();
            };
            if (typeof Stripe === 'undefined') {
                const existing = document.querySelector('script[data-stripe-js="true"]');
                if (existing instanceof HTMLScriptElement) {
                    existing.addEventListener('load', finish, { once: true });
                    existing.addEventListener('error', () => reject(new Error('決済画面を読み込めませんでした')), { once: true });
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.async = true;
                script.dataset.stripeJs = 'true';
                script.onload = finish;
                script.onerror = () => reject(new Error('決済画面を読み込めませんでした'));
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
        const data = result.data as { clientSecret?: string; publishableKey?: string; error?: string };
        if (data.clientSecret) {
            return data;
        }
        throw new Error(data.error || 'Payment intent creation failed');
    }

    destroyPaymentForm(): void {
        this.formVersion += 1;
        this.paymentOrderId = null;
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

    async setupPaymentForm(containerId: string, _amount: number, orderId: number | string): Promise<boolean> {
        this.destroyPaymentForm();
        const version = this.formVersion;
        try {
            await this.init();
            if (version !== this.formVersion) return false;
            const { clientSecret, publishableKey } = await this.createPaymentIntent(orderId);
            if (version !== this.formVersion) return false;
            if (publishableKey && typeof Stripe === 'function') this.stripe = Stripe(publishableKey);
            if (this.stripe === null) {
                throw new Error('Stripe の初期化に失敗しました。決済設定を確認してください');
            }
            this.clientSecret = clientSecret;
            this.elements = this.stripe.elements();
            this.paymentElement = this.elements.create('card', { hidePostalCode: true });
            const mountNode = document.getElementById(containerId);
            if (mountNode === null) {
                throw new Error('決済フォームの表示領域が見つかりません');
            }
            mountNode.replaceChildren();
            this.paymentElement.mount(`#${containerId}`);
            this.paymentOrderId = String(orderId);
            return true;
        } catch (error) {
            if (version !== this.formVersion) return false;
            this.destroyPaymentForm();
            throw error;
        }
    }

    buildReturnUrl(orderId: number | string): string {
        const url = new URL(window.location.href);
        url.searchParams.set('order_id', String(orderId));
        return url.toString();
    }

    async confirmPayment(orderId: number | string): Promise<any> {
        if (!this.stripe || !this.clientSecret || !this.elements || this.paymentOrderId !== String(orderId)) {
            throw new Error('Stripe not initialized');
        }
        const { error, paymentIntent } = await this.stripe.confirmCardPayment(this.clientSecret, {
            payment_method: { card: this.paymentElement },
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
