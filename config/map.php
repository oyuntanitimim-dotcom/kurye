<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tiles (Map UI)
    |--------------------------------------------------------------------------
    |
    | Default uses public OpenStreetMap tiles. For production with minimal
    | external dependency/cost, point this to your own cached proxy or self-hosted
    | tile server domain.
    |
    | Example (self-host):
    | MAP_TILES_URL=https://tiles.yourdomain.com/{z}/{x}/{y}.png
    |
    */
    'tiles' => [
        'url' => env('MAP_TILES_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_TILES_ATTRIBUTION', '&copy; OpenStreetMap contributors'),
    ],
];

