<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password reset broker lines
    |--------------------------------------------------------------------------
    |
    | Keys mirror the Illuminate\Support\Facades\Password constants. The public
    | flow only ever shows 'sent' and 'token': 'user' and 'throttled' would tell
    | a guest whether an email is registered (see PasswordResetService), and are
    | kept here only for framework completeness.
    |
    */

    'reset' => 'Your password has been reset.',
    'sent' => 'We have emailed you the link to reset your password.',
    'throttled' => 'Please wait a moment before retrying.',
    'token' => 'This password reset link has expired or has already been used.',
    'user' => 'No account is associated with this email address.',

];
