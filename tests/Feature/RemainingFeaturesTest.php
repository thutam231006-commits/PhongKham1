<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\RescheduleRequest;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemainingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_customer_cancellation_creates_a_full_refund_request(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment(['customer_id' => $customer->id, 'status' => 'CONFIRMED', 'payment_status' => 'PAID', 'start_at' => now()->addDays(3), 'end_at' => now()->addDays(3)->addHour()]);

        $this->actingAs($customer)->post(route('customer.appointments.cancel', $appointment), ['reason' => 'Không thể tham dự.', 'bank_name' => 'Ngân hàng thử nghiệm', 'bank_account' => '0123456789', 'account_holder' => 'NGUYEN VAN A'])->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CANCELLED_BY_CUSTOMER', 'payment_status' => 'REFUND_PENDING']);
        $this->assertDatabaseHas('refund_requests', ['appointment_id' => $appointment->id, 'percentage' => 100]);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'to_status' => 'CANCELLED_BY_CUSTOMER']);
    }

    public function test_staff_can_approve_a_reschedule_request(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->appointment(['customer_id' => $customer->id, 'status' => 'RESCHEDULE_REQUESTED', 'start_at' => now()->addDays(4), 'end_at' => now()->addDays(4)->addHour()]);
        $request = RescheduleRequest::query()->create(['appointment_id' => $appointment->id, 'requested_start_at' => now()->addDays(5)->setTime(10, 0), 'requested_end_at' => now()->addDays(5)->setTime(11, 0), 'requested_by' => $customer->id]);

        $this->actingAs($staff)->post(route('staff.reschedule-requests.resolve', $request), ['decision' => 'approve'])->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'RESCHEDULED', 'slot_lock_key' => null]);
        $this->assertDatabaseHas('appointments', ['rescheduled_from_id' => $appointment->id, 'status' => 'CONFIRMED']);
        $this->assertDatabaseHas('reschedule_requests', ['id' => $request->id, 'status' => 'APPROVED']);
    }

    public function test_approved_reschedule_keeps_the_online_consultation_type_and_gets_its_own_meeting_room(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->appointment([
            'customer_id' => $customer->id,
            'status' => 'RESCHEDULE_REQUESTED',
            'consultation_type' => 'ONLINE',
            'start_at' => now()->addDays(4),
            'end_at' => now()->addDays(4)->addHour(),
        ]);
        $request = RescheduleRequest::query()->create(['appointment_id' => $appointment->id, 'requested_start_at' => now()->addDays(5)->setTime(10, 0), 'requested_end_at' => now()->addDays(5)->setTime(11, 0), 'requested_by' => $customer->id]);

        $this->actingAs($staff)->post(route('staff.reschedule-requests.resolve', $request), ['decision' => 'approve'])->assertSessionHas('success');

        $newAppointment = Appointment::query()->where('rescheduled_from_id', $appointment->id)->sole();

        $this->assertSame('ONLINE', $newAppointment->consultation_type);
        $this->assertNull($newAppointment->consultation_room_id);
        $this->assertNotNull($newAppointment->meeting_url);
        $this->assertStringContainsString(strtolower($newAppointment->appointment_code), $newAppointment->meeting_url);
    }

    public function test_approved_reschedule_of_an_offline_appointment_assigns_a_consultation_room(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->appointment([
            'customer_id' => $customer->id,
            'status' => 'RESCHEDULE_REQUESTED',
            'consultation_type' => 'OFFLINE',
            'start_at' => now()->addDays(4),
            'end_at' => now()->addDays(4)->addHour(),
        ]);
        $request = RescheduleRequest::query()->create(['appointment_id' => $appointment->id, 'requested_start_at' => now()->addDays(5)->setTime(10, 0), 'requested_end_at' => now()->addDays(5)->setTime(11, 0), 'requested_by' => $customer->id]);

        $this->actingAs($staff)->post(route('staff.reschedule-requests.resolve', $request), ['decision' => 'approve'])->assertSessionHas('success');

        $newAppointment = Appointment::query()->where('rescheduled_from_id', $appointment->id)->sole();

        $this->assertSame('OFFLINE', $newAppointment->consultation_type);
        $this->assertNotNull($newAppointment->consultation_room_id);
        $this->assertNull($newAppointment->meeting_url);
    }

    public function test_pending_appointment_reschedule_returns_a_friendly_message(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment(['customer_id' => $customer->id, 'status' => 'PENDING']);

        $this->actingAs($customer)->post(route('customer.appointments.reschedule', $appointment), ['new_date' => now()->addDays(5)->toDateString(), 'new_time' => '10:00 - 11:00'])->assertRedirect(route('customer.appointments.detail', $appointment))->assertSessionHas('error');

        $this->assertDatabaseMissing('reschedule_requests', ['appointment_id' => $appointment->id]);
    }

    public function test_admin_category_page_is_real_and_can_store_a_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.categories'))->assertOk()->assertSee('Danh mục dịch vụ');
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Nhóm kiểm thử', 'description' => 'Mô tả', 'display_order' => 1, 'is_active' => 1])->assertSessionHas('success');

        $this->assertDatabaseHas('service_categories', ['name' => 'Nhóm kiểm thử']);
    }

    public function test_database_seeder_populates_the_new_operational_tables(): void
    {
        $this->seed();

        $this->assertDatabaseHas('system_settings', ['key' => 'min_booking_lead_hours']);
        $this->assertDatabaseHas('roles', ['code' => 'admin']);
        $this->assertDatabaseHas('permissions', ['code' => 'appointments.manage']);
        $this->assertDatabaseHas('customer_profiles', ['privacy_policy_version' => '2026-01']);
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_new_role_pages_render_without_server_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['admin.categories', 'admin.schedule', 'admin.appointments', 'admin.reviews', 'admin.notifications', 'admin.settings', 'admin.roles', 'admin.payments', 'admin.reports', 'admin.specialists'] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }

        $staff = User::factory()->create(['role' => 'staff']);
        foreach (['staff.appointments', 'staff.customers', 'staff.appointments.book', 'staff.payments'] as $routeName) {
            $this->actingAs($staff)->get(route($routeName))->assertOk();
        }

        $specialistUser = User::factory()->create(['role' => 'specialist']);
        SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        foreach (['specialist.dashboard', 'specialist.schedule', 'specialist.appointments', 'specialist.profile'] as $routeName) {
            $this->actingAs($specialistUser)->get(route($routeName))->assertOk();
        }

        $customer = User::factory()->create(['role' => 'customer']);
        foreach (['customer.dashboard', 'customer.appointments', 'customer.profile', 'customer.review', 'notifications.index'] as $routeName) {
            $this->actingAs($customer)->get(route($routeName))->assertOk();
        }
    }

    public function test_public_specialist_pages_use_database_profiles(): void
    {
        $specialistUser = User::factory()->create(['role' => 'specialist', 'name' => 'Chuyên viên dữ liệu thật']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create(['status' => 'ACTIVE']);
        $profile->services()->attach($service);

        $this->get(route('specialists.index'))->assertOk()->assertSee('Chuyên viên dữ liệu thật');
        $this->get(route('specialists.show', $profile))->assertOk()->assertSee('Chuyên viên dữ liệu thật');
    }

    /** @param array<string, mixed> $overrides */
    private function appointment(array $overrides = []): Appointment
    {
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $specialist = SpecialistProfile::factory()->create(['user_id' => $specialistUser->id, 'status' => 'ACTIVE']);
        $service = Service::factory()->create();
        $specialist->services()->attach($service);
        $customer = isset($overrides['customer_id']) ? User::query()->findOrFail($overrides['customer_id']) : User::factory()->create(['role' => 'customer']);
        $start = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        return Appointment::factory()->create(array_merge(['customer_id' => $customer->id, 'created_by' => $customer->id, 'specialist_profile_id' => $specialist->id, 'service_id' => $service->id, 'start_at' => $start, 'end_at' => $start->addHour(), 'slot_lock_key' => $specialist->id.'|'.$start->format('Y-m-d H:i:s')], $overrides));
    }
}
