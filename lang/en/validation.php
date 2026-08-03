<?php

// Shared validation messages (auth forms + profile pages). The user-facing
// strings stay IDENTICAL in meaning to the ones shown so far; the per-field
// variants that cannot be expressed here live in the messages() overrides of
// the individual components.
return [
    'before' => ':Attribute must be before :date.',
    'boolean' => 'Invalid value.',
    'date' => 'Enter a valid date.',
    'date_format' => 'Use the format :format.',
    'digits' => ':Attribute must be :digits digits long.',
    'email' => 'Enter a valid email address.',
    'max' => [
        'string' => 'Value too long.',
    ],
    'min' => [
        'string' => ':Attribute must contain at least :min characters.',
    ],
    'phone' => 'Enter a valid phone number.',
    'required' => 'Enter :attribute.',
    'same' => ':Attribute does not match :other.',
    'string' => 'Invalid value.',
    'unique' => ':Attribute is already in use.',

    // Custom texts that the generic message cannot reproduce.
    'custom' => [
        'birthDate' => [
            'before' => 'The date of birth must be before today.',
            'date_format' => 'Use the format dd/mm/yyyy.',
        ],
        'email' => [
            'unique' => 'This email is already registered.',
        ],
        'passwordConfirmation' => [
            'required' => 'Repeat the password.',
            'same' => 'The passwords do not match.',
        ],
        'passwordConfirm' => [
            'required' => 'Confirm the new password.',
            'same' => 'The passwords do not match.',
        ],
        // Partner tax details: "Value too long." does not say how long.
        'pec' => [
            'email' => 'Enter a valid certified email (PEC) address.',
        ],
        'sdi' => [
            'max' => 'The SDI code is 7 characters long.',
        ],
        'taxCode' => [
            'max' => 'The tax code cannot exceed 16 characters.',
        ],
        'vat' => [
            'max' => 'The VAT number cannot exceed 13 characters.',
        ],
    ],

    // Field names with article, so "Enter :attribute." reads naturally.
    'attributes' => [
        'address' => 'your address',
        'birthDate' => 'your date of birth',
        'businessName' => 'your business name',
        'city' => 'your city',
        'description' => 'a description',
        'email' => 'your email',
        'firstName' => 'your first name',
        'lastName' => 'your last name',
        'offerType' => 'your offer type',
        'password' => 'your password',
        'pec' => 'your certified email (PEC)',
        'petType' => 'your pet type',
        'phone' => 'your mobile number',
        'postalCode' => 'your postal code',
        'province' => 'your province',
        'recipientEmail' => 'the recipient\'s email',
        'role' => 'your role',
        'sdi' => 'your SDI code',
        'taxCode' => 'your tax code',
        'vat' => 'your VAT number',
        'website' => 'your website',
        'zip' => 'your postal code',
    ],
];
