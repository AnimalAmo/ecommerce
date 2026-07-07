/**
 * Checkout payments — Alpine components (scope window, come richiede x-data).
 * Publishable key / client id arrivano dal blade via config('payment.*'),
 * MAI da VITE_*. Gli SDK Stripe/PayPal sono caricati dinamicamente al primo
 * mount; il copy utente arriva dal blade (lang/it), qui nessuna stringa UI.
 */

// Il Payment Element vive in un iframe: il font va dichiarato esplicitamente.
const NUNITO_FONTS = [{ cssSrc: 'https://fonts.bunny.net/css?family=nunito:400,600,700' }];

// Appearance API: element allineato agli input Flux della pagina checkout
// (Nunito, ink #071825, bordo #C8C8C8 al 70%, radius 3px, focus ciano brand).
const STRIPE_APPEARANCE = {
    theme: 'stripe',
    variables: {
        fontFamily: "'Nunito', sans-serif",
        fontSizeBase: '15px',
        colorText: '#071825',
        colorTextSecondary: '#555555',
        colorTextPlaceholder: '#959595',
        colorPrimary: '#68CDEB',
        borderRadius: '3px',
    },
    rules: {
        '.Input': {
            border: '1px solid rgba(200, 200, 200, 0.7)',
            boxShadow: 'none',
            padding: '10px 15px',
            color: '#0D171A',
        },
        '.Input:focus': {
            border: '1px solid #68CDEB',
            boxShadow: 'none',
            outline: 'none',
        },
        '.Label': {
            color: '#555555',
            fontSize: '12px',
        },
    },
};

let stripeJsPromise = null;

function loadStripeJs() {
    if (window.Stripe) return Promise.resolve(window.Stripe);
    if (stripeJsPromise) return stripeJsPromise;

    stripeJsPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => resolve(window.Stripe);
        script.onerror = () => reject(new Error('Failed to load Stripe.js'));
        document.head.appendChild(script);
    });

    return stripeJsPromise;
}

let paypalSdkPromise = null;

function loadPaypalSdk(clientId) {
    if (window.paypal) return Promise.resolve(window.paypal);
    if (paypalSdkPromise) return paypalSdkPromise;

    paypalSdkPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=EUR&intent=capture`;
        script.onload = () => resolve(window.paypal);
        script.onerror = () => reject(new Error('Failed to load PayPal SDK'));
        document.head.appendChild(script);
    });

    return paypalSdkPromise;
}

function createStripeElements(StripeFactory, publishableKey, clientSecret) {
    const stripe = StripeFactory(publishableKey);
    const elements = stripe.elements({
        clientSecret,
        appearance: STRIPE_APPEARANCE,
        fonts: NUNITO_FONTS,
        locale: 'it',
    });

    return { stripe, elements };
}

// Conferma condivisa Payment/Express Checkout Element: redirect solo se il
// metodo lo impone (Klarna → return_url); a successo il server riverifica il
// PaymentIntent (handlePaymentCallback → captureFromCheckout), mai fidarsi
// dell'esito client-side da solo.
async function confirmStripePayment(component, options) {
    // Timestamp per il watchdog di "Paga ora": una conferma è partita davvero.
    window.__paymentConfirmAt = Date.now();

    const { error, paymentIntent } = await component.stripe.confirmPayment({
        elements: component.elements,
        confirmParams: { return_url: options.returnUrl },
        redirect: 'if_required',
    });

    if (error) {
        component.$wire.onPaymentFailed(error.message || options.incompleteMessage || '');
        return;
    }

    if (paymentIntent && paymentIntent.status === 'succeeded') {
        component.$wire.handlePaymentCallback({ payment_intent_id: paymentIntent.id });
    } else {
        component.$wire.onPaymentFailed(options.incompleteMessage || '');
    }
}

// ─── Payment Element (card, klarna) ─────────────────────────────────────────
// options: { method, returnUrl, incompleteMessage }
window.stripePayment = (clientSecret, publishableKey, options = {}) => ({
    stripe: null,
    elements: null,
    stopListening: null,

    async init() {
        try {
            const StripeFactory = await loadStripeJs();
            ({ stripe: this.stripe, elements: this.elements } = createStripeElements(StripeFactory, publishableKey, clientSecret));
            // Link disattivato: il metodo carta resta la sola superficie del Payment Element
            // (Apple/Google Pay hanno le loro righe dedicate via Express Checkout Element).
            this.elements.create('payment', { layout: 'tabs', wallets: { applePay: 'never', googlePay: 'never', link: 'never' } }).mount(this.$refs.element);
        } catch (error) {
            console.error('[Stripe] init failed', error);
            // Stato al server (paymentUnavailable): box cortese al posto di un "Paga ora" morto.
            this.$wire.reportPaymentInitFailed();
            return;
        }

        // "Paga ora" → processPayment() → evento Livewire; il listener è
        // rimosso al destroy (i re-init di sessione rimontano il componente).
        this.stopListening = Livewire.on('process-payment', ({ method }) => {
            if (method !== options.method) return;
            confirmStripePayment(this, options);
        });

        // Element montato e listener attivo: il server abilita "Paga ora".
        this.$wire.markElementReady();
    },

    destroy() {
        if (typeof this.stopListening === 'function') this.stopListening();
    },
});

// ─── Express Checkout Element (Apple Pay / Google Pay) ──────────────────────
// options: { wallet: 'applePay'|'googlePay', returnUrl, incompleteMessage }
window.stripeExpressCheckout = (clientSecret, publishableKey, options = {}) => ({
    stripe: null,
    elements: null,
    walletUnavailable: false,

    async init() {
        let element;

        try {
            const StripeFactory = await loadStripeJs();
            ({ stripe: this.stripe, elements: this.elements } = createStripeElements(StripeFactory, publishableKey, clientSecret));

            // Solo il wallet della riga selezionata: gli altri metodi ECE spenti.
            element = this.elements.create('expressCheckout', {
                paymentMethods: {
                    applePay: options.wallet === 'applePay' ? 'auto' : 'never',
                    googlePay: options.wallet === 'googlePay' ? 'auto' : 'never',
                    link: 'never',
                    paypal: 'never',
                    amazonPay: 'never',
                },
            });
            element.mount(this.$refs.element);
        } catch (error) {
            console.error('[Stripe ECE] init failed', error);
            this.walletUnavailable = true;
            return;
        }

        // Wallet non disponibile su device/browser: testo di fallback dal blade.
        element.on('ready', ({ availablePaymentMethods }) => {
            if (!availablePaymentMethods) this.walletUnavailable = true;
        });

        element.on('confirm', () => confirmStripePayment(this, options));
    },
});

// ─── PayPal Buttons (SDK classico + Orders API v2) ──────────────────────────
// options: { errorMessage }
window.paypalButtons = (paypalOrderId, clientId, options = {}) => ({
    async init() {
        try {
            const paypal = await loadPaypalSdk(clientId);

            await paypal.Buttons({
                fundingSource: paypal.FUNDING.PAYPAL,
                // L'ordine PayPal è già stato creato server-side in initPaymentSession.
                createOrder: () => paypalOrderId,
                onApprove: (data) => {
                    this.$wire.handlePaymentCallback({ paypal_order_id: data.orderID });
                },
                onError: (error) => {
                    console.error('[PayPal] error', error);
                    this.$wire.onPaymentFailed(options.errorMessage || '');
                },
                style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'paypal' },
            }).render(this.$refs.element);
        } catch (error) {
            console.error('[PayPal] init failed', error);
            // Stato al server (paymentUnavailable): box cortese al posto dei bottoni mai renderizzati.
            this.$wire.reportPaymentInitFailed();
        }
    },
});

// ─── Watchdog "Paga ora" ─────────────────────────────────────────────────────
// processing=true parte dal server al click, ma la conferma vive nel JS: se il
// dispatch process-payment non trova alcun element montato (init fallito, race
// di remount) nessuno risponde e il bottone resterebbe congelato per sempre.
// Il timeout sblocca processing lato server SOLO se nessuna conferma è partita.
window.paymentWatchdog = () => ({
    pending: null,

    start() {
        const startedAt = Date.now();

        clearTimeout(this.pending);
        this.pending = setTimeout(() => {
            const confirmedAt = window.__paymentConfirmAt || 0;

            if (confirmedAt < startedAt - 1000) this.$wire.onPaymentFailed('');
        }, 12000);
    },

    destroy() {
        clearTimeout(this.pending);
    },
});
