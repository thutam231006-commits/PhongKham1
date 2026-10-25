<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmationMail;
use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\AppointmentConfirmationNotification;
use App\Notifications\AppointmentReminderNotification;
use App\Services\AccountActivationService;
use App\Services\MailHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * FR-PUB-002 / FR-STF-004 — email thông báo cho khách hàng.
 *
 * Các test ở đây bảo đảm email thực sự được gửi đi (không bị bỏ qua do cấu
 * hình mail sai) và khi gửi lỗi thì lễ tân nhìn thấy được nguyên nhân.
 */
class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_activation_email_is_sent_to_the_customer(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'khachhang@example.com',
        ]);

        app(AccountActivationService::class)->createAndSend($customer);

        Notification::assertSentTo($customer, AccountActivationNotification::class);

        $customer->refresh();
        $this->assertSame(AccountActivationService::STATUS_SENT, $customer->activation_email_status);
        $this->assertNotNull($customer->activation_email_sent_at);
        $this->assertNull($customer->activation_email_error);
    }

    public function test_account_activation_records_a_clear_reason_when_mail_is_misconfigured(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'khachhang@example.com',
        ]);

        app(AccountActivationService::class)->createAndSend($customer);

        $customer->refresh();
        $this->assertSame(AccountActivationService::STATUS_MISCONFIGURED, $customer->activation_email_status);
        $this->assertNull($customer->activation_email_sent_at);
        $this->assertStringContainsString('MAIL_USERNAME', (string) $customer->activation_email_error);
    }

    public function test_resend_activation_reports_the_failure_reason_to_the_staff(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);

        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'khachhang@example.com',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.customers.resend-activation', $customer))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_mail_health_service_flags_missing_smtp_credentials(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);

        $result = app(MailHealthService::class)->inspectConfiguration();

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['blocking']);
        $this->assertStringContainsString('MAIL_USERNAME', $result['message']);
    }

    public function test_mail_health_service_flags_the_log_mailer_as_unusable(): void
    {
        config(['mail.default' => 'log']);

        $result = app(MailHealthService::class)->inspectConfiguration();

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['blocking']);
        $this->assertStringContainsString('MAIL_MAILER', $result['message']);
    }

    public function test_staff_dashboard_warns_when_mail_cannot_be_sent(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);

        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Hệ thống chưa gửi được email cho khách');
    }

    public function test_appointment_confirmation_and_reminder_notifications_are_sent_to_the_customer(): void
    {
        Notification::fake();

        $appointment = $this->makeAppointment();

        $appointment->customer->notify(new AppointmentConfirmationNotification($appointment));
        $appointment->customer->notify(new AppointmentReminderNotification($appointment));

        Notification::assertSentTo($appointment->customer, AppointmentConfirmationNotification::class);
        Notification::assertSentTo($appointment->customer, AppointmentReminderNotification::class);
    }

    public function test_appointment_mails_render_the_booking_details(): void
    {
        $appointment = $this->makeAppointment();

        $confirmation = new AppointmentConfirmationMail($appointment);
        $reminder = new AppointmentReminderMail($appointment);

        $this->assertStringContainsString($appointment->appointment_code, $confirmation->render());
        $this->assertStringContainsString($appointment->appointment_code, $reminder->render());
    }

    private function makeAppointment(): Appointment
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create();
        $specialist->services()->attach($service);

        $start = now()->addDay()->setTime(10, 0);

        return Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
        ]);
    }
}
