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
        'array' => 'Choose at most :max.',
        'file' => 'The file cannot exceed :max KB.',
        'numeric' => 'The value cannot exceed :max.',
        'string' => 'Value too long.',
    ],
    'min' => [
        'array' => 'Choose at least :min.',
        'file' => 'The file must be at least :min KB.',
        'numeric' => 'The value must be at least :min.',
        'string' => ':Attribute must contain at least :min characters.',
    ],
    'phone' => 'Enter a valid phone number.',
    'required' => 'Enter :attribute.',
    'same' => ':Attribute does not match :other.',
    'string' => 'Invalid value.',
    'unique' => ':Attribute is already in use.',

    // Rules used by the admin panel and the newer forms: without them
    // Laravel fell back to the framework's English text.
    'accepted' => 'You must accept to continue.',
    'after' => 'Enter a date after :date.',
    'after_or_equal' => 'Enter a date on or after :date.',
    'alpha_dash' => 'Use only letters, numbers, dashes and underscores.',
    'array' => 'Invalid value.',
    'before_or_equal' => 'Enter a date on or before :date.',
    'between' => [
        'array' => 'Choose between :min and :max items.',
        'file' => 'The file must be between :min and :max KB.',
        'numeric' => 'Enter a value between :min and :max.',
        'string' => 'Write between :min and :max characters.',
    ],
    'confirmed' => 'The two values do not match.',
    'decimal' => 'Use :decimal decimal places.',
    'dimensions' => 'The image dimensions are not valid.',
    'distinct' => 'This value is repeated.',
    'ends_with' => 'Must end with: :values.',
    'enum' => 'Invalid value.',
    'exists' => 'Invalid value.',
    'file' => 'Upload a file.',
    'filled' => 'This field cannot be empty.',
    'gt' => [
        'numeric' => 'Enter a value greater than :value.',
        'string' => 'Write more than :value characters.',
    ],
    'gte' => [
        'numeric' => 'Enter a value of at least :value.',
        'string' => 'Write at least :value characters.',
    ],
    'image' => 'The file must be an image.',
    'in' => 'Invalid value.',
    'integer' => 'Enter a whole number.',
    'lowercase' => 'Use lowercase letters only.',
    'lt' => [
        'numeric' => 'Enter a value less than :value.',
        'string' => 'Write fewer than :value characters.',
    ],
    'lte' => [
        'numeric' => 'Enter a value of at most :value.',
        'string' => 'Write at most :value characters.',
    ],
    'mimes' => 'Format not allowed: use :values.',
    'mimetypes' => 'Format not allowed: use :values.',
    'not_in' => 'Invalid value.',
    'numeric' => 'Enter a number.',
    'prohibited' => 'This field must be left empty.',
    'regex' => 'Invalid format.',
    'required_if' => 'Enter :attribute.',
    'required_unless' => 'Enter :attribute.',
    'required_with' => 'Enter :attribute.',
    'required_without' => 'Enter :attribute.',
    'size' => [
        'array' => 'Exactly :size items are required.',
        'file' => 'The file must be :size KB.',
        'numeric' => 'The value must be :size.',
        'string' => 'Exactly :size characters are required.',
    ],
    'starts_with' => 'Must start with: :values.',
    'uploaded' => 'Upload failed: please try again.',
    'url' => 'Enter a valid web address.',
    'uuid' => 'Invalid value.',

    // Custom texts that the generic message cannot reproduce.
    'custom' => [
        'birthDate' => [
            'before' => 'The date of birth must be before today.',
            'date_format' => 'Use the format dd/mm/yyyy.',
        ],
        'email' => [
            'unique' => 'This email is already registered.',
        ],
        // Smartbox gift: the text goes into a mail to a stranger.
        'giftDedication.*' => [
            'max' => 'The dedication cannot be longer than 200 characters.',
        ],
        'giftMessage.*' => [
            'max' => 'The message cannot be longer than 500 characters.',
        ],
        // Partner payment option: it is a choice, "Enter" does not read well.
        'paymentMode' => [
            'required' => 'Choose a payment option.',
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
        'taxCode' => [
            'max' => 'The tax code cannot exceed 16 characters.',
        ],
        'vat' => [
            'max' => 'The VAT number cannot exceed 13 characters.',
        ],
    ],

    // Field names with article, so "Enter :attribute." reads naturally.
    'attributes' => [
        'accountHolder' => 'the account holder',
        'address' => 'your address',
        'bic' => 'the BIC',
        'birthDate' => 'your date of birth',
        'businessName' => 'your business name',
        'city' => 'your city',
        'description' => 'a description',
        'email' => 'your email',
        'firstName' => 'your first name',
        'iban' => 'the IBAN',
        'lastName' => 'your last name',
        'offerType' => 'your offer type',
        'password' => 'your password',
        'paymentUrl' => 'the website address',
        'petType' => 'your pet type',
        'phone' => 'your mobile number',
        'postalCode' => 'your postal code',
        'province' => 'your province',
        'recipientEmail' => 'the recipient\'s email',
        'role' => 'your role',
        'taxCode' => 'your tax code',
        'vat' => 'your VAT number',
        'website' => 'your website',
        'zip' => 'your postal code',
    ],
];
