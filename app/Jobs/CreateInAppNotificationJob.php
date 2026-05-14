<?php

namespace App\Jobs;

use App\Modules\Notifications\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateInAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $message,
        /** @var array<string, mixed>|null */
        public ?array $data = null
    ) {
        $this->onQueue((string) config('notifications.in_app_queue', 'notifications'));
    }

    public function handle(): void
    {
        Notification::query()->create([
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
            'status' => 'unread',
            'data' => $this->data,
        ]);
    }
}
