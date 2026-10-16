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
