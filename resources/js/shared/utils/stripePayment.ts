declare const Stripe: ((key: string) => any) | undefined;

type StripeConfirmResult = {
    error?: { message?: string } | null;
    paymentIntent: { id: string; status: string };
};

export class StripePayment {
    stripe: any;
    elements: any;
    paymentElement: any;
    clientSecret: string | null;

    constructor() {
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.clientSecret = null;
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
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'ngrok-skip-browser-warning': 'true',
        };
        if (typeof window !== 'undefined' && window.__PROJECT_KEY__) {
            headers['X-Project-Key'] = window.__PROJECT_KEY__;
        }
        const response = await fetch('/api/payment/create-intent', {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify({
                order_id: orderId,
            }),
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            if (typeof window.__clientDebugLog === 'function') {
                window.__clientDebugLog('stripe_non_json', {
                    endpoint: '/api/payment/create-intent',
                    status: response.status,
                    contentType: contentType || '',
                    bodyHead: text ? String(text).slice(0, 300) : '',
                });
            }
            throw new Error('サーバーエラーが発生しました。JSON形式のレスポンスが返されませんでした。');
        }

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || data.message || 'Payment intent creation failed');
        }
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

        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'ngrok-skip-browser-warning': 'true',
        };
        if (typeof window !== 'undefined' && window.__PROJECT_KEY__) {
            headers['X-Project-Key'] = window.__PROJECT_KEY__;
        }
        const response = await fetch('/api/payment/confirm', {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify({
                payment_intent_id: paymentIntent.id,
                order_id: orderId,
            }),
        });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            if (typeof window.__clientDebugLog === 'function') {
                window.__clientDebugLog('stripe_non_json', {
                    endpoint: '/api/payment/confirm',
                    status: response.status,
                    contentType: contentType || '',
                    bodyHead: text ? String(text).slice(0, 300) : '',
                });
            }
            throw new Error('サーバーエラーが発生しました。JSON形式のレスポンスが返されませんでした。');
        }

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || data.message || 'Payment confirmation failed');
        }
        return data;
    }
}
