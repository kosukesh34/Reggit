
class StripePayment {
    constructor() {
        this.stripe = null;
        this.elements = null;
        this.paymentElement = null;
        this.clientSecret = null;
        this.init();
    }

    async init() {
        // Stripe.jsを読み込む
        if (typeof Stripe === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = () => this.setupStripe();
            document.head.appendChild(script);
        } else {
            this.setupStripe();
        }
    }

    setupStripe() {
        const stripeKey = document.querySelector('meta[name="stripe-key"]')?.content;
        if (!stripeKey) {
            console.error('Stripe key not found. Please set STRIPE_KEY in .env file.');
            return;
        }
        this.stripe = Stripe(stripeKey);
    }

    async createPaymentIntent(amount, orderId = null) {
        try {
            const response = await fetch('/api/payment/create-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: amount,
                    order_id: orderId,
                }),
            });

            // レスポンスがJSONかどうか確認
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('サーバーエラーが発生しました。JSON形式のレスポンスが返されませんでした。');
            }

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || data.message || 'Payment intent creation failed');
            }

            if (data.clientSecret) {
                this.clientSecret = data.clientSecret;
                return data;
            } else {
                throw new Error(data.error || 'Payment intent creation failed');
            }
        } catch (error) {
            console.error('Payment intent error:', error);
            throw error;
        }
    }

    async setupPaymentForm(containerId, amount, orderId) {
        if (!this.stripe) {
            await this.init();
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        try {
            const { clientSecret } = await this.createPaymentIntent(amount, orderId);
            this.clientSecret = clientSecret;

            const appearance = {
                theme: 'stripe',
            };

            this.elements = this.stripe.elements({ 
                clientSecret: clientSecret,
                appearance: appearance 
            });

            this.paymentElement = this.elements.create('payment');
            this.paymentElement.mount(`#${containerId}`);

            return clientSecret;
        } catch (error) {
            console.error('Setup payment form error:', error);
            throw error;
        }
    }

    async confirmPayment(orderId) {
        if (!this.stripe || !this.clientSecret) {
            throw new Error('Stripe not initialized');
        }

        try {
            const { error, paymentIntent } = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: `${window.location.origin}/order-success`,
                },
                redirect: 'if_required',
            });

            if (error) {
                throw error;
            }

            if (paymentIntent.status === 'succeeded') {
                // サーバー側で決済を確認
                const response = await fetch('/api/payment/confirm', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_intent_id: paymentIntent.id,
                        order_id: orderId,
                    }),
                });

                // レスポンスがJSONかどうか確認
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('サーバーエラーが発生しました。JSON形式のレスポンスが返されませんでした。');
                }

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || data.message || 'Payment confirmation failed');
                }

                return data;
            }

            return { success: false, status: paymentIntent.status };
        } catch (error) {
            console.error('Payment confirmation error:', error);
            throw error;
        }
    }
}


window.StripePayment = StripePayment;

