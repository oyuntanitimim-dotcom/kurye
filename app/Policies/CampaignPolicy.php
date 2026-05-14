<?php

namespace App\Policies;

use App\Modules\Firms\Models\Campaign;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isFirmAdmin($user);
    }

    public function view(User $user, Campaign $campaign): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->firmOwnsCampaign($user, $campaign);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isFirmAdmin($user);
    }

    public function update(User $user, Campaign $campaign): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->firmOwnsCampaign($user, $campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->firmOwnsCampaign($user, $campaign);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    private function isFirmAdmin(User $user): bool
    {
        return $user->role?->name === Role::FIRM_ADMIN && $user->firm_id !== null;
    }

    private function firmOwnsCampaign(User $user, Campaign $campaign): bool
    {
        return $this->isFirmAdmin($user)
            && (int) $user->firm_id === (int) $campaign->firm_id;
    }
}
