<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'address',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Koordinatı eksik olan adresler. */
    public function scopeMissingCoords(Builder $q): Builder
    {
        return $q->where(function ($qq): void {
            $qq->whereNull('latitude')->orWhereNull('longitude');
        });
    }
}
