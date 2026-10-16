<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ServiceCategory::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'price' => 500000,
            'status' => 'ACTIVE',
            'display_order' => 1,
        ];
    }
}
