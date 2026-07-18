<?php

return [
    // Step 2: gateway not configured/enabled — info box shown in place of the element
    'payment_unavailable' => 'Online payment is not available at the moment. Try again in a few minutes or choose another method.',
    // JS: Stripe confirmation finished without a successful outcome
    'payment_incomplete' => 'The payment was not completed. Please try again.',

    // Checkout funnel UI strings (blade commerce/checkout)
    'ui' => [
        'page_title' => 'Checkout — AnimalAmo',
        'step_data' => 'Your details',
        'step_payment' => 'Payment',
        'step_done' => 'Done!',
        'verify_personal_data' => 'Check your personal details',
        'field_first_name' => 'First name *',
        'field_last_name' => 'Last name *',
        'field_email' => 'Email *',
        'field_country' => 'Country',
        'field_phone' => 'Mobile *',
        'field_recipient_email' => 'Recipient’s email *',
        'contact_note' => 'We will only contact you about important updates or changes to your booking',
        'continue_purchase' => 'Continue to purchase',
        'select_payment_method' => 'Select a payment method',
        'pay_now' => 'Pay now',
        'order_summary' => 'Order summary',
        'dedicated_to' => 'Dedicated to: :name',
        'message' => 'Message: :message',
        'total' => 'Total',
        'taxes_included' => 'Taxes and fees included',
        'thank_you' => 'Thank you for your purchase!',
        'gift_sent' => 'The Smartbox has been sent to the email: :email',
        'gift_sent_summary' => 'Here is the summary of your purchase:',
        'check_email' => 'Here is the summary, check your email',
        'back_home' => 'Back to Home',
        'go_to_purchases' => 'Go to your purchases',
    ],
];
