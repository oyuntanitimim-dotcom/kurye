<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';

    public const FIRM_ADMIN = 'firm_admin';

    public const RESTAURANT = 'restaurant';

    public const COURIER = 'courier';

    public const CUSTOMER = 'customer';

    protected $fillable = [
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
