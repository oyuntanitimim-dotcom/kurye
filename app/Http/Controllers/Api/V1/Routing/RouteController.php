<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Routing;

use App\Http\Controllers\Controller;
use App\Infrastructure\Routing\OsrmClient;
use App\Modules\Users\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function __construct(private readonly OsrmClient $osrm) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->hasRole(Role::COURIER, Role::FIRM_ADMIN, Role::RESTAURANT)) {
            abort(403);
        }

        $data = $request->validate([
            'from_lat' => ['required', 'numeric'],
            'from_lng' => ['required', 'numeric'],
            'to_lat' => ['required', 'numeric'],
            'to_lng' => ['required', 'numeric'],
        ]);

        $route = $this->osrm->route(
            (float) $data['from_lat'],
            (float) $data['from_lng'],
            (float) $data['to_lat'],
            (float) $data['to_lng'],
        );

        if ($route === null) {
            return response()->json(['ok' => false, 'reason' => 'osrm_unavailable'], 503);
        }

        $r0 = is_array($route['routes'] ?? null) ? ($route['routes'][0] ?? null) : null;
        $geometry = is_array($r0) ? ($r0['geometry'] ?? null) : null;
        $duration = is_array($r0) ? ($r0['duration'] ?? null) : null;
        $distance = is_array($r0) ? ($r0['distance'] ?? null) : null;

        return response()->json([
            'ok' => true,
            'duration_seconds' => is_numeric($duration) ? (int) round((float) $duration) : null,
            'distance_meters' => is_numeric($distance) ? (int) round((float) $distance) : null,
            'geometry' => $geometry,
        ]);
    }
}

