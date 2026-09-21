<?php

return [
    'mail' => [
        'heading' => 'Hi :name,',
        'signature' => 'Talk soon,',
        'approved' => [
            'subject' => 'AnimalAmo — “:name” is live',
            'intro' => 'we have approved “:name”: it is now visible on the site and can receive bookings.',
            'cta' => 'Go to your services',
        ],
        'changes_requested' => [
            'subject' => 'AnimalAmo — “:name” needs a few changes',
            'intro' => 'we have reviewed “:name” and before publishing it we need a few changes.',
            'note_heading' => 'Here is what to fix:',
            'cta' => 'Edit the service',
        ],
    ],
];
