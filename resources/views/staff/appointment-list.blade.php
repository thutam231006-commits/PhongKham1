@extends('layouts.staff')

@section('title', 'Lịch hẹn')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ══════════════════════════════════════════════════════════
         PAGE HEADER
         ══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Danh sách vận hành</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Lịch hẹn</h1>
            <p class="mt-2 max-w-2xl text-sm text-[#52637A]">
                Tra cứu, theo dõi trạng thái và xử lý lịch hẹn của khách hàng.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('staff.appointments.pending') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:border-[#2D82C4] hover:bg-[#EAF4FB]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>
                Mở điều phối lịch
            </a>
            <a href="{{ route('staff.appointments.book') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#23689C]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Đặt lịch hộ
            </a>
        </div>
    </div>

    @if(session('success'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         THẺ SỐ LIỆU — nhìn nhanh tình hình hiện tại
         ══════════════════════════════════════════════════════════ --}}
    <section aria-label="Số liệu tổng quan" class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @php
            $statCards = [
                ['label' => 'Chờ xác nhận', 'value' => $summary['pending'], 'tone' => 'amber', 'href' => route('staff.appointments', ['status' => 'PENDING'])],
                ['label' => 'Lịch hôm nay', 'value' => $summary['today'], 'tone' => 'blue', 'href' => route('staff.appointments', ['date_from' => today()->toDateString(), 'date_to' => today()->toDateString()])],
                ['label' => 'Cần xử lý', 'value' => $summary['attention'], 'tone' => 'orange', 'href' => route('staff.appointments.attention')],
                ['label' => 'Chờ thu tiền mặt', 'value' => $summary['unpaidCash'], 'tone' => 'emerald', 'href' => route('staff.payments')],
                ['label' => 'Tổng lịch hẹn', 'value' => $summary['total'], 'tone' => 'slate', 'href' => route('staff.appointments')],
            ];
            $statTones = [
                'amber'   => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'label' => 'text-amber-700', 'value' => 'text-amber-800'],
                'blue'    => ['bg' => 'bg-[#EAF4FB]', 'border' => 'border-[#B8D2E4]', 'label' => 'text-[#23689C]', 'value' => 'text-[#1B5E91]'],
                'orange'  => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'label' => 'text-orange-700', 'value' => 'text-orange-800'],
                'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'label' => 'text-emerald-700', 'value' => 'text-emerald-800'],
                'slate'   => ['bg' => 'bg-white', 'border' => 'border-[#D7E8F3]', 'label' => 'text-[#52637A]', 'value' => 'text-[#102A43]'],
            ];
        @endphp

        @foreach($statCards as $card)
            @php $tone = $statTones[$card['tone']]; @endphp
            <a href="{{ $card['href'] }}"
               class="group rounded-2xl border {{ $tone['border'] }} {{ $tone['bg'] }} p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-bold uppercase tracking-wide {{ $tone['label'] }}">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-black tabular-nums {{ $tone['value'] }}">{{ number_format($card['value']) }}</p>
            </a>
        @endforeach
    </section>

    {{-- ══════════════════════════════════════════════════════════
         BỘ LỌC — gập/expanded bằng Alpine
         ══════════════════════════════════════════════════════════ --}}
    <section aria-label="Bộ lọc danh sách"
             class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm"
             x-data="{ open: {{ request()->query() ? 'true' : 'false' }} }">

        <div class="flex items-center justify-between gap-3 px-5 py-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#EAF4FB] text-[#2D82C4]" aria-hidden="true">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5.5A2.5 2.5 0 015.5 3H7l2 5-2 1.5A13 13 0 0014.5 17L16 15l5 2v1.5a2.5 2.5 0 01-2.5 2.5C10.492 21 3 13.508 3 5.5z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-black text-[#102A43]">Bộ lọc</h2>
                    <p class="text-xs text-[#718096]">
                        @if(request()->query())
                            Đang áp dụng bộ lọc — {{ $appointments->total() }} lịch phù hợp.
                        @else
                            Chưa áp dụng bộ lọc nào — hiển thị toàn bộ lịch hẹn.
                        @endif
                    </p>
                </div>
            </div>
            <button type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]"
                    :aria-expanded="open.toString()">
                <span x-text="open ? 'Ẩn bộ lọc' : 'Mở bộ lọc'">Mở bộ lọc</span>
                <svg class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>

        <form method="GET" x-show="open" x-transition.opacity.duration.150ms class="border-t border-[#EEF4F8] bg-[#F7FBFE] px-5 py-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Ô tìm kiếm --}}
                <label class="text-xs font-bold text-[#52637A] sm:col-span-2 lg:col-span-4">
                    Tìm lịch hoặc khách
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#8A9BB0]" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                            </svg>
                        </span>
                        <input name="q" value="{{ $data['q'] ?? '' }}" maxlength="100"
                               placeholder="Mã lịch, tên, email hoặc số điện thoại"
                               class="w-full rounded-lg border border-[#B8D2E4] bg-white py-2.5 pl-10 pr-3 text-sm text-[#102A43] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                    </div>
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Từ ngày
                    <input name="date_from" type="date" value="{{ $data['date_from'] ?? '' }}"
                           class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Đến ngày
                    <input name="date_to" type="date" value="{{ $data['date_to'] ?? '' }}"
                           class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Trạng thái
                    <select name="status" class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                        <option value="">Tất cả</option>
                        @foreach(['PENDING' => 'Chờ xác nhận', 'CONFIRMED' => 'Đã xác nhận', 'RESCHEDULE_REQUESTED' => 'Chờ đổi lịch', 'IN_PROGRESS' => 'Đang tư vấn', 'COMPLETED' => 'Hoàn thành', 'NO_SHOW' => 'Vắng mặt', 'CANCELLED_BY_CUSTOMER' => 'Khách đã hủy', 'CANCELLED_BY_CLINIC' => 'Phòng khám hủy'] as $key => $label)
                            <option value="{{ $key }}" @selected(($data['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Chuyên viên
                    <select name="specialist_id" class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                        <option value="">Tất cả</option>
                        @foreach($specialists as $specialist)
                            <option value="{{ $specialist->id }}" @selected((string) ($data['specialist_id'] ?? '') === (string) $specialist->id)>{{ $specialist->user->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Dịch vụ
                    <select name="service_id" class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                        <option value="">Tất cả</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" @selected((string) ($data['service_id'] ?? '') === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Trạng thái thanh toán
                    <select name="payment_status" class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                        <option value="">Tất cả</option>
                        @foreach(['UNPAID' => 'Chưa thanh toán', 'PAID' => 'Đã thanh toán', 'REFUND_PENDING' => 'Chờ hoàn tiền', 'REFUNDED' => 'Đã hoàn tiền'] as $key => $label)
                            <option value="{{ $key }}" @selected(($data['payment_status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-bold text-[#52637A]">
                    Phương thức
                    <select name="payment_method" class="mt-1.5 w-full rounded-lg border border-[#B8D2E4] bg-white text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                        <option value="">Tất cả</option>
                        @foreach(['CASH' => 'Tiền mặt', 'BANK_QR' => 'Chuyển khoản QR'] as $key => $label)
                            <option value="{{ $key }}" @selected(($data['payment_method'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            {{-- Chuyển trạng thái nhanh --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-[#E4EFF7] pt-4">
                <label class="flex items-center gap-2 text-xs font-bold text-[#52637A]">
                    <input type="checkbox" name="attention" value="1" @checked(request()->boolean('attention'))
                           class="h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                    Cần xử lý
                </label>
                <label class="flex items-center gap-2 text-xs font-bold text-[#52637A]">
                    <input type="checkbox" name="unpaid_cash" value="1" @checked(request()->boolean('unpaid_cash'))
                           class="h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                    Đã hoàn thành — chưa thu tiền
                </label>

                <div class="ml-auto flex items-center gap-2">
                    @if(request()->query())
                        <a href="{{ route('staff.appointments') }}"
                           class="rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F0F7FC]">
                            Xoá bộ lọc
                        </a>
                    @endif
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#23689C]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5.5A2.5 2.5 0 015.5 3H7l2 5-2 1.5A13 13 0 0014.5 17L16 15l5 2v1.5a2.5 2.5 0 01-2.5 2.5C10.492 21 3 13.508 3 5.5z"/>
                        </svg>
                        Lọc danh sách
                    </button>
                </div>
            </div>
        </form>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         DANH SÁCH LỊCH
         ══════════════════════════════════════════════════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E8F1F6] px-5 py-4">
            <div>
                <h2 class="text-lg font-black text-[#102A43]">Tất cả lịch hẹn</h2>
                <p class="mt-0.5 text-xs text-[#718096]">{{ $appointments->total() }} lịch phù hợp bộ lọc.</p>
            </div>
            <a href="{{ route('staff.appointments.pending') }}"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-[#2D82C4] hover:underline">
                Sang điều phối
                <span aria-hidden="true">→</span>
            </a>
        </div>

        {{-- Bảng (desktop) --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#EDF6FC] text-xs uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="px-5 py-3 font-bold">Lịch hẹn</th>
                        <th class="px-5 py-3 font-bold">Khách hàng</th>
                        <th class="px-5 py-3 font-bold">Chuyên viên</th>
                        <th class="px-5 py-3 font-bold">Thời gian</th>
                        <th class="px-5 py-3 font-bold">Hình thức</th>
                        <th class="px-5 py-3 font-bold">Trạng thái</th>
                        <th class="px-5 py-3 font-bold">Thanh toán</th>
                        <th class="px-5 py-3 font-bold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($appointments as $appointment)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            {{-- Mã lịch + dịch vụ --}}
                            <td class="px-5 py-4">
                                <p class="font-mono text-xs font-bold text-[#2D82C4]">{{ $appointment->appointment_code }}</p>
                                <p class="mt-0.5 max-w-[11rem] text-xs font-semibold text-[#52637A]">{{ $appointment->service->name }}</p>
                                @if($appointment->needs_attention)
                                    <span class="mt-1.5 inline-flex items-center gap-1 rounded-full border border-orange-300 bg-orange-50 px-2 py-0.5 text-[11px] font-bold text-[#C2410C]">
                                        <span aria-hidden="true">⚠</span> Cần xử lý
                                    </span>
                                @endif
                            </td>

                            {{-- Khách hàng --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                <p class="mt-0.5 text-xs text-[#718096]">{{ $appointment->customer->email }}</p>
                                @if($appointment->customer->phone)
                                    <p class="text-xs text-[#718096]">{{ $appointment->customer->phone }}</p>
                                @endif
                            </td>

                            {{-- Chuyên viên --}}
                            <td class="px-5 py-4 text-sm text-[#334E68]">
                                {{ $appointment->specialistProfile->user->name }}
                            </td>

                            {{-- Thời gian --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="font-semibold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y') }}</p>
                                <p class="text-xs font-bold tabular-nums text-[#2D82C4]">{{ $appointment->start_at->format('H:i') }} – {{ $appointment->end_at->format('H:i') }}</p>
                            </td>

                            {{-- Hình thức --}}
                            <td class="px-5 py-4 text-xs">
                                <p class="font-semibold text-[#334E68]">
                                    {{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : ($appointment->consultationRoom?->name ?? 'Trực tiếp') }}
                                </p>
                                @if($appointment->consultation_type === 'ONLINE' && $appointment->meeting_url)
                                    <a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener" class="mt-1 inline-block font-bold text-[#2D82C4] hover:underline">Vào phòng họp →</a>
                                @elseif($appointment->consultation_type === 'OFFLINE' && ! $appointment->consultation_room_id)
                                    <span class="mt-1 inline-block font-bold text-[#C2410C]">Chưa xếp phòng</span>
                                @endif
                            </td>

                            {{-- Trạng thái --}}
                            <td class="px-5 py-4">
                                <x-status-chip type="appointment" :status="$appointment->status" />
                            </td>

                            {{-- Thanh toán --}}
                            <td class="px-5 py-4">
                                <x-status-chip type="payment" :status="$appointment->payment_status" />
                                <p class="mt-1 text-[11px] text-[#718096]">{{ $appointment->payment_method === 'BANK_QR' ? 'Chuyển khoản QR' : 'Tiền mặt' }}</p>
                            </td>

                            {{-- Thao tác --}}
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('staff.appointments.detail', $appointment) }}"
                                       class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">Xem chi tiết</a>

                                    @if($appointment->status === 'COMPLETED' && $appointment->payment_method === 'CASH' && $appointment->payment_status === 'UNPAID')
                                        <x-modal-confirm id="list-collect-{{ $appointment->id }}"
                                                       title="Xác nhận đã thu tiền mặt"
                                                       description="Xác nhận đã thu {{ number_format((int) $appointment->price, 0, ',', '.') }} VNĐ tiền mặt cho lịch {{ $appointment->appointment_code }}. Người thu được ghi là nhân viên đang đăng nhập. Chỉ thực hiện một lần."
                                                       confirmLabel="Xác nhận đã thu tiền"
                                                       :action="route('staff.payments.collect', $appointment->id)">
                                            <x-slot:trigger>
                                                <button type="button" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100">Xác nhận đã thu tiền</button>
                                            </x-slot:trigger>
                                            <div>
                                                <label for="list-collect-reference-{{ $appointment->id }}" class="block text-sm font-medium text-[#1A202C]">Mã tham chiếu</label>
                                                <input id="list-collect-reference-{{ $appointment->id }}" type="text" name="payment_reference" maxlength="100"
                                                       class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                            </div>
                                        </x-modal-confirm>
                                    @endif

                                    @if($appointment->status === 'PENDING')
                                        <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}">
                                            @csrf
                                            <button class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">Xác nhận</button>
                                        </form>
                                        <a href="{{ route('staff.appointments.pending', ['date' => $appointment->start_at->toDateString(), 'view' => 'day']) }}"
                                           class="rounded-lg border border-[#2D82C4] px-3 py-2 text-xs font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">Điều phối</a>
                                    @elseif($appointment->status === 'CONFIRMED' && $appointment->consultation_type === 'OFFLINE' && ! $appointment->checked_in_at)
                                        <a href="{{ route('staff.checkin', ['date' => $appointment->start_at->toDateString()]) }}"
                                           class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">Check-in</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#EAF4FB] text-[#6FA6CF]" aria-hidden="true">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                                    </svg>
                                </span>
                                <p class="mt-3 text-sm font-bold text-[#102A43]">Không có lịch hẹn phù hợp bộ lọc.</p>
                                <p class="mt-1 text-xs text-[#718096]">Thử xoá bộ lọc hoặc chọn khoảng thời gian khác.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Card list (mobile) --}}
        <div class="divide-y divide-[#EEF4F8] md:hidden">
            @forelse($appointments as $appointment)
                <article class="px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-bold text-[#2D82C4]">{{ $appointment->appointment_code }}</p>
                            <p class="mt-0.5 truncate text-sm font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                            <p class="truncate text-xs text-[#718096]">{{ $appointment->service->name }}</p>
                        </div>
                        <x-status-chip type="appointment" :status="$appointment->status" />
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                        <div>
                            <dt class="font-bold text-[#8A9BB0]">Thời gian</dt>
                            <dd class="mt-0.5 font-semibold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold text-[#8A9BB0]">Chuyên viên</dt>
                            <dd class="mt-0.5 font-semibold text-[#102A43]">{{ $appointment->specialistProfile->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold text-[#8A9BB0]">Hình thức</dt>
                            <dd class="mt-0.5 text-[#52637A]">{{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : ($appointment->consultationRoom?->name ?? 'Trực tiếp') }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold text-[#8A9BB0]">Thanh toán</dt>
                            <dd class="mt-0.5"><x-status-chip type="payment" :status="$appointment->payment_status" /></dd>
                        </div>
                    </dl>

                    @if($appointment->needs_attention)
                        <p class="mt-3 inline-flex items-center gap-1 rounded-full border border-orange-300 bg-orange-50 px-2 py-0.5 text-[11px] font-bold text-[#C2410C]">
                            <span aria-hidden="true">⚠</span> Cần xử lý
                        </p>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('staff.appointments.detail', $appointment) }}"
                           class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A]">Xem chi tiết</a>
                        @if($appointment->status === 'PENDING')
                            <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}">
                                @csrf
                                <button class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white">Xác nhận</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-5 py-14 text-center">
                    <p class="text-sm font-bold text-[#102A43]">Không có lịch hẹn phù hợp bộ lọc.</p>
                    <p class="mt-1 text-xs text-[#718096]">Thử xoá bộ lọc hoặc chọn khoảng thời gian khác.</p>
                </div>
            @endforelse
        </div>

        @if($appointments->hasPages())
            <div class="border-t border-[#E8F1F6] px-5 py-4">
                {{ $appointments->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
