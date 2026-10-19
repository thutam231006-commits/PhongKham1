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
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id]);
        $service = Service::factory()->create(['price' => 650000]);
        $appointment = Appointment::factory()->create(['appointment_code' => 'MGTEST001', 'customer_id' => $customer->id, 'created_by' => $customer->id, 'specialist_profile_id' => $specialist->id, 'service_id' => $service->id, 'status' => 'PENDING', 'payment_status' => 'UNPAID', 'price' => 650000]);
        $payload = ['id' => 'TX-001', 'transferAmount' => 650000, 'content' => 'DATLICHMGTEST001', 'transferType' => 'in'];

        $this->withHeader('Apikey', 'test-secret')->postJson(route('webhooks.sepay'), $payload)->assertOk()->assertJsonPath('data.status', 'MATCHED');
        $this->withHeader('Apikey', 'test-secret')->postJson(route('webhooks.sepay'), $payload)->assertOk();

        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED', 'payment_status' => 'PAID']);
    }

    public function test_invalid_webhook_secret_is_rejected(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);

        $this->withHeader('Apikey', 'wrong')->postJson(route('webhooks.sepay'), [])->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHORIZED_WEBHOOK');
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
