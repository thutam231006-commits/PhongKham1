<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Models\WorkingSchedule;
use App\Notifications\AppointmentConfirmationNotification;
use App\Notifications\AppointmentReminderNotification;
use App\Services\AppointmentReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Yêu cầu 1, 2, 3, 4:
 *  - 1: lịch bác sĩ cập nhật tự động, nhân viên thấy số bệnh nhân / slot trống.
 *  - 2: nhắc lịch tự động 24 giờ và 30 phút + trường thông tin khách hàng.
 *  - 3: chỉ hiển thị khung giờ còn trống, đồng bộ trực tiếp với lịch bác sĩ.
 *  - 4: gửi xác nhận qua email.
 */
class AppointmentScheduleTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------
    // Yêu cầu 3 — chỉ hiển thị khung giờ còn trống, đồng bộ với lịch bác sĩ.
    // ---------------------------------------------------------------------

    public function test_slots_api_only_returns_open_slots_and_hides_booked_ones(): void
    {
        [$service, $specialist, $date] = $this->workingSetup();
        $customer = User::factory()->create(['role' => 'customer']);

        $before = $this->slotsApi($service, $specialist, $date);
        $firstStart = $before->json('data.slots.0.start');
        $this->assertNotEmpty($firstStart);
        $this->assertSame(count($before->json('data.slots')), $before->json('data.available_count'));

        // Đặt khung giờ đầu tiên, khung này phải biến mất khỏi danh sách trống.
        $this->actingAs($customer)->postJson(route('appointments.store'), [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => $firstStart,
            'payment_method' => 'CASH',
        ])->assertCreated();

        $after = $this->slotsApi($service, $specialist, $date);
        $this->assertNotContains($firstStart, array_column($after->json('data.slots'), 'start'));
        $this->assertSame(
            count($before->json('data.slots')) - 1,
            $after->json('data.available_count'),
        );
    }

    public function test_slots_api_requires_valid_specialist_service_and_date(): void
    {
        [$service, $specialist, $date] = $this->workingSetup();

        $this->getJson(route('api.slots.index'))->assertJsonValidationErrors(['service_id', 'specialist_id', 'date']);
        $this->getJson(route('api.slots.index', [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => 'khong-phai-ngay',
        ]))->assertJsonValidationErrors(['date']);
    }

    public function test_slot_step_rejects_a_time_that_is_no_longer_open(): void
    {
        [$service, $specialist, $date] = $this->workingSetup();
        $session = ['booking' => ['service_id' => $service->id, 'specialist_id' => $specialist->id]];

        // 22:00 nằm ngoài khung làm việc 08:00 - 17:00 của chuyên viên.
        // Handler toàn cục chuyển 422 thành redirect kèm thông báo thân thiện.
        $this->withSession($session)
            ->from(route('booking.select-slot', ['date' => $date->toDateString()]))
            ->post(route('booking.step.slot'), ['date' => $date->toDateString(), 'time' => '22:00'])
            ->assertRedirect(route('booking.select-slot', ['date' => $date->toDateString()]))
            ->assertSessionHas('error', 'Khung giờ đã chọn không còn khả dụng. Vui lòng chọn khung giờ khác.');

        // Khung giờ hợp lệ vẫn đi tiếp bình thường.
        $this->withSession($session)
            ->post(route('booking.step.slot'), ['date' => $date->toDateString(), 'time' => '15:00'])
            ->assertRedirect(route('booking.note'));
    }

    public function test_day_without_a_working_schedule_returns_no_slots(): void
    {
        [$service, $specialist] = $this->workingSetup(dirty: true);
        $closedDay = CarbonImmutable::now()->addDays(5);

        // Lịch làm việc chỉ được đặt vào đúng thứ trong tuần đã cấu hình.
        WorkingSchedule::query()->update([
            'day_of_week' => CarbonImmutable::now()->addDays(7)->isoWeekday(),
        ]);

        $this->getJson(route('api.slots.index', [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $closedDay->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonPath('data.available_count', 0)
            ->assertJsonPath('data.slots', []);
    }

    // ---------------------------------------------------------------------
    // Yêu cầu 1 — nhân viên nắm số bệnh nhân và số slot trống.
    // ---------------------------------------------------------------------

    public function test_staff_dashboard_shows_specialist_load_and_hourly_traffic(): void
    {
        [$service, $specialist, $date] = $this->workingSetup();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->postJson(route('appointments.store'), [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => '08:00',
            'payment_method' => 'CASH',
        ])->assertCreated();

        $staff = User::factory()->create(['role' => 'staff']);
        $response = $this->actingAs($staff)->get(route('staff.dashboard', ['date' => $date->toDateString()]));

        $response->assertOk()
            ->assertSee('Tải lịch bác sĩ')
            ->assertSee($specialist->user->name)
            ->assertSee('Lưu lượng theo giờ');

        $load = $response->viewData('specialistLoad');
        $this->assertCount(1, $load);
        $this->assertSame(1, $load->first()->patients);
        $this->assertSame(1, $load->first()->booked);
        $this->assertSame(7, $load->first()->capacity);
        $this->assertSame(6, $load->first()->open);
    }

    // ---------------------------------------------------------------------
    // Yêu cầu 1 — admin theo dõi lưu lượng theo thời gian.
    // ---------------------------------------------------------------------

    public function test_admin_reports_show_daily_and_hourly_traffic(): void
    {
        [$service, $specialist, $date] = $this->workingSetup();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->postJson(route('appointments.store'), [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => '10:00',
            'payment_method' => 'CASH',
        ])->assertCreated();

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admin.reports', [
            'from' => $date->toDateString(),
            'to' => $date->toDateString(),
        ]));

        $response->assertOk()->assertSee('Lưu lượng bệnh nhân theo ngày');

        $days = $response->viewData('patientsByDay');
        $this->assertCount(1, $days);
        $this->assertSame(1, $days->first()['total']);
        $this->assertSame(1, $days->first()['new_patients']);

        $hours = $response->viewData('hourlyTraffic');
        $this->assertSame(1, $hours->firstWhere('hour', 10)['total']);
        $this->assertSame(10, $response->viewData('peakHour')['hour']);
    }

    // ---------------------------------------------------------------------
    // Yêu cầu 4 — gửi xác nhận qua email.
    // ---------------------------------------------------------------------

    public function test_booking_sends_a_confirmation_email_with_the_full_appointment_details(): void
    {
        Notification::fake();
        [$service, $specialist, $date] = $this->workingSetup();
        $customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'khach.thu@mindguide.vn',
        ]);

        $this->actingAs($customer)->postJson(route('appointments.store'), [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => '10:00',
            'payment_method' => 'CASH',
        ])->assertCreated();

        $appointment = Appointment::query()->where('customer_id', $customer->id)->sole();

        Notification::assertSentTo(
            $customer,
            AppointmentConfirmationNotification::class,
            function (AppointmentConfirmationNotification $notification, array $channels) use ($customer, $appointment, $specialist, $service): bool {
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);

                $mail = $notification->toMail($customer);
                $rendered = implode("\n", array_merge(
                    [$mail->subject],
                    $mail->introLines,
                    $mail->outroLines,
                    [$mail->actionText],
                ));

                $this->assertStringContainsString($appointment->appointment_code, $mail->subject);
                $this->assertStringContainsString($service->name, $rendered);
                $this->assertStringContainsString($specialist->user->name, $rendered);
                $this->assertStringContainsString($appointment->start_at->format('d/m/Y H:i'), $rendered);

                return true;
            },
        );
    }

    // ---------------------------------------------------------------------
    // Yêu cầu 2 — nhắc lịch tự động 24 giờ và 30 phút.
    // ---------------------------------------------------------------------

    public function test_reminders_are_sent_once_at_24_hours_and_30_minutes(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'status' => 'CONFIRMED',
            'start_at' => now()->addHours(24),
            'end_at' => now()->addHours(25),
        ]);

        $sent = app(AppointmentReminderService::class)->sendDue();

        $this->assertSame(1, $sent['24_HOURS']);
        $this->assertSame(0, $sent['30_MINUTES']);

        Notification::assertSentTo(
            $customer,
            AppointmentReminderNotification::class,
            fn (AppointmentReminderNotification $notification): bool => $notification->reminderType === '24_HOURS',
        );

        $this->assertNotNull($appointment->refresh()->reminder_24h_at);
        $this->assertNull($appointment->reminder_30m_at);

        // Chạy lại lần nữa: không được gửi trùng.
        $this->assertSame(0, app(AppointmentReminderService::class)->sendDue()['24_HOURS']);
    }

    public function test_second_reminder_is_sent_thirty_minutes_before_the_appointment(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'status' => 'CONFIRMED',
            'start_at' => now()->addMinutes(30),
            'end_at' => now()->addMinutes(90),
        ]);

        $sent = app(AppointmentReminderService::class)->sendDue();

        $this->assertSame(0, $sent['24_HOURS']);
        $this->assertSame(1, $sent['30_MINUTES']);
        Notification::assertSentTo(
            $customer,
            AppointmentReminderNotification::class,
            fn (AppointmentReminderNotification $notification): bool => $notification->reminderType === '30_MINUTES',
        );

        $this->assertNotNull($appointment->refresh()->reminder_30m_at);
    }

    public function test_cancelled_appointments_never_receive_a_reminder(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['role' => 'customer']);

        Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'status' => 'CANCELLED_BY_CUSTOMER',
            'start_at' => now()->addMinutes(30),
            'end_at' => now()->addMinutes(90),
        ]);

        $sent = app(AppointmentReminderService::class)->sendDue();

        $this->assertSame(0, $sent['24_HOURS']);
        $this->assertSame(0, $sent['30_MINUTES']);
        Notification::assertNothingSent();
    }

    public function test_maintenance_command_reports_the_number_of_reminders_sent(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['role' => 'customer']);
        Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'status' => 'CONFIRMED',
            'start_at' => now()->addMinutes(30),
            'end_at' => now()->addMinutes(90),
        ]);

        $this->artisan('appointments:maintain')
            ->expectsOutputToContain('gửi 0 nhắc 24 giờ và 1 nhắc 30 phút')
            ->assertSuccessful();
    }

    // ---------------------------------------------------------------------
    // Yêu cầu 2 — trường thông tin khách hàng: họ tên, địa chỉ, ngày sinh,
    // giới tính, số điện thoại, email.
    // ---------------------------------------------------------------------

    public function test_customer_can_store_gender_and_address_on_their_profile(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Nguyễn Thị Lan',
            'email' => 'lan.nguyen@mindguide.vn',
            'phone' => '0901234567',
        ]);

        $this->actingAs($customer)->post(route('customer.profile.update'), [
            'name' => 'Nguyễn Thị Lan',
            'email' => 'lan.nguyen@mindguide.vn',
            'phone' => '0901234567',
            'gender' => 'FEMALE',
            'address' => '12 Nguyễn Văn Cừ, Quận 1, TP.HCM',
            'date_of_birth' => '1990-05-04',
        ])->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame('FEMALE', $customer->gender);
        $this->assertSame('12 Nguyễn Văn Cừ, Quận 1, TP.HCM', $customer->address);
        $this->assertSame('1990-05-04', $customer->customerProfile->date_of_birth->toDateString());
    }

    public function test_profile_rejects_an_unsupported_gender(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->post(route('customer.profile.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'gender' => 'KHONG_XAC_DINH',
        ])->assertSessionHasErrors('gender');
    }

    // ---------------------------------------------------------------------
    // Yêu cầu 5 — nội dung footer.
    // ---------------------------------------------------------------------

    public function test_footer_shows_brand_links_contact_and_the_clinic_details(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Mind Guide')
            ->assertSee('Hỗ trợ')
            ->assertSee('Liên hệ')
            ->assertSee('Về Mind Guide')
            ->assertSee('hotro@mindguide.vn')
            ->assertSee('0967283126')
            ->assertSee('Nguyễn Chí Thanh, Hà Nội')
            ->assertSee('Đặt lịch tư vấn');
    }

    public function test_footer_no_longer_renders_the_booking_process_or_service_list(): void
    {
        $footer = $this->footerMarkup(route('home'));

        $this->assertStringNotContainsString('Quy trình', $footer);
        $this->assertStringNotContainsString('Hệ thống tự nhắc trước buổi hẹn 24 giờ và 30 phút.', $footer);
        $this->assertStringNotContainsString('Xem tất cả dịch vụ', $footer);
    }

    public function test_footer_keeps_the_emergency_notice_and_copyright(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Lưu ý quan trọng')
            ->assertSee('1900 1567')
            ->assertSee('Mọi quyền được bảo lưu');
    }

    // ---------------------------------------------------------------------

    /**
     * Chỉ lấy riêng markup của <footer> để khẳng định nội dung footer,
     * tránh nhầm lẫn với các mục trùng nội dung nằm ở phần nội dung chính.
     */
    private function footerMarkup(string $url): string
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<footer\b.*?<\/footer>/s', $html, $matches), 'Footer không được render trong layout.');

        return $matches[0];
    }

    /** @return TestResponse */
    private function slotsApi(Service $service, SpecialistProfile $specialist, CarbonImmutable $date)
    {
        return $this->getJson(route('api.slots.index', [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
        ]))->assertOk()->assertJsonPath('success', true);
    }

    /** @return array{0: Service, 1: SpecialistProfile, 2: CarbonImmutable} */
    private function workingSetup(bool $dirty = false): array
    {
        $service = Service::factory()->create();
        $specialist = SpecialistProfile::factory()->create();
        $specialist->services()->attach($service);

        $date = CarbonImmutable::now()->addDays(4);
        WorkingSchedule::factory()->create([
            'specialist_profile_id' => $specialist->id,
            'day_of_week' => $date->isoWeekday(),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        return [$service, $specialist, $date->setTime(10, 0)];
    }
}
