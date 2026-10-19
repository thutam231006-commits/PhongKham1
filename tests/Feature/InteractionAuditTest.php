<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Permission;
use App\Models\RefundRequest;
use App\Models\RescheduleRequest;
use App\Models\Review;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkingSchedule;
use App\Notifications\GenericDatabaseNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InteractionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_and_password_buttons_persist_changes(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'password' => Hash::make('Current@123')]);

        $this->actingAs($customer)->post(route('customer.profile.update'), [
            'name' => 'Khách Hàng Đã Sửa',
            'email' => 'updated@example.test',
            'phone' => '0909123456',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'name' => 'Khách Hàng Đã Sửa', 'email' => 'updated@example.test']);

        $this->actingAs($customer)->post(route('customer.password.change'), [
            'current_password' => 'Current@123',
            'password' => 'Changed@456',
            'password_confirmation' => 'Changed@456',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('Changed@456', $customer->refresh()->password));
    }

    public function test_customer_review_and_notification_buttons_work(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $appointment = $this->appointment(['customer_id' => $customer->id, 'status' => 'COMPLETED', 'completed_at' => now()->subDay()]);

        $this->actingAs($customer)->post(route('customer.review.submit'), [
            'appointment_id' => $appointment->id,
            'rating' => 5,
            'comment' => 'Buổi tư vấn hữu ích.',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['appointment_id' => $appointment->id, 'rating' => 5]);

        $customer->notify(new GenericDatabaseNotification(['title' => 'Thông báo thử', 'message' => 'Nội dung', 'type' => 'test']));
        $notification = $customer->notifications()->firstOrFail();
        $this->actingAs($customer)->get(route('notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_common_notification_and_change_password_buttons_work(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'password' => Hash::make('Current@123')]);
        $customer->notify(new GenericDatabaseNotification(['title' => 'Một', 'message' => 'A', 'type' => 'test']));
        $customer->notify(new GenericDatabaseNotification(['title' => 'Hai', 'message' => 'B', 'type' => 'test']));

        $this->actingAs($customer)->post(route('notifications.read-all'))->assertSessionHas('success');
        $this->assertSame(0, $customer->fresh()->unreadNotifications()->count());

        $this->actingAs($customer)->put(route('password.change.update'), [
            'current_password' => 'Current@123',
            'password' => 'Common@456',
            'password_confirmation' => 'Common@456',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('Common@456', $customer->refresh()->password));
    }

    public function test_specialist_schedule_leave_start_complete_and_no_show_buttons_work(): void
    {
        [$specialistUser, $profile] = $this->specialist();
        $this->actingAs($specialistUser)->post(route('specialist.schedule.store'), [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'effective_from' => now()->toDateString(),
            'is_active' => 1,
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('working_schedules', ['specialist_profile_id' => $profile->id, 'day_of_week' => 1]);
        $schedule = $profile->workingSchedules()->firstOrFail();
        $this->actingAs($specialistUser)->put(route('specialist.schedule.update', $schedule), [
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'is_active' => 0,
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('working_schedules', ['id' => $schedule->id, 'is_active' => 0]);

        $this->actingAs($specialistUser)->post(route('specialist.leaves.store'), [
            'type' => 'DAY_OFF',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'reason' => 'Nghỉ cá nhân',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('schedule_exceptions', ['specialist_profile_id' => $profile->id, 'type' => 'DAY_OFF']);

        $inProgress = $this->appointment(['specialist_profile_id' => $profile->id, 'status' => 'IN_PROGRESS', 'start_at' => now()->subMinutes(5), 'end_at' => now()->addMinutes(55)]);
        $this->actingAs($specialistUser)->post(route('specialist.appointments.complete', $inProgress))->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $inProgress->id, 'status' => 'COMPLETED']);

        $noShow = $this->appointment(['specialist_profile_id' => $profile->id, 'status' => 'CONFIRMED', 'start_at' => now()->subMinutes(20), 'end_at' => now()->addMinutes(40)]);
        $this->actingAs($specialistUser)->post(route('specialist.appointments.no-show', $noShow))->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $noShow->id, 'status' => 'NO_SHOW']);

        $startable = $this->appointment(['specialist_profile_id' => $profile->id, 'status' => 'CONFIRMED', 'start_at' => now()->subMinutes(5), 'end_at' => now()->addMinutes(55)]);
        $this->actingAs($specialistUser)->post(route('specialist.appointments.start', $startable))->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $startable->id, 'status' => 'IN_PROGRESS']);

        $this->actingAs($specialistUser)->put(route('specialist.profile.update'), [
            'title' => 'Chuyên viên đã cập nhật',
            'expertise' => 'Kiểm thử',
            'bio' => 'Thông tin hồ sơ kiểm thử.',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('specialist_profiles', ['id' => $profile->id, 'title' => 'Chuyên viên đã cập nhật']);
    }

    public function test_staff_operational_buttons_create_customer_confirm_cancel_and_collect_cash(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->post(route('staff.customers.store'), [
            'name' => 'Khách tại quầy',
            'email' => 'onsite@example.test',
            'phone' => '0909123456',
            'date_of_birth' => '1990-01-01',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'onsite@example.test', 'role' => 'customer']);
        $newCustomer = User::query()->where('email', 'onsite@example.test')->firstOrFail();
        $this->actingAs($staff)->put(route('staff.customers.update', $newCustomer), [
            'name' => 'Khách đã cập nhật',
            'email' => $newCustomer->email,
            'phone' => '0909000000',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $newCustomer->id, 'name' => 'Khách đã cập nhật']);

        $pending = $this->appointment(['status' => 'PENDING']);
        $this->actingAs($staff)->post(route('staff.appointments.confirm', $pending))->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $pending->id, 'status' => 'CONFIRMED']);

        $cash = $this->appointment(['status' => 'COMPLETED', 'payment_method' => 'CASH', 'payment_status' => 'UNPAID']);
        $this->actingAs($staff)->post(route('staff.payments.collect', $cash), ['payment_reference' => 'CASH-TEST'])->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $cash->id, 'payment_status' => 'PAID']);

        $confirmed = $this->appointment(['status' => 'CONFIRMED']);
        $this->actingAs($staff)->post(route('staff.appointments.cancel', $confirmed), ['reason' => 'Phòng khám cần điều chỉnh lịch.'])->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $confirmed->id, 'status' => 'CANCELLED_BY_CLINIC']);
    }

    public function test_staff_onsite_booking_button_creates_a_confirmed_appointment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create(['role' => 'customer']);
        [, $profile] = $this->specialist();
        $service = Service::factory()->create();
        $profile->services()->attach($service);
        $date = now()->addDays(4);
        WorkingSchedule::factory()->create(['specialist_profile_id' => $profile->id, 'day_of_week' => $date->isoWeekday(), 'start_time' => '08:00', 'end_time' => '17:00']);

        $this->actingAs($staff)->post(route('staff.appointments.store'), [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'specialist_id' => $profile->id,
            'date' => $date->toDateString(),
            'time' => '10:00',
            'payment_method' => 'CASH',
        ])->assertRedirect(route('staff.appointments'));
        $this->assertDatabaseHas('appointments', ['customer_id' => $customer->id, 'status' => 'CONFIRMED', 'source' => 'ONSITE']);
    }

    public function test_staff_can_reject_reschedule_and_confirm_refund(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $appointment = $this->appointment(['status' => 'RESCHEDULE_REQUESTED']);
        $request = RescheduleRequest::query()->create(['appointment_id' => $appointment->id, 'requested_start_at' => now()->addDays(5), 'requested_end_at' => now()->addDays(5)->addHour(), 'requested_by' => $appointment->customer_id]);
        $this->actingAs($staff)->post(route('staff.reschedule-requests.resolve', $request), ['decision' => 'reject', 'reason' => 'Khung giờ đã hết.'])->assertSessionHas('success');
        $this->assertDatabaseHas('reschedule_requests', ['id' => $request->id, 'status' => 'REJECTED']);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CONFIRMED']);

        $refundAppointment = $this->appointment(['status' => 'CANCELLED_BY_CUSTOMER', 'payment_status' => 'REFUND_PENDING', 'refund_status' => 'REFUND_PENDING', 'refund_amount' => 100000]);
        $refund = RefundRequest::query()->create(['appointment_id' => $refundAppointment->id, 'amount' => 100000, 'percentage' => 50, 'status' => 'PENDING']);
        $this->actingAs($staff)->post(route('staff.refunds.complete', $refund), ['reference' => 'REF-001'])->assertSessionHas('success');
        $this->assertDatabaseHas('refund_requests', ['id' => $refund->id, 'status' => 'REFUNDED']);
    }

    public function test_admin_buttons_manage_schedule_review_settings_roles_and_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $profile] = $this->specialist();
        $this->actingAs($admin)->post(route('admin.schedule.store'), [
            'specialist_profile_id' => $profile->id,
            'type' => 'DAY_OFF',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'reason' => 'Kiểm thử ngày nghỉ',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('schedule_exceptions', ['specialist_profile_id' => $profile->id, 'reason' => 'Kiểm thử ngày nghỉ']);

        $appointment = $this->appointment(['specialist_profile_id' => $profile->id, 'status' => 'COMPLETED', 'completed_at' => now()]);
        $review = Review::query()->create(['appointment_id' => $appointment->id, 'customer_id' => $appointment->customer_id, 'specialist_profile_id' => $profile->id, 'rating' => 3, 'comment' => 'Cần kiểm duyệt']);
        $this->actingAs($admin)->put(route('admin.reviews.update', $review), ['status' => 'HIDDEN', 'reason' => 'Không phù hợp'])->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'HIDDEN']);

        SystemSetting::query()->create(['key' => 'test_setting', 'value' => 'old', 'type' => 'string', 'group' => 'test', 'label' => 'Test setting']);
        $this->actingAs($admin)->put(route('admin.settings.update'), ['settings' => ['test_setting' => 'new']])->assertSessionHas('success');
        $this->assertDatabaseHas('system_settings', ['key' => 'test_setting', 'value' => 'new']);

        $role = Role::query()->create(['code' => 'test-role', 'name' => 'Test role']);
        $permission = Permission::query()->create(['code' => 'test.permission', 'name' => 'Test permission', 'group' => 'test']);
        $this->actingAs($admin)->put(route('admin.roles.update', $role), ['permission_ids' => [$permission->id]])->assertSessionHas('success');
        $this->assertTrue($role->refresh()->permissions->contains($permission));

        $recipient = User::factory()->create(['role' => 'customer', 'status' => 'ACTIVE']);
        $this->actingAs($admin)->post(route('admin.notifications.store'), ['audience' => 'customer', 'title' => 'Thông báo vận hành', 'message' => 'Nội dung thử nghiệm'])->assertSessionHas('success');
        $this->assertTrue($recipient->notifications()->exists());
    }

    public function test_admin_can_cancel_appointment_and_export_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = $this->appointment(['status' => 'CONFIRMED']);
        $this->actingAs($admin)->post(route('admin.appointments.cancel', $appointment), ['reason' => 'Đóng lịch để kiểm thử.'])->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'CANCELLED_BY_CLINIC']);

        $this->actingAs($admin)->get(route('admin.reports.export'))->assertOk()->assertDownload();
    }

    public function test_admin_crud_and_toggle_buttons_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Nhân viên mới', 'email' => 'new-staff@example.test', 'role' => 'staff', 'status' => 'ACTIVE'])->assertSessionHas('success');
        $newStaff = User::query()->where('email', 'new-staff@example.test')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.users.update', $newStaff), ['name' => $newStaff->name, 'email' => $newStaff->email, 'role' => 'staff', 'status' => 'DISABLED'])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $newStaff->id, 'status' => 'DISABLED']);

        $this->actingAs($admin)->post(route('admin.services.store'), ['category_id' => $category->id, 'name' => 'Dịch vụ audit nút', 'description' => 'Kiểm thử', 'duration_minutes' => 60, 'price' => 500000, 'status' => 'ACTIVE', 'display_order' => 1])->assertSessionHas('success');
        $this->actingAs($admin)->put(route('admin.services.update', $service), ['category_id' => $category->id, 'name' => 'Dịch vụ đã cập nhật', 'description' => 'Đã sửa', 'duration_minutes' => 45, 'price' => 450000, 'status' => 'INACTIVE', 'display_order' => 2])->assertSessionHas('success');
        $this->assertDatabaseHas('services', ['id' => $service->id, 'status' => 'INACTIVE', 'duration_minutes' => 45]);

        $this->actingAs($admin)->post(route('admin.specialists.store'), ['name' => 'Chuyên viên mới', 'email' => 'new-specialist@example.test', 'title' => 'Chuyên viên', 'service_ids' => [$service->id]])->assertSessionHas('success');
        $profile = SpecialistProfile::query()->whereHas('user', fn ($query) => $query->where('email', 'new-specialist@example.test'))->firstOrFail();
        WorkingSchedule::factory()->create(['specialist_profile_id' => $profile->id]);
        $this->actingAs($admin)->post(route('admin.specialists.toggle', $profile))->assertSessionHas('success');
        $this->assertDatabaseHas('specialist_profiles', ['id' => $profile->id, 'status' => 'ACTIVE']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Danh mục đã cập nhật',
            'display_order' => 2,
            'is_active' => 1,
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('service_categories', ['id' => $category->id, 'name' => 'Danh mục đã cập nhật']);

        $this->actingAs($admin)->put(route('admin.specialists.update', $profile), [
            'name' => $profile->user->name,
            'title' => 'Chuyên viên đã sửa bởi Admin',
            'service_ids' => [$service->id],
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('specialist_profiles', ['id' => $profile->id, 'title' => 'Chuyên viên đã sửa bởi Admin']);
    }

    public function test_admin_refund_button_completes_refund(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $appointment = $this->appointment(['status' => 'CANCELLED_BY_CUSTOMER', 'payment_status' => 'REFUND_PENDING', 'refund_status' => 'REFUND_PENDING']);
        $refund = RefundRequest::query()->create(['appointment_id' => $appointment->id, 'amount' => 200000, 'percentage' => 50, 'status' => 'PENDING']);

        $this->actingAs($admin)->post(route('admin.refunds.complete', $refund), ['reference' => 'ADM-REF-001'])->assertSessionHas('success');
        $this->assertDatabaseHas('refund_requests', ['id' => $refund->id, 'status' => 'REFUNDED', 'reference' => 'ADM-REF-001']);
    }

    /** @return array{0: User, 1: SpecialistProfile} */
    private function specialist(): array
    {
        $user = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        return [$user, $profile];
    }

    /** @param array<string, mixed> $overrides */
    private function appointment(array $overrides = []): Appointment
    {
        $customer = isset($overrides['customer_id']) ? User::query()->findOrFail($overrides['customer_id']) : User::factory()->create(['role' => 'customer']);
        if (isset($overrides['specialist_profile_id'])) {
            $profile = SpecialistProfile::query()->findOrFail($overrides['specialist_profile_id']);
        } else {
            [, $profile] = $this->specialist();
        }
        $service = Service::factory()->create();
        $profile->services()->syncWithoutDetaching([$service->id]);
        $start = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        return Appointment::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'created_by' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'slot_lock_key' => $profile->id.'|'.$start->format('Y-m-d H:i:s'),
        ], $overrides));
    }
}
