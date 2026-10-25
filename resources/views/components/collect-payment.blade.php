{{--
    x-collect-payment — Nút + hộp thoại xác nhận thu tiền mặt tại quầy
    ------------------------------------------------------------------
    Dùng chung cho các màn hình lễ tân (danh sách lịch, chi tiết lịch, bàn thu tiền)
    để thống nhất quy tắc:
      • Chỉ hiển thị khi lịch dùng thanh toán tiền mặt và chưa thanh toán.
      • Cho phép thu ở mọi giai đoạn: trước / trong / sau buổi tư vấn.
      • Mã tham chiếu (payment_reference) là TUỲ CHỌN — có thể bỏ qua.

    Props:
      @type  \App\Models\Appointment  $appointment — lịch hẹn cần thu tiền
      @type  string  $idPrefix — tiền tố sinh id duy nhất cho modal (mặc định "collect")
      @type  string  $size     — "sm" | "md" — kích thước nút
      @type  bool    $fullWidth — nút chiếm hết chiều rộng
--}}

@props([
    'appointment',
    'idPrefix' => 'collect',
    'size'     => 'sm',
    'fullWidth'=> false,
])

@php
    $cashCollectibleStatuses = ['PENDING', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED'];
    $canCollect = in_array($appointment->status, $cashCollectibleStatuses, true)
        && $appointment->payment_method === 'CASH'
        && $appointment->payment_status === 'UNPAID';

    $timingLabel = match ($appointment->status) {
        'PENDING'     => 'trước giờ tư vấn',
        'CONFIRMED'   => 'trước giờ tư vấn',
        'IN_PROGRESS' => 'trong buổi tư vấn',
        default       => 'sau buổi tư vấn',
    };

    $buttonClass = $size === 'md'
        ? 'w-full rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100'
        : 'rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100';

    if ($fullWidth) {
        $buttonClass = str_replace('rounded-lg border', 'w-full rounded-lg border', $buttonClass);
    }
@endphp

@if ($canCollect)
    <x-modal-confirm
        id="{{ $idPrefix }}-{{ $appointment->id }}"
        title="Xác nhận đã thu tiền mặt"
        description="Xác nhận đã thu {{ number_format((int) $appointment->price, 0, ',', '.') }} VNĐ tiền mặt cho lịch {{ $appointment->appointment_code }} (thu {{ $timingLabel }}). Người thu được ghi là nhân viên đang đăng nhập. Chỉ thực hiện một lần."
        confirmLabel="Xác nhận đã thu tiền"
        confirmClass="bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 text-white"
        :action="route('staff.payments.collect', $appointment->id)">
        <x-slot:trigger>
            <button type="button" class="{{ $buttonClass }}">Xác nhận đã thu tiền</button>
        </x-slot:trigger>
        <div>
            <label for="{{ $idPrefix }}-reference-{{ $appointment->id }}" class="block text-sm font-medium text-[#1A202C]">
                Mã tham chiếu
                <span class="text-xs font-normal text-[#4A5568]">(tùy chọn — có thể bỏ qua)</span>
            </label>
            <input id="{{ $idPrefix }}-reference-{{ $appointment->id }}" type="text" name="payment_reference" maxlength="100"
                   placeholder="Ví dụ: tiền mặt quầy lễ tân"
                   class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
    </x-modal-confirm>
@endif
