<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Firm;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        $items = Notification::query()
            ->where('user_id', $u->id)
            ->latest()
            ->paginate(25);

        return response()->json($items);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }
        if ((int) $notification->user_id !== (int) $u->id) {
            abort(403);
        }

        $notification->update(['status' => 'read']);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $u = $request->user();
        if ($u === null || ! $u->isFirmAdmin()) {
            abort(403);
        }

        Notification::query()
            ->where('user_id', $u->id)
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        return response()->json(['ok' => true]);
    }
}

