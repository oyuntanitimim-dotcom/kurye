<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Restaurants\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $firmId = $user->firm_id;
        if ($firmId === null) {
            return response()->json(['message' => 'firm_id gerekli'], 422);
        }

        $items = Restaurant::query()
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->withCount('products')
            ->paginate(20);

        return response()->json($items);
    }

    public function show(Request $request, Restaurant $restaurant): JsonResponse
    {
        if ((int) $restaurant->firm_id !== (int) $request->user()->firm_id) {
            abort(403);
        }

        $restaurant->load(['products' => fn ($q) => $q->where('status', 'active'), 'categories']);

        return response()->json($restaurant);
    }
}
