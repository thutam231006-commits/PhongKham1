@extends('layouts.specialist')

@section('title', 'Lịch làm việc')

@php
    /*
     * Tab hiện tại: ưu tiên tab theo query ?tab=..., mặc định "schedule".
     * Khi đang xem trước tác động (Impact Preview) thì mở tab "exceptions".
     */
    $activeTab = isset($preview) ? 'exceptions' : (request('tab') === 'exceptions' ? 'exceptions' : 'schedule');
    $weekdayOptions = [
        1 => 'Thứ Hai',
        2 => 'Thứ Ba',
        3 => 'Thứ Tư',
        4 => 'Thứ Năm',
        5 => 'Thứ Sáu',
        6 => 'Thứ Bảy',
        7 => 'Chủ Nhật',
    ];
    $previewType = $preview['type'] ?? null;
@endphp

@section('content')
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8"
         x-data="{ tab: '{{ $activeTab }}' }">

        {{-- ══════════════════════════════════════════════════════════
             TIÊU ĐỀ TRANG
             ══════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Nghiệp vụ 1</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Lịch làm việc</h1>
                <p class="mt-2 max-w-3xl text-sm text-[#52637A]">
                    Khai báo thời gian có thể nhận khách và đăng ký nghỉ hoặc chặn giờ.
                    Hệ thống dùng thông tin này để mở khung giờ đặt lịch và phát hiện các lịch hẹn bị ảnh hưởng.
                </p>
            </div>

            @if($pendingReviewCount > 0)
                <div class="inline-flex items-center gap-2 self-start rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    {{ $pendingReviewCount }} ngoại lệ đang chờ Lễ tân điều phối
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════
             THANH TAB
             ══════════════════════════════════════════════════════════ --}}
        <div class="border-b border-[#D7E8F3]">
            <div class="flex gap-1" role="tablist" aria-label="Nhóm cấu hình lịch làm việc">
                <button type="button"
                        role="tab"
                        id="tab-schedule"
                        aria-controls="panel-schedule"
                        :aria-selected="(tab === 'schedule').toString()"
                        @click="tab = 'schedule'"
                        class="relative -mb-px rounded-t-lg px-4 py-3 text-sm font-bold transition"
                        :class="tab === 'schedule' ? 'border-b-2 border-[#2D82C4] text-[#2D82C4]' : 'border-b-2 border-transparent text-[#52637A] hover:text-[#2D82C4]'">
                    Ca cố định
                    <span class="ml-1.5 rounded-full bg-[#EAF4FB] px-2 py-0.5 text-[11px] font-bold text-[#23689C]">{{ $workingSchedules->count() }}</span>
                </button>
                <button type="button"
                        role="tab"
                        id="tab-exceptions"
                        aria-controls="panel-exceptions"
                        :aria-selected="(tab === 'exceptions').toString()"
                        @click="tab = 'exceptions'"
                        class="relative -mb-px rounded-t-lg px-4 py-3 text-sm font-bold transition"
                        :class="tab === 'exceptions' ? 'border-b-2 border-[#2D82C4] text-[#2D82C4]' : 'border-b-2 border-transparent text-[#52637A] hover:text-[#2D82C4]'">
                    Ngoại lệ lịch trình
                    <span class="ml-1.5 rounded-full bg-[#EAF4FB] px-2 py-0.5 text-[11px] font-bold text-[#23689C]">{{ $exceptions->count() }}</span>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             PANEL: CA CỐ ĐỊNH
             ══════════════════════════════════════════════════════════ --}}
        <div id="panel-schedule" role="tabpanel" aria-labelledby="tab-schedule" x-show="tab === 'schedule'" x-cloak>
            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Thêm ca --}}
                <section class="lg:col-span-1" aria-labelledby="add-shift-heading">
                    <form method="POST" action="{{ route('specialist.schedule.store') }}"
                          class="rounded-2xl border border-[#D7E8F3] bg-white p-5">
                        @csrf
                        <h2 id="add-shift-heading" class="text-lg font-black text-[#102A43]">Thêm khung ca</h2>
                        <p class="mt-1 text-xs text-[#718096]">
                            Khung ca là thời gian bạn có mặt, không phải thời lượng dịch vụ.
                        </p>

                        <div class="mt-4 space-y-3">
                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Ngày trong tuần *</span>
                                <select name="day_of_week" required class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                    @foreach($weekdayOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('day_of_week') == $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Bắt đầu *</span>
                                    <input type="time" name="start_time" value="{{ old('start_time', '08:00') }}" required
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Kết thúc *</span>
                                    <input type="time" name="end_time" value="{{ old('end_time', '17:00') }}" required
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Nghỉ từ</span>
                                    <input type="time" name="break_start" value="{{ old('break_start', '12:00') }}"
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Nghỉ đến</span>
                                    <input type="time" name="break_end" value="{{ old('break_end', '13:00') }}"
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                            </div>

                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Hiệu lực từ</span>
                                <input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}"
                                       class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                            </label>

                            <input type="hidden" name="is_active" value="1">

                            <button type="submit"
                                    class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                                Thêm ca
                            </button>
                        </div>

                        @if($errors->any())
                            <div role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                {{ $errors->first() }}
                            </div>
                        @endif
                    </form>

                    <div class="mt-4 rounded-2xl border border-[#BFDBFE] bg-[#EAF4FB] p-4 text-xs leading-relaxed text-[#23689C]">
                        <strong class="block text-[#1B5E91]">Quy tắc bắt buộc</strong>
                        <ul class="mt-1.5 list-disc space-y-1 pl-4">
                            <li>Giờ kết thúc phải sau giờ bắt đầu.</li>
                            <li>Giờ nghỉ phải nằm trong khung ca.</li>
                            <li>Hai khung ca trong cùng ngày không được trùng nhau.</li>
                            <li>Sửa lịch không tự hủy lịch hẹn khách đã xác nhận.</li>
                        </ul>
                    </div>
                </section>

                {{-- Bảng ca theo tuần --}}
                <section class="lg:col-span-2" aria-labelledby="weekly-grid-heading">
                    <div class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                        <div class="border-b border-[#E8F1F6] px-5 py-4">
                            <h2 id="weekly-grid-heading" class="text-lg font-black text-[#102A43]">Khung ca theo tuần</h2>
                            <p class="mt-0.5 text-xs text-[#718096]">Chỉ các ca đang áp dụng mới mở khung giờ đặt lịch.</p>
                        </div>

                        <ul class="divide-y divide-[#EEF4F8]">
                            @foreach($weekdayOptions as $day => $dayLabel)
                                @php $daySchedules = $schedulesByDay[$day] ?? collect(); @endphp
                                <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-start">
                                    <span class="w-24 shrink-0 pt-1 text-xs font-black uppercase tracking-wide text-[#52637A]">{{ $dayLabel }}</span>

                                    <div class="flex-1">
                                        @forelse($daySchedules as $schedule)
                                            @php $isActive = $schedule->is_active; @endphp
                                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] px-3 py-2 last:mb-0">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold tabular-nums text-[#102A43]">
                                                        {{ substr($schedule->start_time, 0, 5) }} – {{ substr($schedule->end_time, 0, 5) }}
                                                    </p>
                                                    <p class="text-[11px] text-[#718096]">
                                                        @if($schedule->break_start)
                                                            Nghỉ {{ substr($schedule->break_start, 0, 5) }}–{{ substr($schedule->break_end, 0, 5) }}
                                                        @else
                                                            Không có giờ nghỉ
                                                        @endif
                                                        @if($schedule->effective_from)
                                                            · Từ {{ $schedule->effective_from->format('d/m/Y') }}
                                                        @endif
                                                    </p>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <span class="rounded-full border px-2 py-0.5 text-[11px] font-bold
                                                        {{ $isActive ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                                                        {{ $isActive ? 'Đang áp dụng' : 'Ngừng áp dụng' }}
                                                    </span>

                                                    <form method="POST" action="{{ route('specialist.schedule.update', $schedule) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="day_of_week" value="{{ $schedule->day_of_week }}">
                                                        <input type="hidden" name="start_time" value="{{ substr($schedule->start_time, 0, 5) }}">
                                                        <input type="hidden" name="end_time" value="{{ substr($schedule->end_time, 0, 5) }}">
                                                        <input type="hidden" name="break_start" value="{{ $schedule->break_start ? substr($schedule->break_start, 0, 5) : '' }}">
                                                        <input type="hidden" name="break_end" value="{{ $schedule->break_end ? substr($schedule->break_end, 0, 5) : '' }}">
                                                        <input type="hidden" name="effective_from" value="{{ $schedule->effective_from?->toDateString() }}">
                                                        <input type="hidden" name="is_active" value="{{ $isActive ? 0 : 1 }}">
                                                        <button type="submit"
                                                                class="rounded-lg border border-[#B8D2E4] bg-white px-2.5 py-1.5 text-[11px] font-bold text-[#2D82C4] transition hover:border-[#2D82C4] hover:bg-[#EAF4FB]">
                                                            {{ $isActive ? 'Tạm tắt' : 'Bật lại' }}
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('specialist.schedule.destroy', $schedule) }}"
                                                          onsubmit="return confirm('Xóa khung ca này khỏi lịch làm việc?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="rounded-lg border border-transparent px-2 py-1.5 text-[11px] font-bold text-[#8CA3B8] transition hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                                                            Xóa
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="pt-1.5 text-xs text-[#8CA3B8]">Chưa có ca làm việc.</p>
                                        @endforelse
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             PANEL: NGOẠI LỆ + IMPACT PREVIEW
             ══════════════════════════════════════════════════════════ --}}
        <div id="panel-exceptions" role="tabpanel" aria-labelledby="tab-exceptions" x-show="tab === 'exceptions'" x-cloak>
            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Bước 1–2: chọn loại, ngày giờ, lý do --}}
                <section class="lg:col-span-1" aria-labelledby="exception-form-heading">
                    <form method="POST" action="{{ route('specialist.leaves.preview') }}"
                          class="rounded-2xl border border-[#D7E8F3] bg-white p-5"
                          x-data="{ type: '{{ old('type', $previewType ?? 'DAY_OFF') }}' }">
                        @csrf
                        <h2 id="exception-form-heading" class="text-lg font-black text-[#102A43]">Đăng ký nghỉ / chặn giờ</h2>
                        <p class="mt-1 text-xs text-[#718096]">
                            Bước 1 và 2: chọn loại ngoại lệ, ngày giờ và lý do. Bạn sẽ xem trước các lịch bị ảnh hưởng trước khi lưu.
                        </p>

                        <div class="mt-4 space-y-3">
                            <fieldset>
                                <legend class="text-xs font-bold text-[#334155]">Loại ngoại lệ *</legend>
                                <div class="mt-1.5 grid grid-cols-2 gap-2">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                                           :class="type === 'DAY_OFF' ? 'border-[#2D82C4] bg-[#EAF4FB] text-[#1B5E91]' : 'border-[#D7E8F3] bg-white text-[#52637A] hover:border-[#B8D2E4]'">
                                        <input type="radio" name="type" value="DAY_OFF" x-model="type" class="text-[#2D82C4] focus:ring-[#2D82C4]">
                                        Nghỉ cả ngày
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                                           :class="type === 'TIME_BLOCK' ? 'border-[#2D82C4] bg-[#EAF4FB] text-[#1B5E91]' : 'border-[#D7E8F3] bg-white text-[#52637A] hover:border-[#B8D2E4]'">
                                        <input type="radio" name="type" value="TIME_BLOCK" x-model="type" class="text-[#2D82C4] focus:ring-[#2D82C4]">
                                        Chặn khoảng giờ
                                    </label>
                                </div>
                            </fieldset>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Từ ngày *</span>
                                    <input type="date" name="start_date" required
                                           value="{{ old('start_date', $preview['start_date'] ?? now()->addDay()->toDateString()) }}"
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Đến ngày *</span>
                                    <input type="date" name="end_date" required
                                           value="{{ old('end_date', $preview['end_date'] ?? now()->addDay()->toDateString()) }}"
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-3" x-show="type === 'TIME_BLOCK'" x-cloak>
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Từ giờ</span>
                                    <input type="time" name="start_time" value="{{ old('start_time', $preview['start_time'] ?? '08:00') }}"
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-[#334155]">Đến giờ</span>
                                    <input type="time" name="end_time" value="{{ old('end_time', $preview['end_time'] ?? '10:00') }}"
                                           class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                </label>
                            </div>

                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Lý do *</span>
                                <input type="text" name="reason" required maxlength="255"
                                       value="{{ old('reason', $preview['reason'] ?? '') }}"
                                       placeholder="Ví dụ: nghỉ phép cá nhân"
                                       class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                            </label>

                            <label class="flex items-center gap-2 text-xs font-semibold text-[#334155]">
                                <input type="checkbox" name="is_emergency" value="1"
                                       @checked(old('is_emergency', $preview['is_emergency'] ?? false))
                                       class="rounded border-[#D7E8F3] text-[#2D82C4] focus:ring-[#2D82C4]">
                                Nghỉ đột xuất
                            </label>

                            <button type="submit"
                                    class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                                Xem lịch bị ảnh hưởng
                            </button>
                        </div>

                        @if($errors->any())
                            <div role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                {{ $errors->first() }}
                            </div>
                        @endif
                    </form>
                </section>

                {{-- Bước 3–4: Impact Preview và xác nhận --}}
                <section class="space-y-4 lg:col-span-2" aria-labelledby="impact-preview-heading">

                    @if(isset($preview) && isset($impactedAppointments))
                        <div class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                            <div class="border-b border-[#E8F1F6] px-5 py-4">
                                <h2 id="impact-preview-heading" class="text-lg font-black text-[#102A43]">Bước 3 — Xem trước tác động</h2>
                                <p class="mt-1 text-xs text-[#718096]">
                                    {{ $previewType === 'TIME_BLOCK' ? 'Chặn khoảng giờ' : 'Nghỉ cả ngày' }}
                                    · {{ \Illuminate\Support\Carbon::parse($preview['start_date'])->format('d/m/Y') }}
                                    @if($preview['end_date'] !== $preview['start_date'])
                                        – {{ \Illuminate\Support\Carbon::parse($preview['end_date'])->format('d/m/Y') }}
                                    @endif
                                    @if($previewType === 'TIME_BLOCK' && ! empty($preview['start_time']))
                                        · {{ $preview['start_time'] }}–{{ $preview['end_time'] }}
                                    @endif
                                    · Lý do: {{ $preview['reason'] }}
                                </p>
                            </div>

                            @if($impactedAppointments->isEmpty())
                                <div class="px-5 py-10 text-center">
                                    <p class="text-sm font-bold text-emerald-700">Không có lịch hẹn nào bị ảnh hưởng.</p>
                                    <p class="mt-1 text-xs text-[#718096]">Bạn có thể xác nhận để lưu ngoại lệ ngay.</p>
                                </div>
                            @else
                                <div class="px-5 pt-4">
                                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                                        <strong>{{ $impactedAppointments->count() }} lịch hẹn</strong> đã xác nhận nằm trong khoảng thời gian này.
                                        Khi lưu, các lịch sẽ được chuyển sang Lễ tân điều phối —
                                        <strong>không tự hủy và không tự đổi chuyên viên</strong>.
                                    </div>
                                </div>

                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full text-left text-sm">
                                        <thead class="bg-[#F7FBFE] text-[11px] uppercase tracking-wide text-[#52637A]">
                                            <tr>
                                                <th class="px-5 py-2.5">Giờ hẹn</th>
                                                <th class="px-5 py-2.5">Khách hàng</th>
                                                <th class="px-5 py-2.5">Dịch vụ</th>
                                                <th class="px-5 py-2.5">Tình trạng</th>
                                                <th class="px-5 py-2.5">Hướng xử lý</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#EEF4F8]">
                                            @foreach($impactedAppointments as $appointment)
                                                <tr>
                                                    <td class="whitespace-nowrap px-5 py-3 font-semibold tabular-nums">
                                                        {{ $appointment->start_at->format('H:i') }}–{{ $appointment->end_at->format('H:i') }}
                                                        <span class="block text-[11px] font-normal text-[#8CA3B8]">{{ $appointment->start_at->format('d/m/Y') }}</span>
                                                    </td>
                                                    <td class="px-5 py-3">{{ $appointment->customer->name }}</td>
                                                    <td class="px-5 py-3 text-[#52637A]">{{ $appointment->service->name }}</td>
                                                    <td class="px-5 py-3">
                                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800">Bị ảnh hưởng</span>
                                                    </td>
                                                    <td class="px-5 py-3 text-[11px] font-semibold text-[#52637A]">Chuyển Lễ tân</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="flex flex-col gap-2 border-t border-[#E8F1F6] px-5 py-4 sm:flex-row sm:justify-end">
                                <a href="{{ route('specialist.schedule', ['tab' => 'exceptions']) }}"
                                   class="inline-flex items-center justify-center rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:border-[#2D82C4] hover:text-[#2D82C4]">
                                    Quay lại chỉnh sửa
                                </a>
                                <form method="POST" action="{{ route('specialist.leaves.store') }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $preview['type'] }}">
                                    <input type="hidden" name="start_date" value="{{ $preview['start_date'] }}">
                                    <input type="hidden" name="end_date" value="{{ $preview['end_date'] }}">
                                    <input type="hidden" name="start_time" value="{{ $preview['start_time'] ?? '' }}">
                                    <input type="hidden" name="end_time" value="{{ $preview['end_time'] ?? '' }}">
                                    <input type="hidden" name="reason" value="{{ $preview['reason'] }}">
                                    <input type="hidden" name="is_emergency" value="{{ ! empty($preview['is_emergency']) ? 1 : 0 }}">
                                    <button type="submit"
                                            class="w-full rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C] sm:w-auto">
                                        Bước 4 — Xác nhận đăng ký
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-[#D7E8F3] bg-white p-10 text-center">
                            <svg class="mx-auto h-10 w-10 text-[#B8D2E4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <p class="mt-3 text-sm font-bold text-[#102A43]">Chưa có bản xem trước</p>
                            <p class="mt-1 text-xs text-[#718096]">
                                Điền thông tin ngoại lệ ở cột bên trái rồi bấm <strong>Xem lịch bị ảnh hưởng</strong>.
                                Hệ thống sẽ liệt kê chính xác các lịch hẹn bị tác động trước khi bạn xác nhận.
                            </p>
                        </div>
                    @endif

                    {{-- Danh sách ngoại lệ đã đăng ký --}}
                    <div class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                        <div class="border-b border-[#E8F1F6] px-5 py-4">
                            <h2 class="text-lg font-black text-[#102A43]">Ngoại lệ đã đăng ký</h2>
                        </div>

                        <ul class="divide-y divide-[#EEF4F8]">
                            @forelse($exceptions as $exception)
                                <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border px-2 py-0.5 text-[11px] font-bold
                                                {{ $exception->type === 'DAY_OFF' ? 'border-purple-200 bg-purple-50 text-purple-700' : 'border-sky-200 bg-sky-50 text-sky-700' }}">
                                                {{ $exception->typeLabel() }}
                                            </span>
                                            @if($exception->is_emergency)
                                                <span class="rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[11px] font-bold text-orange-700">Đột xuất</span>
                                            @endif
                                            <span class="rounded-full border px-2 py-0.5 text-[11px] font-bold
                                                {{ $exception->needsReview() ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                                                {{ $exception->reviewStatusLabel() }}
                                            </span>
                                        </div>

                                        <p class="mt-1.5 text-sm font-bold text-[#102A43]">
                                            {{ $exception->start_date->format('d/m/Y') }}
                                            @if(! $exception->end_date->equalTo($exception->start_date))
                                                – {{ $exception->end_date->format('d/m/Y') }}
                                            @endif
                                            @if($exception->type === 'TIME_BLOCK' && $exception->start_time)
                                                <span class="font-semibold text-[#52637A]">· {{ substr($exception->start_time, 0, 5) }}–{{ substr($exception->end_time, 0, 5) }}</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-xs text-[#718096]">{{ $exception->reason }}</p>

                                        @if($exception->affected_appointments_count > 0)
                                            <p class="mt-1 text-[11px] font-semibold text-amber-800">
                                                {{ $exception->affected_appointments_count }} lịch hẹn đã chuyển Lễ tân điều phối.
                                            </p>
                                        @endif
                                    </div>

                                    @unless($exception->needsReview() && $exception->affected_appointments_count > 0)
                                        <form method="POST" action="{{ route('specialist.leaves.destroy', $exception->id) }}"
                                              onsubmit="return confirm('Hủy ngoại lệ này?');" class="shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg border border-transparent px-2.5 py-1.5 text-[11px] font-bold text-[#8CA3B8] transition hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                                                Hủy
                                            </button>
                                        </form>
                                    @endunless
                                </li>
                            @empty
                                <li class="px-5 py-10 text-center">
                                    <p class="text-sm font-semibold text-[#102A43]">Chưa có ngoại lệ nào.</p>
                                    <p class="mt-1 text-xs text-[#718096]">Nghỉ phép, họp đột xuất hoặc chặn giờ sẽ xuất hiện tại đây.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
