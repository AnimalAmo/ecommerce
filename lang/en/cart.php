<?php

return [
    // CartValidationException messages (danger toast in the components)
    'unavailable_dates' => 'The selected dates are not available.',
    'unavailable_day' => 'The selected day is not available.',
    'past_date' => 'Please select a future date.',
    'invalid_range' => 'The check-out date must be after the check-in date.',
    'invalid_times' => 'The end time must be after the start time.',
    'sold_out' => 'There are not enough spots available.',
    'not_purchasable' => 'This product cannot be purchased.',
    'invalid_participants' => 'The selected number of participants is not valid.',
    // Add-to-cart confirmation toast
    'added' => 'Added to cart.',
    // Validity line of the smartbox card ('Smartbox valid for 12 months', :validity via Format::validity)
    'gift_validity' => 'Smartbox valid for :validity',

    // Cart page UI strings (blade commerce/cart)
    'ui' => [
        'page_title' => 'Cart — AnimalAmo',
        'title' => 'Cart',
        'empty_heading' => 'You haven’t booked any activities yet',
        'empty_text' => 'Get your four-legged friends ready: plan your next adventure together.',
        'empty_cta' => 'Experiences made for you',
        'most_loved' => 'The most loved activities on Animal-amo',
        'prev_cards' => 'Previous cards',
        'next_cards' => 'Next cards',
        'page_number' => 'Page :number',
        'item_one' => 'item',
        'item_many' => 'items',
        'edit' => 'Edit',
        'remove' => 'Remove',
        'gift_dedication_placeholder' => 'Dedicated to',
        'gift_message_placeholder' => 'Message',
        'total' => 'Total',
        'taxes_included' => 'Taxes and fees included',
        'promo_code' => 'Enter promo code',
        'secure_payment' => 'Secure payment method',
        'free_cancellation' => 'Free cancellation',
        'free_cancellation_note' => '(No later than 2 weeks before the event)',
        'go_to_checkout' => 'Go to checkout',
        'edit_booking' => 'Edit booking',
        'check_in' => 'Check-in',
        'check_out' => 'Check-out',
        'day' => 'Day',
        'times' => 'Times',
        'from' => 'From',
        'to' => 'To',
        'guests' => 'Guests',
        'animals' => 'Animals',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
    ],
];
