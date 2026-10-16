<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_dashboard_but_staff_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($staff)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_create_service_and_audit_entry_is_written(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ServiceCategory::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.services.store'), [
                'category_id' => $category->id,
                'name' => 'Dịch vụ quản trị cảm xúc',
                'description' => 'Dịch vụ kiểm thử cho dashboard quản trị.',
                'duration_minutes' => 60,
                'price' => 650000,
                'status' => 'ACTIVE',
                'display_order' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('services', ['name' => 'Dịch vụ quản trị cảm xúc']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'service.created', 'actor_id' => $admin->id]);
    }
}
