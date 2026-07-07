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
    ],
    // Profile — my orders: item count of the list row
    'items_count' => '{1} 1 item|[2,*] :count items',
    // OrderStatus labels
    'status' => [
        'cancelled' => 'Cancelled',
        'paid' => 'Paid',
        'pending' => 'Pending',
    ],
];
