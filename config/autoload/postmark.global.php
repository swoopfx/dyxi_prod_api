<?php

declare(strict_types=1);

return [
    'postmark' => [
        'live' => [
            'authentication_service' => [
                'apikey' => getenv('POSTMARK_API_KEY') ?: (getenv('POSTMARK_API_TOKEN') ?: '2ed8094d-e908-4e93-b21e-0fcb1dff4dd5'),
            ],
            'sender_email' => getenv('POSTMARK_SENDER_EMAIL') ?: (getenv('MAIL_FROM_EMAIL') ?: 'info@imapp.ng'),
        ],
    ],
];
