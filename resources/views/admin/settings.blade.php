@extends('layouts.admin')

@section('title', 'Tham số vận hành & cấu hình')

@php
    $groupOrder = ['booking', 'schedule', 'policy', 'appointment', 'review', 'authentication', 'notification', 'khac'];
    $orderedGroups = collect($groupOrder)
        ->filter(fn (string $group): bool => $grouped->has($group))
        ->concat($grouped->keys()->diff($groupOrder))
        ->values();

    $groupIcons = [
        'booking' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z',
        'schedule' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'policy' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'appointment' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3v3h-3v-3z',
        'review' => 'M11.125 21.378a1 1 0 01-1.06 0L4.5 17.917a1 1 0 01-.293-.707V5.5a2 2 0 012-2h11a2 2 0 012 2v11.71a1 1 0 01-.293.707l-5.789 3.461z',
        'authentication' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'notification' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'khac' => 'M5 12h14M5 7h14M5 17h14',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8" x-data="adminPanel()">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ lịch &amp; cấu hình</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Tham số vận hành</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
                Các tham số dưới đây được đọc trực tiếp khi đặt lịch và xử lý đổi/hủy.
                Thay đổi <strong class="font-bold text-[#334E68]">không</strong> điều chỉnh lợi ích của các lịch đã đặt trước đó —
                mỗi lịch giữ nguyên chính sách áp dụng tại thời điểm đặt.
            </p>
        </div>

        <a href="{{ route('admin.settings.mail') }}"
           class="inline-flex items-center gap-2 self-start rounded-lg border border-[#2D82C4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Cấu hình gửi email
        </a>
    </div>

    {{-- ═══════════════ NAV NHÓM + FORM ═══════════════ --}}
    <div class="grid gap-6 xl:grid-cols-4">

        {{-- Tabs nhóm --}}
        <nav class="xl:col-span-1" aria-label="Nhóm tham số">
            <div class="rounded-2xl border border-[#D7E8F3] bg-white p-2 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
                @foreach($orderedGroups as $group)
                    @php
                        $groupSettings = $grouped->get($group);
                        $isFirst = $loop->first;
                    @endphp
                    <button type="button"
                            @click="toggle('group-{{ $group }}')"
                            :aria-expanded="isOpen('group-{{ $group }}').toString()"
                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left transition hover:bg-[#F3F9FD]">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EAF4FB] text-[#2D82C4]" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $groupIcons[$group] ?? $groupIcons['khac'] }}"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-[#102A43]">{{ $groupLabels[$group] ?? $group }}</span>
                            <span class="block text-[11px] text-[#8A9BB0]">{{ $groupSettings->count() }} tham số</span>
                        </span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-[#8A9BB0] transition-transform" :class="{ 'rotate-180': isOpen('group-{{ $group }}') }"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>

                    <div x-show="isOpen('group-{{ $group }}')" x-cloak x-transition.opacity.duration.150ms
                         class="mb-1 ml-4 border-l-2 border-[#EEF4F8] pl-3">
                        @foreach($groupSettings as $setting)
                            <p class="py-1 text-xs font-semibold text-[#52637A]">
                                <span class="font-mono text-[10px] text-[#8A9BB0]">{{ $setting->key }}</span>
                                <span class="mt-0.5 block font-bold text-[#102A43]">{{ $setting->label }}</span>
                            </p>
                        @endforeach
                    </div>

                    @if(! $loop->last)
                        <div class="my-1 border-t border-[#F1F5F9]"></div>
                    @endif
                @endforeach
            </div>
        </nav>

        {{-- Nội dung nhóm --}}
        <div class="space-y-5 xl:col-span-3">
            @forelse($orderedGroups as $group)
                @php $groupSettings = $grouped->get($group); @endphp
                <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black text-[#102A43]">{{ $groupLabels[$group] ?? $group }}</h2>
                            <p class="mt-1 text-xs text-[#718096]">{{ $groupSettings->count() }} tham số trong nhóm này.</p>
                        </div>
                        <span class="rounded-full bg-[#EAF4FB] px-3 py-1 font-mono text-[10px] font-bold uppercase tracking-wide text-[#23689C]">
                            group: {{ $group }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('PUT')

                        @foreach($groupSettings as $setting)
                            @php
                                $isBoolean = $setting->type === 'boolean';
                                $isInteger = $setting->type === 'integer';
                            @endphp
                            <label class="block text-xs font-bold text-[#52637A] {{ $isBoolean ? 'sm:col-span-2' : '' }}">
                                <span class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span>{{ $setting->label }}</span>
                                    <span class="font-mono text-[10px] font-semibold text-[#8A9BB0]">{{ $setting->key }}</span>
                                </span>

                                @if($isBoolean)
                                    <select name="settings[{{ $setting->key }}]"
                                            class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                                        <option value="1" @selected($setting->value === '1')>Đang bật</option>
                                        <option value="0" @selected($setting->value !== '1')>Đang tắt</option>
                                    </select>
                                @else
                                    <input type="{{ $isInteger ? 'number' : 'text' }}"
                                           @if($isInteger) min="0" @endif
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           maxlength="{{ $isInteger ? null : 500 }}"
                                           class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                                @endif
                            </label>
                        @endforeach

                        <div class="flex flex-wrap items-center gap-3 border-t border-[#EEF4F8] pt-4 sm:col-span-2">
                            <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                                Lưu nhóm "{{ $groupLabels[$group] ?? $group }}"
                            </button>
                            <span class="text-[11px] text-[#8A9BB0]">Giá trị cũ và mới được ghi vào nhật ký kiểm toán.</span>
                        </div>
                    </form>
                </section>
            @empty
                <section class="rounded-2xl border border-dashed border-[#D7E8F3] bg-white p-10 text-center">
                    <p class="text-sm font-bold text-[#102A43]">Chưa có tham số nào.</p>
                    <p class="mt-1 text-xs text-[#718096]">Hãy chạy seeder để tạo giá trị mặc định.</p>
                </section>
            @endforelse
        </div>
    </div>

    {{-- ═══════════════ THAM CHIẾU CHÍNH SÁCH HOÀN TIỀN ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Tham chiếu bậc hoàn tiền</h2>
        <p class="mt-1 text-xs text-[#718096]">
            Bảng minh hoạ giao diện — tỷ lệ thực tế phải được phê duyệt và cấu hình trong nhóm tham số chính sách.
            Mốc thời gian không được mâu thuẫn nhau.
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Thời gian trước giờ hẹn</th>
                        <th class="whitespace-nowrap px-4 py-3 font-bold">Tỷ lệ hoàn minh hoạ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @foreach([
                        ['Từ 48 giờ trở lên', 100, 'emerald'],
                        ['Từ 24 giờ đến dưới 48 giờ', 75, 'blue'],
                        ['Từ 6 giờ đến dưới 24 giờ', 50, 'amber'],
                        ['Dưới 6 giờ', 0, 'red'],
                    ] as $tier)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-[#334E68]">{{ $tier[0] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2 w-24 overflow-hidden rounded-full bg-[#EEF4F8]">
                                        <span class="block h-full rounded-full
                                                     {{ $tier[2] === 'emerald' ? 'bg-emerald-500' : ($tier[2] === 'blue' ? 'bg-[#2D82C4]' : ($tier[2] === 'amber' ? 'bg-amber-500' : 'bg-red-500')) }}"
                                              style="width: {{ $tier[1] }}%"></span>
                                    </span>
                                    <strong class="text-sm tabular-nums text-[#102A43]">{{ $tier[1] }}%</strong>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
