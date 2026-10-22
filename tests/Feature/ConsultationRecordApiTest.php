<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ConsultationRecord;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ConsultationRecordApiTest — FR-CONSULT-001…003 (mục 29 SRS)
 *
 * Bao phủ ba yêu cầu chính:
 *  1. Mã hóa ở mức trường — nội dung không tồn tại dạng rõ trong CSDL.
 *  2. Phân quyền — chỉ chuyên viên PHỤ TRÁCH mới đọc/ghi được nội dung chuyên môn.
 *  3. Audit tự động — mọi lần đọc/ghi đều có bản ghi, không chứa nội dung.
 */
class ConsultationRecordApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_specialist_can_read_and_write_the_consultation_record(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Khách thể hiện lo âu công việc, đã có dấu hiệu cải thiện sau hai buổi.',
                'follow_up_plan' => 'Theo dõi sau hai tuần, luyện tập thở sâu mỗi tối.',
                'follow_up_needed' => true,
                'customer_summary' => 'Buổi đầu tiên đã có bước tiến tích cực.',
                'customer_recommendation' => 'Dành 10 phút mỗi tối để thở sâu và ghi lại cảm xúc.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary', 'Khách thể hiện lo âu công việc, đã có dấu hiệu cải thiện sau hai buổi.')
            ->assertJsonPath('data.version', 1);

        $this->actingAs($specialist)
            ->getJson(route('api.consultation-records.show', $appointment))
            ->assertOk()
            ->assertJsonPath('data.follow_up_plan', 'Theo dõi sau hai tuần, luyện tập thở sâu mỗi tối.')
            ->assertJsonPath('data.customer_summary', 'Buổi đầu tiên đã có bước tiến tích cực.');
    }

    public function test_sensitive_fields_are_encrypted_at_rest(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Nội dung chuyên môn cần được bảo mật tuyệt đối.',
                'follow_up_needed' => false,
            ])
            ->assertOk();

        $stored = DB::table('consultation_records')->where('appointment_id', $appointment->id)->first();

        $this->assertNotSame('Nội dung chuyên môn cần được bảo mật tuyệt đối.', $stored->summary);
        $this->assertDatabaseMissing('consultation_records', [
            'summary' => 'Nội dung chuyên môn cần được bảo mật tuyệt đối.',
        ]);

        // Model vẫn giải mã được khi đọc trong ứng dụng.
        $this->assertSame(
            'Nội dung chuyên môn cần được bảo mật tuyệt đối.',
            ConsultationRecord::query()->where('appointment_id', $appointment->id)->firstOrFail()->summary,
        );
    }

    public function test_other_roles_cannot_read_the_consultation_record(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Nội dung chuyên môn bí mật.',
                'follow_up_needed' => false,
            ])
            ->assertOk();

        foreach (['staff', 'admin', 'customer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson(route('api.consultation-records.show', $appointment))
                ->assertForbidden();
        }
    }

    public function test_specialist_cannot_read_a_record_assigned_to_another_specialist(): void
    {
        [, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $otherSpecialist = User::factory()->create(['role' => 'specialist']);
        SpecialistProfile::factory()->create(['user_id' => $otherSpecialist->id, 'status' => 'ACTIVE']);

        $this->actingAs($otherSpecialist)
            ->getJson(route('api.consultation-records.show', $appointment))
            ->assertNotFound();

        $this->actingAs($otherSpecialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Cố ghi đè hồ sơ của chuyên viên khác.',
                'follow_up_needed' => false,
            ])
            ->assertForbidden();
    }

    public function test_customer_only_sees_the_sanitized_summary(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer, status: 'COMPLETED');

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Đánh giá chuyên môn nội bộ, khách không được thấy.',
                'follow_up_plan' => 'Kế hoạch theo dõi nội bộ, khách không được thấy.',
                'follow_up_needed' => true,
                'customer_summary' => 'Bản tóm lược an toàn cho khách.',
                'customer_recommendation' => 'Khuyến nghị an toàn cho khách.',
            ])
            ->assertOk();

        $response = $this->actingAs($customer)
            ->getJson(route('api.customer.consultation-records.index'))
            ->assertOk();

        $response->assertJsonPath('data.0.customer_summary', 'Bản tóm lược an toàn cho khách.')
            ->assertJsonPath('data.0.customer_recommendation', 'Khuyến nghị an toàn cho khách.')
            ->assertJsonPath('data.0.follow_up_needed', true)
            ->assertJsonPath('data.0.appointment_code', $appointment->appointment_code)
            ->assertJsonPath('data.0.service_name', $appointment->service->name)
            ->assertJsonPath('data.0.specialist_name', $appointment->specialistProfile->user->name);

        $body = $response->getContent();

        $this->assertStringNotContainsString('Đánh giá chuyên môn nội bộ', $body);
        $this->assertStringNotContainsString('Kế hoạch theo dõi nội bộ', $body);
        $this->assertStringNotContainsString('summary', str_replace('customer_summary', '', $body));
    }

    public function test_reading_and_writing_are_audited_without_record_content(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Nội dung tuyệt mật không được ghi vào audit.',
                'follow_up_needed' => false,
            ])
            ->assertOk();

        $this->actingAs($specialist)
            ->getJson(route('api.consultation-records.show', $appointment))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $specialist->id,
            'actor_role' => 'SPECIALIST',
            'action' => 'consultation.write',
            'entity_type' => 'consultation_records',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $specialist->id,
            'action' => 'consultation.read',
            'entity_type' => 'consultation_records',
        ]);

        $this->assertStringNotContainsString(
            'Nội dung tuyệt mật không được ghi vào audit.',
            AuditLog::query()->where('entity_type', 'consultation_records')->get()->toJson(),
        );
    }

    public function test_consultation_record_cannot_be_written_for_an_appointment_that_has_not_started(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile, status: 'CONFIRMED');

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Ghi hồ sơ khi lịch chưa bắt đầu.',
                'follow_up_needed' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('appointment_id');

        $this->assertDatabaseCount('consultation_records', 0);
    }

    public function test_stale_version_is_rejected_with_a_conflict(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Phiên bản đầu tiên.',
                'follow_up_needed' => false,
            ])
            ->assertOk();

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Phiên bản thứ hai.',
                'follow_up_needed' => false,
                'version' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.version', 2);

        // Phiên bản cũ (1) không còn khớp sau khi record đã lên version 2.
        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Ghi đè bằng phiên bản cũ.',
                'follow_up_needed' => false,
                'version' => 1,
            ])
            ->assertStatus(409);
    }

    public function test_specialist_customer_history_only_includes_own_appointments(): void
    {
        [$specialist, $profile] = $this->specialist();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment($profile, $customer);

        $this->actingAs($specialist)
            ->putJson(route('api.consultation-records.upsert', $appointment), [
                'summary' => 'Hồ sơ của buổi tư vấn đã phụ trách.',
                'follow_up_needed' => false,
                'customer_summary' => 'Tóm lược gửi khách.',
            ])
            ->assertOk();

        // Buổi tư vấn của một chuyên viên khác cùng phục vụ khách này.
        $otherSpecialist = User::factory()->create(['role' => 'specialist']);
        $otherProfile = SpecialistProfile::factory()->create(['user_id' => $otherSpecialist->id, 'status' => 'ACTIVE']);
        $otherAppointment = $this->appointment($otherProfile, $customer);

        $this->actingAs($otherSpecialist)
            ->putJson(route('api.consultation-records.upsert', $otherAppointment), [
                'summary' => 'Hồ sơ thuộc chuyên viên khác.',
                'follow_up_needed' => false,
            ])
            ->assertOk();

        $response = $this->actingAs($specialist)
            ->getJson(route('api.consultation-records.customer', $customer))
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.appointment_id', $appointment->id);

        $this->assertStringNotContainsString('Hồ sơ thuộc chuyên viên khác.', $response->getContent());
    }

    public function test_guest_cannot_reach_the_consultation_record_api(): void
    {
        [, $profile] = $this->specialist();
        $appointment = $this->appointment($profile);

        $this->getJson(route('api.consultation-records.show', $appointment))->assertUnauthorized();
        $this->putJson(route('api.consultation-records.upsert', $appointment), [
            'summary' => 'Khách vãng.',
            'follow_up_needed' => false,
        ])->assertUnauthorized();
    }

    /** @return array{0: User, 1: SpecialistProfile} */
    private function specialist(): array
    {
        $user = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        return [$user, $profile];
    }

    private function appointment(SpecialistProfile $profile, ?User $customer = null, string $status = 'IN_PROGRESS'): Appointment
    {
        $customer ??= User::factory()->create(['role' => 'customer']);
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
