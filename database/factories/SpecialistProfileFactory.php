<?php

namespace Database\Factories;

use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecialistProfile>
 */
class SpecialistProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'SPECIALIST']),
            'title' => 'Chuyên viên Tâm lý học lâm sàng',
            'expertise' => 'Tư vấn tâm lý',
            'bio' => fake()->paragraph(),
            'years_of_experience' => 5,
            'status' => 'ACTIVE',
            'display_order' => 1,
        ];
    }
}
