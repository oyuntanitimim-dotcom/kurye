<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\AutoDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoDispatchOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $orderId
    ) {
        $this->onQueue((string) config('courier.dispatch_queue', 'dispatch'));
    }

    public function handle(AutoDispatchService $autoDispatchService): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order === null) {
            return;
        }

        $autoDispatchService->dispatchOrder($order, 'restaurant_courier_request', null);
    }
}
