@extends('layouts.specialist')

@section('title', 'Ca tư vấn')

@section('content')
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ══════════════════════════════════════════════════════════
             TIÊU ĐỀ + BỘ LỌC NGÀY
             ══════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Nghiệp vụ 2</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Ca tư vấn</h1>
                <p class="mt-2 max-w-3xl text-sm text-[#52637A]">
                    Xem lịch được phân công, bắt đầu và hoàn thành ca tư vấn. Sau khi hoàn thành, hệ thống
                    đưa thẳng bạn tới bước ghi nhận hồ sơ chuyên môn.
                </p>
            </div>

            <form method="GET" action="{{ route('specialist.appointments') }}"
                  class="flex flex-wrap items-end gap-2 rounded-xl border border-[#D7E8F3] bg-white p-3">
                <label class="block">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-[#718096]">Ngày làm việc</span>
                    <input type="date" name="date" value="{{ $selectedDate->toDateString() }}"
                           class="mt-1 rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                </label>
                <label class="block">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-[#718096]">Trạng thái</span>
                    <select name="status" class="mt-1 rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                        <option value="">Tất cả</option>
                        @foreach(['CONFIRMED' => 'Đã xác nhận', 'IN_PROGRESS' => 'Đang tư vấn', 'COMPLETED' => 'Đã hoàn thành', 'NO_SHOW' => 'Vắng mặt', 'PENDING' => 'Chờ xác nhận'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Xem lịch
                </button>
                <a href="{{ route('specialist.appointments', ['date' => today()->toDateString()]) }}"
                   class="rounded-lg border border-[#B8D2E4] bg-white px-3 py-2 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                    Hôm nay
                </a>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             THẺ SỐ LIỆU THEO NGÀY
             ══════════════════════════════════════════════════════════ --}}
        <section aria-label="Số liệu ca trong ngày" class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            @php
                $statCards = [
                    ['label' => 'Tổng ca', 'value' => $summary['total'], 'tone' => 'slate'],
                    ['label' => 'Chờ thực hiện', 'value' => $summary['waiting'], 'tone' => 'blue'],
                    ['label' => 'Đang tư vấn', 'value' => $summary['inProgress'], 'tone' => 'emerald'],
                    ['label' => 'Đã hoàn thành', 'value' => $summary['completed'], 'tone' => 'teal'],
                    ['label' => 'Vắng mặt', 'value' => $summary['noShow'], 'tone' => 'rose'],
                ];
                $statTones = [
                    'slate' => ['bg' => 'bg-white', 'border' => 'border-[#D7E8F3]', 'label' => 'text-[#52637A]', 'value' => 'text-[#102A43]'],
                    'blue' => ['bg' => 'bg-[#EAF4FB]', 'border' => 'border-[#B8D2E4]', 'label' => 'text-[#23689C]', 'value' => 'text-[#1B5E91]'],
                    'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'label' => 'text-emerald-700', 'value' => 'text-emerald-800'],
                    'teal' => ['bg' => 'bg-teal-50', 'border' => 'border-teal-200', 'label' => 'text-teal-700', 'value' => 'text-teal-800'],
                    'rose' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'label' => 'text-rose-700', 'value' => 'text-rose-800'],
                ];
            @endphp

            @foreach($statCards as $card)
                @php $tone = $statTones[$card['tone']]; @endphp
                <div class="rounded-2xl border {{ $tone['border'] }} {{ $tone['bg'] }} p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide {{ $tone['label'] }}">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-black tabular-nums {{ $tone['value'] }}">{{ number_format($card['value']) }}</p>
                </div>
            @endforeach
        </section>

        {{-- ══════════════════════════════════════════════════════════
             DANH SÁCH CA
             ══════════════════════════════════════════════════════════ --}}
        <section aria-labelledby="session-list-heading" class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 id="session-list-heading" class="text-lg font-black text-[#102A43]">
                    Ca ngày {{ $selectedDate->format('d/m/Y') }}
                </h2>
                <p class="text-xs text-[#718096]">
                    Hiển thị {{ $appointments->count() }} ca
                </p>
            </div>

            @forelse($appointments as $appointment)
                @php
                    $allowedStartAt = $appointment->start_at->copy()->subMinutes(15);
                    $canStart = $appointment->status === 'CONFIRMED' && now()->gte($allowedStartAt);
                    $isLateStart = $canStart && now()->gt($appointment->end_at);
                    $canComplete = $appointment->status === 'IN_PROGRESS';
                    $canNoShow = $appointment->status === 'CONFIRMED'
                        && now()->gte($appointment->start_at->copy()->addMinutes(15));
                    $canWriteRecord = in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true);
                @endphp

                <article class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                    <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">

                        {{-- Thời gian + khách hàng --}}
                        <div class="flex min-w-0 flex-1 items-start gap-4">
                            <div class="flex h-14 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-[#EAF4FB] text-[#1B5E91]">
                                <strong class="text-base font-black tabular-nums leading-none">{{ $appointment->start_at->format('H:i') }}</strong>
                                <small class="mt-1 text-[10px] font-semibold leading-none">{{ $appointment->end_at->format('H:i') }}</small>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-black text-[#102A43]">{{ $appointment->customer->name }}</h3>
                                    <x-status-chip type="appointment" :status="$appointment->status" />
                                    @if($appointment->consultationRecord)
                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Đã có hồ sơ</span>
                                    @elseif($appointment->status === 'COMPLETED')
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800">Chưa ghi hồ sơ</span>
                                    @endif
                                </div>
                                <p class="mt-1 truncate text-sm text-[#52637A]">{{ $appointment->service->name }}</p>
                                <p class="mt-1 text-[11px] text-[#8CA3B8]">
                                    {{ $appointment->appointment_code }}
                                    · {{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : ($appointment->consultationRoom?->name ?? 'Tại phòng khám') }}
                                    @if($appointment->checked_in_at)
                                        · Check-in {{ $appointment->checked_in_at->format('H:i') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Thao tác theo trạng thái --}}
                        <div class="flex flex-wrap items-center gap-2 lg:justify-end">

                            @if($appointment->status === 'CONFIRMED')
                                <form method="POST" action="{{ route('specialist.appointments.start', $appointment->id) }}">
                                    @csrf
                                    <button type="submit" @disabled(! $canStart)
                                            title="{{ $canStart ? 'Bắt đầu ca tư vấn' : 'Chỉ có thể bắt đầu từ 15 phút trước giờ hẹn' }}"
                                            class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition enabled:hover:bg-[#23689C] disabled:cursor-not-allowed disabled:bg-gray-300">
                                        Bắt đầu
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('specialist.appointments.no-show', $appointment->id) }}"
                                      onsubmit="return confirm('Ghi nhận khách vắng mặt cho ca này?');">
                                    @csrf
                                    <button type="submit" @disabled(! $canNoShow)
                                            title="{{ $canNoShow ? 'Ghi nhận vắng mặt' : 'Chỉ ghi nhận sau 15 phút kể từ giờ hẹn' }}"
                                            class="rounded-lg border border-red-300 bg-white px-3 py-2.5 text-sm font-bold text-red-700 transition enabled:hover:bg-red-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400">
                                        Vắng mặt
                                    </button>
                                </form>
                            @endif

                            @if($appointment->status === 'IN_PROGRESS')
                                <form method="POST" action="{{ route('specialist.appointments.complete', $appointment->id) }}"
                                      onsubmit="return confirm('Hoàn thành ca và chuyển sang ghi hồ sơ?');">
                                    @csrf
                                    <button type="submit"
                                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                                        Hoàn thành ca
                                    </button>
                                </form>
                            @endif

                            @if($canWriteRecord)
                                <a href="{{ route('specialist.consultation-note', $appointment->id) }}"
                                   class="rounded-lg border border-[#2D82C4] bg-white px-3 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                                    {{ $appointment->consultationRecord ? 'Xem hồ sơ' : 'Ghi hồ sơ' }}
                                </a>
                            @endif

                            <a href="{{ route('specialist.appointments.detail', $appointment->id) }}"
                               class="rounded-lg border border-[#D7E8F3] bg-white px-3 py-2.5 text-sm font-bold text-[#52637A] transition hover:border-[#B8D2E4] hover:text-[#2D82C4]">
                                Chi tiết
                            </a>
                        </div>
                    </div>

                    {{-- Gợi ý khi chưa thể thao tác --}}
                    @if($appointment->status === 'CONFIRMED' && ! $canStart && ! $canNoShow)
                        <div class="border-t border-[#E8F1F6] bg-[#F7FBFE] px-5 py-2.5 text-[11px] text-[#718096]">
                            Bạn có thể bắt đầu từ {{ $allowedStartAt->format('d/m/Y H:i') }}.
                            Nếu khách chưa check-in, hãy phối hợp với Lễ tân thay vì tự ghi nhận.
                        </div>
                    @elseif($appointment->status === 'CONFIRMED' && $isLateStart)
                        <div class="border-t border-[#E8F1F6] bg-amber-50 px-5 py-2.5 text-[11px] font-semibold text-amber-900">
                            Ca đã qua giờ hẹn. Bạn vẫn có thể bấm Bắt đầu để ghi nhận khởi động muộn.
                        </div>
                    @endif

                    @if($appointment->status === 'COMPLETED' && ! $appointment->consultationRecord)
                        <div class="border-t border-[#E8F1F6] bg-amber-50 px-5 py-2.5 text-[11px] font-semibold text-amber-900">
                            Ca đã hoàn thành nhưng chưa có hồ sơ chuyên môn. Hãy ghi nhận tóm tắt và kế hoạch theo dõi.
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-[#D7E8F3] bg-white px-5 py-14 text-center">
                    <svg class="mx-auto h-10 w-10 text-[#B8D2E4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                    </svg>
                    <p class="mt-3 text-sm font-bold text-[#102A43]">Không có ca tư vấn nào</p>
                    <p class="mt-1 text-xs text-[#718096]">
                        Ngày {{ $selectedDate->format('d/m/Y') }} chưa có lịch hẹn phù hợp với bộ lọc.
                    </p>
                </div>
            @endforelse
        </section>

        {{-- ══════════════════════════════════════════════════════════
             QUY TẮC TRẠNG THÁI
             ══════════════════════════════════════════════════════════ --}}
        <section class="rounded-2xl border border-[#BFDBFE] bg-[#EAF4FB] p-5">
            <h2 class="text-sm font-black text-[#1B5E91]">Trạng thái và quyền thực hiện</h2>
            <ul class="mt-2 grid gap-2 text-xs leading-relaxed text-[#23689C] sm:grid-cols-2 lg:grid-cols-4">
                <li><strong>Đã xác nhận →</strong> Chuyên viên bắt đầu ca</li>
                <li><strong>Đang tư vấn →</strong> Chuyên viên hoàn thành ca</li>
                <li><strong>Đã xác nhận / Đang tư vấn →</strong> Ghi hồ sơ chuyên môn</li>
                <li><strong>Vắng mặt →</strong> Chỉ sau 15 phút chờ theo quy định</li>
            </ul>
        </section>
    </div>
@endsection
