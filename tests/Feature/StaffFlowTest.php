<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Notifications\GenericDatabaseNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_shows_unread_notifications_and_operational_cards(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->notify(new GenericDatabaseNotification([
            'title' => 'Khách hàng mới đăng ký',
            'message' => 'Khách mới đăng ký: Nguyễn Minh Anh',
            'type' => 'customer.registered',
        ]));

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Thông báo mới')
            ->assertSee('Khách hàng mới đăng ký')
            ->assertSee('Chờ xác nhận')
            ->assertSee('Khách mới 7 ngày');
    }

    public function test_staff_appointments_and_coordination_pages_are_separate(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('staff.appointments'))
            ->assertOk()
            ->assertSee('Tất cả lịch hẹn')
            ->assertSee('Mở điều phối lịch');

        $this->actingAs($staff)
            ->get(route('staff.appointments.pending'))
            ->assertOk()
            ->assertSee('Điều phối lịch tư vấn')
            ->assertSee('Lịch chờ xác nhận')
            ->assertDontSee('Tất cả lịch hẹn');
    }

    public function test_customer_registration_notifies_staff_accounts(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->post(route('register.post'), [
            'name' => 'Khách Đăng Ký Mới',
            'email' => 'new-customer@example.test',
            'password' => 'Customer@123',
            'password_confirmation' => 'Customer@123',
            'dob' => '1995-01-01',
            'phone' => '0901234567',
            'agree_terms' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $staff->id,
        ]);
    }

    public function test_staff_can_open_customer_profile_with_booking_history(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create(['role' => 'customer', 'email' => 'profile@example.test']);
        $customer->customerProfile()->create(['customer_code' => 'KH-00077']);
        $appointment = $this->makeAppointment(['customer_id' => $customer->id]);

        $this->actingAs($staff)
            ->get(route('staff.customers'))
            ->assertSee('Xem hồ sơ khách hàng');

        $this->actingAs($staff)
            ->get(route('staff.customers.profile', $customer))
            ->assertOk()
            ->assertSee('KH-00077')
            ->assertSee($appointment->appointment_code)
            ->assertSee($appointment->service->name)
            ->assertSee('Lịch sử gói và lịch đã đặt')
            ->assertDontSee('Ghi chú tư vấn');
    }

    public function test_staff_created_customer_can_activate_account_from_email_token(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->post(route('staff.customers.store'), [
            'name' => 'Khách Kích Hoạt',
            'email' => 'activation@example.test',
            'phone' => '0901234567',
            'date_of_birth' => '1995-01-01',
        ])->assertSessionHas('success');

        $customer = User::query()->where('email', 'activation@example.test')->firstOrFail();
        $this->assertSame('PENDING_ACTIVATION', $customer->status);
        $token = 'activation-token-for-test';
        DB::table('account_activation_tokens')->insert(['user_id' => $customer->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);

        $this->post(route('account.activation.store'), ['token' => $token, 'password' => 'Customer@123', 'password_confirmation' => 'Customer@123'])->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'activation@example.test', 'status' => 'ACTIVE']);
        $this->assertNotNull($customer->refresh()->email_verified_at);
        $this->assertNotNull(DB::table('account_activation_tokens')->where('user_id', $customer->id)->value('used_at'));
    }

    public function test_staff_can_check_in_a_confirmed_appointment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'CONFIRMED', 'payment_method' => 'CASH']);

        $this->actingAs($staff)->post(route('staff.checkin.store', $appointment->id))->assertSessionHas('success');

        $appointment->refresh();
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertSame($staff->id, $appointment->checked_in_by);
    }

    public function test_staff_can_find_todays_appointment_by_customer_code_email_or_phone(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@example.test',
            'phone' => '0901234567',
        ]);
        $customer->customerProfile()->create(['customer_code' => 'KH-00042']);
        $appointment = $this->makeAppointment([
            'customer_id' => $customer->id,
            'start_at' => now()->setTime(10, 0),
            'end_at' => now()->setTime(11, 0),
        ]);

        foreach (['KH-00042', 'customer@example.test', '0901234567'] as $term) {
            $this->actingAs($staff)
                ->get(route('staff.checkin', ['q' => $term]))
                ->assertOk()
                ->assertSee($appointment->appointment_code);
        }
    }

    public function test_staff_checkin_search_rejects_an_overlong_query(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('staff.checkin', ['q' => str_repeat('a', 101)]))
            ->assertSessionHasErrors('q');
    }

    public function test_staff_can_collect_cash_only_after_completion(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'COMPLETED', 'payment_method' => 'CASH', 'payment_status' => 'UNPAID']);

        $this->actingAs($staff)->post(route('staff.payments.collect', $appointment->id), ['payment_reference' => 'CASH-001'])->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('PAID', $appointment->payment_status);
        $this->assertSame($staff->id, $appointment->collected_by);
        $this->assertSame('CASH-001', $appointment->payment_reference);
    }

    public function test_staff_cannot_collect_an_uncompleted_appointment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->makeAppointment(['status' => 'CONFIRMED', 'payment_method' => 'CASH']);

        $this->actingAs($staff)->post(route('staff.payments.collect', $appointment->id))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'payment_status' => 'UNPAID']);
    }

    /** @param array<string, mixed> $overrides */
    private function makeAppointment(array $overrides = []): Appointment
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create();
        $specialist->services()->attach($service);
        $start = CarbonImmutable::now()->addDay()->setTime(10, 0);

        return Appointment::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'slot_lock_key' => $specialist->id.'|'.$start->format('Y-m-d H:i:s'),
        ], $overrides));
    }
}
