<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsultationRoom;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Services\AttentionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SCR-STF-03 (chi tiết lịch hẹn) & SCR-STF-07 (điều phối lịch bị ảnh hưởng).
 */
class StaffCoordinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_appointment_detail_without_seeing_the_private_customer_note(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'consultation_type' => 'OFFLINE',
            'customer_note' => 'Ghi chú riêng tư cần bảo mật',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.appointments.detail', $appointment))
            ->assertOk()
            ->assertSee($appointment->appointment_code)
            ->assertSee('Thông tin lịch hẹn')
            ->assertSee('Thanh toán')
            ->assertSee('Lịch sử trạng thái')
            ->assertSee('Thao tác điều phối')
            ->assertDontSee('Ghi chú riêng tư cần bảo mật');
    }

    public function test_staff_cannot_open_appointment_detail_of_another_role(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->makeAppointment();

        $this->actingAs($customer)
            ->get(route('staff.appointments.detail', $appointment))
            ->assertForbidden();
    }

    public function test_staff_detail_shows_the_online_meeting_link(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'consultation_type' => 'ONLINE',
            'meeting_url' => 'https://meet.jit.si/mindguide-mg123456',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.appointments.detail', $appointment))
            ->assertOk()
            ->assertSee('https://meet.jit.si/mindguide-mg123456')
            ->assertSee('Trực tuyến');
    }

    public function test_attention_list_groups_flagged_appointments_by_reason(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'needs_attention' => true,
            'attention_reason' => AttentionService::REASON_SPECIALIST_INACTIVE,
            'attention_flagged_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.appointments.attention'))
            ->assertOk()
            ->assertSee('Điều phối lịch bị ảnh hưởng')
            ->assertSee(AttentionService::REASON_SPECIALIST_INACTIVE)
            ->assertSee($appointment->appointment_code);
    }

    public function test_attention_list_hides_appointments_that_do_not_need_attention(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->makeAppointment(['status' => 'CONFIRMED', 'needs_attention' => false]);

        $this->actingAs($staff)
            ->get(route('staff.appointments.attention'))
            ->assertOk()
            ->assertSee('Không có lịch nào cần xử lý');
    }

    public function test_attention_list_can_be_filtered_by_specialist(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'needs_attention' => true,
            'attention_reason' => AttentionService::REASON_SCHEDULE_EXCEPTION,
            'attention_flagged_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.appointments.attention', ['specialist_id' => $appointment->specialist_profile_id]))
            ->assertOk()
            ->assertSee($appointment->appointment_code);

        $otherSpecialist = SpecialistProfile::factory()->create();
        $this->actingAs($staff)
            ->get(route('staff.appointments.attention', ['specialist_id' => $otherSpecialist->id]))
            ->assertOk()
            ->assertSee('Không có lịch nào cần xử lý');
    }

    public function test_staff_can_reschedule_a_flagged_appointment_and_the_flag_is_cleared(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'consultation_type' => 'OFFLINE',
            'needs_attention' => true,
            'attention_reason' => AttentionService::REASON_SPECIALIST_INACTIVE,
            'attention_flagged_at' => now(),
        ]);
        $newStart = CarbonImmutable::now()->addDays(2)->setTime(14, 0);

        $this->actingAs($staff)
            ->post(route('staff.appointments.reschedule', $appointment), [
                'date' => $newStart->toDateString(),
                'time' => '14:00',
                'reason' => 'Chuyên viên nghỉ ngày đó.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'RESCHEDULED',
            'needs_attention' => false,
            'attention_reason' => null,
            'attention_flagged_at' => null,
        ]);

        $newAppointment = Appointment::query()
            ->where('rescheduled_from_id', $appointment->id)
            ->sole();
        $this->assertSame('CONFIRMED', $newAppointment->status);
        $this->assertSame('OFFLINE', $newAppointment->consultation_type);
        $this->assertNotNull($newAppointment->consultation_room_id);
        $this->assertContains('appointment.rescheduled', $this->notificationTypes($appointment->customer_id));
    }

    public function test_staff_can_cancel_a_flagged_appointment_and_the_flag_is_cleared(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'needs_attention' => true,
            'attention_reason' => AttentionService::REASON_NO_ROOM,
            'attention_flagged_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.appointments.cancel', $appointment), ['reason' => 'Chưa xếp được phòng.'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'CANCELLED_BY_CLINIC',
            'needs_attention' => false,
            'attention_reason' => null,
        ]);
    }

    public function test_staff_can_send_a_manual_notification_to_the_customer(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'CONFIRMED']);

        $this->actingAs($staff)
            ->post(route('staff.appointments.notify', $appointment), ['message' => 'Vui lòng đến sớm 10 phút.'])
            ->assertSessionHas('success');

        $this->assertContains('appointment.manual_notice', $this->notificationTypes($appointment->customer_id));
    }

    public function test_staff_can_mark_a_confirmed_appointment_as_no_show(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $start = CarbonImmutable::now()->subMinutes(30);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'start_at' => $start,
            'end_at' => $start->addHour(),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.appointments.no-show', $appointment))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'NO_SHOW']);
    }

    public function test_staff_cannot_mark_no_show_before_the_waiting_window_elapses(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'start_at' => CarbonImmutable::now()->addHour(),
            'end_at' => CarbonImmutable::now()->addHour()->addMinutes(60),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.appointments.no-show', $appointment))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED']);
    }

    public function test_admin_schedule_exception_flags_affected_appointments_for_staff_coordination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = $this->makeAppointment([
            'status' => 'CONFIRMED',
            'start_at' => CarbonImmutable::now()->addDays(2)->setTime(10, 0),
            'end_at' => CarbonImmutable::now()->addDays(2)->setTime(11, 0),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schedule.store'), [
                'specialist_profile_id' => $appointment->specialist_profile_id,
                'type' => 'DAY_OFF',
                'start_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
                'end_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
                'reason' => 'Nghỉ lễ',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'needs_attention' => true,
            'attention_reason' => AttentionService::REASON_SCHEDULE_EXCEPTION,
        ]);
    }

    /**
     * Danh sách loại thông báo đã gửi cho một người dùng (payload nằm trong cột `data`).
     *
     * @return array<int, string>
     */
    private function notificationTypes(int $notifiableId): array
    {
        return DB::table('notifications')
            ->where('notifiable_id', $notifiableId)
            ->pluck('data')
            ->map(fn (string $data): string => (string) (json_decode($data, true)['type'] ?? ''))
            ->all();
    }

    /** @param array<string, mixed> $overrides */
    private function makeAppointment(array $overrides = []): Appointment
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create();
        $specialist->services()->attach($service);
        $room = ConsultationRoom::query()->firstOrCreate(
            ['name' => 'Phòng tư vấn '.$specialist->id],
            ['location' => 'Tầng 1 - Mind Guide', 'is_active' => true],
        );
        $start = CarbonImmutable::now()->addDay()->setTime(10, 0);

        return Appointment::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'slot_lock_key' => $specialist->id.'|'.$start->format('Y-m-d H:i:s'),
            'consultation_type' => 'OFFLINE',
            'consultation_room_id' => $room->id,
        ], $overrides));
    }
}
