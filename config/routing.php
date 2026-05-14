<?php

declare(strict_types=1);

return [
    'osrm' => [
        // Example: http://127.0.0.1:5000
        'base_url' => rtrim((string) env('OSRM_BASE_URL', ''), '/'),
        // profile: "driving", "driving-traffic" (if supported), "cycling", "walking"
        'profile' => (string) env('OSRM_PROFILE', 'driving'),
        'timeout_seconds' => (int) env('OSRM_TIMEOUT_SECONDS', 6),
    ],
];

