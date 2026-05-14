<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null) {
            abort(401);
        }

        $u->loadMissing(['role', 'courierProfile']);

        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            // mobile expects: courier | restaurant | firm_admin
            'role' => $u->role?->name,
            'firm_id' => $u->firm_id,
            'restaurant_id' => $u->restaurant_id,
            'courier_id' => $u->courierProfile?->id,
        ]);
    }
}

