<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Couriers\Models\CourierLocation;
use App\Services\Courier\CourierLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierLocationApiController extends Controller
{
    public function __construct(
        private readonly CourierLocationService $courierLocationService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $c = $request->user()->courierProfile;
        if ($c === null) {
            abort(403);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $now = now();
        CourierLocation::query()->upsert(
            [[
                'courier_id' => $c->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'updated_at' => $now,
            ]],
            ['courier_id'],
            ['latitude', 'longitude', 'updated_at']
        );

        $this->courierLocationService->updateLocation(
            (int) $c->firm_id,
            $c->id,
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        return response()->json(['ok' => true]);
    }
}
