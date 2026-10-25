@extends('layouts.staff')

@section('title', 'Xếp phòng tư vấn')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ══════════════════════════════════════════════════════════
         PAGE HEADER
         ══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Điều phối lịch tư vấn</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Xếp phòng tư vấn</h1>
            <p class="mt-2 max-w-2xl text-sm text-[#52637A]">
                Phòng khám có {{ $capacity['total'] }} phòng tư vấn. Lễ tân theo dõi tải phòng theo ngày và xếp phòng
                cho các lịch trùng khung giờ để tránh chờ đợi.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}"
                       class="rounded-lg border border-[#B8D2E4] bg-white px-3 py-2.5 text-sm focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                <button class="rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">Xem</button>
            </form>
            <form method="POST" action="{{ route('staff.appointments.rooms.auto-assign') }}">
                @csrf
                <button class="inline-flex items-center gap-2 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#23689C]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Xếp phòng tự động
                </button>
            </form>
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
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12c4.97 0 9-4.03 9-9s-4.03-9-9-9-9 4.03-9 9 4.03 9 9 9z"/></svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         THẺ SỐ LIỆU — TẢI PHÒNG
         ══════════════════════════════════════════════════════════ --}}
    @php
        $totalBooked = collect($board)->sum('booked');
        $busiest = collect($board)->sortByDesc('booked')->first();
    @endphp
    <section aria-label="Tải phòng trong ngày" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-[#52637A]">Tổng số phòng</p>
            <p class="mt-2 text-3xl font-black tabular-nums text-[#102A43]">{{ $capacity['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-[#52637A]">Lịch đã xếp phòng</p>
            <p class="mt-2 text-3xl font-black tabular-nums text-[#2D82C4]">{{ $totalBooked }}</p>
        </div>
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-[#52637A]">Chờ xếp phòng</p>
            <p class="mt-2 text-3xl font-black tabular-nums {{ $unassigned->isEmpty() ? 'text-emerald-700' : 'text-orange-600' }}">{{ $unassigned->count() }}</p>
        </div>
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-[#52637A]">Phòng tải nặng nhất</p>
            <p class="mt-2 text-3xl font-black tabular-nums text-[#102A43]">{{ $busiest['booked'] ?? 0 }}</p>
            @if($busiest)
                <p class="mt-0.5 truncate text-xs text-[#718096]">{{ $busiest['name'] }}</p>
            @endif
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         BẢNG XẾP PHÒNG THEO PHÒNG
         ══════════════════════════════════════════════════════════ --}}
    <section aria-label="Bảng xếp phòng" class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E8F1F6] px-5 py-4">
            <div>
                <h2 class="text-lg font-black text-[#102A43]">Lịch theo phòng — {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</h2>
                <p class="mt-0.5 text-xs text-[#718096]">Mỗi phòng chỉ phục vụ một buổi tư vấn tại một thời điểm.</p>
            </div>
            <a href="{{ route('staff.appointments') }}"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-[#2D82C4] hover:underline">
                Về danh sách lịch
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="grid gap-px bg-[#E8F1F6] sm:grid-cols-2 lg:grid-cols-4">
            @forelse($board as $room)
                <section class="flex flex-col bg-white p-4" aria-labelledby="room-{{ $room['id'] }}">
                    <div class="flex items-start justify-between gap-2 border-b border-[#EEF4F8] pb-3">
                        <div class="min-w-0">
                            <h3 id="room-{{ $room['id'] }}" class="truncate text-sm font-black text-[#102A43]">{{ $room['name'] }}</h3>
                            <p class="mt-0.5 text-[11px] text-[#718096]">{{ $room['booked'] }} lịch trong ngày</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-black {{ $room['booked'] === 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-[#EAF4FB] text-[#2D82C4]' }}">
                            {{ $room['booked'] === 0 ? 'Trống' : $room['booked'].' lịch' }}
                        </span>
                    </div>

                    <ol class="mt-3 flex-1 space-y-2">
                        @forelse($room['appointments'] as $appointment)
                            <li class="rounded-lg border border-[#D7E8F3] bg-[#F7FBFE] p-2.5">
                                <p class="text-xs font-black tabular-nums text-[#2D82C4]">
                                    {{ $appointment->start_at->format('H:i') }} – {{ $appointment->end_at->format('H:i') }}
                                </p>
                                <p class="mt-0.5 truncate text-xs font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                <p class="truncate text-[11px] text-[#718096]">{{ $appointment->specialistProfile->user->name }}</p>
                                <div class="mt-1.5"><x-status-chip type="appointment" :status="$appointment->status" /></div>
                            </li>
                        @empty
                            <li class="rounded-lg border border-dashed border-emerald-200 bg-emerald-50/50 px-2.5 py-4 text-center text-[11px] font-semibold text-emerald-700">
                                Chưa có lịch
                            </li>
                        @endforelse
                    </ol>
                </section>
            @empty
                <div class="col-span-full bg-white px-5 py-14 text-center">
                    <p class="text-sm font-bold text-[#102A43]">Chưa thiết lập phòng tư vấn.</p>
                    <p class="mt-1 text-xs text-[#718096]">Liên hệ quản trị viên để bổ sung phòng.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════
         LỊCH CHƯA CÓ PHÒNG — LỄ TÂN XẾP TAY
         ══════════════════════════════════════════════════════════ --}}
    <section aria-label="Lịch chưa xếp phòng" class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-orange-100 bg-orange-50/70 px-5 py-4">
            <div>
                <h2 class="text-lg font-black text-orange-900">Lịch trực tiếp chưa có phòng</h2>
                <p class="mt-0.5 text-xs text-orange-700">Xếp phòng thủ công khi hệ thống không tự phân bổ được.</p>
            </div>
            <span class="rounded-full bg-orange-500 px-3 py-1 text-xs font-black text-white">{{ $unassigned->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#F7FBFE] text-xs uppercase tracking-wide text-[#52637A]">
                    <tr>
                        <th class="px-5 py-3 font-bold">Thời gian</th>
                        <th class="px-5 py-3 font-bold">Khách hàng</th>
                        <th class="px-5 py-3 font-bold">Chuyên viên</th>
                        <th class="px-5 py-3 font-bold">Dịch vụ</th>
                        <th class="px-5 py-3 font-bold">Phòng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($unassigned as $appointment)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="font-semibold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y') }}</p>
                                <p class="text-xs font-bold tabular-nums text-[#2D82C4]">{{ $appointment->start_at->format('H:i') }} – {{ $appointment->end_at->format('H:i') }}</p>
                                <p class="mt-1 font-mono text-[11px] text-[#718096]">{{ $appointment->appointment_code }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                <p class="mt-0.5 text-xs text-[#718096]">{{ $appointment->customer->phone ?? $appointment->customer->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-[#334E68]">{{ $appointment->specialistProfile->user->name }}</td>
                            <td class="px-5 py-4 text-xs text-[#52637A]">{{ $appointment->service->name }}</td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('staff.appointments.room.assign', $appointment) }}" class="flex gap-2">
                                    @csrf
                                    <select name="consultation_room_id" required
                                            class="rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                        <option value="">Chọn phòng…</option>
                                        @foreach($board as $room)
                                            <option value="{{ $room['id'] }}">{{ $room['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <button class="shrink-0 rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">Xếp</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="text-sm font-bold text-emerald-700">Tất cả lịch trực tiếp đã được xếp phòng.</p>
                                <p class="mt-1 text-xs text-[#718096]">Không có lịch nào đang chờ phòng.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
