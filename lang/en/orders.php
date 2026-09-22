<?php

return [
    // Copy of the transactional emails (OrderConfirmationMail / SmartboxGiftMail)
    'mail' => [
        'confirmation' => [
            'subject' => 'Order confirmation :order_number',
            'title' => 'Thank you for your purchase!',
            'greeting' => 'Hi :name,',
            'intro' => 'your order :order_number is confirmed: here is the summary.',
            'table_item' => 'Product',
            'table_price' => 'Price',
            'gift_flag' => '(gift)',
            'total' => 'Total',
            'payment' => 'Payment method: :method',
            'outro' => 'If you have any questions about your order, just reply to this email.',
            'closing' => 'See you soon',
            'signature' => 'The AnimalAmo team',
        ],
        'gift' => [
            'subject' => ':buyer has given you a Smartbox',
            'title' => 'You have received a gift!',
            'intro' => ':buyer has given you the ":title" Smartbox on AnimalAmo.',
            'dedication' => 'Dedicated to: :dedication',
            'message' => 'Message: :message',
            'validity' => 'The Smartbox is valid until :date.',
            'closing' => 'See you soon',
            'signature' => 'The AnimalAmo team',
        ],
        // "Pay at the property" variant of OrderConfirmationMail (greeting, table and closing stay in confirmation)
        'on_site' => [
            'subject' => 'Booking confirmed :order_number',
            'title' => 'Booking confirmed!',
            'intro' => 'your booking :order_number is confirmed: here is the summary. You have not paid anything on AnimalAmo, you pay the partner directly.',
            'amount_due' => 'To be paid directly to the partner :partner, at the property or on their website: :amount',
            // Same line when the partner has neither a company name nor a name (or the profile is gone)
            'amount_due_without_partner' => 'To be paid directly to the partner, at the property or on their website: :amount',
            'pay_on_website' => 'Pay on the partner website',
            'partner' => 'Address: :name, :address',
            // Address without the partner name
            'address' => 'Address: :address',
        ],
        // Mail to the partner on every new booking (PartnerNewBookingMail), online and at the property
        'partner_booking' => [
            'subject' => 'New booking :order_number',
            'title' => 'You have a new booking!',
            // Greeting when the partner has neither a name nor a company name
            'greeting_without_name' => 'Hi,',
            'intro' => 'a customer has booked on AnimalAmo: order :order_number of :date. Here are the details.',
            'table_people' => 'People',
            'paid_online' => 'Paid online',
            'to_collect' => 'For you to collect: :amount',
            'cta' => 'View the booking',
        ],
    ],
    // Profile — my orders: item count of the list row
    'items_count' => '{1} 1 item|[2,*] :count items',
    // OrderStatus labels
    'status' => [
        'cancelled' => 'Cancelled',
        'confirmed' => 'Confirmed',
        'paid' => 'Paid',
        'pending' => 'Pending',
    ],
    // OrderPaymentMode labels
    'payment_mode' => [
        'online' => 'Online payment',
        'on_site' => 'Payment on site',
    ],
];
