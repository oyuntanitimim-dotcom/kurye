<?php

namespace App\Http\Controllers\Courier;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderStateService $orderStateService
    ) {}

    public function accept(Order $order): RedirectResponse
    {
        $courier = Auth::user()->courierProfile;
        if ($courier === null) {
            abort(403);
        }

        if ($order->firm_id !== $courier->firm_id) {
            abort(403);
        }

        if ($order->status !== OrderStatus::Ready->value) {
            return back()->withErrors(['order' => 'Sipariş bu aşamada atanamaz.']);
        }

        if ($order->restaurant_courier_requested_at === null) {
            return back()->withErrors(['order' => 'Restoran henüz kurye çağırmadı; sipariş kurye şirketine iletilmedi.']);
        }

        $order->update(['courier_id' => $courier->id]);
        // Havuzdan üstlenme: ek «atama onayı» yok; doğrudan kabul edilmiş sayılır.
        $this->orderStateService->transition($order->fresh(), OrderStatus::CourierAccepted);

        return back()->with('status', 'Sipariş size atandı.');
    }

    public function acceptFirmAssignment(Order $order): RedirectResponse
    {
        $this->guardCourier($order);
        if ($order->status !== OrderStatus::CourierAssigned->value) {
            return back()->withErrors(['order' => 'Bu sipariş atama onayı beklemiyor.']);
        }

        $this->orderStateService->transition($order, OrderStatus::CourierAccepted, [
            'event' => 'courier_accepted_firm_assignment',
        ]);

        return back()->with('status', 'Görev kabul edildi.');
    }

    public function declineFirmAssignment(Request $request, Order $order): RedirectResponse
    {
        $this->guardCourier($order);
        $data = $request->validate([
            'reason' => ['required', 'string', 'in:unavailable,vehicle_issue,transfer'],
        ]);

        if ($order->status !== OrderStatus::CourierAssigned->value) {
            return back()->withErrors(['order' => 'Bu sipariş atama bekleyen durumda değil.']);
        }

        DB::transaction(function () use ($order, $data): void {
            $order->courier_id = null;
            $order->save();
            $this->orderStateService->transition($order->fresh(), OrderStatus::Ready, [
                'event' => 'courier_declined_assignment',
                'reason' => $data['reason'],
            ]);
        });

        $reasonLabel = match ($data['reason']) {
            'unavailable' => 'Müsait değil',
            'vehicle_issue' => 'Araç arızası',
            'transfer' => 'Devir / başka kurye',
            default => $data['reason'],
        };
        $fresh = $order->fresh();
        $this->orderStateService->notifyFirmAdmins(
            $fresh,
            'Kurye atamayı reddetti',
            'Sipariş #'.$fresh->id.' — '.$reasonLabel.'. Yeni kurye ataması yapın.',
            ['event' => 'courier_declined_assignment', 'reason' => $data['reason']]
        );

        return back()->with('status', 'Atama reddedildi; firma bilgilendirildi.');
    }

    public function pickedUp(Order $order): RedirectResponse
    {
        $this->guardCourier($order);
        if ($order->status !== OrderStatus::CourierAccepted->value) {
            return back()->withErrors(['order' => 'Önce görevi kabul edin veya üstlenin.']);
        }

        $this->orderStateService->transition($order, OrderStatus::PickedUp);

        return back()->with('status', 'Sipariş alındı.');
    }

    public function onTheWay(Order $order): RedirectResponse
    {
        $this->guardCourier($order);
        if ($order->status !== OrderStatus::PickedUp->value) {
            return back()->withErrors(['order' => 'Önce siparişi aldığınızı işaretleyin.']);
        }

        $this->orderStateService->transition($order, OrderStatus::OnTheWay);

        return back()->with('status', 'Yolda.');
    }

    public function delivered(Order $order): RedirectResponse
    {
        $this->guardCourier($order);
        if (! in_array($order->status, [OrderStatus::PickedUp->value, OrderStatus::OnTheWay->value], true)) {
            return back()->withErrors(['order' => 'Teslim için önce siparişi alıp yola çıkmalısınız.']);
        }

        $this->orderStateService->transition($order, OrderStatus::Delivered);

        return back()->with('status', 'Teslim edildi.');
    }

    private function guardCourier(Order $order): void
    {
        $c = Auth::user()->courierProfile;
        if ($c === null || (int) $order->courier_id !== (int) $c->id) {
            abort(403);
        }
    }
}
