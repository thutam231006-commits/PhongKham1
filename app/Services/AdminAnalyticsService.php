<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\RefundRequest;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * AdminAnalyticsService — số liệu cho Dashboard và Báo cáo của Admin.
 *
 * Nguyên tắc nghiệp vụ (đặc tả Admin Module §4.3–4.4):
 *  - Không gộp trạng thái lịch hẹn với trạng thái thanh toán.
 *  - Dòng tiền thuần = thanh toán hợp lệ − hoàn tiền đã thực hiện.
 *  - Khoản hoàn mới ở trạng thái yêu cầu/chờ duyệt không được trừ vào dòng tiền.
 */
final class AdminAnalyticsService
{
    /**
     * Thời điểm ghi nhận tiền thực thu.
     *
     * Thanh toán online đặt `paid_at`, còn thu tiền mặt tại quầy chỉ đặt
     * `collected_at`. Dùng COALESCE để khoản thu tiền mặt không bị bỏ sót
     * khỏi báo cáo dòng tiền (đặc tả §4.4 — "thanh toán hợp lệ trong kỳ").
     */
    private const PAID_AT_EXPRESSION = 'COALESCE(appointments.paid_at, appointments.collected_at)';

    /** @return array{from: CarbonImmutable, to: CarbonImmutable, previousFrom: CarbonImmutable, previousTo: CarbonImmutable, days: int} */
    public function period(?string $from, ?string $to): array
    {
        $toDate = $to ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay();
        $fromDate = $from ? CarbonImmutable::parse($from)->startOfDay() : $toDate->subDays(29)->startOfDay();
        $days = max(1, (int) $fromDate->diffInDays($toDate) + 1);

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'previousFrom' => $fromDate->subDays($days),
            'previousTo' => $fromDate->subSecond(),
            'days' => $days,
        ];
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function summary(array $period): array
    {
        $query = Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']]);
        $financial = $this->financials($period);

        return [
            ...$financial,
            'appointments' => (clone $query)->count(),
            'today' => Appointment::query()->whereDate('start_at', today())->count(),
            'needs_attention' => Appointment::query()->where('needs_attention', true)->count(),
            'cash_pending' => Appointment::query()->where('status', 'COMPLETED')->where('payment_method', 'CASH')->where('payment_status', 'UNPAID')->count(),
        ];
    }

    /**
     * Dòng tiền của một kỳ: chỉ tính thanh toán thành công và hoàn tiền đã thực hiện.
     *
     * Khoản đã thu rồi mới hoàn (REFUNDED/REFUND_PENDING) vẫn được tính vào
     * "tiền thực thu" của kỳ phát sinh thanh toán — nếu bỏ ra ngoài thì kỳ thu
     * sẽ bị hụt và kỳ hoàn sẽ âm không có căn cứ đối chiếu.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable}  $period
     * @return array{collected: float, refunded: float, net_revenue: float, pending_refunds: float, refund_requests_pending: int}
     */
    public function financials(array $period): array
    {
        $collected = (float) Appointment::query()
            ->whereIn('payment_status', ['PAID', 'REFUND_PENDING', 'REFUNDED'])
            ->whereRaw(self::PAID_AT_EXPRESSION.' BETWEEN ? AND ?', [$period['from'], $period['to']])
            ->sum('price');

        $refunded = (float) Appointment::query()
            ->whereBetween('refunded_at', [$period['from'], $period['to']])
            ->where('refund_status', 'REFUNDED')
            ->sum('refund_amount');

        return [
            'collected' => $collected,
            'refunded' => $refunded,
            'net_revenue' => $collected - $refunded,
            'pending_refunds' => (float) Appointment::query()
                ->where('refund_status', 'REFUND_PENDING')
                ->sum('refund_amount'),
            'refund_requests_pending' => RefundRequest::query()->where('status', 'PENDING')->count(),
        ];
    }

    /**
     * So sánh kỳ đang xem với kỳ liền trước cùng độ dài — trả về % thay đổi.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, previousFrom: CarbonImmutable, previousTo: CarbonImmutable}  $period
     * @return array<string, float|null>
     */
    public function trend(array $period): array
    {
        $previous = ['from' => $period['previousFrom'], 'to' => $period['previousTo']];
        $currentFinancials = $this->financials($period);
        $previousFinancials = $this->financials($previous);

        $currentAppointments = Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']])->count();
        $previousAppointments = Appointment::query()->whereBetween('start_at', [$period['previousFrom'], $period['previousTo']])->count();

        return [
            'collected' => $this->percentChange($previousFinancials['collected'], $currentFinancials['collected']),
            'net_revenue' => $this->percentChange($previousFinancials['net_revenue'], $currentFinancials['net_revenue']),
            'refunded' => $this->percentChange($previousFinancials['refunded'], $currentFinancials['refunded']),
            'appointments' => $this->percentChange((float) $previousAppointments, (float) $currentAppointments),
        ];
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function statusCounts(array $period): Collection
    {
        return Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']])
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
    }

    /**
     * Chuỗi dòng tiền theo từng ngày — luôn đủ mọi ngày trong kỳ để biểu đồ
     * không bị đứt đoạn khi có ngày không phát sinh giao dịch.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable}  $period
     * @return Collection<int, array{day: string, collected: float, refunded: float, net: float}>
     */
    public function dailyRevenue(array $period): Collection
    {
        $collected = Appointment::query()
            ->whereIn('payment_status', ['PAID', 'REFUND_PENDING', 'REFUNDED'])
            ->whereRaw(self::PAID_AT_EXPRESSION.' BETWEEN ? AND ?', [$period['from'], $period['to']])
            ->selectRaw('DATE('.self::PAID_AT_EXPRESSION.') AS day, SUM(price) AS collected')
            ->groupBy('day')->pluck('collected', 'day');

        $refunded = Appointment::query()
            ->whereBetween('refunded_at', [$period['from'], $period['to']])
            ->where('refund_status', 'REFUNDED')
            ->selectRaw('DATE(refunded_at) AS day, SUM(COALESCE(refund_amount, 0)) AS refunded')
            ->groupBy('day')->pluck('refunded', 'day');

        $series = collect();
        for ($day = $period['from']->startOfDay(); $day->lte($period['to']); $day = $day->addDay()) {
            $key = $day->toDateString();
            $paid = (float) ($collected[$key] ?? 0);
            $back = (float) ($refunded[$key] ?? 0);

            $series->push([
                'day' => $key,
                'collected' => $paid,
                'refunded' => $back,
                'net' => $paid - $back,
            ]);
        }

        return $series;
    }

    /**
     * Cơ cấu dòng tiền theo phương thức thanh toán.
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable}  $period
     * @return Collection<int, array{method: string, label: string, total: float, count: int}>
     */
    public function paymentMethodBreakdown(array $period): Collection
    {
        $labels = ['CASH' => 'Tiền mặt tại quầy', 'BANK_QR' => 'Chuyển khoản QR'];

        return Appointment::query()
            ->whereIn('payment_status', ['PAID', 'REFUND_PENDING', 'REFUNDED'])
            ->whereRaw(self::PAID_AT_EXPRESSION.' BETWEEN ? AND ?', [$period['from'], $period['to']])
            ->selectRaw('payment_method, COUNT(*) AS total_count, SUM(price) AS total_amount')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row): array => [
                'method' => (string) $row->payment_method,
                'label' => $labels[(string) $row->payment_method] ?? (string) $row->payment_method,
                'total' => (float) $row->total_amount,
                'count' => (int) $row->total_count,
            ]);
    }

    /**
     * Doanh thu theo gói dịch vụ (chỉ tính giao dịch thành công trong kỳ).
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable}  $period
     * @return Collection<int, array{name: string, total: int, revenue: float}>
     */
    public function revenueByService(array $period): Collection
    {
        return Appointment::query()
            ->whereIn('payment_status', ['PAID', 'REFUND_PENDING', 'REFUNDED'])
            ->whereRaw(self::PAID_AT_EXPRESSION.' BETWEEN ? AND ?', [$period['from'], $period['to']])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->selectRaw('services.name, COUNT(*) AS total, SUM(appointments.price) AS revenue')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function serviceCounts(array $period): Collection
    {
        return Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->selectRaw('services.name, COUNT(*) AS total')
            ->groupBy('services.id', 'services.name')->orderByDesc('total')->get();
    }

    /**
     * Việc cần xử lý ngay trên Dashboard (đặc tả §IV.2 — danh sách ưu tiên).
     *
     * @return array<int, array{key: string, label: string, count: int, href: string, tone: string}>
     */
    public function workQueue(): array
    {
        $pendingReviews = Review::query()->where('status', 'PENDING_REVIEW')->count();
        $attention = Appointment::query()->where('needs_attention', true)->count();
        $pendingRefunds = RefundRequest::query()->where('status', 'PENDING')->count();
        $pendingConfirm = Appointment::query()->where('status', 'PENDING')->where('start_at', '>', now())->count();

        return [
            ['key' => 'attention', 'label' => 'Lịch bị ảnh hưởng cần điều phối', 'count' => $attention, 'href' => route('admin.appointments', ['attention' => 1]), 'tone' => 'orange'],
            ['key' => 'refunds', 'label' => 'Yêu cầu hoàn tiền chờ chi', 'count' => $pendingRefunds, 'href' => route('admin.payments'), 'tone' => 'amber'],
            ['key' => 'reviews', 'label' => 'Đánh giá chờ kiểm duyệt', 'count' => $pendingReviews, 'href' => route('admin.reviews', ['status' => 'PENDING_REVIEW']), 'tone' => 'blue'],
            ['key' => 'pending', 'label' => 'Lịch chờ xác nhận', 'count' => $pendingConfirm, 'href' => route('admin.appointments', ['status' => 'PENDING']), 'tone' => 'slate'],
        ];
    }

    private function percentChange(float $previous, float $current): ?float
    {
        if ($previous === 0.0) {
            return $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
