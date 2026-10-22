<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ConsultationRecordWebTest — hồ sơ tư vấn trên giao diện (SCR-SPE-06, SCR-CUS-02)
 *
 * Bao phủ:
 *  - Chuyên viên xem hồ sơ khách hàng và ghi kết quả buổi tư vấn tâm lý.
 *  - Khách hàng xem bản tóm lược trong hồ sơ cá nhân của mình.
 *  - Staff/Admin KHÔNG thấy nội dung tư vấn và mọi lần xem đều được audit.
 */
class ConsultationRecordWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialist_can_open_a_customer_profile_and_see_consultation_results(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer);

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Kết quả buổi tư vấn tâm lý: khách đã ổn định hơn về giấc ngủ.',
                'follow_up_plan' => 'Theo dõi sau hai tuần.',
                'follow_up_needed' => 1,
                'customer_summary' => 'Bạn đã tiến bộ rõ rệt trong hai tuần qua.',
                'customer_recommendation' => 'Tiếp tục ghi nhật cảm xúc mỗi tối.',
            ])
            ->assertSessionHas('success');

        $response = $this->actingAs($specialist)
            ->get(route('specialist.customers.profile', $customer->id))
            ->assertOk();

        $response->assertSee('Kết quả buổi tư vấn tâm lý: khách đã ổn định hơn về giấc ngủ.')
            ->assertSee('Theo dõi sau hai tuần.')
            ->assertSee('Bạn đã tiến bộ rõ rệt trong hai tuần qua.');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $specialist->id,
            'action' => 'consultation.write',
            'entity_type' => 'consultation_records',
        ]);
    }

    public function test_specialist_cannot_open_a_customer_profile_of_another_specialist(): void
    {
        [, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $this->appointment($profile, $customer);

        $otherSpecialist = User::factory()->create(['role' => 'specialist']);
        SpecialistProfile::factory()->create(['user_id' => $otherSpecialist->id, 'status' => 'ACTIVE']);

        $this->actingAs($otherSpecialist)
            ->get(route('specialist.customers.profile', $customer->id))
            ->assertOk()
            ->assertDontSee('Kết quả buổi tư vấn');
    }

    public function test_customer_sees_only_the_sanitized_summary_in_their_profile(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer, status: 'COMPLETED');

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Nhận định chuyên môn nội bộ KHÔNG được lộ cho khách.',
                'follow_up_plan' => 'Kế hoạch nội bộ KHÔNG được lộ cho khách.',
                'follow_up_needed' => 1,
                'customer_summary' => 'Tóm lược an toàn dành cho bạn.',
                'customer_recommendation' => 'Khuyến nghị an toàn dành cho bạn.',
            ])
            ->assertSessionHas('success');

        $response = $this->actingAs($customer)
            ->get(route('customer.medical-history'))
            ->assertOk();

        $response->assertSee('Tóm lược an toàn dành cho bạn.')
            ->assertSee('Khuyến nghị an toàn dành cho bạn.')
            ->assertDontSee('Nhận định chuyên môn nội bộ KHÔNG được lộ cho khách.')
            ->assertDontSee('Kế hoạch nội bộ KHÔNG được lộ cho khách.');
    }

    public function test_staff_customer_profile_does_not_expose_consultation_content(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer);

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Nội dung tư vấn tuyệt mật không dành cho Staff.',
                'follow_up_needed' => 0,
            ])
            ->assertSessionHas('success');

        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('staff.customers.profile', $customer->id))
            ->assertOk()
            ->assertDontSee('Nội dung tư vấn tuyệt mật không dành cho Staff.');
    }

    public function test_customer_profile_tab_shows_the_sanitized_consultation_results(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer, status: 'COMPLETED');

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Nhận định chuyên môn nội bộ KHÔNG được lộ trong hồ sơ cá nhân.',
                'follow_up_needed' => 1,
                'customer_summary' => 'Tóm lược hiển thị trong hồ sơ cá nhân.',
                'customer_recommendation' => 'Khuyến nghị hiển thị trong hồ sơ cá nhân.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($customer)
            ->get(route('customer.profile'))
            ->assertOk()
            ->assertSee('Tóm lược hiển thị trong hồ sơ cá nhân.')
            ->assertSee('Khuyến nghị hiển thị trong hồ sơ cá nhân.')
            ->assertDontSee('Nhận định chuyên môn nội bộ KHÔNG được lộ trong hồ sơ cá nhân.');
    }

    public function test_reading_a_consultation_record_is_audited(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer);

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Ghi hồ sơ trước.',
                'follow_up_needed' => 0,
            ])
            ->assertSessionHas('success');

        $this->actingAs($specialist)
            ->get(route('specialist.consultation-note', $appointment->id))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $specialist->id,
            'action' => 'consultation.read',
            'entity_type' => 'consultation_records',
        ]);

        $this->assertStringNotContainsString(
            'Ghi hồ sơ trước.',
            AuditLog::query()->where('entity_type', 'consultation_records')->get()->toJson(),
        );
    }

    public function test_consultation_record_cannot_be_written_before_the_appointment_starts(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer, status: 'CONFIRMED');

        $this->actingAs($specialist)
            ->post(route('specialist.consultation-note.store', $appointment->id), [
                'summary' => 'Ghi hồ sơ quá sớm.',
                'follow_up_needed' => 0,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('consultation_records', 0);
    }

    /** @return array{0: User, 1: SpecialistProfile} */
    private function specialist(): array
    {
        $user = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        return [$user, $profile];
    }

    private function appointment(SpecialistProfile $profile, User $customer, string $status = 'IN_PROGRESS'): Appointment
    {
        $service = Service::factory()->create();
        $profile->services()->syncWithoutDetaching([$service->id]);
        $start = CarbonImmutable::now()->addHour();

        return Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'status' => $status,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'slot_lock_key' => $profile->id.'|'.$start->format('Y-m-d H:i:s'),
        ]);
    }
}
