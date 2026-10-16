{{--
    SCR-CUS-03 — Đặt lịch tư vấn tâm lý kèm bước thanh toán (FR-APPT-001, FR-SCHEDULE-004, FR-PAY-001/002/006)
    ============================================================================================================
    Căn cứ: ThietKeGiaoDien.md §SCR-CUS-03 + §1.1 -> §1.8 + rules.md
    Bố cục:
      - Cảnh báo an toàn khẩn cấp cố định ở đầu trang (NFR-USA-003).
      - Layout 2 cột:
        + Cột trái (lg:col-span-8): Quy trình các bước đặt lịch (Wizard).
        + Cột phải (lg:col-span-4): Thẻ tóm tắt thông tin đặt lịch & tổng tiền (Sticky Sidebar).
      - Bước 3 (Ngày & Giờ): Tinh chỉnh grid grid-cols-2 md:grid-cols-4 gap-3 chuẩn Tailwind, ô chọn ngày hiện đại.
--}}

@extends('layouts.app')

@section('title', 'Đặt lịch Tư vấn Tâm lý — Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Đặt lịch tư vấn</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- ── 1. KHỐI CẢNH BÁO AN TOÀN KHẨN CẤP CỐ ĐỊNH Ở ĐẦU MÀN HÌNH (NFR-USA-003, SCR-CUS-03) ── --}}
    <aside aria-label="Cảnh báo an toàn khẩn cấp" class="mb-8 rounded-2xl border-2 border-amber-300 bg-amber-50/90 p-4 sm:p-5 shadow-xs">
        <div class="flex items-start gap-3.5">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-200/80 text-amber-900">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-sm font-bold text-amber-950 sm:text-base">
                    Lưu ý quan trọng: Mind Guide không phục vụ các tình huống cấp cứu tâm thần khẩn cấp
                </h2>
                <p class="mt-1 text-xs sm:text-sm text-amber-900 leading-relaxed">
                    Nếu bạn hoặc người thân đang trong cơn khủng hoảng cảm xúc cấp bách, có suy nghĩ tự làm hại bản thân hoặc cần can thiệp y tế tức thời,
                    vui lòng gọi ngay <a href="tel:115" class="font-bold underline text-amber-950 hover:text-red-700">115</a> (Cấp cứu y tế)
                    hoặc đường dây nóng quốc gia <a href="tel:19001567" class="font-bold underline text-amber-950 hover:text-red-700">1900 1567</a> (Hỗ trợ tâm thần miễn phí 24/7).
                </p>
            </div>
        </div>
    </aside>

    {{-- ── 2. DỮ LIỆU ĐỂ ALPINE SỬ DỤNG ── --}}
    @php
        $servicesJson = $services->map(function ($s) {
            return [
                'id' => (int) $s->id,
                'name' => $s->name,
                'category_name' => $s->category_name ?? ($s->category->name ?? 'Tư vấn tâm lý'),
                'duration_minutes' => (int) ($s->duration_minutes ?? 60),
                'price' => $s->price !== null ? (int) $s->price : 0,
                'price_label' => $s->price !== null ? number_format($s->price, 0, ',', '.') . ' đ' : 'Liên hệ niêm yết',
            ];
        })->values();

        $specialistsJson = $specialists->map(function ($sp) {
            return [
                'id' => (int) $sp->id,
                'name' => $sp->name,
                'title' => $sp->title,
                'rating' => (float) ($sp->average_rating ?? $sp->rating ?? 5.0),
                'reviews_count' => (int) ($sp->visible_reviews_count ?? $sp->reviews_count ?? 0),
                'experience' => is_numeric($sp->experience_years ?? null) ? ($sp->experience_years . ' năm kinh nghiệm') : ($sp->experience ?? 'Nhiều năm kinh nghiệm'),
                'service_ids' => array_map('intval', (array) ($sp->service_ids ?? [1, 2, 3, 4, 5, 6])),
            ];
        })->values();

        $defaultServiceId = $selectedServiceId ? (int) $selectedServiceId : 1;
        $defaultSpecialistId = $selectedSpecialistId ? (int) $selectedSpecialistId : 1;
    @endphp

    {{-- ── 3. KHỐI WIZARD ALPINE.JS VỚI LAYOUT 2 CỘT ── --}}
    <div x-data="{
        step: 1,
        totalSteps: 6,
        servicesData: JSON.parse(decodeURIComponent('{{ rawurlencode(json_encode($servicesJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}')),
        specialistsData: JSON.parse(decodeURIComponent('{{ rawurlencode(json_encode($specialistsJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}')),

        // State người dùng lựa chọn
        selectedServiceId: {{ $defaultServiceId }},
        selectedSpecialistId: {{ $defaultSpecialistId }},
        selectedDate: '{{ date('Y-m-d', strtotime('+1 day')) }}',
        selectedTime: '09:30 - 10:30',
        note: '',
        paymentMethod: 'CASH',
        agreeTerms: false,
        agreeRefundPolicy: false,

        // State kết quả đặt lịch
        isBookingSuccess: false,
        bookingCode: 'MG' + Math.floor(100000 + Math.random() * 900000),
        countdownMinutes: 10,
        countdownSeconds: 0,
        timerInterval: null,
        bookingError: '',
        isSubmitting: false,

        init() {
            this.ensureSpecialistCompatibility();
        },

        ensureSpecialistCompatibility() {
            const available = this.filteredSpecialists();
            if (available.length > 0) {
                const isCurrentValid = available.some(sp => sp.id === parseInt(this.selectedSpecialistId));
                if (!isCurrentValid) {
                    this.selectedSpecialistId = available[0].id;
                }
            }
        },

        filteredSpecialists() {
            const sId = parseInt(this.selectedServiceId);
            return this.specialistsData.filter(sp => sp.service_ids.includes(sId));
        },

        getSelectedService() {
            return this.servicesData.find(s => s.id === parseInt(this.selectedServiceId)) || this.servicesData[0] || {};
        },

        getSelectedSpecialist() {
            return this.specialistsData.find(sp => sp.id === parseInt(this.selectedSpecialistId)) || this.specialistsData[0] || {};
        },

        formatMoney(amount) {
            if (!amount) return '0 đ';
            return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
        },

        formatDisplayDate(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return dateStr;
        },

        setDate(offsetDays) {
            const d = new Date();
            d.setDate(d.getDate() + offsetDays);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            this.selectedDate = `${yyyy}-${mm}-${dd}`;
        },

        nextStep() {
            if (this.step < this.totalSteps) {
                this.step++;
                window.scrollTo({ top: 120, behavior: 'smooth' });
            }
        },

        prevStep() {
            if (this.step > 1) {
                this.step--;
                window.scrollTo({ top: 120, behavior: 'smooth' });
            }
        },

        goToStep(targetStep) {
            if (targetStep < this.step) {
                this.step = targetStep;
                window.scrollTo({ top: 120, behavior: 'smooth' });
            }
        },

        async confirmBooking() {
            this.bookingError = '';
            this.isSubmitting = true;

            try {
                const response = await fetch('{{ route('appointments.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('[name=csrf-token]')?.content || '',
                    },
                    body: JSON.stringify({
                        service_id: this.selectedServiceId,
                        specialist_id: this.selectedSpecialistId,
                        date: this.selectedDate,
                        time: this.selectedTime,
                        customer_note: this.note,
                        payment_method: this.paymentMethod,
                    }),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.error?.message || 'Không thể tạo lịch hẹn. Vui lòng thử lại.');
                }

                this.bookingCode = payload.data.appointment_code;
                this.isBookingSuccess = true;
                window.scrollTo({ top: 100, behavior: 'smooth' });
            } catch (error) {
                this.bookingError = error.message || 'Không thể tạo lịch hẹn. Vui lòng thử lại.';
            } finally {
                this.isSubmitting = false;
            }
        },

        startCountdown() {
            this.countdownMinutes = 10;
            this.countdownSeconds = 0;
            if (this.timerInterval) clearInterval(this.timerInterval);

            this.timerInterval = setInterval(() => {
                if (this.countdownSeconds > 0) {
                    this.countdownSeconds--;
                } else if (this.countdownMinutes > 0) {
                    this.countdownMinutes--;
                    this.countdownSeconds = 59;
                } else {
                    clearInterval(this.timerInterval);
                }
            }, 1000);
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('Đã sao chép: ' + text);
        }
    }">

        {{-- ── GIAO DIỆN KHI ĐẶT LỊCH THÀNH CÔNG ── --}}
        <div x-show="isBookingSuccess" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-10 shadow-sm">

            {{-- TRƯỜNG HỢP 1: TIỀN MẶT TẠI QUẦY (SCR-CUS-03) --}}
            <template x-if="paymentMethod === 'CASH'">
                <div class="text-center max-w-2xl mx-auto">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    <h2 class="mt-4 text-2xl font-black text-[#0F172A] sm:text-3xl">
                        Đặt lịch thành công! Vui lòng đến trước 10 phút để thực hiện dịch vụ.
                    </h2>
                    <p class="mt-2 text-sm text-[#4A5568]">
                        Mã lịch hẹn của bạn là: <strong class="text-[#1D4B43] text-base" x-text="bookingCode"></strong>.
                        Lịch hẹn đã được hệ thống ghi nhận ở trạng thái đã xác nhận (CONFIRMED).
                    </p>

                    <div class="mt-8 rounded-xl border border-gray-200 bg-[#F7FAFC] p-6 text-left space-y-3">
                        <div class="flex justify-between border-b border-gray-200 pb-2 text-sm">
                            <span class="text-[#4A5568]">Dịch vụ:</span>
                            <span class="font-bold text-[#0F172A]" x-text="getSelectedService().name"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-2 text-sm">
                            <span class="text-[#4A5568]">Chuyên viên:</span>
                            <span class="font-bold text-[#0F172A]" x-text="getSelectedSpecialist().name"></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-2 text-sm">
                            <span class="text-[#4A5568]">Thời gian tư vấn:</span>
                            <span class="font-bold text-[#0F172A]"><span x-text="selectedTime"></span> ngày <span x-text="formatDisplayDate(selectedDate)"></span></span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-2 text-sm">
                            <span class="text-[#4A5568]">Phương thức:</span>
                            <span class="font-bold text-[#1D4B43]">Tiền mặt tại quầy</span>
                        </div>
                        <div class="flex justify-between text-base pt-1">
                            <span class="font-bold text-[#0F172A]">Số tiền cần thanh toán tại quầy:</span>
                            <span class="font-black text-[#1D4B43] text-lg" x-text="formatMoney(getSelectedService().price)"></span>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap justify-center gap-4">
                        <a href="{{ url('/') }}" class="rounded-xl bg-[#1D4B43] px-6 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                            Về trang chủ
                        </a>
                        <a href="{{ route('customer.appointments') }}" class="rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-bold text-[#0F172A] hover:bg-gray-50 transition">
                            Xem lịch hẹn của tôi
                        </a>
                    </div>
                </div>
            </template>

            {{-- TRƯỜNG HỢP 2: CHUYỂN KHOẢN QR VIETQR (SCR-CUS-03) --}}
            <template x-if="paymentMethod === 'BANK_QR'">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3.5 py-1 text-xs font-bold text-amber-900 mb-2">
                            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Trạng thái: Chờ thanh toán (Giữ chỗ 10 phút)
                        </span>
                        <h2 class="text-2xl font-black text-[#0F172A] sm:text-3xl">
                            Quét mã VietQR để hoàn tất giữ chỗ
                        </h2>
                        <p class="mt-2 text-sm text-[#4A5568]">
                            Vui lòng quét mã QR hoặc chuyển khoản chính xác nội dung bên dưới. Hệ thống sẽ xác nhận tự động sau khi nhận được tiền.
                        </p>

                        {{-- Đồng hồ đếm ngược 10 phút --}}
                        <div class="mt-4 inline-flex items-center gap-2 rounded-xl bg-red-50 border border-red-200 px-4 py-2 text-red-700 font-bold text-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Thời gian giữ chỗ còn lại:</span>
                            <span class="font-mono text-base font-black" x-text="`${String(countdownMinutes).padStart(2, '0')}:${String(countdownSeconds).padStart(2, '0')}`"></span>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8 items-center bg-[#F7FAFC] p-6 rounded-2xl border border-gray-200">
                        {{-- Cột QR động --}}
                        <div class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-xs border border-gray-200">
                            <div class="w-56 h-56 bg-gray-100 rounded-lg flex items-center justify-center relative overflow-hidden border">
                                <svg class="w-48 h-48 text-[#1D4B43]" viewBox="0 0 100 100" fill="currentColor">
                                    <path d="M10 10h30v30h-30z M15 15v20h20v-20z M20 20h10v10h-10z M60 10h30v30h-30z M65 15v20h20v-20z M70 20h10v10h-10z M10 60h30v30h-30z M15 65v20h20v-20z M20 70h10v10h-10z M45 10h10v20h-10z M10 45h20v10h-20z M45 45h10v10h-10z M60 45h20v10h-20z M45 60h10v30h-10z M60 60h10v10h-10z M75 60h15v15h-15z M60 75h10v15h-10z M75 80h15v10h-15z"/>
                                </svg>
                                <div class="absolute inset-x-0 bottom-1 text-center bg-white/90 text-[10px] font-bold text-[#1D4B43] py-0.5">
                                    VIETQR • MBBANK
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-[#4A5568] text-center font-medium">
                                Sử dụng App Ngân hàng hoặc Ví MoMo để quét mã
                            </p>
                        </div>

                        {{-- Cột thông tin chuyển khoản thủ công --}}
                        <div class="space-y-3.5 text-sm">
                            <div class="border-b border-gray-200 pb-2">
                                <span class="text-xs text-[#4A5568] block">Ngân hàng thụ hưởng:</span>
                                <span class="font-bold text-[#0F172A]">MB Bank (Ngân hàng Quân Đội)</span>
                            </div>
                            <div class="border-b border-gray-200 pb-2 flex justify-between items-center">
                                <div>
                                    <span class="text-xs text-[#4A5568] block">Số tài khoản:</span>
                                    <span class="font-mono font-bold text-[#0F172A] text-base">0987654321</span>
                                </div>
                                <button type="button" @click="copyToClipboard('0987654321')" class="text-xs font-bold text-[#1D4B43] hover:underline bg-[#E8F0EC] px-2.5 py-1 rounded-md">
                                    Sao chép
                                </button>
                            </div>
                            <div class="border-b border-gray-200 pb-2">
                                <span class="text-xs text-[#4A5568] block">Chủ tài khoản:</span>
                                <span class="font-bold text-[#0F172A]">PHONG KHAM TAM LY MIND GUIDE</span>
                            </div>
                            <div class="border-b border-gray-200 pb-2 flex justify-between items-center">
                                <div>
                                    <span class="text-xs text-[#4A5568] block">Số tiền thanh toán:</span>
                                    <span class="font-mono font-black text-[#1D4B43] text-lg" x-text="formatMoney(getSelectedService().price)"></span>
                                </div>
                                <button type="button" @click="copyToClipboard(getSelectedService().price)" class="text-xs font-bold text-[#1D4B43] hover:underline bg-[#E8F0EC] px-2.5 py-1 rounded-md">
                                    Sao chép
                                </button>
                            </div>
                            <div class="flex justify-between items-center bg-amber-50 p-3 rounded-xl border border-amber-200">
                                <div>
                                    <span class="text-xs font-bold text-amber-900 block">Nội dung chuyển khoản (bắt buộc):</span>
                                    <span class="font-mono font-black text-[#1D4B43] text-base" x-text="'DATLICH ' + bookingCode"></span>
                                </div>
                                <button type="button" @click="copyToClipboard('DATLICH ' + bookingCode)" class="text-xs font-bold text-white bg-[#1D4B43] px-3 py-1.5 rounded-lg hover:bg-[#14352F] transition">
                                    Sao chép
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-center text-[#4A5568] italic">
                        * Vui lòng chuyển đúng số tiền và đúng nội dung chuyển khoản để hệ thống xác thực tự động trong 10-30 giây.
                    </p>

                    <div class="mt-8 flex flex-wrap justify-center gap-4">
                        <button type="button" @click="alert('Hệ thống đang kiểm tra giao dịch với ngân hàng...')" class="rounded-xl bg-[#1D4B43] px-6 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                            Tôi đã chuyển tiền thành công
                        </button>
                        <a href="{{ route('customer.appointments') }}" class="rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-bold text-[#0F172A] hover:bg-gray-50 transition">
                            Về danh sách lịch hẹn
                        </a>
                    </div>
                </div>
            </template>

        </div>

        {{-- ── GIAO DIỆN WIZARD 2 CỘT (KHI CHƯA HOÀN TẤT) ── --}}
        <div x-show="!isBookingSuccess">

            {{-- Tiêu đề quy trình --}}
            <div class="max-w-2xl mb-8">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
                    <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
                    Đặt lịch tư vấn tâm lý trực tuyến
                </span>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-[#0F172A] sm:text-4xl">
                    Đặt lịch hẹn cùng chuyên gia
                </h1>
                <p class="mt-2 text-sm leading-relaxed text-[#334155]">
                    Lựa chọn dịch vụ, chuyên gia tâm lý và thời gian thích hợp. Thông tin được mã hóa bảo mật hoàn toàn.
                </p>
            </div>

            {{-- ═══ BẮT ĐẦU LAYOUT 2 CỘT ═══ --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- ── CỘT TRÁI (8/12): CÁC BƯỚC ĐẶT LỊCH (WIZARD) ── --}}
                <div class="lg:col-span-8 space-y-6">

                    {{-- Thanh tiến trình (Progress Bar) --}}
                    <div class="rounded-2xl bg-white p-5 border border-gray-200/80 shadow-xs">
                        <div class="flex items-center justify-between mb-3 text-xs font-bold text-[#4A5568]">
                            <span>Bước <strong class="text-[#1D4B43] text-sm" x-text="step"></strong> / 6</span>
                            <span x-text="[
                                '1. Chọn gói dịch vụ',
                                '2. Chọn chuyên viên',
                                '3. Chọn ngày & khung giờ',
                                '4. Ghi chú cá nhân',
                                '5. Phương thức thanh toán',
                                '6. Tóm tắt & Xác nhận'
                            ][step - 1]" class="text-[#1D4B43] text-sm font-bold"></span>
                        </div>

                        <div class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-[#1D4B43] h-2.5 rounded-full transition-all duration-300"
                                 :style="'width: ' + ((step / totalSteps) * 100) + '%'"></div>
                        </div>

                        {{-- Các bước clickable trên Desktop --}}
                        <div class="hidden md:grid grid-cols-6 gap-2 mt-4 text-center text-xs font-semibold">
                            <template x-for="(stName, idx) in ['Dịch vụ', 'Chuyên viên', 'Thời gian', 'Ghi chú', 'Thanh toán', 'Xác nhận']">
                                <button type="button"
                                        @click="goToStep(idx + 1)"
                                        :disabled="idx + 1 > step"
                                        class="py-1 px-2 rounded-md transition text-left sm:text-center truncate"
                                        :class="step > idx + 1 ? 'text-[#1D4B43] font-bold hover:bg-[#E8F0EC]' : (step === idx + 1 ? 'text-[#0F172A] font-extrabold border-b-2 border-[#1D4B43]' : 'text-gray-400 cursor-not-allowed')">
                                    <span x-text="(idx + 1) + '. ' + stName"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Khối nội dung từng bước --}}
                    <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">

                        {{-- ═══ BƯỚC 1: CHỌN DỊCH VỤ ═══ --}}
                        <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                            <div class="pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-[#0F172A]">Bước 1: Chọn gói dịch vụ tư vấn</h2>
                                <p class="mt-0.5 text-sm text-[#4A5568]">Chọn dịch vụ phù hợp nhất với nhu cầu và vấn đề của bạn.</p>
                            </div>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <template x-for="svc in servicesData" :key="svc.id">
                                    <label class="relative flex flex-col p-5 rounded-xl border-2 cursor-pointer transition select-none"
                                           :class="selectedServiceId == svc.id ? 'border-[#1D4B43] bg-[#E8F0EC]/40 shadow-xs ring-1 ring-[#1D4B43]' : 'border-gray-200 hover:border-[#709782] bg-white'">
                                        <input type="radio" name="service" :value="svc.id" x-model="selectedServiceId" @change="ensureSpecialistCompatibility()" class="sr-only">
                                        
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="inline-block text-[11px] font-bold text-[#1D4B43] bg-[#E8F0EC] px-2.5 py-0.5 rounded-full" x-text="svc.category_name"></span>
                                            <div class="h-4 w-4 rounded-full border flex items-center justify-center"
                                                 :class="selectedServiceId == svc.id ? 'border-[#1D4B43] bg-[#1D4B43]' : 'border-gray-300'">
                                                <div class="h-1.5 w-1.5 rounded-full bg-white" x-show="selectedServiceId == svc.id"></div>
                                            </div>
                                        </div>

                                        <span class="text-base font-bold text-[#0F172A] leading-snug flex-1" x-text="svc.name"></span>

                                        <div class="mt-4 pt-3 border-t border-gray-100/80 flex items-center justify-between text-xs font-semibold">
                                            <span class="text-[#4A5568] flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-[#709782]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span x-text="svc.duration_minutes + ' phút'"></span>
                                            </span>
                                            <span class="text-[#1D4B43] text-sm font-black" x-text="svc.price_label"></span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- ═══ BƯỚC 2: CHỌN CHUYÊN VIÊN ═══ --}}
                        <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                            <div class="pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-[#0F172A]">Bước 2: Chọn chuyên viên tâm lý</h2>
                                <p class="mt-0.5 text-sm text-[#4A5568]">
                                    Đang lọc chuyên gia phụ trách gói: <strong class="text-[#1D4B43]" x-text="getSelectedService().name"></strong>
                                </p>
                            </div>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <template x-for="spc in filteredSpecialists()" :key="spc.id">
                                    <label class="relative flex items-start gap-4 p-5 rounded-xl border-2 cursor-pointer transition select-none"
                                           :class="selectedSpecialistId == spc.id ? 'border-[#1D4B43] bg-[#E8F0EC]/40 shadow-xs ring-1 ring-[#1D4B43]' : 'border-gray-200 hover:border-[#709782] bg-white'">
                                        <input type="radio" name="specialist" :value="spc.id" x-model="selectedSpecialistId" class="sr-only">
                                        
                                        <div class="h-14 w-14 rounded-full bg-[#E8F0EC] text-[#1D4B43] font-bold text-lg flex items-center justify-center flex-shrink-0 ring-2 ring-[#709782]/30"
                                             x-text="spc.name.charAt(0)">
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="text-base font-bold text-[#0F172A] truncate" x-text="spc.name"></span>
                                                <div class="h-4 w-4 rounded-full border flex items-center justify-center flex-shrink-0"
                                                     :class="selectedSpecialistId == spc.id ? 'border-[#1D4B43] bg-[#1D4B43]' : 'border-gray-300'">
                                                    <div class="h-1.5 w-1.5 rounded-full bg-white" x-show="selectedSpecialistId == spc.id"></div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-[#4A5568] mt-0.5" x-text="spc.title"></p>

                                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold">
                                                <span class="text-amber-600 flex items-center gap-1">
                                                    <span>★</span> <span x-text="spc.rating.toFixed(1)"></span>
                                                    <span class="text-gray-400 font-normal" x-text="'(' + spc.reviews_count + ')'"></span>
                                                </span>
                                                <span class="text-[#4A5568]" x-text="spc.experience"></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>

                            <div x-show="filteredSpecialists().length === 0" class="p-8 text-center bg-gray-50 rounded-xl mt-4">
                                <p class="text-sm text-gray-500">Chưa có chuyên viên nào phụ trách gói dịch vụ này. Vui lòng quay lại bước 1 chọn dịch vụ khác.</p>
                            </div>
                        </div>

                        {{-- ═══ BƯỚC 3: CHỌN NGÀY & KHUNG GIỜ (ĐÃ REFACTOR THEO YÊU CẦU GRID 2 / 4) ═══ --}}
                        <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                            <div class="pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-[#0F172A]">Bước 3: Chọn ngày & khung giờ tư vấn</h2>
                                <p class="mt-0.5 text-sm text-[#4A5568]">
                                    Lịch hẹn cùng chuyên viên: <strong class="text-[#1D4B43]" x-text="getSelectedSpecialist().name"></strong>
                                </p>
                            </div>

                            <div class="mt-6 space-y-6">
                                {{-- 1. KHU VỰC CHỌN NGÀY HIỆN ĐẠI --}}
                                <div class="rounded-2xl border border-gray-200 bg-[#F7FAFC] p-5">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                                        <label for="booking-date" class="flex items-center gap-2 text-sm font-bold text-[#0F172A]">
                                            <svg class="h-5 w-5 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>Chọn ngày tư vấn:</span>
                                        </label>

                                        {{-- Nút chọn nhanh (Quick Select) --}}
                                        <div class="flex items-center gap-1.5 text-xs font-semibold">
                                            <span class="text-[#4A5568] mr-1">Gợi ý:</span>
                                            <button type="button" @click="setDate(1)"
                                                    class="rounded-lg bg-white px-2.5 py-1 text-[#1D4B43] border border-gray-200 hover:bg-[#E8F0EC] transition shadow-2xs">
                                                Ngày mai
                                            </button>
                                            <button type="button" @click="setDate(2)"
                                                    class="rounded-lg bg-white px-2.5 py-1 text-[#1D4B43] border border-gray-200 hover:bg-[#E8F0EC] transition shadow-2xs">
                                                Ngày kia
                                            </button>
                                            <button type="button" @click="setDate(3)"
                                                    class="rounded-lg bg-white px-2.5 py-1 text-[#1D4B43] border border-gray-200 hover:bg-[#E8F0EC] transition shadow-2xs">
                                                Sau 3 ngày
                                            </button>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <input type="date"
                                               id="booking-date"
                                               x-model="selectedDate"
                                               min="{{ date('Y-m-d') }}"
                                               max="{{ date('Y-m-d', strtotime('+30 days')) }}"
                                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base font-bold text-[#0F172A] shadow-xs focus:border-[#1D4B43] focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 transition">
                                    </div>

                                    <div class="mt-2.5 flex items-center justify-between text-xs text-[#4A5568]">
                                        <span>Ngày đang chọn: <strong class="text-[#1D4B43] font-bold" x-text="formatDisplayDate(selectedDate)"></strong></span>
                                        <span>* Hỗ trợ đặt trước từ 2 giờ đến tối đa 30 ngày.</span>
                                    </div>
                                </div>

                                {{-- 2. KHU VỰC CHỌN KHUNG GIỜ: GRID GRID-COLS-2 MD:GRID-COLS-4 GAP-3 CHUẨN --}}
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <label class="flex items-center gap-2 text-sm font-bold text-[#0F172A]">
                                            <svg class="h-5 w-5 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Khung giờ khả dụng trong ngày:</span>
                                        </label>
                                        <span class="text-xs text-emerald-700 font-semibold flex items-center gap-1">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            Thời gian thực
                                        </span>
                                    </div>

                                    {{-- Grid 2 cột mobile, 4 cột máy tính theo đúng yêu cầu người dùng --}}
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        @foreach(['08:00 - 09:00', '09:30 - 10:30', '11:00 - 12:00', '13:30 - 14:30', '15:00 - 16:00', '16:30 - 17:30', '18:00 - 19:00', '19:30 - 20:30'] as $slot)
                                            <label class="relative flex flex-col items-center justify-center p-3.5 rounded-xl border-2 cursor-pointer font-bold text-xs sm:text-sm transition select-none shadow-2xs"
                                                   :class="selectedTime == '{{ $slot }}' ? 'border-[#1D4B43] bg-[#1D4B43] text-white shadow-md ring-2 ring-[#1D4B43]/30 scale-[1.02]' : 'border-gray-200 text-[#0F172A] hover:border-[#709782] bg-white hover:bg-gray-50'">
                                                <input type="radio" name="time" value="{{ $slot }}" x-model="selectedTime" class="sr-only">
                                                <span class="tracking-tight whitespace-nowrap">{{ $slot }}</span>
                                                <span class="text-[10px] font-normal mt-1 flex items-center gap-1"
                                                      :class="selectedTime == '{{ $slot }}' ? 'text-emerald-200' : 'text-emerald-600'">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                    Còn chỗ
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>

                                    <p class="mt-3 text-xs text-[#4A5568] italic">
                                        * Thời lượng buổi tư vấn được xác định theo gói dịch vụ đã chọn (<span x-text="getSelectedService().duration_minutes"></span> phút).
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- ═══ BƯỚC 4: GHI CHÚ CHO CHUYÊN VIÊN ═══ --}}
                        <div x-show="step === 4" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                            <div class="pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-[#0F172A]">Bước 4: Ghi chú trước buổi hẹn (Tùy chọn)</h2>
                                <p class="mt-0.5 text-sm text-[#4A5568]">Chia sẻ ngắn gọn điều bạn muốn chuyên viên lắng nghe để buổi tư vấn đạt hiệu quả cao nhất.</p>
                            </div>

                            <div class="mt-6">
                                <label for="booking-note" class="sr-only">Ghi chú cá nhân</label>
                                <textarea id="booking-note"
                                          x-model="note"
                                          rows="5"
                                          maxlength="500"
                                          placeholder="Ví dụ: Dạo gần đây tôi hay bị mất ngủ, căng thẳng vì áp lực công việc hoặc cần tham vấn về mối quan hệ..."
                                          class="w-full rounded-xl border border-gray-300 p-4 text-sm text-[#0F172A] focus:border-[#1D4B43] focus:ring-2 focus:ring-[#1D4B43]/20 bg-[#F7FAFC] leading-relaxed"></textarea>
                                
                                <div class="mt-2 flex items-center justify-between text-xs text-[#4A5568]">
                                    <span class="flex items-center gap-1 text-emerald-700 font-semibold">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Bảo mật §1.7: Chỉ chuyên viên phụ trách buổi hẹn mới đọc được ghi chú này.
                                    </span>
                                    <span :class="note.length > 450 ? 'text-amber-600 font-bold' : ''">
                                        <span x-text="note.length"></span>/500 ký tự
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- ═══ BƯỚC 5: PHƯƠNG THỨC THANH TOÁN & HOÀN TIỀN ═══ --}}
                        <div x-show="step === 5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                            <div class="pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-[#0F172A]">Bước 5: Chọn phương thức thanh toán</h2>
                                <p class="mt-0.5 text-sm text-[#4A5568]">Minh bạch, bảo mật và hoàn tiền rõ ràng theo đúng cam kết.</p>
                            </div>

                            <div class="mt-6 space-y-4">
                                <label class="flex items-start gap-4 p-5 rounded-xl border-2 cursor-not-allowed transition select-none opacity-60 border-gray-200 bg-gray-50">
                                    <input type="radio" name="paymentMethod" value="BANK_QR" class="mt-1" disabled>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-[#0F172A] text-base">Chuyển khoản QR (sắp mở)</span>
                                            <span class="text-xs font-bold text-gray-600 bg-gray-200 px-2 py-0.5 rounded">Đang hoàn thiện</span>
                                        </div>
                                        <p class="text-xs text-[#4A5568] mt-1 leading-relaxed">
                                            Tính năng thanh toán VietQR/SePay sẽ được bổ sung sau khi luồng đặt lịch tiền mặt ổn định.
                                        </p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-4 p-5 rounded-xl border-2 cursor-pointer transition select-none"
                                       :class="paymentMethod === 'CASH' ? 'border-[#1D4B43] bg-[#E8F0EC]/40 shadow-xs ring-1 ring-[#1D4B43]' : 'border-gray-200 hover:border-[#709782] bg-white'">
                                    <input type="radio" name="paymentMethod" value="CASH" x-model="paymentMethod" class="mt-1">
                                    <div class="flex-1">
                                        <span class="font-bold text-[#0F172A] text-base">Tiền mặt tại quầy lễ tân</span>
                                        <p class="text-xs text-[#4A5568] mt-1 leading-relaxed">
                                            Xác nhận lịch hẹn ngay lập tức. Bạn thanh toán trực tiếp tại quầy khi đến phòng khám (vui lòng đến trước 10 phút).
                                        </p>
                                    </div>
                                </label>
                            </div>

                            {{-- Bảng chính sách hủy & hoàn tiền minh bạch (3 mốc) --}}
                            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50/70 p-5">
                                <div class="flex items-center gap-2 mb-2 text-amber-900 font-bold text-xs uppercase tracking-wider">
                                    <svg class="h-4 w-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                                    </svg>
                                    Chính sách hủy hẹn & Hoàn tiền minh bạch (CR-001)
                                </div>
                                <ul class="text-xs text-amber-900 space-y-2 leading-relaxed">
                                    <li class="flex items-start gap-2">
                                        <span class="font-bold text-emerald-800">• Hoàn 100%:</span> Khi hủy lịch trước giờ hẹn từ <strong>24 giờ trở lên</strong>.
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="font-bold text-amber-800">• Hoàn 50%:</span> Khi hủy lịch trong khoảng từ <strong>12 giờ đến dưới 24 giờ</strong> trước giờ hẹn.
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="font-bold text-red-800">• Hoàn 0%:</span> Khi hủy lịch trong vòng <strong>dưới 12 giờ</strong> trước giờ hẹn.
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {{-- ═══ BƯỚC 6: XÁC NHẬN TOÀN BỘ THÔNG TIN ═══ --}}
                        <div x-show="step === 6" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                            <div class="pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-[#0F172A]">Bước 6: Xác nhận thông tin lịch hẹn</h2>
                                <p class="mt-0.5 text-sm text-[#4A5568]">Vui lòng rà soát lại thông tin trước khi nhấn nút xác nhận đặt lịch.</p>
                            </div>

                            <div class="mt-6 rounded-2xl border border-gray-200 bg-[#F7FAFC] p-6 space-y-3.5">
                                <div class="flex justify-between items-center border-b border-gray-200 pb-2.5 text-sm">
                                    <span class="text-[#4A5568]">Gói dịch vụ:</span>
                                    <span class="font-bold text-[#0F172A]" x-text="getSelectedService().name"></span>
                                </div>
                                <div class="flex justify-between items-center border-b border-gray-200 pb-2.5 text-sm">
                                    <span class="text-[#4A5568]">Chuyên viên phụ trách:</span>
                                    <span class="font-bold text-[#0F172A]" x-text="getSelectedSpecialist().name"></span>
                                </div>
                                <div class="flex justify-between items-center border-b border-gray-200 pb-2.5 text-sm">
                                    <span class="text-[#4A5568]">Ngày tư vấn:</span>
                                    <span class="font-bold text-[#0F172A]" x-text="formatDisplayDate(selectedDate)"></span>
                                </div>
                                <div class="flex justify-between items-center border-b border-gray-200 pb-2.5 text-sm">
                                    <span class="text-[#4A5568]">Khung giờ:</span>
                                    <span class="font-bold text-[#0F172A]" x-text="selectedTime"></span>
                                </div>
                                <div class="flex justify-between items-center border-b border-gray-200 pb-2.5 text-sm">
                                    <span class="text-[#4A5568]">Hình thức thanh toán:</span>
                                    <span class="font-bold text-[#1D4B43]" x-text="paymentMethod === 'BANK_QR' ? 'Chuyển khoản QR (VietQR)' : 'Tiền mặt tại quầy'"></span>
                                </div>
                                <div class="flex justify-between items-center pt-1 text-base">
                                    <span class="font-bold text-[#0F172A]">Tổng thanh toán:</span>
                                    <span class="font-black text-[#1D4B43] text-xl" x-text="formatMoney(getSelectedService().price)"></span>
                                </div>
                            </div>

                            {{-- Checkbox đồng ý điều khoản & chính sách --}}
                            <div class="mt-6 space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="agreeTerms" class="mt-1 h-4 w-4 rounded border-gray-300 text-[#1D4B43] focus:ring-[#1D4B43]">
                                    <span class="text-xs text-[#334155] leading-relaxed">
                                        Tôi đồng ý với <a href="{{ route('terms') }}" target="_blank" class="font-bold text-[#1D4B43] underline">Điều khoản sử dụng</a> và
                                        <a href="{{ route('privacy') }}" target="_blank" class="font-bold text-[#1D4B43] underline">Chính sách quyền riêng tư</a> của Mind Guide.
                                    </span>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="agreeRefundPolicy" class="mt-1 h-4 w-4 rounded border-gray-300 text-[#1D4B43] focus:ring-[#1D4B43]">
                                    <span class="text-xs text-[#334155] leading-relaxed">
                                        Tôi đã đọc và hiểu rõ <strong class="text-amber-900">Chính sách hủy & hoàn tiền</strong> theo 3 mốc thời gian quy định.
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Nút điều khiển Tiếp tục / Quay lại --}}
                        <div class="mt-8 flex items-center justify-between pt-6 border-t border-gray-100">
                            <button type="button"
                                    x-show="step > 1"
                                    @click="prevStep()"
                                    class="rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-bold text-[#0F172A] hover:bg-gray-50 transition shadow-xs">
                                ← Quay lại bước trước
                            </button>
                            <div x-show="step === 1"></div>

                            <button type="button"
                                    x-show="step < totalSteps"
                                    @click="nextStep()"
                                    class="rounded-xl bg-[#1D4B43] px-8 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs flex items-center gap-2">
                                <span>Tiếp tục</span>
                                <span>→</span>
                            </button>

                            <button type="button"
                                    x-show="step === totalSteps"
                                    :disabled="!agreeTerms || !agreeRefundPolicy || isSubmitting"
                                    :class="(!agreeTerms || !agreeRefundPolicy || isSubmitting) ? 'opacity-50 cursor-not-allowed bg-gray-400' : 'bg-[#1D4B43] hover:bg-[#14352F] shadow-md'"
                                    @click="confirmBooking()"
                                    class="rounded-xl px-8 py-3 text-sm font-bold text-white transition">
                                <span x-text="isSubmitting ? 'Đang xử lý...' : 'Xác nhận đặt lịch ngay'"></span>
                            </button>
                        </div>

                        <p x-show="bookingError" x-text="bookingError" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"></p>

                    </div>
                </div>

                {{-- ── CỘT PHẢI (4/12): THẺ TÓM TẮT THÔNG TIN ĐẶT LỊCH & TỔNG TIỀN (STICKY SIDEBAR) ── --}}
                <div class="lg:col-span-4 sticky top-20 space-y-4">
                    <div class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs">
                        <div class="flex items-center gap-2 pb-3.5 border-b border-gray-100 text-[#0F172A]">
                            <svg class="h-5 w-5 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="text-base font-bold text-[#0F172A]">Tóm tắt lịch hẹn</h3>
                        </div>

                        <div class="divide-y divide-gray-100 text-sm">
                            {{-- Dịch vụ --}}
                            <div class="py-3">
                                <span class="text-xs text-[#4A5568] block">Gói dịch vụ:</span>
                                <strong class="text-sm text-[#0F172A] leading-snug block mt-0.5" x-text="getSelectedService().name"></strong>
                                <span class="text-xs text-[#709782] font-semibold mt-1 inline-block" x-text="'Thời lượng: ' + getSelectedService().duration_minutes + ' phút'"></span>
                            </div>

                            {{-- Chuyên viên --}}
                            <div class="py-3">
                                <span class="text-xs text-[#4A5568] block">Chuyên viên phụ trách:</span>
                                <strong class="text-sm text-[#0F172A] block mt-0.5" x-text="getSelectedSpecialist().name"></strong>
                                <span class="text-xs text-amber-600 block mt-0.5" x-text="'★ ' + getSelectedSpecialist().rating + ' (' + getSelectedSpecialist().experience + ')'"></span>
                            </div>

                            {{-- Thời gian --}}
                            <div class="py-3">
                                <span class="text-xs text-[#4A5568] block">Thời gian tư vấn:</span>
                                <div class="mt-1 flex items-center gap-1.5 text-sm font-bold text-[#0F172A]">
                                    <svg class="h-4 w-4 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span x-text="formatDisplayDate(selectedDate)"></span>
                                </div>
                                <div class="mt-0.5 flex items-center gap-1.5 text-xs font-semibold text-[#1D4B43]">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="selectedTime"></span>
                                </div>
                            </div>

                            {{-- Phương thức --}}
                            <div class="py-3">
                                <span class="text-xs text-[#4A5568] block">Hình thức thanh toán:</span>
                                <span class="text-xs font-bold text-[#1D4B43] mt-0.5 block" x-text="paymentMethod === 'BANK_QR' ? 'Chuyển khoản QR (VietQR)' : 'Tiền mặt tại quầy'"></span>
                            </div>

                            {{-- Tổng tiền --}}
                            <div class="pt-3.5 pb-1">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-sm font-bold text-[#0F172A]">Tổng chi phí:</span>
                                    <span class="text-xl font-black text-[#1D4B43]" x-text="formatMoney(getSelectedService().price)"></span>
                                </div>
                                <span class="text-[11px] text-[#4A5568] block mt-1">* Đã bao gồm phí tham vấn & tài liệu theo dõi</span>
                            </div>
                        </div>
                    </div>

                    {{-- Khối thông tin bảo mật & hoàn tiền cam kết --}}
                    <div class="rounded-2xl border border-[#709782]/30 bg-[#E8F0EC]/50 p-4 text-xs text-[#334155] space-y-2">
                        <div class="flex items-center gap-2 font-bold text-[#1D4B43]">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span>Cam kết từ Mind Guide</span>
                        </div>
                        <ul class="space-y-1.5 leading-relaxed pl-1">
                            <li>✓ Mã hóa bảo mật thông tin buổi hẹn (§1.7)</li>
                            <li>✓ Hủy lịch trước 24h hoàn tiền 100%</li>
                            <li>✓ Đội ngũ chuyên gia được cấp phép chuẩn mực</li>
                        </ul>
                    </div>
                </div>

            </div>
            {{-- ═══ KẾT THÚC LAYOUT 2 CỘT ═══ --}}

        </div>

    </div>

</div>
@endsection
