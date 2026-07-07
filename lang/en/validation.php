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
    'email' => 'Enter a valid email address.',
    'max' => [
        'string' => 'Value too long.',
    ],
    'min' => [
        'string' => ':Attribute must contain at least :min characters.',
    ],
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
    ],

    // Field names with article, so "Enter :attribute." reads naturally.
    'attributes' => [
        'address' => 'your address',
        'birthDate' => 'your date of birth',
        'city' => 'your city',
        'email' => 'your email',
        'firstName' => 'your first name',
        'lastName' => 'your last name',
        'password' => 'your password',
        'petType' => 'your pet type',
        'phone' => 'your mobile number',
        'postalCode' => 'your postal code',
        'recipientEmail' => 'the recipient\'s email',
        'zip' => 'your postal code',
    ],
];
