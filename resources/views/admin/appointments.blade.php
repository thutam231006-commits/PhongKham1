@extends('layouts.admin')

@section('title', 'Lịch hẹn toàn hệ thống')

@php
    $statusOptions = [
        'PENDING' => 'Chờ thanh toán',
        'CONFIRMED' => 'Đã xác nhận',
        'RESCHEDULE_REQUESTED' => 'Chờ duyệt đổi lịch',
        'IN_PROGRESS' => 'Đang diễn ra',
        'COMPLETED' => 'Đã hoàn thành',
        'NO_SHOW' => 'Vắng mặt',
        'CANCELLED_BY_CUSTOMER' => 'Khách đã hủy',
        'CANCELLED_BY_CLINIC' => 'Phòng khám đã hủy',
    ];
    $paymentOptions = ['UNPAID' => 'Chưa thanh toán', 'PAID' => 'Đã thanh toán', 'REFUND_PENDING' => 'Chờ hoàn tiền', 'REFUNDED' => 'Đã hoàn tiền'];
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ lịch &amp; cấu hình</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Lịch hẹn toàn hệ thống</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Trang này không hiển thị ghi chú riêng tư hay hồ sơ tư vấn. Hủy lịch bởi phòng khám luôn kèm lý do và
            tự sinh yêu cầu hoàn tiền khi khách đã thanh toán.
        </p>
    </div>

    {{-- ═══════════════ BỘ LỌC ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-xs font-bold text-[#52637A] xl:col-span-2">
                Tìm theo mã lịch, tên khách hoặc dịch vụ
                <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nhập từ khoá…"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái lịch
                <select name="status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái thanh toán
                <select name="payment_status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($paymentOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 sm:col-span-2 xl:col-span-4">
                <label class="flex items-center gap-2 text-xs font-bold text-[#52637A]">
                    <input type="checkbox" name="attention" value="1" @checked(request()->boolean('attention'))
                           class="h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                    Chỉ lịch cần xử lý
                </label>
                <label class="flex items-center gap-2 text-xs font-bold text-[#52637A]">
                    <input type="checkbox" name="unpaid_cash" value="1" @checked(request()->boolean('unpaid_cash'))
                           class="h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                    Đã hoàn thành — chưa thu tiền mặt
                </label>

                <div class="ml-auto flex items-center gap-2">
                    <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                        Lọc danh sách
                    </button>
                    @if(request()->query())
                        <a href="{{ route('admin.appointments') }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                            Xoá bộ lọc
                        </a>
                    @endif
                    <span class="text-xs font-semibold text-[#8A9BB0]">{{ $appointments->total() }} lịch</span>
                </div>
            </div>
        </form>
    </section>

    {{-- ═══════════════ DANH SÁCH ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="overflow-x-auto">
            <table class="min-w-[1180px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Mã lịch</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Khách hàng</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Chuyên viên</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thời gian</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Giá</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thanh toán</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($appointments as $appointment)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="px-5 py-4">
                                <p class="whitespace-nowrap font-mono text-xs font-black text-[#2D82C4]">{{ $appointment->appointment_code }}</p>
                                <p class="mt-1 max-w-[12rem] text-xs font-semibold text-[#52637A]">{{ $appointment->service->name }}</p>
                                @if($appointment->needs_attention)
                                    <span class="mt-1.5 inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-orange-300 bg-orange-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-[#C2410C]">
                                        <span aria-hidden="true">⚠</span> {{ $appointment->attention_reason ?: 'Cần xử lý' }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <p class="whitespace-nowrap font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                                <p class="whitespace-nowrap text-xs text-[#8A9BB0]">{{ $appointment->customer->email }}</p>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap text-sm text-[#334E68]">
                                {{ $appointment->specialistProfile->user->name }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="font-semibold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y') }}</p>
                                <p class="text-xs font-bold tabular-nums text-[#2D82C4]">{{ $appointment->start_at->format('H:i') }} – {{ $appointment->end_at->format('H:i') }}</p>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap text-sm font-black tabular-nums text-[#102A43]">
                                {{ number_format((float) $appointment->price, 0, ',', '.') }}
                            </td>

                            <td class="px-5 py-4"><x-status-chip :status="$appointment->status" /></td>

                            <td class="px-5 py-4">
                                <x-status-chip type="payment" :status="$appointment->payment_status" />
                            </td>

                            <td class="px-5 py-4">
                                @if(in_array($appointment->status, ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'], true))
                                    <x-modal-confirm
                                        id="cancel-appt-{{ $appointment->id }}"
                                        title="Hủy lịch {{ $appointment->appointment_code }}"
                                        description="Lịch của khách {{ $appointment->customer->name }} ngày {{ $appointment->start_at->format('d/m/Y H:i') }} sẽ được hủy. Nếu khách đã thanh toán, hệ thống tự tạo yêu cầu hoàn tiền theo chính sách."
                                        confirm-label="Hủy lịch"
                                        :require-reason="true"
                                        reason-label="Lý do hủy lịch"
                                        :action="route('admin.appointments.cancel', $appointment)">
                                        <x-slot:trigger>
                                            <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                Hủy lịch
                                            </button>
                                        </x-slot:trigger>
                                    </x-modal-confirm>
                                @else
                                    <span class="text-xs text-[#8A9BB0]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <p class="text-sm font-bold text-[#102A43]">Không có lịch hẹn phù hợp bộ lọc.</p>
                                <p class="mt-1 text-xs text-[#718096]">Thử xoá bộ lọc hoặc chọn khoảng thời gian khác.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($appointments->hasPages())
            <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $appointments->links() }}</div>
        @endif
    </section>
</div>
@endsection
