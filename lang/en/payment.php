<?php

return [
    // Payment flow errors (toast/messages at checkout)
    'errors' => [
        'already_placed' => 'This payment has already been recorded: your order is confirmed and no new charge was made.',
        'amount_changed' => 'The charged amount no longer matches the order total: the charge has been reversed. Please review the summary and try again.',
        'capture_failed' => 'Payment failed: no charge was confirmed. Try again or choose another method.',
        'config_missing' => 'This payment method is not available at the moment. Please choose another one.',
        'init_failed' => 'We were unable to start the payment. Please try again in a moment.',
        'refunded_after_error' => 'An error occurred while recording your order: the amount has been refunded. Please try again.',
        'refunded_after_soldout' => 'The selected spot has just sold out: the amount has been refunded.',
        'total_updated' => 'The order total has changed: please review the summary and try again.',
        'wallet_unavailable' => 'This payment method is not available on this device or browser.',
    ],

    // PaymentMethod labels (checkout method rows, step 2)
    'methods' => [
        'apple_pay' => 'Apple Pay',
        'card' => 'Credit or debit card',
        'google_pay' => 'Google Pay',
        'klarna' => 'Klarna',
        'paypal' => 'PayPal',
    ],
];
