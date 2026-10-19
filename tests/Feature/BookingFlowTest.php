<?php

namespace Tests\Feature;

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
            'status' => 'CONFIRMED',
            'payment_method' => 'CASH',
        ]);
        $this->assertDatabaseHas('appointment_status_histories', [
            'to_status' => 'CONFIRMED',
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
