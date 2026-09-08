<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profile — messages
    |--------------------------------------------------------------------------
    |
    | Toasts and context-specific profile/security validation messages, kept
    | here (not in the generic validation.php) because they vary by context.
    |
    */

    'saved' => 'Changes saved.',

    'email_in_use' => 'This email is already in use.',
    'current_password_for_email' => 'Enter your current password to change your email.',
    'current_password_required' => 'Enter your current password.',
    'current_password_incorrect' => 'Your current password is incorrect.',
    'new_password_required' => 'Enter your new password.',

    /*
    |--------------------------------------------------------------------------
    | Profile — UI (tab titles, sidebar, headings, labels, buttons)
    |--------------------------------------------------------------------------
    */

    // Browser tab titles (Livewire component ->title)
    'title_profile' => 'Profile — AnimalAmo',
    'title_orders' => 'My orders — AnimalAmo',
    'title_order_summary' => 'Order summary — AnimalAmo',
    'title_payment' => 'Payment method — AnimalAmo',
    'title_security' => 'Security — AnimalAmo',
    'title_events' => 'Events I attend — AnimalAmo',

    // Profile sidebar items
    'nav_profile' => 'Profile',
    'nav_payment' => 'Payment method',
    'nav_security' => 'Security',
    'nav_orders' => 'My orders',
    'nav_events' => 'Events I attend',

    // Mobile menu items (XD app "Profilo": shorter labels than the desktop sidebar)
    'nav_personal_data' => 'Personal details',
    'nav_payment_data' => 'Payment details',

    // "Become a partner" request: the entry disappears once the user is a partner
    'nav_become_partner' => 'Become a partner',
    'nav_partner_request_sent' => 'Partner request sent',
    'nav_partner_area' => 'Partner area',

    // Card headings
    'personal_info_title' => 'Personal information',
    'orders_title' => 'My orders',
    'order_summary_title' => 'Order summary',
    'payment_title' => 'Payment method information',
    'security_title' => 'Security and Privacy',
    'interests_title' => 'My interests',

    // Common buttons / items
    'save' => 'Save',
    'cancel' => 'Cancel',
    'confirm' => 'Confirm',
    'back' => 'Back',
    'no_results' => 'No results',

    // "My orders" empty state: what every freshly registered user sees. It promises
    // no catalogue, and the CTA leads to Animal Times (content that really exists).
    'orders_empty' => 'Your orders will show up here, as soon as you place your first one.',
    'orders_empty_cta' => 'Read the Animal Times stories',

    // Tabs (Upcoming / Past)
    'tab_upcoming' => 'Upcoming',
    'tab_past' => 'Past',

    // Personal details labels
    'field_first_name' => 'First name',
    'field_last_name' => 'Last name',
    'field_birth_date' => 'Date of birth',
    'field_email' => 'Email',
    'field_pet_type' => 'Pet type',
    'field_address' => 'Address',
    'field_city' => 'City',
    'field_zip' => 'Postal code',
    'field_phone' => 'Mobile',
    'field_current_password_email' => 'Current password (to change your email)',

    // Payment method
    'payment_card_holder' => 'Cardholder',
    'payment_card_number' => 'Card number',
    'payment_card_expiry' => 'Expiry date',
    'payment_card_cvv' => 'Security code',
    'payment_edit_card' => 'Edit card',
    'payment_remove_card' => 'Delete card',
    'payment_remove_confirm' => 'Delete the saved card?',
    'payment_card_saved' => 'Card saved successfully',
    'payment_card_removed' => 'Card deleted',
    'payment_card_error' => 'We could not save your card. Please try again.',
    'payment_card_incomplete' => 'Complete the card details to save it.',
    'payment_unavailable' => 'Saving a card is not available right now. Please try again later.',

    // Security and Privacy
    'current_password_label' => 'Current password',
    'password_label' => 'Password',
    'password_confirm_label' => 'Confirm password',
    'reset_password' => 'Reset password',
    'privacy_settings' => 'Privacy settings',
    'delete_account' => 'Delete account',

    // Order summary / review
    'write_review' => 'Write a review',
    'view_review' => 'View review',
    'review_title_placeholder' => 'Title',
    'review_text_placeholder' => 'Review',
    'review_title_label' => 'Title',
    'review_text_label' => 'Description',
    'review_share' => 'Share',
    'review_shared' => 'Review shared successfully!',
    'gift_dedicated_to' => 'Dedicated to: :name',
    'gift_message' => 'Message: :message',

    // Events
    'attend' => 'Attend',

];
