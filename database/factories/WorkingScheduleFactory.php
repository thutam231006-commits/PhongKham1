<?php

namespace Database\Factories;

use App\Models\SpecialistProfile;
use App\Models\WorkingSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingSchedule>
 */
class WorkingScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'specialist_profile_id' => SpecialistProfile::factory(),
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'break_start' => null,
            'break_end' => null,
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ];
    }
}
