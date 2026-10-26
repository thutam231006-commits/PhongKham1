@extends('layouts.admin')

@section('title', 'Tổng quan vận hành')

@php
    use App\Support\AdminPalette;
    use App\Support\AppointmentStatus;

    $labels = AppointmentStatus::labels();

    $statusItems = $statusCounts
        ->map(fn ($total, $status): array => [
            'label' => $labels[$status] ?? $status,
            'value' => (int) $total,
            'color' => AdminPalette::forAppointmentStatus((string) $status),
        ])
        ->sortByDesc('value')
        ->values()
        ->all();

    $revenueLabels = $dailyRevenue->map(fn (array $row): string => \Carbon\CarbonImmutable::parse($row['day'])->format('d/m'))->all();
    $revenueSeries = [
        ['name' => 'Tiền thực thu', 'color' => '#2D82C4', 'values' => $dailyRevenue->pluck('collected')->all()],
        ['name' => 'Tiền đã hoàn', 'color' => '#EA580C', 'values' => $dailyRevenue->pluck('refunded')->all()],
        ['name' => 'Dòng tiền thuần', 'color' => '#16A34A', 'values' => $dailyRevenue->pluck('net')->all()],
    ];

    $maxServiceTotal = (int) ($serviceCounts->max('total') ?? 0);

    $formatVnd = fn (float|int $value): string => number_format((float) $value, 0, ',', '.');
    $toneStyles = [
        'orange' => 'border-orange-200 bg-orange-50 text-orange-800',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800',
        'blue' => 'border-[#B8D2E4] bg-[#EAF4FB] text-[#23689C]',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ PAGE HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Bảng điều khiển quản trị</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Tổng quan vận hành</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
                Kỳ báo cáo {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}.
                Tiền thực thu ghi nhận theo ngày thanh toán, tiền hoàn theo ngày hoàn tất —
                hai dòng tiền được tách riêng để không đếm trùng.
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
                Áp dụng kỳ
            </button>
            <a href="{{ route('admin.reports', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
               class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                Xem báo cáo đầy đủ
            </a>
        </form>
    </div>

    {{-- ═══════════════ THẺ CHỈ SỐ TÀI CHÍNH ═══════════════ --}}
    <section aria-label="Chỉ số tài chính" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card
            label="Tiền thực thu"
            :value="$formatVnd($stats['collected'])"
            unit="VNĐ"
            :hint="'Ghi nhận trong kỳ'"
            :trend="$trend['collected']"
            tone="blue"
            :href="route('admin.reports', ['from' => $from->toDateString(), 'to' => $to->toDateString()])"
            icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />

        <x-admin.stat-card
            label="Tiền đã hoàn"
            :value="$formatVnd($stats['refunded'])"
            unit="VNĐ"
            hint="Chỉ tính khoản đã chi thực tế"
            :trend="$trend['refunded']"
            :trend-good="false"
            tone="amber"
            :href="route('admin.payments')"
            icon="M9 14l-4-4 4-4m-4 4h11a4 4 0 010 8h-1" />

        <x-admin.stat-card
            label="Dòng tiền thuần"
            :value="$formatVnd($stats['net_revenue'])"
            unit="VNĐ"
            hint="Thực thu − đã hoàn"
            :trend="$trend['net_revenue']"
            tone="emerald"
            :href="route('admin.reports', ['from' => $from->toDateString(), 'to' => $to->toDateString()])"
            icon="M3 17l6-6 4 4 7-7m0 0h-5m5 0v5" />

        <x-admin.stat-card
            label="Tiền chờ hoàn"
            :value="$formatVnd($stats['pending_refunds'])"
            unit="VNĐ"
            :hint="$stats['refund_requests_pending'].' yêu cầu chờ chi'"
            tone="red"
            :href="route('admin.payments')"
            icon="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </section>

    {{-- ═══════════════ THẺ CHỈ SỐ VẬN HÀNH ═══════════════ --}}
    <section aria-label="Chỉ số vận hành" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card
            label="Lịch hẹn trong kỳ"
            :value="number_format($stats['appointments'])"
            unit="lịch"
            :trend="$trend['appointments']"
            tone="slate"
            :href="route('admin.appointments')"
            icon="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />

        <x-admin.stat-card
            label="Lịch hẹn hôm nay"
            :value="number_format($stats['today'])"
            unit="lịch"
            hint="Theo giờ hệ thống"
            tone="blue"
            :href="route('admin.appointments')"
            icon="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />

        <x-admin.stat-card
            label="Khách hàng"
            :value="number_format($stats['customers'])"
            unit="tài khoản"
            hint="Toàn hệ thống"
            tone="emerald"
            :href="route('admin.users', ['role' => 'customer'])"
            icon="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />

        <x-admin.stat-card
            label="Chuyên viên đang nhận lịch"
            :value="number_format($stats['active_specialists'])"
            unit="hồ sơ"
            hint="Trạng thái ACTIVE"
            tone="blue"
            :href="route('admin.specialists')"
            icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
    </section>

    {{-- ═══════════════ BIỂU ĐỒ DÒNG TIỀN ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-[#102A43]">Dòng tiền theo ngày</h2>
                <p class="mt-1 max-w-2xl text-xs leading-relaxed text-[#718096]">
                    Ba đường tương ứng ba khái niệm khác nhau: khoản thu thành công, khoản hoàn đã chi thực tế
                    và dòng tiền thuần. Giao dịch thất bại hoặc khoản hoàn mới ở trạng thái yêu cầu không được tính.
                </p>
            </div>
            <a href="{{ route('admin.reports', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                Phân tích chi tiết
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="mt-5">
            <x-admin.area-chart
                id="dashboard-revenue"
                :labels="$revenueLabels"
                :series="$revenueSeries"
                :height="220"
                format="currency" />
        </div>
    </section>

    {{-- ═══════════════ TRẠNG THÁI + CÔNG VIỆC ═══════════════ --}}
    <div class="grid gap-6 xl:grid-cols-5">
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6 xl:col-span-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-[#102A43]">Cơ cấu lịch hẹn theo trạng thái</h2>
                    <p class="mt-1 text-xs text-[#718096]">Các trạng thái loại trừ lẫn nhau — tổng bằng số lịch trong kỳ.</p>
                </div>
                <span class="rounded-full bg-[#EAF4FB] px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide text-[#23689C]">
                    {{ $from->format('d/m') }} – {{ $to->format('d/m') }}
                </span>
            </div>

            <div class="mt-5">
                <x-admin.donut-chart
                    :items="$statusItems"
                    center-label="Tổng lịch"
                    :center-value="number_format($stats['appointments'])" />
            </div>
        </section>

        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6 xl:col-span-2">
            <h2 class="text-lg font-black text-[#102A43]">Cần xử lý ngay</h2>
            <p class="mt-1 text-xs text-[#718096]">Nhấn vào từng mục để mở danh sách đã lọc sẵn.</p>

            <ul class="mt-4 space-y-2.5">
                @foreach($workQueue as $item)
                    <li>
                        <a href="{{ $item['href'] }}"
                           class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition hover:-translate-y-0.5 hover:shadow-md
                                  {{ $item['count'] > 0 ? ($toneStyles[$item['tone']] ?? $toneStyles['slate']) : 'border-[#E4EFF7] bg-[#F7FBFE] text-[#52637A]' }}">
                            <span class="min-w-0 text-sm font-bold">{{ $item['label'] }}</span>
                            <span class="shrink-0 rounded-full bg-white/70 px-2.5 py-1 text-sm font-black tabular-nums">{{ number_format($item['count']) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 rounded-xl border border-[#E4EFF7] bg-[#F7FBFE] p-4">
                <p class="text-[11px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Đối soát tiền mặt</p>
                <p class="mt-1.5 text-sm text-[#334E68]">
                    <strong class="text-lg font-black text-[#102A43]">{{ number_format($stats['cash_pending']) }}</strong>
                    lịch đã hoàn thành nhưng chưa thu tiền mặt.
                </p>
                <a href="{{ route('admin.appointments', ['unpaid_cash' => 1]) }}"
                   class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-[#2D82C4] hover:underline">
                    Mở danh sách cần thu
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    </div>

    {{-- ═══════════════ LỊCH SẮP TỚI + DỊCH VỤ ═══════════════ --}}
    <div class="grid gap-6 xl:grid-cols-5">
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)] xl:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E8F1F6] px-5 py-4">
                <div>
                    <h2 class="text-lg font-black text-[#102A43]">Lịch hẹn sắp tới</h2>
                    <p class="mt-0.5 text-xs text-[#718096]">Sáu lịch gần nhất còn hiệu lực.</p>
                </div>
                <a href="{{ route('admin.appointments') }}" class="text-xs font-bold text-[#2D82C4] hover:underline">Xem tất cả →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                        <tr>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Thời gian</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Khách hàng</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Dịch vụ</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF4F8]">
                        @forelse($upcomingAppointments as $appointment)
                            <tr class="transition hover:bg-[#F3F9FD]">
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <p class="font-bold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y') }}</p>
                                    <p class="text-xs font-semibold tabular-nums text-[#2D82C4]">{{ $appointment->start_at->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                    <p class="text-xs text-[#718096]">{{ $appointment->specialistProfile->user->name }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-xs font-semibold text-[#334E68]">{{ $appointment->service->name }}</td>
                                <td class="px-5 py-3.5"><x-status-chip :status="$appointment->status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-[#718096]">Không có lịch hẹn sắp tới.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6 xl:col-span-2">
            <h2 class="text-lg font-black text-[#102A43]">Dịch vụ được đặt nhiều</h2>
            <p class="mt-1 text-xs text-[#718096]">Sắp xếp theo số lịch trong kỳ.</p>

            <ul class="mt-4 space-y-3">
                @forelse($serviceCounts as $row)
                    @php $percent = $maxServiceTotal > 0 ? (int) round($row->total / $maxServiceTotal * 100) : 0; @endphp
                    <li>
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="min-w-0 truncate text-sm font-semibold text-[#334E68]">{{ $row->name }}</span>
                            <span class="shrink-0 text-sm font-black tabular-nums text-[#102A43]">{{ number_format($row->total) }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[#EEF4F8]">
                            <div class="h-full rounded-full bg-[#2D82C4]" style="width: {{ $percent }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="rounded-xl border border-dashed border-[#D7E8F3] bg-[#F7FBFE] px-4 py-8 text-center text-sm text-[#718096]">
                        Không có dữ liệu trong kỳ.
                    </li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ═══════════════ LỊCH BỊ ẢNH HƯỞNG ═══════════════ --}}
    @if($attentionAppointments->isNotEmpty())
        <section class="rounded-2xl border border-orange-200 bg-orange-50/60 p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-black text-[#9A3412]">
                        <span aria-hidden="true">⚠</span> Lịch bị ảnh hưởng cần điều phối
                    </h2>
                    <p class="mt-1 text-xs text-[#9A3412]/80">Phát sinh khi ngày nghỉ, chặn giờ hoặc chuyên viên ngừng nhận lịch.</p>
                </div>
                <a href="{{ route('admin.appointments', ['attention' => 1]) }}"
                   class="rounded-lg bg-[#C2410C] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#9A3412]">
                    Mở danh sách ưu tiên
                </a>
            </div>

            <ul class="mt-4 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($attentionAppointments as $appointment)
                    <li class="rounded-xl border border-orange-200 bg-white p-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-mono text-[11px] font-bold text-[#C2410C]">{{ $appointment->appointment_code }}</p>
                            <span class="whitespace-nowrap text-[11px] font-bold text-[#9A3412]">{{ $appointment->start_at->format('d/m H:i') }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                        <p class="truncate text-xs text-[#718096]">{{ $appointment->service->name }} · <span class="font-semibold text-[#334E68]">{{ $appointment->specialistProfile->user->name }}</span></p>
                        <p class="mt-1.5 inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-[#9A3412]">
                            {{ $appointment->attention_reason ?: 'Cần xử lý' }}
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
