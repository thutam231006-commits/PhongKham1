@extends('layouts.staff')

@section('title', 'Lịch cần xử lý')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Điều phối lịch bị ảnh hưởng</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43]">Lịch cần xử lý</h1>
            <p class="mt-1 max-w-2xl text-sm text-[#52637A]">Các lịch bị gắn cờ khi chuyên viên nghỉ, ngừng hoạt động hoặc chưa xếp được phòng. Xử lý xong thì cờ được xóa và khách nhận thông báo.</p>
        </div>
        <a href="{{ route('staff.appointments') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-[#2D82C4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] hover:bg-[#EAF4FB] transition">
            ← Về danh sách lịch
        </a>
    </div>

    @if(session('success'))
        <div role="alert" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    {{-- BỘ ĐẾM --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Còn lại cần xử lý</span>
            <p class="mt-2 text-4xl font-black text-orange-600">{{ $appointments->count() }}</p>
        </div>
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Nhóm nguyên nhân</span>
            <p class="mt-2 text-4xl font-black text-[#2D82C4]">{{ $groupedByReason->count() }}</p>
        </div>
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Chưa xếp phòng</span>
            <p class="mt-2 text-4xl font-black text-orange-600">
                {{ $appointments->filter(fn($a) => $a->consultation_type === 'OFFLINE' && !$a->consultation_room_id)->count() }}
            </p>
        </div>
    </div>

    {{-- BỘ LỌC --}}
    <form method="GET" class="grid gap-3 rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-sm sm:grid-cols-3">
        <label class="text-xs font-bold text-[#52637A]">
            Chuyên viên
            <select name="specialist_id" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                <option value="">Tất cả chuyên viên</option>
                @foreach($specialists as $sp)
                <option value="{{ $sp->id }}" @selected((string)($data['specialist_id']??'') === (string)$sp->id)>{{ $sp->user->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-bold text-[#52637A]">
            Ngày
            <input name="date" type="date" value="{{ $data['date'] ?? '' }}"
                   class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
        </label>
        <div class="flex items-end">
            <button class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition">
                Lọc danh sách
            </button>
        </div>
    </form>

    {{-- DANH SÁCH NHÓM THEO NGUYÊN NHÂN --}}
    @forelse($groupedByReason as $reason => $items)
    <section class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-sm" aria-labelledby="reason-{{ md5($reason) }}">
        <div class="flex items-center justify-between border-b border-orange-100 bg-orange-50/70 px-5 py-4">
            <h2 id="reason-{{ md5($reason) }}" class="text-base font-black text-orange-800">{{ $reason }}</h2>
            <span class="rounded-full bg-orange-500 px-3 py-1 text-xs font-black text-white">{{ $items->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#F7FBFE] text-xs uppercase text-[#52637A]">
                    <tr>
                        <th class="px-5 py-3">Ngày giờ</th>
                        <th class="px-5 py-3">Khách hàng</th>
                        <th class="px-5 py-3">Chuyên viên</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3">Thời điểm gắn cờ</th>
                        <th class="px-5 py-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @foreach($items as $appointment)
                    <tr class="hover:bg-[#F7FBFE] transition">
                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#102A43]">
                            {{ $appointment->start_at->format('d/m/Y H:i') }} – {{ $appointment->end_at->format('H:i') }}
                            <p class="mt-0.5 font-mono text-xs text-[#2D82C4]">{{ $appointment->appointment_code }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-[#102A43]">{{ $appointment->customer->name }}</p>
                            <p class="text-xs text-[#718096]">{{ $appointment->service->name }}</p>
                        </td>
                        <td class="px-5 py-4 text-[#52637A]">{{ $appointment->specialistProfile->user->name }}</td>
                        <td class="px-5 py-4">
                            <x-status-chip type="appointment" :status="$appointment->status"/>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-xs text-[#718096]">
                            {{ $appointment->attention_flagged_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('staff.appointments.detail', $appointment) }}"
                                   class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] hover:bg-[#F7FBFE] transition">
                                    Xem chi tiết
                                </a>
                                {{-- Đổi lịch do phòng khám --}}
                                <button type="button"
                                        onclick="document.getElementById('reschedule-modal-{{ $appointment->id }}').showModal()"
                                        class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white hover:bg-[#23689C] transition">
                                    Điều phối lịch
                                </button>
                                {{-- Hủy do phòng khám --}}
                                <form method="POST" action="{{ route('staff.appointments.cancel', $appointment) }}"
                                      onsubmit="return confirm('Bạn chắc chắn hủy lịch này và thông báo cho khách?')">
                                    @csrf
                                    <input type="hidden" name="reason" value="Phòng khám xử lý sự cố">
                                    <button class="rounded-lg border border-red-300 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50 transition">
                                        Hủy do PK
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Modals điều phối lịch --}}
    @foreach($items as $appointment)
    <dialog id="reschedule-modal-{{ $appointment->id }}"
            class="w-full max-w-lg rounded-2xl border border-[#D7E8F3] bg-white p-6 shadow-2xl backdrop:bg-black/40">
        <h3 class="text-lg font-black text-[#102A43]">Điều phối lịch — {{ $appointment->customer->name }}</h3>
        <p class="mt-1 text-sm text-[#52637A]">{{ $appointment->service->name }} · {{ $appointment->start_at->format('d/m/Y H:i') }}</p>
        <form method="POST" action="{{ route('staff.appointments.reschedule', $appointment) }}" class="mt-5 space-y-4">
            @csrf
            <label class="block text-xs font-bold text-[#52637A]">
                Thời gian mới
                <input type="datetime-local" name="new_start_at" required
                       class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
            </label>
            <label class="block text-xs font-bold text-[#52637A]">
                Chuyên viên (nếu đổi)
                <select name="specialist_id" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                    <option value="">Giữ nguyên chuyên viên cũ</option>
                    @foreach($specialists as $sp)
                    <option value="{{ $sp->id }}">{{ $sp->user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-bold text-[#52637A]">
                Lý do điều phối
                <textarea name="reason" rows="2" required
                          class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]"
                          placeholder="Ghi rõ nguyên nhân để thông báo cho khách…"></textarea>
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        onclick="document.getElementById('reschedule-modal-{{ $appointment->id }}').close()"
                        class="rounded-lg border border-[#B8D2E4] px-4 py-2 text-sm font-bold text-[#52637A] hover:bg-[#F0F7FC] transition">
                    Hủy bỏ
                </button>
                <button type="submit"
                        class="rounded-lg bg-[#2D82C4] px-4 py-2 text-sm font-bold text-white hover:bg-[#23689C] transition">
                    Xác nhận điều phối
                </button>
            </div>
        </form>
    </dialog>
    @endforeach

    @empty
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-14 text-center shadow-sm">
        <svg class="mx-auto h-10 w-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="mt-3 text-sm font-bold text-emerald-700">Tất cả lịch hẹn đang ổn định!</p>
        <p class="mt-1 text-xs text-emerald-600">Không có lịch nào cần xử lý.</p>
    </div>
    @endforelse
</div>
@endsection
