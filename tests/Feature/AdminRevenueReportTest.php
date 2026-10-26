<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\RefundRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Đặc tả Admin Module §4.4 — dòng tiền thuần = thanh toán hợp lệ − hoàn tiền thực tế.
 *
 * Bài kiểm thử bám vào hai lỗi nghiệp vụ dễ mắc:
 *  1. Tiền mặt tại quầy chỉ ghi `collected_at`, không ghi `paid_at` — nếu báo cáo
 *     chỉ đọc `paid_at` thì toàn bộ doanh thu tiền mặt bị mất.
 *  2. Khoản đã thu rồi mới hoàn vẫn phải tính vào kỳ phát sinh thanh toán.
 */
class AdminRevenueReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(array $attributes = []): Appointment
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'ACTIVE']);
        $specialistUser = User::factory()->create(['role' => 'specialist', 'status' => 'ACTIVE']);
        $profile = SpecialistProfile::query()->create([
            'user_id' => $specialistUser->id,
            'title' => 'Chuyên viên tâm lý',
            'status' => 'ACTIVE',
        ]);

        return Appointment::factory()->create([
            'customer_id' => $customer->id,
            'specialist_profile_id' => $profile->id,
            'service_id' => Service::factory()->create([
                'category_id' => ServiceCategory::factory()->create()->id,
            ])->id,
            ...$attributes,
        ]);
    }

    public function test_cash_collected_payment_counts_as_revenue(): void
    {
        $this->makeAppointment([
            'payment_status' => 'PAID',
            'payment_method' => 'CASH',
            'price' => 750000,
            'paid_at' => null,
            'collected_at' => now(),
        ]);

        $analytics = app(AdminAnalyticsService::class);
        $period = $analytics->period(now()->subDays(3)->toDateString(), now()->toDateString());

        $this->assertSame(750000.0, $analytics->financials($period)['collected']);
        $this->assertSame(750000.0, $analytics->dailyRevenue($period)->sum('collected'));
    }

    public function test_online_paid_payment_still_counts_as_revenue(): void
    {
        $this->makeAppointment([
            'payment_status' => 'PAID',
            'payment_method' => 'BANK_QR',
            'price' => 900000,
            'paid_at' => now(),
        ]);

        $analytics = app(AdminAnalyticsService::class);
        $period = $analytics->period(now()->subDays(3)->toDateString(), now()->toDateString());

        $this->assertSame(900000.0, $analytics->financials($period)['collected']);
    }

    public function test_pending_refund_is_not_deducted_but_completed_refund_is(): void
    {
        $settled = $this->makeAppointment([
            'payment_status' => 'REFUNDED',
            'payment_method' => 'BANK_QR',
            'price' => 800000,
            'paid_at' => now(),
            'refunded_at' => now(),
            'refund_amount' => 400000,
            'refund_status' => 'REFUNDED',
        ]);

        $this->makeAppointment([
            'payment_status' => 'REFUND_PENDING',
            'payment_method' => 'CASH',
            'price' => 500000,
            'collected_at' => now(),
            'refund_amount' => 250000,
            'refund_status' => 'REFUND_PENDING',
        ]);

        RefundRequest::query()->create([
            'appointment_id' => $settled->id,
            'amount' => 400000,
            'percentage' => 50,
            'status' => 'REFUNDED',
            'processed_at' => now(),
        ]);

        $analytics = app(AdminAnalyticsService::class);
        $period = $analytics->period(now()->subDays(3)->toDateString(), now()->toDateString());
        $financials = $analytics->financials($period);

        // Cả hai khoản đã thu đều vào "tiền thực thu" của kỳ.
        $this->assertSame(1300000.0, $financials['collected']);
        // Chỉ khoản hoàn đã thực hiện mới bị trừ khỏi dòng tiền thuần.
        $this->assertSame(400000.0, $financials['refunded']);
        $this->assertSame(900000.0, $financials['net_revenue']);
        $this->assertSame(250000.0, $financials['pending_refunds']);
    }

    public function test_unpaid_appointment_is_excluded_from_revenue(): void
    {
        $this->makeAppointment([
            'payment_status' => 'UNPAID',
            'payment_method' => 'CASH',
            'price' => 900000,
        ]);

        $analytics = app(AdminAnalyticsService::class);
        $period = $analytics->period(now()->subDays(3)->toDateString(), now()->toDateString());

        $this->assertSame(0.0, $analytics->financials($period)['collected']);
    }

    public function test_reports_screen_shows_collected_revenue(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ACTIVE']);

        $this->makeAppointment([
            'payment_status' => 'PAID',
            'payment_method' => 'CASH',
            'price' => 750000,
            'collected_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports', ['from' => now()->subDays(3)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('750.000');
    }

    public function test_exporting_report_writes_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ACTIVE']);

        $this->actingAs($admin)
            ->get(route('admin.reports.export', ['from' => now()->subDays(3)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report.exported',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_revenue_csv_export_contains_cash_flow_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ACTIVE']);

        $this->makeAppointment([
            'payment_status' => 'PAID',
            'payment_method' => 'CASH',
            'price' => 750000,
            'collected_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', [
                'type' => 'revenue',
                'from' => now()->subDays(3)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Dòng tiền thuần', $csv);
        $this->assertStringContainsString('750000', $csv);
    }

    public function test_appointments_csv_export_keeps_status_breakdown(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ACTIVE']);

        $this->makeAppointment([
            'status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'payment_method' => 'CASH',
            'price' => 750000,
            'collected_at' => now(),
            'start_at' => now(),
            'end_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', [
                'type' => 'appointments',
                'from' => now()->subDays(3)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Trạng thái', $csv);
        $this->assertStringContainsString('COMPLETED', $csv);
    }
}
