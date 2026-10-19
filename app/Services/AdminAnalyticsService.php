<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class AdminAnalyticsService
{
    /** @return array{from: CarbonImmutable, to: CarbonImmutable, previousFrom: CarbonImmutable, previousTo: CarbonImmutable} */
    public function period(?string $from, ?string $to): array
    {
        $toDate = $to ? CarbonImmutable::parse($to)->endOfDay() : CarbonImmutable::now()->endOfDay();
        $fromDate = $from ? CarbonImmutable::parse($from)->startOfDay() : $toDate->subDays(29)->startOfDay();
        $days = max(1, $fromDate->diffInDays($toDate) + 1);

        return ['from' => $fromDate, 'to' => $toDate, 'previousFrom' => $fromDate->subDays($days), 'previousTo' => $fromDate->subSecond()];
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function summary(array $period): array
    {
        $query = Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']]);
        $collected = (float) Appointment::query()->whereBetween('paid_at', [$period['from'], $period['to']])->where('payment_status', 'PAID')->sum('price');
        $refunded = (float) Appointment::query()->whereBetween('refunded_at', [$period['from'], $period['to']])->where('payment_status', 'REFUNDED')->sum('refund_amount');

        return [
            'collected' => $collected,
            'refunded' => $refunded,
            'net_revenue' => $collected - $refunded,
            'pending_refunds' => (float) Appointment::query()->where('refund_status', 'REFUND_PENDING')->sum('refund_amount'),
            'appointments' => (clone $query)->count(),
            'today' => Appointment::query()->whereDate('start_at', today())->count(),
            'needs_attention' => Appointment::query()->where('needs_attention', true)->count(),
            'cash_pending' => Appointment::query()->where('status', 'COMPLETED')->where('payment_method', 'CASH')->where('payment_status', 'UNPAID')->count(),
        ];
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function statusCounts(array $period): Collection
    {
        return Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']])->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function dailyRevenue(array $period): Collection
    {
        $collected = Appointment::query()->whereBetween('paid_at', [$period['from'], $period['to']])->where('payment_status', 'PAID')
            ->selectRaw('DATE(paid_at) AS day')->selectRaw('SUM(price) AS collected')->groupBy('day')->get()->keyBy('day');
        $refunded = Appointment::query()->whereBetween('refunded_at', [$period['from'], $period['to']])->where('payment_status', 'REFUNDED')
            ->selectRaw('DATE(refunded_at) AS day')->selectRaw('SUM(COALESCE(refund_amount, 0)) AS refunded')->groupBy('day')->get()->keyBy('day');
        $days = $collected->keys()->merge($refunded->keys())->unique()->sort()->values();

        return $days->map(function (string $day) use ($collected, $refunded): object {
            return (object) [
                'day' => $day,
                'collected' => (float) ($collected->get($day)->collected ?? 0),
                'refunded' => (float) ($refunded->get($day)->refunded ?? 0),
            ];
        });
    }

    /** @param array{from: CarbonImmutable, to: CarbonImmutable} $period */
    public function serviceCounts(array $period): Collection
    {
        return Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->selectRaw('services.name, COUNT(*) AS total')
            ->groupBy('services.id', 'services.name')->orderByDesc('total')->get();
    }
}
