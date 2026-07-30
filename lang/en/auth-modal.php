<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login / registration / partner modals (XD "Pop-Up - Login")
    |--------------------------------------------------------------------------
    */

    // Common
    'close' => 'Close',
    'welcome' => 'Welcome',
    'back' => 'Back',
    'email' => 'Email',
    'password' => 'Password',
    'forgot_password' => 'Forgot password',
    'login' => 'Log in',

    // Client card (login modal)
    'client' => [
        'title' => 'Log in as Customer',
        'no_account' => 'Don\'t have an account?',
        'register_free' => 'Sign up for free',
    ],

    // Partner card / modal
    'partner' => [
        'title' => 'Log in as Partner',
        'already_partner' => 'Already a partner?',
        'access_reserved_area' => 'Access your reserved area',
        'want_to_become' => 'Want to become our partner?',
        'fill_form' => 'Fill out the form',
        'not_yet_partner' => 'Not a partner yet?',
        'request_access' => 'Request access',
    ],

    // "Forgot password" modal (reset link request)
    'forgot' => [
        'title' => 'Forgot password',
        'intro' => 'Enter the email address you signed up with: we will send you the link to create a new password.',
        'send_link' => 'Send the link',
        'sent_title' => 'Check your email',
        'sent_text' => 'If :email belongs to an AnimalAmo account, you will receive the password reset link in a few minutes.',
        'sent_validity' => 'The link stays valid for 60 minutes.',
        'sent_spam_hint' => 'Can\'t find the email? Check your spam folder too.',
        'back_to_login' => 'Back to login',
    ],

    // Reset page (reached from the email link)
    'reset' => [
        'title' => 'Create a new password',
        'intro' => 'Choose a new password for the :email account.',
        'new_password' => 'New password',
        'repeat_password' => 'Repeat the new password',
        'submit' => 'Save password',
        'done_title' => 'Password updated',
        'done_text' => 'You can now log in to AnimalAmo with your new password.',
        'done_cta' => 'Log in',
        'invalid_title' => 'Link no longer valid',
        'invalid_text' => 'This password reset link has expired or has already been used. Request a new one: it only takes a few seconds.',
        'invalid_cta' => 'Request a new link',
        'back_home' => 'Back to home',
        'title_page' => 'Reset your password',
    ],

    // Reset link email (no XD design: Laravel markdown)
    'reset_mail' => [
        'subject' => 'Reset your AnimalAmo password',
        'heading' => 'Hi :name,',
        'intro' => 'We received a request to reset the password of the AnimalAmo account linked to this email address.',
        'cta' => 'Reset the password',
        'expiry' => 'The link is valid for :count minutes.',
        'ignore' => 'If you did not request a password change you can ignore this email: your password stays unchanged.',
        'signature' => 'See you soon,',
    ],

    // Step-by-step registration modal
    'register' => [
        'personal_info' => 'Personal information',
        'address' => 'Address',
        'pet' => 'Pet',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'birth_date' => 'Date of birth',
        'phone' => 'Mobile phone',
        'repeat_password' => 'Repeat password',
        'address_field' => 'Address',
        'city' => 'City',
        'postal_code' => 'Postcode',
        'pet_type' => 'Pet type',
        'newsletter' => 'Subscribe to the newsletter',
        'privacy_consent' => 'I consent to the use of my personal data to receive exclusive promotions.',
        'continue' => 'Continue',
        'create_profile' => 'Create profile',
    ],

];
