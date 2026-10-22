<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Models\WorkingSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialist_can_open_dashboard_but_customer_cannot(): void
    {
        $specialist = User::factory()->create(['role' => 'specialist']);
        SpecialistProfile::factory()->create(['user_id' => $specialist->id, 'status' => 'ACTIVE']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($specialist)->get(route('specialist.dashboard'))->assertOk();
        $this->actingAs($customer)->get(route('specialist.dashboard'))->assertForbidden();
    }

    public function test_specialist_cannot_create_overlapping_working_schedule(): void
    {
        $specialist = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $specialist->id, 'status' => 'ACTIVE']);
        WorkingSchedule::factory()->create([
            'specialist_profile_id' => $profile->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($specialist)
            ->post(route('specialist.schedule.store'), [
                'day_of_week' => 1,
                'start_time' => '11:00',
                'end_time' => '14:00',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('working_schedules', 1);
    }

    public function test_assigned_specialist_can_save_encrypted_consultation_record(): void
    {
        $specialist = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $specialist->id, 'status' => 'ACTIVE']);
        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::factory()->create();
        $profile->services()->attach($service);
        $start = CarbonImmutable::now()->addHour();
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'status' => 'IN_PROGRESS',
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'slot_lock_key' => $profile->id.'|'.$start->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Tóm tắt sức khỏe tinh thần của khách hàng.',
                'follow_up_plan' => 'Theo dõi sau hai tuần.',
                'follow_up_needed' => 1,
            ])
            ->assertSessionHas('success');

        $record = $appointment->refresh()->consultationRecord;
        $this->assertSame('Tóm tắt sức khỏe tinh thần của khách hàng.', $record->summary);
        $this->assertDatabaseMissing('consultation_records', ['summary' => 'Tóm tắt sức khỏe tinh thần của khách hàng.']);
    }
}
