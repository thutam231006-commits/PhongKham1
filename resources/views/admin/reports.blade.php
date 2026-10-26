@extends('layouts.admin')

@section('title', 'Báo cáo vận hành')

@php
    use App\Support\AdminPalette;
    use App\Support\AppointmentStatus;

    $labels = AppointmentStatus::labels();
    $formatVnd = fn (float|int $value): string => number_format((float) $value, 0, ',', '.');

    $revenueLabels = $dailyRevenue->map(fn (array $row): string => \Carbon\CarbonImmutable::parse($row['day'])->format('d/m'))->all();
    $revenueSeries = [
        ['name' => 'Tiền thực thu', 'color' => '#2D82C4', 'values' => $dailyRevenue->pluck('collected')->all()],
        ['name' => 'Tiền đã hoàn', 'color' => '#EA580C', 'values' => $dailyRevenue->pluck('refunded')->all()],
        ['name' => 'Dòng tiền thuần', 'color' => '#16A34A', 'values' => $dailyRevenue->pluck('net')->all()],
    ];

    $statusItems = $byStatus
        ->map(fn ($total, $status): array => [
            'label' => $labels[$status] ?? $status,
            'value' => (int) $total,
            'color' => AdminPalette::forAppointmentStatus((string) $status),
        ])
        ->sortByDesc('value')
        ->values()
        ->all();

    $paymentItems = $paymentMethods
        ->map(fn (array $row): array => [
            'label' => $row['label'],
            'value' => $row['total'],
            'color' => $row['method'] === 'CASH' ? '#D97706' : '#2D82C4',
        ])
        ->all();

    $maxServiceRevenue = (float) ($revenueByService->max('revenue') ?? 0);
    $maxServiceCount = (int) ($byService->max('total') ?? 0);
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân tích vận hành</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Báo cáo vận hành</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
                Báo cáo chỉ dùng số liệu tổng hợp, không chứa thông tin định danh hay nội dung tư vấn.
                <strong class="font-bold text-[#334E68]">Dòng tiền thuần</strong> = thanh toán hợp lệ − hoàn tiền đã thực hiện,
                không bao gồm giao dịch thất bại hay khoản hoàn mới ở trạng thái yêu cầu.
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-2 rounded-xl border border-[#D7E8F3] bg-white p-3">
            <label class="text-[11px] font-bold text-[#52637A]">
                Từ ngày
                <input type="date" name="from" value="{{ $from->toDateString() }}"
                       class="mt-1 block rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
            </label>
            <label class="text-[11px] font-bold text-[#52637A]">
                Đến ngày
                <input type="date" name="to" value="{{ $to->toDateString() }}"
                       class="mt-1 block rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
            </label>
            <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                Lọc báo cáo
            </button>
        </form>

        {{-- Đặc tả §4.5 — chọn loại báo cáo rồi xuất CSV tương ứng. --}}
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-3">
            <p class="text-[11px] font-bold text-[#52637A]">Loại báo cáo xuất</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <a href="{{ route('admin.reports.export', ['type' => 'revenue', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                    </svg>
                    Xuất dòng tiền CSV
                </a>
                <a href="{{ route('admin.reports.export', ['type' => 'appointments', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                    </svg>
                    Xuất lịch hẹn CSV
                </a>
            </div>
            <p class="mt-2 text-[11px] leading-relaxed text-[#8A9BB0]">
                File dòng tiền dùng cùng nguồn số liệu với biểu đồ (gom theo ngày thanh toán).
                Mọi lượt xuất đều được ghi vào nhật ký kiểm toán.
            </p>
        </div>
    </div>

    {{-- ═══════════════ THẺ SỐ LIỆU ═══════════════ --}}
    <section aria-label="Số liệu tài chính" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card
            label="Tiền thực thu"
            :value="$formatVnd($financial['collected'])"
            unit="VNĐ"
            :trend="$trend['collected']"
            tone="blue"
            icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />

        <x-admin.stat-card
            label="Tiền đã hoàn"
            :value="$formatVnd($financial['refunded'])"
            unit="VNĐ"
            hint="Khoản đã chi thực tế"
            :trend="$trend['refunded']"
            :trend-good="false"
            tone="amber"
            icon="M9 14l-4-4 4-4m-4 4h11a4 4 0 010 8h-1" />

        <x-admin.stat-card
            label="Dòng tiền thuần"
            :value="$formatVnd($financial['net_revenue'])"
            unit="VNĐ"
            hint="Thực thu − đã hoàn"
            :trend="$trend['net_revenue']"
            tone="emerald"
            icon="M3 17l6-6 4 4 7-7m0 0h-5m5 0v5" />

        <x-admin.stat-card
            label="Tiền chờ hoàn"
            :value="$formatVnd($financial['pending_refunds'])"
            unit="VNĐ"
            :hint="$financial['refund_requests_pending'].' yêu cầu chờ chi'"
            tone="red"
            icon="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </section>

    {{-- ═══════════════ BIỂU ĐỒ DOANH THU ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-[#102A43]">Doanh thu & dòng tiền theo ngày</h2>
                <p class="mt-1 max-w-3xl text-xs leading-relaxed text-[#718096]">
                    Đường xanh là dòng tiền thuần, đường xanh dương là tiền thực thu và đường cam là tiền đã hoàn.
                    Nhấn chuột lên điểm dữ liệu để xem giá trị chính xác.
                </p>
            </div>
            <span class="rounded-full bg-[#EAF4FB] px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide text-[#23689C]">
                {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}
            </span>
        </div>

        <div class="mt-5">
            <x-admin.area-chart
                id="reports-revenue"
                :labels="$revenueLabels"
                :series="$revenueSeries"
                :height="300"
                format="currency" />
        </div>
    </section>

    {{-- ═══════════════ CƠ CẤU ═══════════════ --}}
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
            <h2 class="text-lg font-black text-[#102A43]">Cơ cấu trạng thái lịch hẹn</h2>
            <p class="mt-1 text-xs text-[#718096]">Trạng thái lịch hẹn được thống kê riêng, không gộp với trạng thái thanh toán.</p>
            <div class="mt-5">
                <x-admin.donut-chart
                    :items="$statusItems"
                    center-label="Tổng lịch"
                    :center-value="number_format($byStatus->sum())" />
            </div>
        </section>

        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
            <h2 class="text-lg font-black text-[#102A43]">Cơ cấu tiền thực thu theo phương thức</h2>
            <p class="mt-1 text-xs text-[#718096]">Chỉ tính các giao dịch đã ghi nhận thành công trong kỳ.</p>
            <div class="mt-5">
                <x-admin.donut-chart
                    :items="$paymentItems"
                    center-label="Đã thu"
                    :center-value="$formatVnd($financial['collected'])"
                    empty-message="Chưa có giao dịch thành công trong kỳ." />
            </div>
        </section>
    </div>

    {{-- ═══════════════ DOANH THU THEO GÓI DỊCH VỤ ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Doanh thu theo gói dịch vụ</h2>
        <p class="mt-1 text-xs text-[#718096]">Sắp xếp theo dòng tiền đã thu, kèm số lịch đã đặt trong kỳ.</p>

        <div class="mt-5 space-y-4">
            @forelse($revenueByService as $row)
                @php
                    $revenuePercent = $maxServiceRevenue > 0 ? (int) round($row['revenue'] / $maxServiceRevenue * 100) : 0;
                    $countPercent = $maxServiceCount > 0 ? (int) round($row['total'] / $maxServiceCount * 100) : 0;
                @endphp
                <div>
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <span class="min-w-0 truncate text-sm font-bold text-[#102A43]">{{ $row['name'] }}</span>
                        <span class="shrink-0 text-sm font-black tabular-nums text-[#2D82C4]">{{ $formatVnd($row['revenue']) }} <span class="text-[10px] font-bold text-[#8A9BB0]">VNĐ</span></span>
                    </div>
                    <div class="mt-2 flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-3">
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-[#EEF4F8]">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#2D82C4] to-[#6FA6CF]" style="width: {{ $revenuePercent }}%"></div>
                        </div>
                        <span class="shrink-0 text-[11px] font-bold text-[#718096]">{{ number_format($row['total']) }} lịch · {{ $revenuePercent }}%</span>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-[#F1F5F9]">
                        <div class="h-full rounded-full bg-[#6FA6CF]" style="width: {{ $countPercent }}%"></div>
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-[#D7E8F3] bg-[#F7FBFE] px-4 py-10 text-center text-sm text-[#718096]">
                    Chưa có giao dịch thành công trong kỳ đã chọn.
                </p>
            @endforelse
        </div>
    </section>

    {{-- ═══════════════ LƯU LƯỢNG THEO NGÀY ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Lưu lượng bệnh nhân theo ngày</h2>
        <p class="mt-1 text-xs text-[#718096]">Số bệnh nhân khác nhau, tổng số lịch và số lịch bị hủy trong từng ngày của kỳ lọc.</p>

        @php($peakDay = $patientsByDay->sortByDesc('total')->first())
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Ngày</th>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Bệnh nhân</th>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Tổng lịch</th>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Đã hủy</th>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Mức tải</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @foreach($patientsByDay as $row)
                        @php($percent = $peakDay && $peakDay['total'] > 0 ? (int) round($row['total'] / $peakDay['total'] * 100) : 0)
                        <tr class="transition hover:bg-[#F3F9FD]">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-[#102A43]">{{ \Carbon\CarbonImmutable::parse($row['day'])->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-black tabular-nums text-[#2D82C4]">{{ $row['new_patients'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $row['total'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums {{ $row['cancelled'] > 0 ? 'font-bold text-red-700' : 'text-[#718096]' }}">{{ $row['cancelled'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-28 overflow-hidden rounded-full bg-[#EEF4F8]">
                                        <div class="h-full rounded-full bg-[#2D82C4]" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold tabular-nums text-[#718096]">{{ $percent }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ═══════════════ LƯU LƯỢNG THEO GIỜ ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Lưu lượng theo khung giờ trong ngày {{ \Carbon\CarbonImmutable::parse($to)->format('d/m/Y') }}</h2>
        <p class="mt-1 text-xs text-[#718096]">
            Giờ nào khách đến nhiều nhất — phục vụ điều phối nhân sự.
            @if($peakHour && $peakHour['total'] > 0)
                <span class="font-bold text-[#2D82C4]">Giờ cao điểm {{ str_pad($peakHour['hour'], 2, '0', STR_PAD_LEFT) }}:00</span> với {{ $peakHour['total'] }} lịch.
            @endif
        </p>

        <div class="mt-5 flex flex-wrap items-end gap-2">
            @foreach($hourlyTraffic as $hour)
                @php($barHeight = $peakHour && $peakHour['total'] > 0 ? max(6, (int) round($hour['total'] / $peakHour['total'] * 100)) : 0)
                <div class="flex w-10 flex-col items-center gap-1.5">
                    <span class="text-xs font-black tabular-nums text-[#102A43]">{{ $hour['total'] }}</span>
                    <div class="w-full rounded-t bg-[#2D82C4] {{ $barHeight === 0 ? 'bg-[#E4EFF7]' : '' }}" style="height: {{ max(6, $barHeight) }}px" title="{{ str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) }}:00 — {{ $hour['total'] }} lịch"></div>
                    <span class="text-[10px] font-semibold text-[#8A9BB0]">{{ str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) }}h</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
