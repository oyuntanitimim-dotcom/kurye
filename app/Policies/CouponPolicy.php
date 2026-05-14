<?php

namespace App\Policies;

use App\Modules\Firms\Models\Coupon;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isFirmAdmin($user);
    }

    public function view(User $user, Coupon $coupon): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->firmOwnsCoupon($user, $coupon);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isFirmAdmin($user);
    }

    public function update(User $user, Coupon $coupon): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->firmOwnsCoupon($user, $coupon);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->firmOwnsCoupon($user, $coupon);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    private function isFirmAdmin(User $user): bool
    {
        return $user->role?->name === Role::FIRM_ADMIN && $user->firm_id !== null;
    }

    private function firmOwnsCoupon(User $user, Coupon $coupon): bool
    {
        return $this->isFirmAdmin($user)
            && (int) $user->firm_id === (int) $coupon->firm_id;
    }
}
