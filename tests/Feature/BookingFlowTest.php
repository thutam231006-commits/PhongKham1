<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ConsultationRoom;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Models\WorkingSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_book_a_cash_appointment(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(3);

        $response = $this->actingAs($customer)->postJson(route('appointments.store'), [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => '10:00 - 11:00',
            'customer_note' => 'Tôi muốn trao đổi về giấc ngủ.',
            'payment_method' => 'CASH',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'specialist_profile_id' => $specialist->id,
            'status' => 'PENDING',
            'payment_method' => 'CASH',
        ]);
        $this->assertDatabaseHas('appointment_status_histories', [
            'to_status' => 'PENDING',
            'action' => 'create',
        ]);
    }

    public function test_the_same_specialist_slot_cannot_be_booked_twice(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $secondCustomer = User::factory()->create(['role' => 'CUSTOMER']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(3);
        $payload = [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => '10:00 - 11:00',
            'payment_method' => 'CASH',
        ];

        $this->actingAs($customer)->postJson(route('appointments.store'), $payload)->assertCreated();

        $this->actingAs($secondCustomer)
            ->postJson(route('appointments.store'), $payload)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'APPOINTMENT_SLOT_UNAVAILABLE');
    }

    public function test_guests_cannot_create_an_appointment(): void
    {
        $this->postJson(route('appointments.store'), [])->assertUnauthorized();
    }

    public function test_booking_flow_moves_through_five_routes_and_preserves_session_state(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(4)->setTime(10, 0);
        WorkingSchedule::query()->where('specialist_profile_id', $specialist->id)->update(['day_of_week' => $date->isoWeekday(), 'start_time' => '08:00', 'end_time' => '17:00']);

        $this->get(route('appointments.book'))->assertOk()->assertSee('Chọn gói dịch vụ tư vấn');
        $this->post(route('booking.step.service'), ['service_id' => $service->id])->assertRedirect(route('booking.select-specialist', ['service_id' => $service->id]));
        $this->withSession(['booking' => ['service_id' => $service->id]])->get(route('booking.select-specialist', ['service_id' => $service->id]))->assertOk()->assertSee($specialist->user->name);
        $this->withSession(['booking' => ['service_id' => $service->id]])->post(route('booking.step.specialist'), ['specialist_id' => $specialist->id])->assertRedirect(route('booking.select-slot'));
        $this->withSession(['booking' => ['service_id' => $service->id, 'specialist_id' => $specialist->id]])->get(route('booking.select-slot', ['date' => $date->toDateString()]))->assertOk()->assertSee('10:20');
        $this->withSession(['booking' => ['service_id' => $service->id, 'specialist_id' => $specialist->id]])->post(route('booking.step.slot'), ['date' => $date->toDateString(), 'time' => '10:20'])->assertRedirect(route('booking.note'));
        $this->withSession(['booking' => ['service_id' => $service->id, 'specialist_id' => $specialist->id, 'date' => $date->toDateString(), 'time' => '10:20', 'start_at' => $date->format('Y-m-d').' 10:20:00']])->post(route('booking.step.note'), ['customer_note' => 'Ghi chú bước tư vấn.'])->assertRedirect(route('booking.confirm'));
    }

    public function test_customer_can_confirm_web_booking_with_a_database_safe_idempotency_key(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        $response = $this->actingAs($customer)
            ->withSession(['booking' => [
                'service_id' => $service->id,
                'specialist_id' => $specialist->id,
                'date' => $date->toDateString(),
                'time' => '10:00',
                'start_at' => $date->format('Y-m-d H:i:s'),
            ]])
            ->post(route('booking.confirm.store'), ['payment_method' => 'CASH']);

        $appointment = Appointment::query()->where('customer_id', $customer->id)->sole();

        $response->assertRedirect(route('customer.appointments.detail', $appointment));
        $this->assertNotNull($appointment->idempotency_key);
        $this->assertLessThanOrEqual(80, strlen($appointment->idempotency_key));
    }

    public function test_staff_confirmation_assigns_a_room_and_notifies_the_specialist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        $this->actingAs($customer)
            ->withSession(['booking' => [
                'service_id' => $service->id,
                'specialist_id' => $specialist->id,
                'date' => $date->toDateString(),
                'time' => '10:00',
                'start_at' => $date->format('Y-m-d H:i:s'),
            ]])
            ->post(route('booking.confirm.store'), ['payment_method' => 'CASH', 'consultation_type' => 'OFFLINE'])
            ->assertRedirect();

        $appointment = Appointment::query()->where('customer_id', $customer->id)->sole();

        $this->actingAs($staff)
            ->post(route('staff.appointments.confirm', $appointment))
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('CONFIRMED', $appointment->status);
        $this->assertNotNull($appointment->consultation_room_id);
        $this->assertInstanceOf(ConsultationRoom::class, $appointment->consultationRoom);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $specialist->user_id]);
    }

    public function test_staff_confirmation_attaches_the_configured_google_meet_link(): void
    {
        config(['services.google_meet.url' => 'https://meet.google.com/test-room']);
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        $this->actingAs($customer)
            ->withSession(['booking' => [
                'service_id' => $service->id,
                'specialist_id' => $specialist->id,
                'date' => $date->toDateString(),
                'time' => '10:00',
                'start_at' => $date->format('Y-m-d H:i:s'),
            ]])
            ->post(route('booking.confirm.store'), ['payment_method' => 'CASH', 'consultation_type' => 'ONLINE'])
            ->assertRedirect();

        $appointment = Appointment::query()->where('customer_id', $customer->id)->sole();
        $this->actingAs($staff)->post(route('staff.appointments.confirm', $appointment));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'consultation_type' => 'ONLINE',
            'meeting_url' => 'https://meet.google.com/test-room',
            'consultation_room_id' => null,
        ]);
    }

    public function test_staff_can_reassign_a_pending_appointment_to_an_available_specialist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        [$service, $currentSpecialist] = $this->bookingSetup();
        $replacement = SpecialistProfile::factory()->create();
        $replacement->services()->attach($service);
        $start = CarbonImmutable::now()->addDays(3)->setTime(10, 0);
        WorkingSchedule::factory()->create([
            'specialist_profile_id' => $replacement->id,
            'day_of_week' => $start->isoWeekday(),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $currentSpecialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'status' => 'PENDING',
            'consultation_type' => 'OFFLINE',
            'slot_lock_key' => $currentSpecialist->id.'|'.$start->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.appointments.reassign', $appointment), ['specialist_id' => $replacement->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'specialist_profile_id' => $replacement->id, 'status' => 'PENDING']);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'action' => 'reassign_specialist']);
    }

    public function test_specialists_api_filters_by_selected_service(): void
    {
        [$service, $specialist] = $this->bookingSetup();
        $otherService = Service::factory()->create();

        $this->getJson(route('api.specialists.index', ['service_id' => $service->id]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $specialist->id]);
        $this->getJson(route('api.specialists.index', ['service_id' => $otherService->id]))
            ->assertOk()
            ->assertJsonMissing(['id' => $specialist->id]);
    }

    public function test_booking_limit_error_returns_to_confirmation_with_a_friendly_message(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(4)->setTime(10, 0);
        WorkingSchedule::query()->where('specialist_profile_id', $specialist->id)->update([
            'day_of_week' => $date->isoWeekday(),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        Appointment::factory()->count(3)->sequence(
            ['start_at' => now()->addDay()->setTime(8, 0), 'end_at' => now()->addDay()->setTime(9, 0)],
            ['start_at' => now()->addDays(2)->setTime(8, 0), 'end_at' => now()->addDays(2)->setTime(9, 0)],
            ['start_at' => now()->addDays(3)->setTime(8, 0), 'end_at' => now()->addDays(3)->setTime(9, 0)],
        )->create([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $specialist->id,
            'service_id' => $service->id,
            'status' => 'CONFIRMED',
            'slot_lock_key' => null,
        ]);

        $booking = [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'date' => $date->toDateString(),
            'time' => '10:00',
            'start_at' => $date->format('Y-m-d H:i:s'),
        ];

        $this->actingAs($customer)
            ->withSession(['booking' => $booking])
            ->post(route('booking.confirm.store'), ['payment_method' => 'BANK_QR'])
            ->assertRedirect(route('booking.confirm'))
            ->assertSessionHas('error', 'Bạn đã đạt giới hạn 3 lịch hẹn đang hoạt động.');

        $this->assertDatabaseCount('appointments', 3);
    }

    public function test_bank_qr_booking_redirects_to_sepay_payment_page(): void
    {
        config([
            'services.sepay.bank_account' => '104603996207',
            'services.sepay.bank_code' => 'VietinBank',
            'services.sepay.account_name' => 'KHUAT THU TAM',
        ]);
        $customer = User::factory()->create(['role' => 'customer']);
        [$service, $specialist] = $this->bookingSetup();
        $date = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        $response = $this->actingAs($customer)
            ->withSession(['booking' => [
                'service_id' => $service->id,
                'specialist_id' => $specialist->id,
                'date' => $date->toDateString(),
                'time' => '10:00',
                'start_at' => $date->format('Y-m-d H:i:s'),
                'customer_note' => 'Thanh toán qua SePay.',
            ]])
            ->post(route('booking.confirm.store'), ['payment_method' => 'BANK_QR']);

        $appointment = Appointment::query()->where('customer_id', $customer->id)->sole();
        $response->assertRedirect(route('booking.payment.show', $appointment));
        $this->actingAs($customer)
            ->get(route('booking.payment.show', $appointment))
            ->assertOk()
            ->assertSee('SEVQR DATLICH'.$appointment->appointment_code)
            ->assertSee('104603996207')
            ->assertSee('KHUAT THU TAM');
    }

    /** @return array{0: Service, 1: SpecialistProfile} */
    private function bookingSetup(): array
    {
        $service = Service::factory()->create();
        $specialist = SpecialistProfile::factory()->create();
        $specialist->services()->attach($service);
        WorkingSchedule::factory()->create([
            'specialist_profile_id' => $specialist->id,
            'day_of_week' => CarbonImmutable::now()->addDays(3)->isoWeekday(),
        ]);

        return [$service, $specialist];
    }
}
