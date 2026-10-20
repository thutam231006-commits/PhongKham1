{{--
    SCR-CUS-05 — Chi tiết lịch hẹn: hủy, hoàn tiền, lịch sử (FR-APPT-004/006/014, FR-PAY-002/009/010)
    SCR-CUS-06 — Yêu cầu đổi lịch (FR-APPT-008)
    ===================================================================================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-05 & §SCR-CUS-06
    Bố cục:
      - Mã lịch hẹn, chuyên gia, gói dịch vụ, hình thức (Online/Offline kèm mã QR check-in).
      - Khối thanh toán & Bảng tính hoàn tiền minh bạch (>=24h: 100%, 12-24h: 50%, <12h: 0%).
      - Ghi chú riêng tư của khách (bảo mật §1.7, có modal sửa ghi chú).
      - Dòng thời gian lịch sử trạng thái (Timeline).
      - Pop-up hủy lịch 2 bước có form hoàn tiền ngân hàng và Modal đổi lịch hẹn.
--}}

@extends('layouts.app')

@section('title', 'Chi Tiết Lịch Hẹn ' . $appointment->code . ' | Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li>
            <a href="{{ route('customer.appointments') }}" class="hover:text-[#2D82C4] hover:underline transition">Lịch hẹn của tôi</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">{{ $appointment->code }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-8"
     x-data="{
        showCancelModal: false,
        showRescheduleModal: false,
        showEditNoteModal: false,
        cancelStep: 1,
        cancelReason: '',
        bankName: '',
        bankAccount: '',
        bankOwner: '',
        newDate: '{{ date('Y-m-d', strtotime('+3 days')) }}',
        newTime: '11:00 - 12:00',
        rescheduleReason: '',
     }">

    {{-- Thông báo session --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Header tiêu đề & Trạng thái --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 sm:p-8 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-black text-[#0F172A]">
                    Lịch hẹn: {{ $appointment->code }}
                </h1>
                <x-status-chip type="appointment" :status="$appointment->status" />
                <x-status-chip type="payment" :status="$appointment->payment_status" />
            </div>
            <p class="mt-1 text-xs sm:text-sm text-[#4A5568]">
                Đặt lịch ngày {{ date('d/m/Y', strtotime($appointment->date)) }} • Hình thức: <strong class="text-[#0F172A]">{{ $appointment->type === 'ONLINE' ? 'Trực tuyến' : 'Trực tiếp tại phòng khám' }}</strong>
            </p>
        </div>

        {{-- Các nút hành động chính theo trạng thái lịch hẹn --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Đổi lịch (CONFIRMED, >=24h, < 2 lần) --}}
            @if($appointment->status === 'CONFIRMED' && ($appointment->can_reschedule ?? true))
                <button type="button"
                        data-modal-open="reschedule-appointment-modal"
                        onclick="document.getElementById('reschedule-appointment-modal').style.display = 'flex'; return false;"
                        @click="showRescheduleModal = true"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-bold text-[#0F172A] hover:bg-gray-50 transition shadow-2xs">
                    Yêu cầu đổi lịch
                </button>
            @endif

            {{-- Hủy lịch (CONFIRMED hoặc PENDING) --}}
            @if(in_array($appointment->status, ['CONFIRMED', 'PENDING']))
                <button type="button"
                        data-modal-open="cancel-appointment-modal"
                        onclick="document.getElementById('cancel-appointment-modal').style.display = 'flex'; return false;"
                        @click="showCancelModal = true; cancelStep = 1"
                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-bold text-red-700 hover:bg-red-100 transition shadow-2xs">
                    Hủy lịch hẹn
                </button>
            @endif

            {{-- Thanh toán lại khi PENDING --}}
            @if($appointment->status === 'PENDING' && $appointment->payment_method === 'BANK_QR')
                <a href="{{ route('booking.payment.show', ['appointment' => $appointment->id]) }}"
                   class="rounded-xl bg-[#2D82C4] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                    Thanh toán ngay (VietQR)
                </a>
            @endif

            {{-- Đánh giá khi COMPLETED --}}
            @if($appointment->status === 'COMPLETED')
                <a href="{{ route('customer.review', ['appointment_id' => $appointment->id]) }}"
                   class="rounded-xl bg-[#2D82C4] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                    Đánh giá buổi tư vấn
                </a>
            @endif
        </div>
    </div>

    {{-- Layout 2 cột: Thông tin chi tiết & Khối QR / Check-in --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- CỘT TRÁI (8/12): THÔNG TIN BUỔI HẸN, THANH TOÁN, TIMELINE --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Thông tin dịch vụ & chuyên gia --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-7 shadow-xs space-y-5">
                <h2 class="text-base font-bold text-[#0F172A] border-b border-gray-100 pb-3">
                    Chi tiết buổi tham vấn
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <span class="text-xs text-[#4A5568] block">Gói dịch vụ đã chọn:</span>
                        <h3 class="text-base font-bold text-[#0F172A] mt-1">{{ $appointment->service_name }}</h3>
                        <span class="text-xs text-[#6FA6CF] font-semibold mt-1 inline-block">Thời lượng: 60 - 90 phút</span>
                    </div>

                    <div>
                        <span class="text-xs text-[#4A5568] block">Chuyên gia tâm lý phụ trách:</span>
                        <h3 class="text-base font-bold text-[#0F172A] mt-1">{{ $appointment->specialist_name }}</h3>
                        <span class="text-xs text-[#4A5568] mt-1 block">{{ $appointment->specialist_title ?? 'Chuyên viên Tâm lý học' }}</span>
                    </div>

                    <div>
                        <span class="text-xs text-[#4A5568] block">Thời gian tư vấn:</span>
                        <div class="font-bold text-sm text-[#0F172A] flex items-center gap-1.5 mt-1">
                            <svg class="h-4 w-4 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ date('d/m/Y', strtotime($appointment->date)) }}</span>
                            <span class="text-gray-300">•</span>
                            <span>{{ $appointment->time_slot }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs text-[#4A5568] block">Địa điểm / Phòng hẹn:</span>
                        <strong class="font-bold text-sm text-[#0F172A] mt-1 block">{{ $appointment->location }}</strong>
                        @if($appointment->type === 'ONLINE' && !empty($appointment->meeting_url))
                            <a href="{{ $appointment->meeting_url }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-[#2D82C4] hover:underline">
                                <span>Vào phòng họp trực tuyến</span>
                                <span>→</span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Ghi chú của khách hàng (Bảo mật §1.7) --}}
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-[#0F172A] flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Ghi chú cá nhân gửi chuyên viên (§1.7 bảo mật)
                        </span>

                        @if(in_array($appointment->status, ['PENDING', 'CONFIRMED']))
                            <button type="button" @click="showEditNoteModal = true" class="text-xs font-bold text-[#2D82C4] hover:underline">
                                Sửa ghi chú
                            </button>
                        @endif
                    </div>
                    <div class="rounded-xl bg-[#F5FAFE] p-4 text-xs text-[#334155] border border-gray-200/80 leading-relaxed italic">
                        {{ $appointment->customer_note ?? 'Bạn chưa để lại ghi chú nào cho buổi tham vấn này.' }}
                    </div>
                </div>
            </div>

            {{-- Khối thông tin thanh toán & hoàn tiền --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-7 shadow-xs space-y-4">
                <h2 class="text-base font-bold text-[#0F172A] border-b border-gray-100 pb-3">
                    Thông tin thanh toán & Hóa đơn
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-[#4A5568] block">Phương thức:</span>
                        <strong class="text-sm font-bold text-[#0F172A] mt-0.5 block">
                            {{ $appointment->payment_method === 'BANK_QR' ? 'Chuyển khoản QR (VietQR)' : 'Tiền mặt tại quầy' }}
                        </strong>
                    </div>

                    <div>
                        <span class="text-[#4A5568] block">Trạng thái thanh toán:</span>
                        <div class="mt-1">
                            <x-status-chip type="payment" :status="$appointment->payment_status" />
                        </div>
                    </div>

                    <div>
                        <span class="text-[#4A5568] block">Tổng số tiền:</span>
                        <span class="text-base font-black text-[#2D82C4] mt-0.5 block">
                            {{ number_format($appointment->amount ?? 500000, 0, ',', '.') }} đ
                        </span>
                    </div>
                </div>

                {{-- Thông báo hoàn tiền nếu trạng thái REFUND_PENDING hoặc REFUNDED --}}
                @if(in_array($appointment->payment_status, ['REFUND_PENDING', 'REFUNDED']))
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-xs text-amber-900 space-y-1">
                        <strong class="font-bold block">Thông tin hoàn tiền:</strong>
                        <p>Số tiền hoàn: <strong>{{ number_format(($appointment->amount ?? 500000) * 1.0, 0, ',', '.') }} đ (Tỷ lệ: 100%)</strong></p>
                        <p>Thời gian nhận tiền: 3–5 ngày làm việc qua tài khoản ngân hàng thụ hưởng.</p>
                    </div>
                @endif
            </div>

            {{-- Dòng thời gian lịch sử trạng thái (Timeline) --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-7 shadow-xs">
                <h2 class="text-base font-bold text-[#0F172A] border-b border-gray-100 pb-3 mb-4">
                    Lịch sử cập nhật trạng thái
                </h2>

                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-200">
                    @foreach($timeline as $t)
                        <div class="relative">
                            <div class="absolute -left-6 top-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-[#2D82C4] shadow-xs"></div>
                            <div class="text-xs">
                                <span class="font-bold text-[#0F172A]">{{ $t->label }}</span>
                                <span class="text-gray-400 ml-2">{{ $t->time }} (bởi {{ $t->actor }})</span>
                                <p class="text-[#4A5568] mt-0.5">{{ $t->desc }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- CỘT PHẢI (4/12): MÃ QR CHECK-IN & HƯỚNG DẪN --}}
        <div class="lg:col-span-4 sticky top-20 space-y-6">

            {{-- Mã QR Check-in --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs text-center space-y-4">
                <span class="inline-block rounded-full bg-[#EAF4FB] px-3 py-0.5 text-xs font-bold text-[#2D82C4]">
                    Mã Check-in buổi hẹn
                </span>

                <div class="mx-auto w-44 h-44 bg-gray-50 border border-gray-200 rounded-2xl flex items-center justify-center p-3 relative shadow-2xs">
                    {{-- QR SVG Demo --}}
                    <svg class="w-36 h-36 text-[#2D82C4]" viewBox="0 0 100 100" fill="currentColor">
                        <path d="M10 10h30v30h-30z M15 15v20h20v-20z M20 20h10v10h-10z M60 10h30v30h-30z M65 15v20h20v-20z M70 20h10v10h-10z M10 60h30v30h-30z M15 65v20h20v-20z M20 70h10v10h-10z M45 10h10v20h-10z M10 45h20v10h-20z M45 45h10v10h-10z M60 45h20v10h-20z M45 60h10v30h-10z M60 60h10v10h-10z M75 60h15v15h-15z M60 75h10v15h-10z M75 80h15v10h-15z"/>
                    </svg>
                </div>

                <div>
                    <span class="font-mono text-sm font-black text-[#0F172A] block tracking-wider">{{ $appointment->code }}</span>
                    <p class="text-xs text-[#4A5568] mt-1">Đưa mã QR cho lễ tân khi bạn đến phòng khám để được tiếp đón nhanh chóng.</p>
                </div>
            </div>

            {{-- Hướng dẫn trước buổi hẹn --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 text-xs text-[#334155] space-y-3 shadow-xs">
                <h4 class="font-bold text-[#0F172A] flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                    </svg>
                    Lưu ý trước buổi tư vấn
                </h4>
                <ul class="space-y-2 leading-relaxed text-[#4A5568]">
                    <li>• Vui lòng có mặt trước giờ hẹn ít nhất 10 phút.</li>
                    <li>• Chuẩn bị không gian yên tĩnh và tai nghe nếu tham vấn trực tuyến.</li>
                    <li>• Hỗ trợ đổi lịch trước 24 giờ mà không phát sinh thêm chi phí.</li>
                </ul>
            </div>

        </div>

    </div>

    {{-- ═══ POP-UP HỦY LỊCH HẸN 2 BƯỚC (FR-PAY-009/010) ═══ --}}
    <div id="cancel-appointment-modal" x-show="showCancelModal" x-cloak @click.self="showCancelModal = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="pointer-events-auto w-full max-w-lg rounded-2xl bg-white p-6 sm:p-8 shadow-xl space-y-6">
            
            {{-- Bước 1: Xem trước tỷ lệ hoàn tiền --}}
            <div x-show="cancelStep === 1">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-red-700 flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Xác nhận hủy lịch hẹn
                    </h3>
                    <button type="button" data-modal-close="cancel-appointment-modal" onclick="document.getElementById('cancel-appointment-modal').style.display = 'none'; return false;" @click.stop="showCancelModal = false" @keydown.escape.window="showCancelModal = false" class="relative z-10 cursor-pointer p-2 text-gray-400 hover:text-gray-600" aria-label="Đóng hộp thoại hủy lịch">✕</button>
                </div>

                <div class="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50 text-xs text-amber-900 space-y-2">
                    <span class="font-bold block uppercase tracking-wider text-[11px]">Chính sách hoàn tiền theo FR-PAY-009/010:</span>
                    <div class="flex justify-between border-b border-amber-200/60 pb-1">
                        <span>Thời gian còn lại đến giờ hẹn:</span>
                        <strong class="font-bold text-emerald-800">Trên 24 giờ</strong>
                    </div>
                    <div class="flex justify-between border-b border-amber-200/60 pb-1">
                        <span>Số tiền bạn đã thanh toán:</span>
                        <strong class="font-bold">{{ number_format($appointment->amount ?? 500000, 0, ',', '.') }} đ</strong>
                    </div>
                    <div class="flex justify-between pt-1 text-sm font-bold">
                        <span>Tỷ lệ & Số tiền được hoàn:</span>
                        <span class="text-emerald-800 font-black">100% ({{ number_format($appointment->amount ?? 500000, 0, ',', '.') }} đ)</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-bold text-[#0F172A] mb-1">Lý do hủy lịch (tùy chọn):</label>
                    <textarea x-model="cancelReason" rows="3" placeholder="Xin hãy cho chúng tôi biết lý do bạn hủy lịch..."
                              class="w-full rounded-xl border border-gray-300 p-3 text-xs text-[#0F172A] focus:border-red-500 focus:ring-1 focus:ring-red-500"></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-modal-close="cancel-appointment-modal" onclick="document.getElementById('cancel-appointment-modal').style.display = 'none'; return false;" @click.stop="showCancelModal = false" class="relative z-10 cursor-pointer rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
                        Đóng
                    </button>
                    <button type="button" @click="cancelStep = 2" class="rounded-xl bg-red-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-red-700">
                        Tiếp tục: Điền thông tin hoàn tiền →
                    </button>
                </div>
            </div>

            {{-- Bước 2: Điền thông tin tài khoản ngân hàng nhận tiền --}}
            <div x-show="cancelStep === 2">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-[#0F172A]">Thông tin ngân hàng nhận hoàn tiền</h3>
                    <button type="button" data-modal-close="cancel-appointment-modal" onclick="document.getElementById('cancel-appointment-modal').style.display = 'none'; return false;" @click.stop="showCancelModal = false" class="relative z-10 cursor-pointer p-2 text-gray-400 hover:text-gray-600" aria-label="Đóng hộp thoại hoàn tiền">✕</button>
                </div>

                <form method="POST" action="{{ route('customer.appointments.cancel', ['id' => $appointment->id]) }}" class="mt-4 space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-[#0F172A] mb-1">Tên ngân hàng thụ hưởng *</label>
                        <input type="text" x-model="bankName" name="bank_name" required placeholder="Ví dụ: Vietcombank, Techcombank, MB Bank"
                               class="w-full rounded-xl border border-gray-300 p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block font-bold text-[#0F172A] mb-1">Số tài khoản ngân hàng *</label>
                        <input type="text" x-model="bankAccount" name="bank_account" required placeholder="Ví dụ: 0987654321"
                               class="w-full rounded-xl border border-gray-300 p-2.5 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-[#0F172A] mb-1">Họ tên chủ tài khoản (Viết hoa không dấu) *</label>
                        <input type="text" x-model="bankOwner" name="bank_owner" required placeholder="NGUYEN VAN AN"
                               class="w-full rounded-xl border border-gray-300 p-2.5 text-sm uppercase">
                    </div>

                    <p class="text-[11px] text-gray-500 italic">
                        * Thông tin tài khoản chỉ được dùng cho mục đích hoàn tiền và được bảo mật tuyệt đối.
                    </p>

                    <div class="mt-6 flex justify-between gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="cancelStep = 1" class="text-xs font-bold text-gray-600 hover:underline">
                            ← Quay lại
                        </button>
                        <button type="submit" class="rounded-xl bg-red-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-red-700">
                            Xác nhận hủy & Gửi yêu cầu hoàn tiền
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    {{-- ═══ MODAL YÊU CẦU ĐỔI LỊCH (SCR-CUS-06) ═══ --}}
    <div id="reschedule-appointment-modal" x-show="showRescheduleModal" x-cloak @click.self="showRescheduleModal = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="pointer-events-auto w-full max-w-lg rounded-2xl bg-white p-6 sm:p-8 shadow-xl space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-[#0F172A]">Yêu cầu dời lịch hẹn</h3>
                    <p class="text-xs text-[#4A5568] mt-0.5">Số lần đã đổi: {{ $appointment->reschedule_count ?? 0 }} / 2 lần tối đa</p>
                </div>
                <button type="button" data-modal-close="reschedule-appointment-modal" onclick="document.getElementById('reschedule-appointment-modal').style.display = 'none'; return false;" @click.stop="showRescheduleModal = false" @keydown.escape.window="showRescheduleModal = false" class="relative z-10 cursor-pointer p-2 text-gray-400 hover:text-gray-600" aria-label="Đóng hộp thoại đổi lịch">✕</button>
            </div>

            <form method="POST" action="{{ route('customer.appointments.reschedule', ['id' => $appointment->id]) }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-[#0F172A] mb-1">Chọn ngày tư vấn mới *</label>
                    <input type="date" x-model="newDate" name="new_date" required min="{{ date('Y-m-d') }}"
                           class="w-full rounded-xl border border-gray-300 p-2.5 text-sm font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-[#0F172A] mb-1">Khung giờ mới *</label>
                    <select x-model="newTime" name="new_time" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm">
                        <option value="08:00 - 09:00">08:00 - 09:00</option>
                        <option value="09:30 - 10:30">09:30 - 10:30</option>
                        <option value="11:00 - 12:00">11:00 - 12:00</option>
                        <option value="13:30 - 14:30">13:30 - 14:30</option>
                        <option value="15:00 - 16:00">15:00 - 16:00</option>
                        <option value="16:30 - 17:30">16:30 - 17:30</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-[#0F172A] mb-1">Lý do dời lịch (tùy chọn)</label>
                    <textarea x-model="rescheduleReason" name="reason" rows="3" placeholder="Ghi chú lý do nếu cần..."
                              class="w-full rounded-xl border border-gray-300 p-2.5 text-xs text-[#0F172A]"></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" data-modal-close="reschedule-appointment-modal" onclick="document.getElementById('reschedule-appointment-modal').style.display = 'none'; return false;" @click.stop="showRescheduleModal = false" class="relative z-10 cursor-pointer rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-bold text-gray-700">
                        Hủy
                    </button>
                    <button type="submit" class="rounded-xl bg-[#2D82C4] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#23689C]">
                        Gửi yêu cầu đổi lịch
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@push('scripts')
<script>
    document.addEventListener('click', function (event) {
        const openButton = event.target.closest('[data-modal-open]');
        if (openButton) {
            const modal = document.getElementById(openButton.dataset.modalOpen);
            if (modal) modal.style.display = '';
            return;
        }

        const closeButton = event.target.closest('[data-modal-close]');
        if (!closeButton) return;

        const modal = document.getElementById(closeButton.dataset.modalClose);
        if (!modal) return;

        modal.style.display = 'none';
        const stateRoot = modal.closest('[x-data]');
        if (stateRoot && window.Alpine && typeof window.Alpine.$data === 'function') {
            const state = window.Alpine.$data(stateRoot);
            if (closeButton.dataset.modalClose === 'cancel-appointment-modal') state.showCancelModal = false;
            if (closeButton.dataset.modalClose === 'reschedule-appointment-modal') state.showRescheduleModal = false;
        }
    });
    window.addEventListener('pageshow', function () {
        ['cancel-appointment-modal', 'reschedule-appointment-modal'].forEach(function (id) {
            const modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
        });
    });
</script>
@endpush
@endsection
