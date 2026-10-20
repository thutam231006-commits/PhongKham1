<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sepay_webhook_matches_payment_and_is_idempotent(): void
    {
        config([
            'services.sepay.webhook_api_key' => 'test-secret',
            'services.sepay.bank_account' => '104603996207',
        ]);
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id]);
        $service = Service::factory()->create(['price' => 650000]);
        $appointment = Appointment::factory()->create(['appointment_code' => 'MGTEST001', 'customer_id' => $customer->id, 'created_by' => $customer->id, 'specialist_profile_id' => $specialist->id, 'service_id' => $service->id, 'status' => 'PENDING', 'payment_status' => 'UNPAID', 'price' => 650000]);
        $payload = ['id' => 'TX-001', 'accountNumber' => '104603996207', 'transferAmount' => 650000, 'content' => 'SEVQR DATLICHMGTEST001', 'transferType' => 'in'];

        $this->withHeader('Authorization', 'Apikey test-secret')->postJson(route('webhooks.sepay'), $payload)->assertOk()->assertJsonPath('success', true);
        $this->withHeader('Authorization', 'Apikey test-secret')->postJson(route('webhooks.sepay'), $payload)->assertOk();

        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED', 'payment_status' => 'PAID']);
    }

    public function test_invalid_webhook_secret_is_rejected(): void
    {
        config(['services.sepay.webhook_api_key' => 'test-secret']);

        $this->withHeader('Apikey', 'wrong')->postJson(route('webhooks.sepay'), [])->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHORIZED_WEBHOOK');
    }

    public function test_sepay_webhook_rejects_payment_to_another_bank_account(): void
    {
        config([
            'services.sepay.webhook_api_key' => 'test-secret',
            'services.sepay.bank_account' => '104603996207',
        ]);
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id]);
        $service = Service::factory()->create(['price' => 650000]);
        $appointment = Appointment::factory()->create(['appointment_code' => 'MGTEST002', 'customer_id' => $customer->id, 'created_by' => $customer->id, 'specialist_profile_id' => $specialist->id, 'service_id' => $service->id, 'status' => 'PENDING', 'payment_status' => 'UNPAID', 'price' => 650000, 'payment_expires_at' => now()->addMinutes(10)]);

        $payload = ['id' => 'TX-002', 'accountNumber' => '000000000000', 'transferAmount' => 650000, 'content' => 'SEVQR DATLICHMGTEST002', 'transferType' => 'in'];

        $this->withHeader('Authorization', 'Apikey test-secret')->postJson(route('webhooks.sepay'), $payload)->assertOk();

        $this->assertDatabaseHas('payment_transactions', ['external_id' => 'TX-002', 'status' => 'ACCOUNT_MISMATCH']);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'PENDING', 'payment_status' => 'UNPAID']);
    }

    public function test_customer_can_poll_their_sepay_payment_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'payment_method' => 'BANK_QR',
            'payment_status' => 'UNPAID',
            'status' => 'PENDING',
            'payment_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($customer)
            ->getJson(route('booking.payment.status', $appointment))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'UNPAID')
            ->assertJsonPath('data.appointment_status', 'PENDING');

        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($otherCustomer)->getJson(route('booking.payment.status', $appointment))->assertForbidden();
    }

    public function test_sepay_webhook_accepts_a_valid_hmac_signature_and_rejects_replay(): void
    {
        config([
            'services.sepay.webhook_secret' => 'hmac-secret',
            'services.sepay.webhook_api_key' => null,
            'services.sepay.bank_account' => '104603996207',
        ]);
        $payload = [
            'id' => 'TX-HMAC-001',
            'accountNumber' => '104603996207',
            'transferAmount' => 100000,
            'content' => 'SEVQR DATLICHUNKNOWN',
            'transferType' => 'in',
        ];
        $timestamp = time();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.json_encode($payload), 'hmac-secret');

        $this->withHeaders([
            'X-SePay-Signature' => $signature,
            'X-SePay-Timestamp' => (string) $timestamp,
        ])->postJson(route('webhooks.sepay'), $payload)->assertOk()->assertExactJson(['success' => true]);

        $oldTimestamp = $timestamp - 301;
        $oldSignature = 'sha256='.hash_hmac('sha256', $oldTimestamp.'.'.json_encode($payload), 'hmac-secret');
        $this->withHeaders([
            'X-SePay-Signature' => $oldSignature,
            'X-SePay-Timestamp' => (string) $oldTimestamp,
        ])->postJson(route('webhooks.sepay'), $payload)->assertUnauthorized();
    }

    public function test_expired_sepay_hold_is_cancelled_and_releases_the_slot(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'payment_method' => 'BANK_QR',
            'payment_status' => 'UNPAID',
            'status' => 'PENDING',
            'payment_expires_at' => now()->subMinute(),
            'slot_lock_key' => 'expired-slot-lock',
        ]);

        $this->actingAs($customer)
            ->getJson(route('booking.payment.status', $appointment))
            ->assertOk()
            ->assertJsonPath('data.appointment_status', 'CANCELLED_BY_CLINIC');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'CANCELLED_BY_CLINIC',
            'slot_lock_key' => null,
        ]);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $appointment->id,
            'action' => 'payment_expired',
        ]);
    }

    public function test_account_is_locked_after_five_failed_logins(): void
    {
        $user = User::factory()->create(['email' => 'locked@example.test', 'password' => Hash::make('Correct@123'), 'status' => 'ACTIVE']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.post'), ['email' => $user->email, 'password' => 'Wrong@123']);
        }

        $this->assertTrue($user->refresh()->locked_until->isFuture());
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'Correct@123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
