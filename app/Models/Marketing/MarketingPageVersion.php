<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingPageVersion extends Model
{
    protected $fillable = [
        'marketing_page_id',
        'version',
        'blocks_json',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'blocks_json' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(MarketingPage::class, 'marketing_page_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
