<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case CourierAssigned = 'courier_assigned';
    /** Firma atamasını kurye kabul etti (veya havuzdan üstlendi); henüz restorandan alınmadı. */
    case CourierAccepted = 'courier_accepted';
    case PickedUp = 'picked_up';
    case OnTheWay = 'on_the_way';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Beklemede',
            self::Accepted => 'Onaylandı',
            self::Preparing => 'Hazırlanıyor',
            self::Ready => 'Hazır',
            self::CourierAssigned => 'Kurye atandı',
            self::CourierAccepted => 'Kabul etti',
            self::PickedUp => 'Alındı',
            self::OnTheWay => 'Yolda',
            self::Delivered => 'Teslim edildi',
            self::Cancelled => 'İptal',
        };
    }
}
