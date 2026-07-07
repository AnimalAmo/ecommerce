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
    // Add-to-cart confirmation toast
    'added' => 'Added to cart.',
    // Validity line of the smartbox card ('Smartbox valid for 12 months', :validity via Format::validity)
    'gift_validity' => 'Smartbox valid for :validity',
];
