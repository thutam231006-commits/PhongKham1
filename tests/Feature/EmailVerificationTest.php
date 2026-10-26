<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\EmailVerificationNotification;
use App\Services\AccountActivationService;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * SCR-PUB-10 — các cách xác thực email.
 *
 * Ngoài phương án bấm link trong email, hệ thống hỗ trợ:
 *  - mã xác thực 6 số (OTP) gửi kèm email;
 *  - lễ tân gửi lại mã kích hoạt cho khách;
 *  - lễ tân xác nhận email thủ công tại quầy khi khách không nhận được email.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_both_a_verification_link_and_a_code(): void
    {
        Notification::fake();

        SystemSetting::query()->create([
            'key' => 'email_verification_required',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'authentication',
            'label' => 'Bắt buộc xác thực email',
        ]);

        $this->post(route('register.post'), [
            'name' => 'Nguyễn Minh Anh',
            'email' => 'minhanh@example.com',
            'password' => 'MatKhau@2026',
            'password_confirmation' => 'MatKhau@2026',
            'dob' => '1995-04-12',
            'phone' => '0901234567',
            'agree_terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'minhanh@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, EmailVerificationNotification::class);
    }

    public function test_user_can_verify_the_email_with_the_six_digit_code(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email' => 'minhanh@example.com',
            'email_verified_at' => null,
        ]);

        $service = app(EmailVerificationService::class);
        $code = $service->issueCode($user, EmailVerificationService::PURPOSE_VERIFICATION);

        $this->post(route('verification.code.store'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('CODE', $user->email_verified_via);
    }

    public function test_a_wrong_code_does_not_verify_the_email(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email' => 'minhanh@example.com',
            'email_verified_at' => null,
        ]);

        app(EmailVerificationService::class)->issueCode($user, EmailVerificationService::PURPOSE_VERIFICATION);

        $this->post(route('verification.code.store'), [
            'email' => $user->email,
            'code' => '000000',
        ])->assertSessionHas('error');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email' => 'minhanh@example.com',
            'email_verified_at' => null,
        ]);

        $service = app(EmailVerificationService::class);
        $code = $service->issueCode($user, EmailVerificationService::PURPOSE_VERIFICATION);

        $this->assertTrue($service->verifyCode($user, EmailVerificationService::PURPOSE_VERIFICATION, $code));
        $this->assertFalse($service->verifyCode($user, EmailVerificationService::PURPOSE_VERIFICATION, $code));
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'email' => 'minhanh@example.com']);

        $service = app(EmailVerificationService::class);
        $code = $service->issueCode($user, EmailVerificationService::PURPOSE_VERIFICATION);

        $this->travel(config('auth.verification.otp_expire', 15) + 1)->minutes();

        $this->assertFalse($service->verifyCode($user, EmailVerificationService::PURPOSE_VERIFICATION, $code));
    }

    public function test_customer_can_activate_the_account_with_the_code(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'minhanh@example.com',
            'email_verified_at' => null,
        ]);

        $service = app(EmailVerificationService::class);
        $code = $service->issueCode($customer, EmailVerificationService::PURPOSE_ACTIVATION);

        $this->post(route('account.activation.code.store'), [
            'email' => $customer->email,
            'code' => $code,
            'password' => 'MatKhau@2026',
            'password_confirmation' => 'MatKhau@2026',
        ])->assertRedirect(route('login'));

        $customer->refresh();
        $this->assertSame('ACTIVE', $customer->status);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertSame('CODE', $customer->email_verified_via);
    }

    public function test_staff_can_send_the_activation_code_to_the_customer(): void
    {
        Notification::fake();

        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'minhanh@example.com',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.customers.activation-code', $customer))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($customer, EmailVerificationCodeNotification::class);
    }

    public function test_staff_can_verify_the_email_manually_at_the_reception(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'minhanh@example.com',
            'email_verified_at' => null,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.customers.verify-manual', $customer), [
                'password' => 'MatKhau@2026',
                'password_confirmation' => 'MatKhau@2026',
                'reason' => 'Khách không nhận được email, xác nhận tại quầy.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $customer->refresh();
        $this->assertSame('ACTIVE', $customer->status);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertSame('STAFF', $customer->email_verified_via);
    }

    public function test_manual_verification_requires_a_reason(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'minhanh@example.com',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.customers.verify-manual', $customer), [
                'password' => 'MatKhau@2026',
                'password_confirmation' => 'MatKhau@2026',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertSame('PENDING_ACTIVATION', $customer->fresh()->status);
    }

    public function test_activation_email_carries_both_the_link_and_the_code(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'PENDING_ACTIVATION',
            'email' => 'minhanh@example.com',
        ]);

        app(AccountActivationService::class)->createAndSend($customer);

        $customer->refresh();
        $this->assertSame(AccountActivationService::STATUS_SENT, $customer->activation_email_status);

        $code = app(EmailVerificationService::class);
        $this->assertTrue($code->hasActiveCode($customer, EmailVerificationService::PURPOSE_ACTIVATION));
    }
}
