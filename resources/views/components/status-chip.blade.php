{{--
    x-status-chip — Chip trạng thái lịch hẹn & thanh toán
    -------------------------------------------------------
    Căn cứ: ThietKeGiaoDien.md §1.8 + Bảng màu §2.1 / §2.2
    NFR-USA-001: nhãn tiếng Việt
    Quy tắc: KHÔNG dùng màu làm dấu hiệu duy nhất — luôn kèm chữ.

    Props:
      @type   string  $status   — mã trạng thái (xem bảng bên dưới)
      @type   string  $group    — 'appointment' (mặc định) | 'payment'
      @type   string  $size     — 'sm' (mặc định) | 'md' | 'lg'

    Ví dụ:
      <x-status-chip status="PENDING" />
      <x-status-chip status="PAID" group="payment" size="md" />
      <x-status-chip status="CANCELLED_BY_CLINIC" />
--}}

@props([
    'status' => '',
    'group'  => 'appointment',   {{-- 'appointment' | 'payment' --}}
    'size'   => 'sm',            {{-- 'sm' | 'md' | 'lg' --}}
])

@php
    /*
    |--------------------------------------------------------------------------
    | BẢNG TRẠNG THÁI LỊCH HẸN — mục 1.8 ThietKeGiaoDien.md / §2.1 bảng màu
    |--------------------------------------------------------------------------
    | text        = màu chữ
    | bg          = màu nền chip
    | border      = màu viền chip
    | icon        = ký hiệu unicode phụ (hỗ trợ non-color signal)
    |--------------------------------------------------------------------------
    */
    $appointmentMap = [
        'PENDING' => [
            'label'  => 'Chờ phòng khám xác nhận',
            'text'   => '#B45309',
            'bg'     => '#FEF3C7',
            'border' => '#FDE68A',
            'icon'   => '⏳',
        ],
        'CONFIRMED' => [
            'label'  => 'Đã xác nhận',
            'text'   => '#1D4ED8',
            'bg'     => '#EFF6FF',
            'border' => '#BFDBFE',
            'icon'   => '✓',
        ],
        'RESCHEDULE_REQUESTED' => [
            'label'  => 'Chờ duyệt đổi lịch',
            'text'   => '#D97706',
            'bg'     => '#FFFBEB',
            'border' => '#FDE68A',
            'icon'   => '↻',
        ],
        'RESCHEDULED' => [
            'label'  => 'Đã đổi lịch',
            'text'   => '#4B5563',
            'bg'     => '#F3F4F6',
            'border' => '#E5E7EB',
            'icon'   => '↔',
        ],
        'IN_PROGRESS' => [
            'label'  => 'Đang diễn ra',
            'text'   => '#15803D',
            'bg'     => '#F0FDF4',
            'border' => '#BBF7D0',
            'icon'   => '▶',
        ],
        'COMPLETED' => [
            'label'  => 'Đã hoàn thành',
            'text'   => '#166534',
            'bg'     => '#DCFCE7',
            'border' => '#86EFAC',
            'icon'   => '✔',
        ],
        'CANCELLED_BY_CUSTOMER' => [
            'label'  => 'Khách đã hủy',
            'text'   => '#6B7280',
            'bg'     => '#F9FAFB',
            'border' => '#E5E7EB',
            'icon'   => '✕',
        ],
        'CANCELLED_BY_CLINIC' => [
            'label'  => 'Phòng khám đã hủy',
            'text'   => '#B91C1C',
            'bg'     => '#FEF2F2',
            'border' => '#FCA5A5',
            'icon'   => '✕',
        ],
        'NO_SHOW' => [
            'label'  => 'Vắng mặt',
            'text'   => '#991B1B',
            'bg'     => '#FEE2E2',
            'border' => '#F87171',
            'icon'   => '✗',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | BẢNG TRẠNG THÁI THANH TOÁN — §2.2 bảng màu
    |--------------------------------------------------------------------------
    */
    $paymentMap = [
        'UNPAID' => [
            'label'  => 'Chưa thanh toán',
            'text'   => '#B45309',
            'bg'     => '#FEF3C7',
            'border' => '#FDE68A',
            'icon'   => '○',
        ],
        'PAID' => [
            'label'  => 'Đã thanh toán',
            'text'   => '#15803D',
            'bg'     => '#F0FDF4',
            'border' => '#BBF7D0',
            'icon'   => '✓',
        ],
        'REFUND_PENDING' => [
            'label'  => 'Chờ hoàn tiền',
            'text'   => '#C2410C',
            'bg'     => '#FFEDD5',
            'border' => '#FED7AA',
            'icon'   => '↩',
        ],
        'REFUNDED' => [
            'label'  => 'Đã hoàn tiền',
            'text'   => '#374151',
            'bg'     => '#F3F4F6',
            'border' => '#D1D5DB',
            'icon'   => '↩',
        ],
        'NOT_REQUIRED' => [
            'label'  => 'Không cần thanh toán',
            'text'   => '#4B5563',
            'bg'     => '#F9FAFB',
            'border' => '#E5E7EB',
            'icon'   => '—',
        ],
        // Alias ngắn gọn (Staff dùng)
        'CASH' => [
            'label'  => 'Tiền mặt tại quầy',
            'text'   => '#374151',
            'bg'     => '#F3F4F6',
            'border' => '#D1D5DB',
            'icon'   => '💵',
        ],
        'BANK_QR' => [
            'label'  => 'Chuyển khoản QR',
            'text'   => '#1D4ED8',
            'bg'     => '#EFF6FF',
            'border' => '#BFDBFE',
            'icon'   => '🏦',
        ],
    ];

    /*
    |----------------------------------------------------------------------
    | BẢNG TRẠNG THÁI TÀI KHOẢN (dùng trong SCR-ADM-02, STF-04)
    |----------------------------------------------------------------------
    */
    $accountMap = [
        'PENDING_VERIFICATION' => [
            'label'  => 'Chờ xác thực email',
            'text'   => '#D97706',
            'bg'     => '#FFFBEB',
            'border' => '#FDE68A',
            'icon'   => '✉',
        ],
        'ACTIVE' => [
            'label'  => 'Đang hoạt động',
            'text'   => '#15803D',
            'bg'     => '#F0FDF4',
            'border' => '#BBF7D0',
            'icon'   => '●',
        ],
        'LOCKED' => [
            'label'  => 'Tạm khóa',
            'text'   => '#B91C1C',
            'bg'     => '#FEF2F2',
            'border' => '#FCA5A5',
            'icon'   => '🔒',
        ],
        'DISABLED' => [
            'label'  => 'Đã vô hiệu hóa',
            'text'   => '#6B7280',
            'bg'     => '#F9FAFB',
            'border' => '#E5E7EB',
            'icon'   => '⊘',
        ],
    ];

    // Chọn bảng theo group
    $map = match ($group) {
        'payment' => $paymentMap,
        'account' => $accountMap,
        default   => $appointmentMap,
    };

    $statusKey = strtoupper(trim($status));
    $chip = $map[$statusKey] ?? [
        'label'  => $statusKey ?: 'Không xác định',
        'text'   => '#4B5563',
        'bg'     => '#F3F4F6',
        'border' => '#E5E7EB',
        'icon'   => '?',
    ];

    // Kích thước padding / font
    $sizeClasses = match ($size) {
        'lg'    => 'px-3 py-1.5 text-sm gap-1.5',
        'md'    => 'px-2.5 py-1 text-sm gap-1',
        default => 'px-2 py-0.5 text-xs gap-1',  // sm
    };
@endphp

{{--
    Inline style để đảm bảo màu chính xác theo design spec.
    (Tailwind purge không giữ màu dynamic nếu dùng class interpolation.)
--}}
<span
    role="status"
    aria-label="Trạng thái: {{ $chip['label'] }}"
    style="
        color: {{ $chip['text'] }};
        background-color: {{ $chip['bg'] }};
        border-color: {{ $chip['border'] }};
    "
    {{ $attributes->merge([
        'class' => 'inline-flex items-center font-medium rounded-full border ' . $sizeClasses
    ]) }}>
    {{-- Icon phụ — non-color signal (NFR không dùng màu làm dấu hiệu duy nhất) --}}
    <span aria-hidden="true" class="leading-none">{{ $chip['icon'] }}</span>
    <span>{{ $chip['label'] }}</span>
    {{-- Slot tùy chỉnh (vd. kèm lý do hủy) --}}
    {{ $slot }}
</span>
