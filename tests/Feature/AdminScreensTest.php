<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Permission;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Role;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kiểm tra toàn bộ màn hình quản trị mới render được với dữ liệu thật.
 *
 * Mục tiêu: bắt sớm lỗi Blade/thiếu biến khi thiết kế lại giao diện Admin
 * theo đặc tả nghiệp vụ (tai_khoan_admin.md).
 */
class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ACTIVE']);
    }

    public function test_all_admin_screens_render_without_errors(): void
    {
        $admin = $this->admin();

        $category = ServiceCategory::factory()->create(['name' => 'Tâm lý cá nhân']);
        $service = Service::factory()->create([
            'category_id' => $category->id,
            'name' => 'Gói tham vấn 60 phút',
            'status' => 'ACTIVE',
        ]);

        $specialistUser = User::factory()->create(['role' => 'specialist', 'status' => 'ACTIVE']);
        $profile = SpecialistProfile::query()->create([
            'user_id' => $specialistUser->id,
            'title' => 'Chuyên viên tâm lý',
            'status' => 'ACTIVE',
        ]);
        $profile->services()->sync([$service->id]);

        $customer = User::factory()->create(['role' => 'customer', 'status' => 'ACTIVE']);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => $service->id,
            'status' => 'CONFIRMED',
        ]);

        Review::query()->create([
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'rating' => 3,
            'comment' => 'Buổi tư vấn ổn.',
            'status' => 'PENDING_REVIEW',
        ]);

        RefundRequest::query()->create([
            'appointment_id' => $appointment->id,
            'amount' => 100000,
            'percentage' => 50,
            'status' => 'PENDING',
        ]);

        ScheduleException::query()->create([
            'type' => 'HOLIDAY',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'reason' => 'Nghỉ lễ kiểm thử',
            'created_by' => $admin->id,
        ]);

        SystemSetting::query()->create([
            'key' => 'buffer_minutes',
            'value' => '10',
            'type' => 'integer',
            'group' => 'schedule',
            'label' => 'Thời gian đệm giữa các ca',
        ]);

        $role = Role::query()->create(['code' => 'staff', 'name' => 'Lễ tân', 'is_system' => true]);
        $permission = Permission::query()->create(['code' => 'appointments.view', 'name' => 'Xem lịch hẹn', 'group' => 'appointments']);
        $role->permissions()->sync([$permission->id]);

        $routes = [
            'admin.dashboard',
            'admin.users',
            'admin.roles',
            'admin.services',
            'admin.categories',
            'admin.specialists',
            'admin.schedule',
            'admin.settings',
            'admin.settings.mail',
            'admin.appointments',
            'admin.reviews',
            'admin.reports',
            'admin.audit-logs',
            'admin.payments',
            'admin.notifications',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get(route($route));

            $response->assertOk();
        }
    }

    public function test_specialist_preview_impact_counts_are_exposed(): void
    {
        $admin = $this->admin();
        $specialistUser = User::factory()->create(['role' => 'specialist']);
        $profile = SpecialistProfile::query()->create([
            'user_id' => $specialistUser->id,
            'title' => 'Chuyên viên',
            'status' => 'ACTIVE',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        Appointment::factory()->create([
            'customer_id' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => Service::factory()->create()->id,
            'status' => 'CONFIRMED',
            'payment_status' => 'PAID',
            'start_at' => now()->addDays(2),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.specialists'))
            ->assertOk()
            ->assertSee('Lịch đã thanh toán (sắp tới)');
    }

    public function test_schedule_filter_by_scope_returns_only_clinic_holidays(): void
    {
        $admin = $this->admin();

        ScheduleException::query()->create([
            'type' => 'HOLIDAY',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Ngày lễ toàn phòng khám',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.schedule', ['scope' => 'clinic']))
            ->assertOk()
            ->assertSee('Ngày lễ toàn phòng khám');
    }
}
