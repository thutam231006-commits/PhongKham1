{{--
    SCR-STF-07 — Điều phối lịch bị ảnh hưởng (FR-SCHEDULE-005)
    ===================================================================================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-STF-07

    Danh sách lịch có cờ "cần xử lý", nhóm theo nguyên nhân. Mỗi lịch có thể
    "Đổi lịch do phòng khám" hoặc "Hủy do phòng khám"; xử lý xong thì cờ biến mất
    và khách được thông báo.
--}}

@extends('layouts.app')

@section('title', 'Điều phối lịch bị ảnh hưởng')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 border-b border-[#D7E8F3] pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-[#6FA6CF]">Lịch bị ảnh hưởng</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-[#102A43]">Điều phối lịch bị ảnh hưởng</h1>
            <p class="mt-2 max-w-2xl text-sm text-[#52637A]">Các lịch bị gắn cờ khi chuyên viên nghỉ, ngừng hoạt động hoặc chưa xếp được phòng. Xử lý xong thì cờ được xóa và khách nhận thông báo.</p>
        </div>
        <a href="{{ route('staff.appointments') }}" class="rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] hover:bg-[#EAF4FB]">Về danh sách lịch</a>
    </div>

    @if(session('success'))<div role="alert" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-3" aria-label="Bộ đếm còn lại">
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-4"><span class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Còn lại cần xử lý</span><p class="mt-2 text-3xl font-black text-[#C2410C]">{{ $appointments->count() }}</p></div>
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-4"><span class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Nhóm nguyên nhân</span><p class="mt-2 text-3xl font-black text-[#2D82C4]">{{ $groupedByReason->count() }}</p></div>
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-4"><span class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Lịch chưa xếp phòng</span><p class="mt-2 text-3xl font-black text-[#C2410C]">{{ $appointments->filter(fn ($appointment) => $appointment->consultation_type === 'OFFLINE' && ! $appointment->consultation_room_id)->count() }}</p></div>
    </section>

    <form method="GET" class="grid gap-3 rounded-xl border border-[#D7E8F3] bg-white p-4 sm:grid-cols-3">
        <label class="text-xs font-bold text-[#52637A]">Chuyên viên<select name="specialist_id" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm"><option value="">Tất cả chuyên viên</option>@foreach($specialists as $specialist)<option value="{{ $specialist->id }}" @selected((string) ($data['specialist_id'] ?? '') === (string) $specialist->id)>{{ $specialist->user->name }}</option>@endforeach</select></label>
        <label class="text-xs font-bold text-[#52637A]">Ngày<input name="date" type="date" value="{{ $data['date'] ?? '' }}" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm"></label>
        <button class="self-end rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C]">Lọc danh sách</button>
    </form>

    @forelse($groupedByReason as $reason => $items)
        <section class="overflow-hidden rounded-xl border border-[#D7E8F3] bg-white" aria-labelledby="reason-{{ md5($reason) }}">
            <div class="flex items-center justify-between border-b border-[#E8F1F6] bg-[#FFF9EA] px-5 py-4">
                <h2 id="reason-{{ md5($reason) }}" class="text-lg font-black text-[#6B4A00]">{{ $reason }}</h2>
                <span class="rounded-full bg-[#F2B84B] px-3 py-1 text-xs font-black text-white">{{ $items->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[#F7FBFE] text-xs uppercase text-[#52637A]"><tr><th class="px-5 py-3">Ngày giờ</th><th class="px-5 py-3">Khách hàng</th><th class="px-5 py-3">Chuyên viên</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Thời điểm gắn cờ</th><th class="px-5 py-3">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-[#EEF4F8]">
                        @foreach($items as $appointment)
                            <tr class="hover:bg-[#FBFDFF]">
                                <td class="whitespace-nowrap px-5 py-4 font-semibold">{{ $appointment->start_at->format('d/m/Y H:i') }} - {{ $appointment->end_at->format('H:i') }}</td>
                                <td class="px-5 py-4"><p class="font-semibold text-[#102A43]">{{ $appointment->customer->name }}</p><p class="text-xs text-[#718096]">{{ $appointment->service->name }}</p></td>
                                <td class="px-5 py-4">{{ $appointment->specialistProfile->user->name }}</td>
                                <td class="px-5 py-4"><x-status-chip type="appointment" :status="$appointment->status" /></td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-[#718096]">{{ $appointment->attention_flagged_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('staff.appointments.detail', $appointment) }}" class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] hover:bg-[#F7FBFE]">Xem chi tiết</a>
                                        <x-modal-confirm
                                            id="attention-cancel-{{ $appointment->id }}"
                                            title="Hủy do phòng khám"
                                            description="Hủy lịch {{ $appointment->appointment_code }} của khách {{ $appointment->customer->name }} lúc {{ $appointment->start_at->format('d/m/Y H:i') }}.@if($appointment->payment_status === 'PAID') Khách sẽ được hoàn 100%, cần bổ sung thông tin ngân hàng.@endif Hành động này không thể hoàn tác."
                                            confirmLabel="Hủy do phòng khám"
                                            :requireReason="true"
                                            reasonLabel="Lý do hủy lịch"
                                            :action="route('staff.appointments.cancel', $appointment)">
                                            <x-slot:trigger>
                                                <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100">Hủy do phòng khám</button>
                                            </x-slot:trigger>
                                        </x-modal-confirm>
                                        <x-modal-confirm
                                            id="attention-reschedule-{{ $appointment->id }}"
                                            title="Đổi lịch do phòng khám"
                                            description="Đổi lịch {{ $appointment->appointment_code }} sang khung giờ mới. Lịch cũ sẽ ở trạng thái Đã đổi lịch, cờ cần xử lý được xóa và khách nhận thông báo kèm mã lịch mới. Khách được hủy không bị phạt."
                                            confirmLabel="Xác nhận đổi lịch"
                                            :requireReason="true"
                                            reasonLabel="Lý do đổi lịch"
                                            :action="route('staff.appointments.reschedule', $appointment)">
                                            <x-slot:trigger>
                                                <button type="button" class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white hover:bg-[#23689C]">Đổi lịch do phòng khám</button>
                                            </x-slot:trigger>
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="attention-reschedule-date-{{ $appointment->id }}" class="block text-sm font-medium text-[#1A202C]">Ngày mới</label>
                                                    <input id="attention-reschedule-date-{{ $appointment->id }}" type="date" name="date" required min="{{ now()->toDateString() }}" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                                </div>
                                                <div>
                                                    <label for="attention-reschedule-time-{{ $appointment->id }}" class="block text-sm font-medium text-[#1A202C]">Giờ mới</label>
                                                    <input id="attention-reschedule-time-{{ $appointment->id }}" type="time" name="time" required step="900" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                                </div>
                                            </div>
                                        </x-modal-confirm>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="rounded-xl border border-dashed border-[#B8D2E4] bg-white p-12 text-center">
            <p class="text-sm font-bold text-[#102A43]">Không có lịch nào cần xử lý.</p>
            <p class="mt-1 text-sm text-[#718096]">Tất cả lịch bị ảnh hưởng đã được điều phối xong.</p>
        </div>
    @endforelse
</div>
@endsection
