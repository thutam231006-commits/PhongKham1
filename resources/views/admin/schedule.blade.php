@extends('layouts.admin')

@section('title', 'Ngày nghỉ phòng khám')

@php
    $typeLabels = [
        'HOLIDAY' => 'Ngày lễ phòng khám',
        'DAY_OFF' => 'Nghỉ cả ngày',
        'TIME_BLOCK' => 'Chặn một khoảng giờ',
    ];
    $typeIcons = [
        'HOLIDAY' => '🎉',
        'DAY_OFF' => '📅',
        'TIME_BLOCK' => '⏱',
    ];
    $totalExceptions = (int) $typeCounts->sum();
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ lịch &amp; cấu hình</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Ngày nghỉ phòng khám</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Ngày nghỉ chỉ <strong class="font-bold text-[#334E68]">ngừng cung cấp khung giờ mới</strong> — lịch đã xác nhận
            không tự động bị hủy. Hệ thống hiển thị danh sách xung đột để Lễ tân điều phối.
        </p>
    </div>

    {{-- ═══════════════ KPI ═══════════════ --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Tổng ngoại lệ" :value="$totalExceptions" unit="bản ghi"
                           hint="Toàn bộ ngày nghỉ &amp; chặn giờ" tone="blue"
                           icon="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
        <x-admin.stat-card label="Ngày lễ" :value="(int) ($typeCounts['HOLIDAY'] ?? 0)" unit="bản ghi"
                           hint="Áp dụng toàn phòng khám" tone="amber"
                           icon="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        <x-admin.stat-card label="Nghỉ cả ngày" :value="(int) ($typeCounts['DAY_OFF'] ?? 0)" unit="bản ghi"
                           hint="Theo từng chuyên viên" tone="slate"
                           icon="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        <x-admin.stat-card label="Chờ Lễ tân điều phối" :value="$pendingReview->count()" unit="yêu cầu"
                           :hint="$pendingReview->isNotEmpty() ? 'Cần xử lý lịch bị ảnh hưởng' : 'Không có yêu cầu tồn'"
                           :tone="$pendingReview->isNotEmpty() ? 'red' : 'emerald'"
                           icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </div>

    {{-- ═══════════════ YÊU CẦU CHỜ ĐIỀU PHỐI ═══════════════ --}}
    @if($pendingReview->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-orange-200 bg-orange-50/40 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
            <div class="border-b border-orange-200 px-5 py-4">
                <h2 class="text-base font-black text-[#102A43]">Yêu cầu nghỉ chờ điều phối</h2>
                <p class="mt-1 text-xs text-[#8A6A3F]">
                    Chuyên viên đã gửi yêu cầu nghỉ. Lịch đã xác nhận trong khoảng nghỉ cần được chuyển chuyên viên hoặc liên hệ khách.
                </p>
            </div>

            <ul class="divide-y divide-orange-100">
                @foreach($pendingReview as $pending)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3.5">
                        <span class="rounded-md bg-white px-2 py-1 text-[11px] font-extrabold uppercase tracking-wide text-[#C2410C] ring-1 ring-inset ring-orange-200">
                            {{ $typeLabels[$pending->type] ?? $pending->type }}
                        </span>
                        <p class="min-w-0 flex-1 text-sm font-bold text-[#102A43]">
                            {{ $pending->specialistProfile?->user?->name ?? 'Toàn phòng khám' }}
                            <span class="font-semibold text-[#52637A]">
                                · {{ $pending->start_date?->format('d/m/Y') }}
                                @if($pending->end_date && ! $pending->end_date->equalTo($pending->start_date))
                                    – {{ $pending->end_date->format('d/m/Y') }}
                                @endif
                                @if($pending->start_time)
                                    · {{ substr((string) $pending->start_time, 0, 5) }}–{{ substr((string) $pending->end_time, 0, 5) }}
                                @endif
                            </span>
                        </p>
                        <p class="text-xs text-[#8A6A3F]">{{ $pending->reason }}</p>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-[#C2410C] ring-1 ring-inset ring-orange-200">
                            {{ $pending->affected_appointments_count }} lịch ảnh hưởng
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ═══════════════ TẠO NGOẠI LỆ ═══════════════ --}}
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] xl:col-span-1">
            <h2 class="text-lg font-black text-[#102A43]">Thêm ngày nghỉ</h2>
            <p class="mt-1 text-xs text-[#718096]">Hệ thống kiểm tra ngày hợp lệ và ngày nghỉ trùng trước khi lưu.</p>

            <form method="POST" action="{{ route('admin.schedule.store') }}" class="mt-4 space-y-3.5">
                @csrf

                <label class="block text-xs font-bold text-[#52637A]">
                    Phạm vi áp dụng
                    <select name="specialist_profile_id" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                        <option value="">Toàn phòng khám (ngày lễ)</option>
                        @foreach($specialists as $specialist)
                            <option value="{{ $specialist->id }}" @selected(old('specialist_profile_id') == $specialist->id)>
                                {{ $specialist->user?->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-[10px] font-semibold text-[#8A9BB0]">
                        Chọn "Toàn phòng khám" cho ngày lễ; chọn chuyên viên cho nghỉ cá nhân.
                    </span>
                </label>

                <label class="block text-xs font-bold text-[#52637A]">
                    Loại nghỉ <span class="text-red-500" aria-hidden="true">*</span>
                    <select name="type" required class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                        @foreach($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', 'HOLIDAY') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-xs font-bold text-[#52637A]">
                        Từ ngày <span class="text-red-500" aria-hidden="true">*</span>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required
                               class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    </label>
                    <label class="block text-xs font-bold text-[#52637A]">
                        Đến ngày <span class="text-red-500" aria-hidden="true">*</span>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required
                               class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-xs font-bold text-[#52637A]">
                        Giờ bắt đầu
                        <input type="time" name="start_time" value="{{ old('start_time') }}"
                               class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    </label>
                    <label class="block text-xs font-bold text-[#52637A]">
                        Giờ kết thúc
                        <input type="time" name="end_time" value="{{ old('end_time') }}"
                               class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    </label>
                </div>
                <p class="-mt-2 text-[10px] font-semibold text-[#8A9BB0]">
                    Chỉ bắt buộc khi chọn "Chặn một khoảng giờ".
                </p>

                <label class="block text-xs font-bold text-[#52637A]">
                    Lý do / ghi chú nội bộ <span class="text-red-500" aria-hidden="true">*</span>
                    <textarea name="reason" rows="2" required maxlength="255"
                              class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm"
                              placeholder="Ví dụ: Nghỉ Quốc khánh 2/9">{{ old('reason') }}</textarea>
                </label>

                <label class="flex items-start gap-2 text-xs font-bold text-[#52637A]">
                    <input type="checkbox" name="is_emergency" value="1" @checked(old('is_emergency'))
                           class="mt-0.5 h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                    <span>
                        Nghỉ đột xuất
                        <span class="block font-semibold text-[#8A9BB0]">Ưu tiên cảnh báo Lễ tân điều phối ngay.</span>
                    </span>
                </label>

                <div class="rounded-lg bg-[#F3F9FD] px-3 py-2.5">
                    <p class="text-[11px] font-bold text-[#23689C]">Xem trước ảnh hưởng</p>
                    <p class="mt-1 text-[11px] leading-relaxed text-[#52637A]">
                        Đang có <strong class="font-bold text-[#102A43]">{{ $upcoming->count() }}</strong> lịch sắp tới trong hệ thống.
                        Sau khi lưu, các lịch nằm trong khoảng nghỉ sẽ được đánh dấu "cần xử lý" để Lễ tân điều phối.
                    </p>
                </div>

                <button type="submit" class="w-full rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lưu ngoại lệ lịch
                </button>
            </form>
        </section>

        {{-- ═══════════════ DANH SÁCH NGOẠI LỆ ═══════════════ --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)] xl:col-span-2">
            <div class="border-b border-[#E8F1F6] px-5 py-4">
                <h2 class="text-base font-black text-[#102A43]">Danh sách ngày nghỉ</h2>

                <form method="GET" class="mt-3 flex flex-wrap items-end gap-2">
                    <label class="text-xs font-bold text-[#52637A]">
                        Loại
                        <select name="type" class="mt-1 rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                            <option value="">Tất cả</option>
                            @foreach($typeLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-xs font-bold text-[#52637A]">
                        Phạm vi
                        <select name="scope" class="mt-1 rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                            <option value="">Tất cả</option>
                            <option value="clinic" @selected(request('scope') === 'clinic')>Toàn phòng khám</option>
                            <option value="specialist" @selected(request('scope') === 'specialist')>Theo chuyên viên</option>
                        </select>
                    </label>

                    <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#23689C]">
                        Lọc
                    </button>

                    @if(request()->query())
                        <a href="{{ route('admin.schedule') }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                            Xoá lọc
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[860px] text-left text-sm">
                    <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                        <tr>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Phạm vi</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Loại</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Thời gian</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Lý do</th>
                            <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF4F8]">
                        @forelse($exceptions as $exception)
                            <tr class="align-top transition hover:bg-[#F3F9FD]">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-[#102A43]">
                                        {{ $exception->specialistProfile?->user?->name ?? 'Toàn phòng khám' }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-[#8A9BB0]">
                                        Tạo bởi {{ $exception->creator?->name ?? 'hệ thống' }}
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-[#F3F9FD] px-2 py-1 text-[11px] font-bold text-[#23689C] ring-1 ring-inset ring-[#D7E8F3]">
                                        <span aria-hidden="true">{{ $typeIcons[$exception->type] ?? '•' }}</span>
                                        {{ $typeLabels[$exception->type] ?? $exception->type }}
                                    </span>
                                    @if($exception->is_emergency)
                                        <span class="mt-1 block text-[10px] font-extrabold uppercase tracking-wide text-orange-700">Đột xuất</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-semibold text-[#102A43]">
                                        {{ $exception->start_date?->format('d/m/Y') }}
                                        @if($exception->end_date && ! $exception->end_date->equalTo($exception->start_date))
                                            – {{ $exception->end_date->format('d/m/Y') }}
                                        @endif
                                    </p>
                                    @if($exception->start_time)
                                        <p class="text-[11px] font-bold tabular-nums text-[#2D82C4]">
                                            {{ substr((string) $exception->start_time, 0, 5) }}–{{ substr((string) $exception->end_time, 0, 5) }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <p class="max-w-[18rem] text-xs leading-relaxed text-[#52637A]">{{ $exception->reason }}</p>
                                </td>

                                <td class="px-5 py-4">
                                    @if($exception->needsReview())
                                        <span class="inline-flex items-center gap-1 rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-[#C2410C]">
                                            <span aria-hidden="true">⚠</span> Chờ điều phối
                                        </span>
                                        <p class="mt-1 text-[10px] font-semibold text-[#8A6A3F]">
                                            {{ $exception->affected_appointments_count }} lịch ảnh hưởng
                                        </p>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-800">
                                            <span aria-hidden="true">✓</span> Đã ghi nhận
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <p class="text-sm font-bold text-[#102A43]">Chưa có ngày nghỉ nào.</p>
                                    <p class="mt-1 text-xs text-[#718096]">Thêm ngày lễ hoặc nghỉ riêng ở biểu mẫu bên trái.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($exceptions->hasPages())
                <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $exceptions->links() }}</div>
            @endif
        </section>
    </div>

    {{-- ═══════════════ LỊCH SẮP TỚI (XEM TRƯỚC ẢNH HƯỞNG) ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="border-b border-[#E8F1F6] px-5 py-4">
            <h2 class="text-base font-black text-[#102A43]">Lịch sắp tới có thể bị ảnh hưởng</h2>
            <p class="mt-1 text-xs text-[#718096]">
                Đối chiếu với khoảng nghỉ trước khi lưu. Lịch đã xác nhận không bị hủy tự động.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[900px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Mã lịch</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Khách hàng</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Chuyên viên</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thời gian</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($upcoming as $appointment)
                        <tr class="transition hover:bg-[#F3F9FD]">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-black text-[#2D82C4]">
                                {{ $appointment->appointment_code }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-bold text-[#102A43]">{{ $appointment->customer?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-[#334E68]">{{ $appointment->specialistProfile?->user?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <p class="font-semibold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y') }}</p>
                                <p class="text-xs font-bold tabular-nums text-[#2D82C4]">{{ $appointment->start_at->format('H:i') }}–{{ $appointment->end_at->format('H:i') }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5"><x-status-chip :status="$appointment->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-xs text-[#718096]">
                                Không có lịch sắp tới trong hệ thống.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
