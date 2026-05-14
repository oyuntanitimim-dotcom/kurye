<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Firms\Models\Firm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationSettingsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $firm = Firm::query()->findOrFail((int) $u->firm_id);

        $data = $request->validate([
            'auto_assign_best_after_eta' => ['required', 'boolean'],
        ]);

        $prev = is_array($firm->settings) ? $firm->settings : [];
        $next = array_merge($prev, [
            'auto_assign_best_after_eta' => (bool) $data['auto_assign_best_after_eta'],
        ]);

        $firm->update(['settings' => $next]);

        return response()->json([
            'ok' => true,
            'settings' => $firm->mergedOperationSettings(),
        ]);
    }
}

