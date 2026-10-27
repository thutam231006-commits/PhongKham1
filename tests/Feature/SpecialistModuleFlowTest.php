<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Models\WorkingSchedule;
use App\Notifications\AppointmentInformationNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * SpecialistModuleFlowTest — luồng nghiệp vụ phân khu Chuyên viên.
 *
 * Bao phủ:
 *  - Impact Preview trước khi đăng ký ngoại lệ lịch trình.
 *  - Ngoại lệ có lịch bị ảnh hưởng chuyển sang trạng thái chờ Lễ tân điều phối.
 *  - Hoàn thành ca đưa thẳng tới form ghi hồ sơ.
 *  - Không thể bắt đầu ca khi lịch không thuộc chuyên viên đang đăng nhập.
 */
class SpecialistModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_page_shows_both_configuration_tabs(): void
    {
        [$specialist] = $this->specialist();

        $this->actingAs($specialist)
            ->get(route('specialist.schedule'))
            ->assertOk()
            ->assertSee('Ca cố định')
            ->assertSee('Ngoại lệ lịch trình');
    }

    public function test_impact_preview_lists_confirmed_appointments_before_saving(): void
    {
        [$specialist, $profile] = $this->specialist();
        $affected = $this->appointment($profile, now()->addDays(5)->setTime(9, 0), 'CONFIRMED');

        $this->actingAs($specialist)
            ->post(route('specialist.leaves.preview'), [
                'type' => 'DAY_OFF',
                'start_date' => $affected->start_at->toDateString(),
                'end_date' => $affected->start_at->toDateString(),
                'reason' => 'Nghỉ phép cá nhân',
            ])
            ->assertOk()
            ->assertSee('Xem trước tác động')
            ->assertSee($affected->customer->name)
            ->assertSee('Chuyển Lễ tân');

        // Bước xem trước chỉ đọc dữ liệu, chưa được ghi ngoại lệ.
        $this->assertDatabaseCount('schedule_exceptions', 0);
    }

    public function test_registering_a_leave_with_affected_appointments_waits_for_staff_coordination(): void
    {
        [$specialist, $profile] = $this->specialist();
        $affected = $this->appointment($profile, now()->addDays(6)->setTime(14, 0), 'CONFIRMED');

        $this->actingAs($specialist)
            ->post(route('specialist.leaves.store'), [
                'type' => 'DAY_OFF',
                'start_date' => $affected->start_at->toDateString(),
                'end_date' => $affected->start_at->toDateString(),
                'reason' => 'Việc gia đình',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedule_exceptions', [
            'specialist_profile_id' => $profile->id,
            'type' => 'DAY_OFF',
            'review_status' => 'PENDING_REVIEW',
            'affected_appointments_count' => 1,
        ]);

        // Không tự hủy lịch của khách; chỉ gắn cờ để Lễ tân điều phối.
        $this->assertDatabaseHas('appointments', [
            'id' => $affected->id,
            'status' => 'CONFIRMED',
            'needs_attention' => true,
        ]);
    }

    public function test_leave_without_conflicts_is_recorded_immediately(): void
    {
        [$specialist, $profile] = $this->specialist();
        $this->appointment($profile, now()->addDays(7)->setTime(9, 0), 'CONFIRMED');

        $this->actingAs($specialist)
            ->post(route('specialist.leaves.store'), [
                'type' => 'DAY_OFF',
                'start_date' => now()->addDays(20)->toDateString(),
                'end_date' => now()->addDays(20)->toDateString(),
                'reason' => 'Nghỉ dài ngày',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedule_exceptions', [
            'specialist_profile_id' => $profile->id,
            'review_status' => 'APPROVED',
            'affected_appointments_count' => 0,
        ]);
    }

    public function test_pending_leave_cannot_be_removed_by_the_specialist(): void
    {
        [$specialist, $profile] = $this->specialist();
        $this->appointment($profile, now()->addDays(8)->setTime(10, 0), 'CONFIRMED');

        $this->actingAs($specialist)->post(route('specialist.leaves.store'), [
            'type' => 'DAY_OFF',
            'start_date' => now()->addDays(8)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'reason' => 'Nghỉ đột xuất',
        ]);

        $exception = ScheduleException::query()->firstOrFail();

        $this->actingAs($specialist)
            ->delete(route('specialist.leaves.destroy', $exception->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('schedule_exceptions', ['id' => $exception->id]);
    }

    public function test_completing_a_session_redirects_straight_to_the_consultation_form(): void
    {
        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile, now()->subMinutes(20), 'IN_PROGRESS');

        $this->actingAs($specialist)
            ->post(route('specialist.appointments.complete', $appointment->id))
            ->assertRedirect(route('specialist.consultation-note', $appointment->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'COMPLETED']);
    }

    public function test_specialist_can_send_session_information_to_the_customer_only(): void
    {
        Notification::fake();

        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile, now()->addDay(), 'CONFIRMED');

        $this->actingAs($specialist)
            ->post(route('specialist.appointments.send-information', $appointment->id), ['note' => 'Mang theo kết quả xét nghiệm.'])
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $appointment->customer,
            AppointmentInformationNotification::class,
            fn (AppointmentInformationNotification $notification): bool => $notification->appointment->id === $appointment->id
                && $notification->extraNote === 'Mang theo kết quả xét nghiệm.',
        );

        // Chỉ khách hàng nhận — chuyên viên thực hiện thao tác không nhận bản sao.
        Notification::assertNotSentTo($specialist, AppointmentInformationNotification::class);
        Notification::assertCount(1);
    }

    public function test_specialist_cannot_send_information_for_a_customer_without_email(): void
    {
        Notification::fake();

        [$specialist, $profile] = $this->specialist();
        $appointment = $this->appointment($profile, now()->addDay(), 'CONFIRMED');
        $appointment->customer->forceFill(['email' => null])->save();

        $this->actingAs($specialist)
            ->post(route('specialist.appointments.send-information', $appointment->id))
            ->assertSessionHas('error');

        Notification::assertNothingSent();
    }

    public function test_specialist_can_start_a_late_appointment_after_its_scheduled_end(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27 15:00:00'));
        [$specialist, $profile] = $this->specialist();
        // Ca 60 phút đã kết thúc 2 tiếng trước (khách đến muộn / ca trôi giờ).
        $appointment = $this->appointment($profile, now()->subHours(3), 'CONFIRMED');

        $this->actingAs($specialist)
            ->post(route('specialist.appointments.start', $appointment->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'IN_PROGRESS']);
    }

    public function test_specialist_cannot_start_an_appointment_of_another_specialist(): void
    {
        [, $profile] = $this->specialist();
        $appointment = $this->appointment($profile, now(), 'CONFIRMED');

        [$otherSpecialist] = $this->specialist();

        $this->actingAs($otherSpecialist)
            ->post(route('specialist.appointments.start', $appointment->id))
            ->assertNotFound();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED']);
    }

    public function test_specialist_can_reenable_a_disabled_working_schedule(): void
    {
        [$specialist, $profile] = $this->specialist();
        $schedule = WorkingSchedule::factory()->create([
            'specialist_profile_id' => $profile->id,
            'day_of_week' => 3,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_active' => false,
        ]);

        $this->actingAs($specialist)
            ->put(route('specialist.schedule.update', $schedule), [
                'day_of_week' => 3,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'is_active' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('working_schedules', ['id' => $schedule->id, 'is_active' => true]);
    }

    /**
     * @return array{0: User, 1: SpecialistProfile}
     */
    private function specialist(): array
    {
        $user = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create();
        $profile->services()->attach($service);

        return [$user, $profile];
    }

    private function appointment(SpecialistProfile $profile, Carbon $start, string $status): Appointment
    {
        $customer = User::factory()->create(['role' => 'customer']);

        return Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => $profile->services()->firstOrFail()->id,
            'status' => $status,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'confirmed_at' => $status === 'CONFIRMED' ? now() : null,
            'started_at' => $status === 'IN_PROGRESS' ? now()->subMinutes(20) : null,
        ]);
    }
}
