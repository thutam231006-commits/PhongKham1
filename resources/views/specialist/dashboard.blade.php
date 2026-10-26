@extends('layouts.specialist')

@section('title', 'Tổng quan')

@section('content')
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ══════════════════════════════════════════════════════════
             LỜI CHÀO + HÀNH ĐỘNG CHÍNH
             ══════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Khu vực chuyên viên</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">
                    Xin chào, {{ $profile->user->name }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-[#52637A]">
                    Hôm nay bạn có <strong class="text-[#2D82C4]">{{ $summary['todayTotal'] }}</strong> ca tư vấn.
                    Ưu tiên xử lý các ca đang chờ và hoàn tất hồ sơ chuyên môn.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('specialist.schedule') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:border-[#2D82C4] hover:bg-[#EAF4FB]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                    </svg>
                    Lịch làm việc
                </a>
                <a href="{{ route('specialist.appointments') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#23689C]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                    </svg>
                    Vào ca tư vấn
                </a>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             THẺ SỐ LIỆU
             ══════════════════════════════════════════════════════════ --}}
        <section aria-label="Số liệu tổng quan" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @php
                $statCards = [
                    ['label' => 'Ca hôm nay', 'value' => $summary['todayTotal'], 'tone' => 'blue', 'hint' => 'Tổng lịch trong ngày'],
                    ['label' => 'Cần thực hiện', 'value' => $summary['todayUpcoming'], 'tone' => 'amber', 'hint' => 'Đã xác nhận / đang tư vấn'],
                    ['label' => 'Ca tuần này', 'value' => $summary['weekTotal'], 'tone' => 'slate', 'hint' => 'Từ thứ Hai đến Chủ nhật'],
                    ['label' => 'Hồ sơ còn thiếu', 'value' => $summary['needsRecord'], 'tone' => 'emerald', 'hint' => 'Ca đã xong, chưa ghi hồ sơ'],
                ];
                $statTones = [
                    'blue' => ['bg' => 'bg-[#EAF4FB]', 'border' => 'border-[#B8D2E4]', 'label' => 'text-[#23689C]', 'value' => 'text-[#1B5E91]'],
                    'amber' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'label' => 'text-amber-700', 'value' => 'text-amber-800'],
                    'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'label' => 'text-emerald-700', 'value' => 'text-emerald-800'],
                    'slate' => ['bg' => 'bg-white', 'border' => 'border-[#D7E8F3]', 'label' => 'text-[#52637A]', 'value' => 'text-[#102A43]'],
                ];
            @endphp

            @foreach($statCards as $card)
                @php $tone = $statTones[$card['tone']]; @endphp
                <div class="rounded-2xl border {{ $tone['border'] }} {{ $tone['bg'] }} p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide {{ $tone['label'] }}">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-black tabular-nums {{ $tone['value'] }}">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-[11px] text-[#718096]">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </section>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ══════════════════════════════════════════════════════
                 CỘT CHÍNH — LỊCH HÔM NAY
                 ══════════════════════════════════════════════════════ --}}
            <section class="lg:col-span-2" aria-labelledby="today-heading">
                <div class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E8F1F6] px-5 py-4">
                        <div>
                            <h2 id="today-heading" class="text-lg font-black text-[#102A43]">Lịch hôm nay</h2>
                            <p class="mt-0.5 text-xs text-[#718096]">{{ now()->translatedFormat('l, d/m/Y') }}</p>
                        </div>
                        <a href="{{ route('specialist.appointments') }}" class="text-sm font-bold text-[#2D82C4] hover:underline">Xem tất cả →</a>
                    </div>

                    <ul class="divide-y divide-[#EEF4F8]">
                        @forelse($today as $appointment)
                            <li>
                                <a href="{{ route('specialist.appointments.detail', $appointment->id) }}"
                                   class="flex flex-col gap-3 px-5 py-4 transition hover:bg-[#F7FBFE] sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-[#EAF4FB] text-[#1B5E91]">
                                            <strong class="text-sm font-black tabular-nums leading-none">{{ $appointment->start_at->format('H:i') }}</strong>
                                            <small class="mt-1 text-[10px] font-semibold leading-none">{{ $appointment->end_at->format('H:i') }}</small>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                            <p class="truncate text-sm text-[#52637A]">{{ $appointment->service->name }}</p>
                                            <p class="mt-1 text-[11px] text-[#8CA3B8]">
                                                {{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : ($appointment->consultationRoom?->name ?? 'Tại phòng khám') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <x-status-chip type="appointment" :status="$appointment->status" />
                                        <svg class="h-4 w-4 text-[#B8D2E4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="px-5 py-12 text-center">
                                <p class="text-sm font-semibold text-[#102A43]">Hôm nay bạn chưa có ca tư vấn.</p>
                                <p class="mt-1 text-xs text-[#718096]">Bạn có thể kiểm tra lại ca cố định trong Lịch làm việc.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- ══════════════════════════════════════════════════════
                 CỘT PHỤ — VIỆC CẦN LÀM
                 ══════════════════════════════════════════════════════ --}}
            <aside class="space-y-4" aria-label="Việc cần làm">
                <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-[#52637A]">Hồ sơ chờ ghi nhận</h2>

                    <ul class="mt-4 space-y-3">
                        @forelse($needsRecord as $appointment)
                            <li class="rounded-xl border border-[#E8F1F6] bg-[#F7FBFE] p-3">
                                <p class="text-sm font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                <p class="mt-0.5 text-xs text-[#52637A]">{{ $appointment->service->name }}</p>
                                <p class="mt-0.5 text-[11px] text-[#8CA3B8]">{{ $appointment->start_at->format('d/m/Y H:i') }}</p>
                                <a href="{{ route('specialist.consultation-note', $appointment->id) }}"
                                   class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">
                                    Ghi hồ sơ
                                </a>
                            </li>
                        @empty
                            <li class="rounded-xl border border-dashed border-[#D7E8F3] bg-[#F7FBFE] px-4 py-6 text-center">
                                <p class="text-xs font-semibold text-[#52637A]">Không có hồ sơ nào bị bỏ dở.</p>
                            </li>
                        @endforelse
                    </ul>

                    @if($summary['needsRecord'] > $needsRecord->count())
                        <p class="mt-3 text-center text-[11px] text-[#8CA3B8]">
                            Còn {{ $summary['needsRecord'] - $needsRecord->count() }} hồ sơ khác cần ghi nhận.
                        </p>
                    @endif
                </div>

                <div class="rounded-2xl border border-[#BFDBFE] bg-[#EAF4FB] p-5">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2V9a2 2 0 00-2-2h-1V6a4 4 0 10-8 0v1H6a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <h2 class="text-sm font-black text-[#1B5E91]">Hồ sơ chuyên môn được bảo vệ</h2>
                            <p class="mt-1 text-xs leading-relaxed text-[#23689C]">
                                Nội dung tư vấn được mã hóa, chỉ bạn đọc được và mọi lần truy cập đều được ghi nhận.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
