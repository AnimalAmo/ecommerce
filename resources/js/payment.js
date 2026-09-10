/**
 * Checkout payments — Alpine components (scope window, come richiede x-data).
 * La publishable key arriva dal blade via config('payment.*'), MAI da
 * VITE_*. Stripe.js è caricato dinamicamente al primo mount; il copy utente
 * arriva dal blade (lang/it), qui nessuna stringa UI.
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

function createStripeElements(StripeFactory, publishableKey, clientSecret, stripeAccount) {
    // Direct charge: il PaymentIntent vive sull'account connesso del venditore,
    // quindi Stripe.js va inizializzato sullo stesso account o non lo trova.
    const stripe = StripeFactory(publishableKey, stripeAccount ? { stripeAccount } : undefined);
    const elements = stripe.elements({
        clientSecret,
        appearance: STRIPE_APPEARANCE,
        fonts: NUNITO_FONTS,
        locale: 'it',
    });

    return { stripe, elements };
}

// Conferma condivisa Payment/Express Checkout Element: return_url è
// richiesto da confirmPayment ma i metodi attivi (carta, wallet) confermano
// senza redirect; a successo il server riverifica il PaymentIntent
// (handlePaymentCallback → captureFromCheckout), mai fidarsi dell'esito
// client-side da solo.
async function confirmStripePayment(component, options) {
    // Timestamp per il watchdog di "Paga ora": una conferma è partita davvero.
    window.__paymentConfirmAt = Date.now();

    // Carta salvata: nessun Element montato, il PaymentIntent ha già il
    // payment method allegato lato server e basta il client secret.
    const source = component.elements
        ? { elements: component.elements }
        : { clientSecret: component.clientSecret };

    const { error, paymentIntent } = await component.stripe.confirmPayment({
        ...source,
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

// ─── Payment Element (card) ─────────────────────────────────────────────────
// options: { method, returnUrl, incompleteMessage }
window.stripePayment = (clientSecret, publishableKey, options = {}) => ({
    stripe: null,
    elements: null,
    stopListening: null,

    async init() {
        try {
            const StripeFactory = await loadStripeJs();
            ({ stripe: this.stripe, elements: this.elements } = createStripeElements(StripeFactory, publishableKey, clientSecret, options.stripeAccount));
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

// ─── Carta salvata (checkout, nessun Element) ───────────────────────────────
// Il PaymentIntent arriva dal server già col customer e il payment method
// dell'utente: qui serve solo Stripe.js per confermare (ed eventuale 3DS).
// options: { method, returnUrl, incompleteMessage }
window.stripeSavedCard = (clientSecret, publishableKey, options = {}) => ({
    stripe: null,
    elements: null,
    clientSecret,
    stopListening: null,

    async init() {
        try {
            const StripeFactory = await loadStripeJs();
            this.stripe = StripeFactory(publishableKey, options.stripeAccount ? { stripeAccount: options.stripeAccount } : undefined);
        } catch (error) {
            console.error('[Stripe saved card] init failed', error);
            this.$wire.reportPaymentInitFailed();
            return;
        }

        this.stopListening = Livewire.on('process-payment', ({ method }) => {
            if (method !== options.method) return;
            confirmStripePayment(this, options);
        });

        this.$wire.markElementReady();
    },

    destroy() {
        if (typeof this.stopListening === 'function') this.stopListening();
    },
});

// ─── Payment Element in modalità setup (Profilo → Dati pagamento) ──────────
// Salva la carta senza addebito: il PAN va da Stripe, noi vediamo solo il
// SetupIntent, che il server riverifica prima di persistere il payment method.
// options: { returnUrl, incompleteMessage }
window.stripeSetupMethod = (clientSecret, publishableKey, options = {}) => ({
    stripe: null,
    elements: null,
    stopListening: null,

    async init() {
        try {
            const StripeFactory = await loadStripeJs();
            ({ stripe: this.stripe, elements: this.elements } = createStripeElements(StripeFactory, publishableKey, clientSecret, options.stripeAccount));
            // Il titolare è un campo nostro (label del mock XD) e il mock non
            // prevede il paese: entrambi 'never', li passiamo in confirmParams.
            this.elements
                .create('payment', {
                    layout: 'tabs',
                    fields: { billingDetails: { name: 'never', address: { country: 'never' } } },
                    wallets: { applePay: 'never', googlePay: 'never', link: 'never' },
                })
                .mount(this.$refs.element);
        } catch (error) {
            console.error('[Stripe setup] init failed', error);
            this.$wire.reportSetupInitFailed();
            return;
        }

        // "Salva" → save() valida il titolare lato server e poi dispatcha.
        this.stopListening = Livewire.on('confirm-setup', ({ name }) => this.confirm(name));

        this.$wire.markElementReady();
    },

    async confirm(name) {
        // return_url è richiesto dall'API ma la carta conferma senza redirect;
        // in caso di 3DS con redirect Stripe torna qui con setup_intent nell'url.
        const { error, setupIntent } = await this.stripe.confirmSetup({
            elements: this.elements,
            confirmParams: {
                return_url: options.returnUrl,
                payment_method_data: {
                    billing_details: { name, address: { country: options.country } },
                },
            },
            redirect: 'if_required',
        });

        if (error) {
            this.$wire.onSetupFailed(error.message || options.incompleteMessage || '');
            return;
        }

        if (setupIntent && setupIntent.status === 'succeeded') {
            this.$wire.onSetupSucceeded({ setup_intent_id: setupIntent.id });
        } else {
            this.$wire.onSetupFailed(options.incompleteMessage || '');
        }
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
            ({ stripe: this.stripe, elements: this.elements } = createStripeElements(StripeFactory, publishableKey, clientSecret, options.stripeAccount));

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
