<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(3)->setTime(10, 0);

        return [
            'appointment_code' => 'MG'.fake()->unique()->numerify('######'),
            'customer_id' => User::factory()->state(['role' => 'CUSTOMER']),
            'specialist_profile_id' => SpecialistProfile::factory(),
            'service_id' => Service::factory(),
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'price' => 500000,
            'status' => 'CONFIRMED',
            'payment_status' => 'UNPAID',
            'payment_method' => 'CASH',
            'source' => 'ONLINE',
            'customer_note' => null,
            'created_by' => User::factory()->state(['role' => 'CUSTOMER']),
            'slot_lock_key' => fake()->unique()->numerify('1|2026-01-01 10:##:##'),
            'needs_attention' => false,
        ];
    }
}
