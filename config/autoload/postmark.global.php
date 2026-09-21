<?php

declare(strict_types=1);

return [
    'postmark' => [
        'live' => [
            'authentication_service' => [
                'apikey' => getenv('POSTMARK_API_KEY') ?: (getenv('POSTMARK_API_TOKEN') ?: 'POSTMARK_LIVE_KEY_DUMMY_12345'),
            ],
            'sender_email' => getenv('POSTMARK_SENDER_EMAIL') ?: (getenv('MAIL_FROM_EMAIL') ?: 'info@imapp.ng'),
        ],
    ],
];
