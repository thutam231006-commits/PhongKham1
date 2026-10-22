<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_check_in_a_confirmed_appointment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'CONFIRMED', 'payment_method' => 'CASH']);

        $this->actingAs($staff)->post(route('staff.checkin.store', $appointment->id))->assertSessionHas('success');

        $appointment->refresh();
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertSame($staff->id, $appointment->checked_in_by);
    }

    public function test_staff_can_collect_cash_only_after_completion(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'COMPLETED', 'payment_method' => 'CASH', 'payment_status' => 'UNPAID']);

        $this->actingAs($staff)->post(route('staff.payments.collect', $appointment->id), ['payment_reference' => 'CASH-001'])->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('PAID', $appointment->payment_status);
        $this->assertSame($staff->id, $appointment->collected_by);
        $this->assertSame('CASH-001', $appointment->payment_reference);
    }

    public function test_staff_cannot_collect_an_uncompleted_appointment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'CONFIRMED', 'payment_method' => 'CASH']);

        $this->actingAs($staff)->post(route('staff.payments.collect', $appointment->id))->assertStatus(409);
    }

    /** @param array<string, mixed> $overrides */
    private function makeAppointment(array $overrides = []): Appointment
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create();
        $specialist->services()->attach($service);
        $start = CarbonImmutable::now()->addDay()->setTime(10, 0);

        return Appointment::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'slot_lock_key' => $specialist->id.'|'.$start->format('Y-m-d H:i:s'),
        ], $overrides));
    }
}
