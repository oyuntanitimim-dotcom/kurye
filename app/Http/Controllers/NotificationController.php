<?php

namespace App\Http\Controllers;

use App\Modules\Notifications\Models\Notification;
use App\Modules\Users\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $notifications = Notification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(25);

        $hasUnread = Notification::query()
            ->where('user_id', $user->id)
            ->where('status', 'unread')
            ->exists();

        $layout = match ($user->role?->name) {
            Role::SUPER_ADMIN => 'layouts.admin',
            Role::FIRM_ADMIN => 'layouts.firm',
            Role::RESTAURANT => 'layouts.restaurant',
            Role::COURIER => 'layouts.courier',
            default => 'layouts.app',
        };

        return view('notifications.index', [
            'title' => 'Bildirimler',
            'notifications' => $notifications,
            'layout' => $layout,
            'hasUnread' => $hasUnread,
        ]);
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        $this->assertOwns($notification);

        $notification->update(['status' => 'read']);

        return back()->with('status', 'Bildirim okundu olarak işaretlendi.');
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::query()
            ->where('user_id', Auth::id())
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        return back()->with('status', 'Tüm bildirimler okundu olarak işaretlendi.');
    }

    private function assertOwns(Notification $notification): void
    {
        if ((int) $notification->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}
