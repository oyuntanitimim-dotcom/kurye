<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'map' => [
                'tiles_url' => (string) config('map.tiles.url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
                'tiles_attribution' => (string) config('map.tiles.attribution', '&copy; OpenStreetMap contributors'),
            ],
            'routing' => [
                'osrm_base_url' => (string) config('routing.osrm.base_url', ''),
                'osrm_profile' => (string) config('routing.osrm.profile', 'driving'),
            ],
        ]);
    }
}

