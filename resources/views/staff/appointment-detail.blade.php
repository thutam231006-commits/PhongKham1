{{--
    SCR-STF-03 — Chi tiết lịch hẹn: điều phối (FR-APPT-005…014, FR-PAY-005/007/008/011, FR-NOTI-005)
    ===================================================================================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-STF-03 & §8 ma trận hành động theo trạng thái.

    Nguyên tắc bảo mật (§1.7): Staff KHÔNG thấy `customer_note`, nội dung hồ sơ tư vấn.
    Mọi hành động không hoàn tác mở hộp xác nhận nêu rõ đối tượng và hậu quả.
--}}

@extends('layouts.app')

@section('title', 'Chi tiết lịch hẹn ' . $appointment->appointment_code)

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 border-b border-[#D7E8F3] pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-[#6FA6CF]">Điều phối lịch hẹn</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-[#102A43]">{{ $appointment->appointment_code }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-status-chip type="appointment" :status="$appointment->status" size="md" />
                <x-status-chip type="payment" :status="$appointment->payment_status" size="md" />
                @if($appointment->needs_attention)
                    <span class="inline-flex items-center gap-1 rounded-full border border-orange-300 bg-orange-50 px-3 py-1 text-xs font-bold text-[#C2410C]">⚠ {{ $appointment->attention_reason ?? 'Cần xử lý' }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('staff.appointments') }}" class="rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] hover:bg-[#EAF4FB]">Về danh sách lịch</a>
            <a href="{{ route('staff.checkin', ['date' => $appointment->start_at->toDateString()]) }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A]">Màn check-in</a>
        </div>
    </div>

    @if(session('success'))<div role="alert" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
        {{-- Khối thông tin lịch hẹn — không có ghi chú khách (§1.7) --}}
        <div class="space-y-6">
            <section class="rounded-xl border border-[#D7E8F3] bg-white" aria-labelledby="appointment-heading">
                <div class="border-b border-[#E8F1F6] px-5 py-4"><h2 id="appointment-heading" class="text-lg font-black text-[#102A43]">Thông tin lịch hẹn</h2></div>
                <dl class="grid gap-5 px-5 py-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Khách hàng</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->customer->name }}</dd><dd class="text-xs text-[#52637A]">{{ $appointment->customer->email }}</dd><dd class="text-xs text-[#52637A]">{{ $appointment->customer->phone }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Chuyên viên</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->specialistProfile->user->name }}</dd><dd class="text-xs text-[#52637A]">{{ $appointment->specialistProfile->title }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Dịch vụ</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->service->name }}</dd><dd class="text-xs text-[#52637A]">{{ $appointment->duration_minutes }} phút · đệm {{ $appointment->buffer_minutes }} phút</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Thời gian</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y H:i') }} - {{ $appointment->end_at->format('H:i') }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Hình thức tư vấn</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : 'Trực tiếp tại phòng khám' }}</dd>@if($appointment->consultation_type === 'ONLINE' && $appointment->meeting_url)<a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener" class="mt-1 block text-xs font-bold text-[#2D82C4] hover:underline">Vào phòng họp →</a>@elseif($appointment->consultation_type === 'OFFLINE')<dd class="text-xs text-[#52637A]">{{ $appointment->consultationRoom?->name ?? 'Chưa xếp phòng' }}</dd>@endif</div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Nguồn đặt</dt><dd class="mt-1 font-bold text-[#102A43]">{{ match ($appointment->source) { 'ONLINE' => 'Khách tự đặt trên web', 'ONSITE' => 'Nhân viên đặt tại quầy', 'STAFF' => 'Phòng khám đổi lịch', 'RESCHEDULE' => 'Duyệt yêu cầu đổi lịch', default => $appointment->source } }}</dd><dd class="text-xs text-[#52637A]">Người tạo: {{ $appointment->createdBy?->name ?? '—' }}</dd></div>
                    @if($appointment->rescheduledFrom)
                        <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase text-[#718096]">Lịch trước đó</dt><dd class="mt-1"><a href="{{ route('staff.appointments.detail', $appointment->rescheduledFrom) }}" class="font-bold text-[#2D82C4] hover:underline">{{ $appointment->rescheduledFrom->appointment_code }}</a> <span class="text-xs text-[#52637A]">{{ $appointment->rescheduledFrom->start_at->format('d/m/Y H:i') }}</span></dd></div>
                    @endif
                </dl>
            </section>

            {{-- Khối thanh toán --}}
            <section class="rounded-xl border border-[#D7E8F3] bg-white" aria-labelledby="payment-heading">
                <div class="border-b border-[#E8F1F6] px-5 py-4"><h2 id="payment-heading" class="text-lg font-black text-[#102A43]">Thanh toán</h2></div>
                <dl class="grid gap-5 px-5 py-5 sm:grid-cols-3">
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Phương thức</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->payment_method === 'BANK_QR' ? 'Chuyển khoản QR (VietQR)' : 'Tiền mặt tại quầy' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Số tiền</dt><dd class="mt-1 text-lg font-black text-[#2D82C4]">{{ number_format((int) $appointment->price, 0, ',', '.') }} VNĐ</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-[#718096]">Hình thức xác nhận</dt><dd class="mt-1 font-bold text-[#102A43]">{{ $appointment->paid_at ? 'Tự động qua webhook' : ($appointment->collected_at ? 'Tiền mặt' : 'Chưa xác nhận') }}</dd></div>
                    @if($appointment->payment_reference)
                        <div><dt class="text-xs font-bold uppercase text-[#718096]">Mã giao dịch ngân hàng</dt><dd class="mt-1 font-mono text-sm text-[#102A43]">{{ $appointment->payment_reference }}</dd></div>
                    @endif
                    @if($appointment->collected_at)
                        <div><dt class="text-xs font-bold uppercase text-[#718096]">Người thu & thời điểm thu</dt><dd class="mt-1 text-sm text-[#102A43]">{{ $appointment->collected_at->format('d/m/Y H:i') }}</dd></div>
                    @endif
                    @if($appointment->refundRequest)
                        <div><dt class="text-xs font-bold uppercase text-[#718096]">Yêu cầu hoàn tiền</dt><dd class="mt-1 text-sm text-[#102A43]">{{ number_format((int) $appointment->refundRequest->amount, 0, ',', '.') }} VNĐ · {{ $appointment->refundRequest->percentage }}%</dd><dd class="text-xs font-bold {{ $appointment->refundRequest->status === 'PENDING' ? 'text-[#C2410C]' : 'text-[#15803D]' }}">{{ $appointment->refundRequest->status === 'PENDING' ? 'Đang chờ xử lý' : 'Đã hoàn tiền' }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Giao dịch liên quan --}}
            <section class="rounded-xl border border-[#D7E8F3] bg-white" aria-labelledby="transactions-heading">
                <div class="border-b border-[#E8F1F6] px-5 py-4"><h2 id="transactions-heading" class="text-lg font-black text-[#102A43]">Giao dịch liên quan</h2></div>
                @if($appointment->paymentTransactions->isNotEmpty())
                    <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-[#F7FBFE] text-xs uppercase text-[#52637A]"><tr><th class="px-5 py-3">Thời điểm</th><th class="px-5 py-3">Số tiền</th><th class="px-5 py-3">Nội dung</th><th class="px-5 py-3">Loại khớp</th></tr></thead><tbody class="divide-y divide-[#EEF4F8]">@foreach($appointment->paymentTransactions as $transaction)<tr><td class="whitespace-nowrap px-5 py-3">{{ $transaction->created_at->format('d/m/Y H:i') }}</td><td class="px-5 py-3 font-bold">{{ number_format((int) $transaction->amount, 0, ',', '.') }} VNĐ</td><td class="px-5 py-3 text-xs text-[#52637A]">{{ $transaction->content ?? '—' }}</td><td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $transaction->status === 'MATCHED' ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-[#C2410C]' }}">{{ $transaction->status }}</span></td></tr>@endforeach</tbody></table></div>
                @else
                    <p class="px-5 py-8 text-center text-sm text-[#718096]">Chưa có giao dịch ngân hàng nào khớp với lịch này.</p>
                @endif
            </section>

            {{-- Lịch sử trạng thái --}}
            <section class="rounded-xl border border-[#D7E8F3] bg-white" aria-labelledby="history-heading">
                <div class="border-b border-[#E8F1F6] px-5 py-4"><h2 id="history-heading" class="text-lg font-black text-[#102A43]">Lịch sử trạng thái</h2><p class="mt-1 text-xs text-[#718096]">Bao gồm lý do nội bộ và người thực hiện.</p></div>
                <ol class="divide-y divide-[#EEF4F8]">@forelse($appointment->statusHistory->sortByDesc('created_at') as $history)<li class="px-5 py-4"><div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-bold text-[#102A43]">{{ \App\Support\AppointmentStatus::label($history->to_status) }}</p><time class="text-xs text-[#8A9BB0]">{{ $history->created_at?->format('d/m/Y H:i') }}</time></div><p class="mt-1 text-xs text-[#52637A]">Hành động: <strong>{{ $history->action }}</strong> · Người thực hiện: {{ $history->actor_role }}{{ $history->actor_id ? ' #'.$history->actor_id : '' }}</p>@if($history->reason)<p class="mt-1 text-xs italic text-[#718096]">Lý do: {{ $history->reason }}</p>@endif</li>@empty<li class="px-5 py-8 text-center text-sm text-[#718096]">Chưa có lịch sử trạng thái.</li>@endforelse</ol>
            </section>
        </div>

        {{-- Cột thao tác điều phối --}}
        <div class="space-y-6">
            <section class="rounded-xl border border-[#D7E8F3] bg-white" aria-labelledby="actions-heading">
                <div class="border-b border-[#E8F1F6] px-5 py-4"><h2 id="actions-heading" class="text-lg font-black text-[#102A43]">Thao tác điều phối</h2><p class="mt-1 text-xs text-[#718096]">Các nút hiển thị theo trạng thái lịch hẹn hiện tại.</p></div>
                <div class="space-y-3 px-5 py-5">
                    @if($appointment->status === 'PENDING')
                        <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}">@csrf<button class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C]">Xác nhận lịch hẹn</button></form>
                    @endif

                    @if($appointment->status === 'CONFIRMED')
                        <form method="POST" action="{{ route('staff.appointments.start', $appointment) }}">@csrf<button class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C]">Bắt đầu hộ buổi tư vấn</button></form>
                        <x-modal-confirm
                            id="staff-no-show-{{ $appointment->id }}"
                            title="Đánh dấu khách vắng mặt"
                            description="Đánh dấu vắng mặt cho lịch {{ $appointment->appointment_code }} lúc {{ $appointment->start_at->format('d/m/Y H:i') }}. Lịch sẽ chuyển sang trạng thái Vắng mặt và không được hoàn tiền. Chỉ thực hiện sau khi đã chờ đủ 15 phút."
                            confirmLabel="Đánh dấu vắng mặt"
                            :action="route('staff.appointments.no-show', $appointment)">
                            <x-slot:trigger>
                                <button type="button" class="w-full rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] hover:bg-[#F7FBFE]">Đánh dấu vắng mặt</button>
                            </x-slot:trigger>
                        </x-modal-confirm>
                    @endif

                    @if(in_array($appointment->status, ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'], true))
                        <x-modal-confirm
                            id="staff-cancel-{{ $appointment->id }}"
                            title="Hủy lịch do phòng khám"
                            description="Hủy lịch {{ $appointment->appointment_code }} của khách {{ $appointment->customer->name }} lúc {{ $appointment->start_at->format('d/m/Y H:i') }}.@if($appointment->payment_status === 'PAID') Khách sẽ được hoàn 100%, cần bổ sung thông tin ngân hàng.@endif Hành động này không thể hoàn tác."
                            confirmLabel="Hủy lịch hẹn"
                            :requireReason="true"
                            reasonLabel="Lý do hủy lịch"
                            :action="route('staff.appointments.cancel', $appointment)">
                            <x-slot:trigger>
                                <button type="button" class="w-full rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-100">Hủy lịch hẹn</button>
                            </x-slot:trigger>
                        </x-modal-confirm>
                    @endif

                    @if(in_array($appointment->status, ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'], true))
                        <x-modal-confirm
                            id="staff-reschedule-{{ $appointment->id }}"
                            title="Đổi lịch do phòng khám"
                            description="Đổi lịch {{ $appointment->appointment_code }} sang khung giờ mới. Lịch cũ sẽ ở trạng thái Đã đổi lịch và khách nhận thông báo kèm mã lịch mới. Khách được hủy không bị phạt."
                            confirmLabel="Xác nhận đổi lịch"
                            :requireReason="true"
                            reasonLabel="Lý do đổi lịch"
                            :action="route('staff.appointments.reschedule', $appointment)">
                            <x-slot:trigger>
                                <button type="button" class="w-full rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] hover:bg-[#EAF4FB]">Đổi lịch do phòng khám</button>
                            </x-slot:trigger>
                            <div class="space-y-3">
                                <div>
                                    <label for="staff-reschedule-date" class="block text-sm font-medium text-[#1A202C]">Ngày mới</label>
                                    <input id="staff-reschedule-date" type="date" name="date" required min="{{ now()->toDateString() }}" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label for="staff-reschedule-time" class="block text-sm font-medium text-[#1A202C]">Giờ mới</label>
                                    <input id="staff-reschedule-time" type="time" name="time" required step="900" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                </div>
                            </div>
                        </x-modal-confirm>
                    @endif

                    @if($appointment->status === 'PENDING')
                        <x-modal-confirm
                            id="staff-reassign-{{ $appointment->id }}"
                            title="Chuyển chuyên viên"
                            description="Chuyển lịch {{ $appointment->appointment_code }} sang chuyên viên khác. Hệ thống sẽ kiểm tra lịch làm việc và xung đột khung giờ trước khi chuyển."
                            confirmLabel="Chuyển chuyên viên"
                            :action="route('staff.appointments.reassign', $appointment)">
                            <x-slot:trigger>
                                <button type="button" class="w-full rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] hover:bg-[#F7FBFE]">Chuyển chuyên viên</button>
                            </x-slot:trigger>
                            <div>
                                <label for="staff-reassign-specialist" class="block text-sm font-medium text-[#1A202C]">Chuyên viên mới</label>
                                <select id="staff-reassign-specialist" name="specialist_id" required class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                    @foreach($specialists as $specialist)
                                        <option value="{{ $specialist->id }}" @disabled($specialist->id === $appointment->specialist_profile_id)>{{ $specialist->user->name }} — {{ $specialist->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </x-modal-confirm>
                    @endif

                    <x-modal-confirm
                        id="staff-notify-{{ $appointment->id }}"
                        title="Gửi thông báo cho khách"
                        description="Gửi thông báo thủ công tới khách {{ $appointment->customer->name }}. Nội dung không được chứa HTML và tối đa 500 ký tự. Hành động được ghi vào nhật ký audit."
                        confirmLabel="Gửi thông báo"
                        :requireReason="true"
                        reasonLabel="Nội dung thông báo"
                        reasonName="message"
                        :action="route('staff.appointments.notify', $appointment)">
                        <x-slot:trigger>
                            <button type="button" class="w-full rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] hover:bg-[#EAF4FB]">Gửi thông báo thủ công</button>
                        </x-slot:trigger>
                    </x-modal-confirm>

                    @if($appointment->status === 'COMPLETED' && $appointment->payment_method === 'CASH' && $appointment->payment_status === 'UNPAID')
                        <x-modal-confirm
                            id="staff-collect-{{ $appointment->id }}"
                            title="Xác nhận đã thu tiền mặt"
                            description="Xác nhận đã thu {{ number_format((int) $appointment->price, 0, ',', '.') }} VNĐ tiền mặt cho lịch {{ $appointment->appointment_code }}. Người thu được ghi là nhân viên đang đăng nhập. Chỉ thực hiện một lần."
                            confirmLabel="Xác nhận đã thu tiền"
                            :action="route('staff.payments.collect', $appointment->id)">
                            <x-slot:trigger>
                                <button type="button" class="w-full rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Xác nhận đã thu tiền</button>
                            </x-slot:trigger>
                            <div>
                                <label for="staff-collect-reference" class="block text-sm font-medium text-[#1A202C]">Mã tham chiếu</label>
                                <input id="staff-collect-reference" type="text" name="payment_reference" maxlength="100" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            </div>
                        </x-modal-confirm>
                    @endif
                </div>
            </section>

            @if($appointment->status === 'IN_PROGRESS')
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">Buổi tư vấn đang diễn ra. Lịch ở trạng thái này không thể hủy; chuyên viên phụ trách sẽ hoàn thành hoặc đánh dấu vắng mặt.</div>
            @endif
        </div>
    </div>
</div>
@endsection
