<?php

namespace App\Services;

use App\Modules\Firms\Models\Firm;

final class FirmContext
{
    private ?Firm $firm = null;

    public function set(?Firm $firm): void
    {
        $this->firm = $firm;
    }

    public function firm(): ?Firm
    {
        return $this->firm;
    }

    public function id(): ?int
    {
        return $this->firm?->id;
    }

    public function require(): Firm
    {
        if ($this->firm === null) {
            abort(404, 'Kurye şirketi bulunamadı.');
        }

        return $this->firm;
    }
}
