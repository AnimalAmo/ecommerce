<?php

return [
    // Step 2: gateway not configured/enabled — info box shown in place of the element
    'payment_unavailable' => 'Online payment is not available at the moment. Try again in a few minutes or choose another method.',
    // JS: Stripe confirmation finished without a successful outcome
    'payment_incomplete' => 'The payment was not completed. Please try again.',

    // "Pay at the property" branch (partner without online payment): step 2 is a confirmation, no Stripe
    'on_site' => [
        'step_label' => 'Confirm',
        'login_required' => 'To book and pay the partner directly you need to sign in to your account.',
        'mode_changed' => 'The partner now accepts online payment: complete your booking by paying here.',
        'throttle' => 'Too many confirmation attempts. Please try again in :seconds seconds.',
        'title' => 'Confirm your booking',
        'notice' => 'You will pay :amount directly to :partner, at the property or on their website.',
        'pay_on_website' => 'Go to the partner\'s website to pay or book',
        'confirm_cta' => 'Confirm booking',
        'thank_you' => 'Booking confirmed!',
        'thank_you_sub' => 'Here is the summary, check your email: you will pay the partner directly.',
    ],

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
        'saved_card' => 'Saved card •••• :last4',
        'new_card' => 'Use another card',
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
