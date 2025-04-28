<?php

declare(strict_types=1);

return [
    'sources' => [
        'google' => [
            'calendar_key' => env('GOOGLE_CALENDAR_API_KEY', ''),
            'calendar_id' => env('GOOGLE_CALENDAR_ID', ''),
        ],
    ],
];
