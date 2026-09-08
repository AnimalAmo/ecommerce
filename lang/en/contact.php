<?php

return [
    'page_title' => 'Contact us',
    'heading' => 'Contact us',
    'intro' => 'Need information or support? Fill in the form and we will get back to you as soon as possible.',

    'first_name' => 'First name',
    'last_name' => 'Last name',
    'email' => 'Email',
    'reason' => 'Reason for contacting us',
    'reason_info' => 'General information',
    'reason_support' => 'Help with an order',
    'reason_partner' => 'Collaborations and partnerships',
    'reason_other' => 'Other',
    'select_placeholder' => 'Select…',
    'message' => 'Message',
    'message_placeholder' => 'Tell us how we can help…',
    'submit' => 'Send message',

    'thanks_heading' => 'Thank you!',
    'thanks_line_1' => 'We have received your message.',
    'thanks_line_2' => 'We will get back to you as soon as possible.',

    'map_alt' => 'Map of our headquarters — Animal Amo Srl',
    'info_heading' => 'Our contacts',
    'info_email_title' => 'Email',
    'info_instagram_title' => 'Instagram',

    // Internal notification copy (ContactMessageMail) — read by the client,
    // not by the visitor, but it goes through __() like everything else.
    'notification_mail' => [
        'subject' => 'New message from the contact form — :reason',
        'title' => 'New message from the website',
        'intro' => 'Someone filled in the contact form choosing ":reason".',
        'field_name' => 'Name',
        'field_email' => 'Email',
        'field_reason' => 'Reason',
        'field_message' => 'Message',
        'outro' => 'Just reply to this email: your answer goes straight to the person who wrote in.',
        'signature' => 'AnimalAmo',
    ],
];
