<?php

declare(strict_types=1);

namespace Database\Factories\Marketing;

use App\Models\Marketing\MarketingSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingSite>
 */
class MarketingSiteFactory extends Factory
{
    protected $model = MarketingSite::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'primary_domain' => null,
            'is_published' => true,
            'theme' => null,
        ];
    }
}
