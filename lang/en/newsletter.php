<?php

return [
    // `consent` is stored VERBATIM as proof of consent: see lang/it/newsletter.php.
    'footer' => [
        'heading' => 'Subscribe to the newsletter',
        'intro' => 'Pet-friendly places, events and ideas for travelling with your pet. An email every now and then, no spam.',
        'email_label' => 'Your email address',
        'email_placeholder' => 'name@example.com',
        'consent' => 'By subscribing I agree to receive the AnimalAmo newsletter. I can unsubscribe at any time from the link at the bottom of every email.',
        'privacy' => 'Privacy policy',
        'submit' => 'Subscribe',
        'success_title' => 'Check your inbox',
        'success' => 'If the address is not subscribed yet, you will receive an email: click the link to confirm your subscription.',
        'throttled' => 'Too many attempts. Try again in :minutes minutes.',
    ],

    'confirm' => [
        'page_title' => 'Confirm subscription',
        'title' => 'Confirm your subscription?',
        'text' => ':email will receive the AnimalAmo newsletter: pet-friendly places, events and ideas for travelling with your pet.',
        'submit' => 'Confirm my subscription',
        'done_title' => 'Subscription confirmed',
        'done_text' => 'Thank you! From now on you will receive the AnimalAmo newsletter. You can unsubscribe at any time from the link at the bottom of every email.',
        'invalid_title' => 'Invalid link',
        'invalid_text' => 'The confirmation link is not valid or has expired. You can subscribe again from the site footer: you will receive a new email.',
        'back_home' => 'Back to home',
        'consent' => 'I confirmed from the link received by email that I want to receive the AnimalAmo newsletter.',
    ],

    'unsubscribe' => [
        'page_title' => 'Unsubscribe from the newsletter',
        'title' => 'Do you want to unsubscribe?',
        'text' => ':email will no longer receive the AnimalAmo newsletter. Emails about your orders will keep arriving.',
        'submit' => 'Unsubscribe me',
        'done_title' => 'You have been unsubscribed',
        'done_text' => 'You will no longer receive the newsletter. If you change your mind, you can subscribe again from the site footer.',
        'back_home' => 'Back to home',
    ],

    'mail' => [
        'confirm' => [
            'subject' => 'Confirm your newsletter subscription',
            'preheader' => 'One click and you are in: without confirmation we will not write to you.',
            'heading' => 'Just one click left',
            'intro' => 'You asked to receive the AnimalAmo newsletter. To complete your subscription, please confirm your address.',
            'courtesy_intro' => 'When you signed up on AnimalAmo you ticked the newsletter box. We are renewing our list and writing to you only once: if you want to keep receiving it, please confirm your address.',
            'cta' => 'Confirm my subscription',
            'ignore' => 'The link is valid for :days days. If this was not you, ignore this email: without confirmation you will receive nothing.',
            'courtesy_ignore' => 'The link is valid for :days days. If you do not confirm, we will not write to you again.',
        ],
        'layout' => [
            'reason' => 'You are receiving this email because you subscribed to the AnimalAmo newsletter.',
            'unsubscribe' => 'Unsubscribe',
            'privacy' => 'Privacy policy',
            'test_notice' => 'Test send: the unsubscribe link only works in real sends.',
            'button_fallback' => 'If the ":action" button does not work, copy and paste this address into your browser:',
        ],
    ],
];
