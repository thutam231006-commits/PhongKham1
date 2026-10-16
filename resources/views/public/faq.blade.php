{{--
    Câu hỏi thường gặp — FAQ Mind Guide
    ====================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-03, §1.7, FR-PAY-009/010
--}}

@extends('layouts.app')

@section('title', 'Câu Hỏi Thường Gặp (FAQ) — Phòng Khám Tâm Lý Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Câu hỏi thường gặp (FAQ)</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-12">

    {{-- Tiêu đề trang --}}
    <div class="max-w-3xl">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
            <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
            Giải đáp thắc mắc về dịch vụ tư vấn
        </span>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-[#0F172A] sm:text-4xl">
            Câu Hỏi Thường Gặp
        </h1>
        <p class="mt-2 text-sm sm:text-base leading-relaxed text-[#334155]">
            Những thông tin giải đáp chi tiết về quy trình đặt lịch, chính sách bảo mật, thanh toán và các mốc hoàn tiền tại Mind Guide.
        </p>
    </div>

    {{-- Khối Accordion Alpine.js --}}
    <div x-data="{ activeAccordion: 1 }" class="max-w-4xl space-y-4">

        {{-- Câu 1: Quy trình đặt lịch và hình thức tham vấn --}}
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-xs">
            <button type="button"
                    @click="activeAccordion = (activeAccordion === 1 ? null : 1)"
                    class="w-full flex items-center justify-between p-5 text-left font-bold text-[#0F172A] hover:bg-gray-50 transition">
                <span class="text-base flex items-center gap-2">
                    <span class="text-[#1D4B43]">01.</span> Quy trình đặt lịch tư vấn diễn ra như thế nào?
                </span>
                <svg class="h-5 w-5 text-[#1D4B43] transition-transform duration-200"
                     :class="{ 'rotate-180': activeAccordion === 1 }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="activeAccordion === 1" x-collapse class="px-5 pb-5 text-sm text-[#4A5568] leading-relaxed border-t border-gray-100 pt-3">
                <p>
                    Quy trình gồm 3 bước đơn giản: (1) Bạn lựa chọn gói dịch vụ và chuyên viên tâm lý mong muốn; (2) Chọn ngày giờ còn trống theo lịch thời gian thực; (3) Điền ghi chú (tùy chọn) và xác nhận thanh toán (bằng VietQR tự động hoặc Tiền mặt tại quầy).
                </p>
                <p class="mt-2">
                    Sau khi hoàn tất, hệ thống sẽ gửi thông báo xác nhận và hướng dẫn chuẩn bị trước buổi hẹn qua email của bạn.
                </p>
            </div>
        </div>

        {{-- Câu 2: Chính sách hủy và hoàn tiền --}}
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-xs">
            <button type="button"
                    @click="activeAccordion = (activeAccordion === 2 ? null : 2)"
                    class="w-full flex items-center justify-between p-5 text-left font-bold text-[#0F172A] hover:bg-gray-50 transition">
                <span class="text-base flex items-center gap-2">
                    <span class="text-[#1D4B43]">02.</span> Chính sách hủy hẹn và hoàn tiền được quy định ra sao?
                </span>
                <svg class="h-5 w-5 text-[#1D4B43] transition-transform duration-200"
                     :class="{ 'rotate-180': activeAccordion === 2 }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="activeAccordion === 2" x-collapse class="px-5 pb-5 text-sm text-[#4A5568] leading-relaxed border-t border-gray-100 pt-3">
                <p>Theo đặc tả minh bạch của Mind Guide (FR-PAY-009/010), chính sách hoàn tiền áp dụng 3 mốc rõ ràng:</p>
                <ul class="mt-2 space-y-1.5 list-disc pl-5">
                    <li><strong>Hoàn lại 100%:</strong> Khi bạn thực hiện hủy lịch hẹn trước giờ hẹn từ <strong>24 giờ trở lên</strong>.</li>
                    <li><strong>Hoàn lại 50%:</strong> Khi bạn hủy lịch hẹn trong khoảng thời gian từ <strong>12 giờ đến dưới 24 giờ</strong> trước giờ hẹn.</li>
                    <li><strong>Không hoàn tiền (0%):</strong> Khi bạn hủy lịch trong vòng <strong>dưới 12 giờ</strong> trước giờ hẹn hoặc vắng mặt không báo trước.</li>
                </ul>
                <p class="mt-2 text-xs text-amber-800 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                    Khoản tiền hoàn sẽ được phòng khám đối soát và chuyển khoản trực tiếp về tài khoản ngân hàng của bạn trong 3–5 ngày làm việc.
                </p>
            </div>
        </div>

        {{-- Câu 3: Bảo mật thông tin thân chủ --}}
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-xs">
            <button type="button"
                    @click="activeAccordion = (activeAccordion === 3 ? null : 3)"
                    class="w-full flex items-center justify-between p-5 text-left font-bold text-[#0F172A] hover:bg-gray-50 transition">
                <span class="text-base flex items-center gap-2">
                    <span class="text-[#1D4B43]">03.</span> Thông tin và câu chuyện của tôi có được bảo mật không?
                </span>
                <svg class="h-5 w-5 text-[#1D4B43] transition-transform duration-200"
                     :class="{ 'rotate-180': activeAccordion === 3 }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="activeAccordion === 3" x-collapse class="px-5 pb-5 text-sm text-[#4A5568] leading-relaxed border-t border-gray-100 pt-3">
                <p>
                    <strong>Tuyệt đối bảo mật (§1.7 NFR-PRV).</strong> Toàn bộ dữ liệu ghi chú trước buổi hẹn và hồ sơ tham vấn đều được mã hóa trên máy chủ.
                </p>
                <p class="mt-2">
                    Chỉ có duy nhất chuyên viên phụ trách trực tiếp buổi hẹn mới có quyền truy cập hồ sơ của bạn. Kể cả nhân viên tiếp tân (Staff) hay quản trị viên hệ thống (Admin) cũng không được phép xem nội dung tư vấn cá nhân của khách hàng.
                </p>
            </div>
        </div>

        {{-- Câu 4: Buổi tư vấn trực tuyến hay trực tiếp --}}
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-xs">
            <button type="button"
                    @click="activeAccordion = (activeAccordion === 4 ? null : 4)"
                    class="w-full flex items-center justify-between p-5 text-left font-bold text-[#0F172A] hover:bg-gray-50 transition">
                <span class="text-base flex items-center gap-2">
                    <span class="text-[#1D4B43]">04.</span> Phòng khám có hỗ trợ tư vấn trực tuyến (Online) không?
                </span>
                <svg class="h-5 w-5 text-[#1D4B43] transition-transform duration-200"
                     :class="{ 'rotate-180': activeAccordion === 4 }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="activeAccordion === 4" x-collapse class="px-5 pb-5 text-sm text-[#4A5568] leading-relaxed border-t border-gray-100 pt-3">
                <p>
                    Có. Mind Guide cung cấp linh hoạt cả hai hình thức:
                </p>
                <ul class="mt-2 space-y-1 list-disc pl-5">
                    <li><strong>Tham vấn trực tiếp:</strong> Tại cơ sở phòng khám ở Quận 1, TP.HCM trong không gian cách âm, ấm cúng và thư giãn.</li>
                    <li><strong>Tham vấn trực tuyến (Online):</strong> Qua phòng họp video mã hóa riêng tư, giúp bạn kết nối với chuyên gia từ bất kỳ đâu.</li>
                </ul>
            </div>
        </div>

        {{-- Câu 5: Dành cho ai và giới hạn tuổi --}}
        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-xs">
            <button type="button"
                    @click="activeAccordion = (activeAccordion === 5 ? null : 5)"
                    class="w-full flex items-center justify-between p-5 text-left font-bold text-[#0F172A] hover:bg-gray-50 transition">
                <span class="text-base flex items-center gap-2">
                    <span class="text-[#1D4B43]">05.</span> Người dưới 18 tuổi có thể đặt lịch tư vấn được không?
                </span>
                <svg class="h-5 w-5 text-[#1D4B43] transition-transform duration-200"
                     :class="{ 'rotate-180': activeAccordion === 5 }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="activeAccordion === 5" x-collapse class="px-5 pb-5 text-sm text-[#4A5568] leading-relaxed border-t border-gray-100 pt-3">
                <p>
                    Theo quy định pháp lý và tiêu chuẩn hành nghề tâm lý (FR-AUTH-001), khách hàng đăng ký tài khoản tự đặt lịch trực tuyến phải từ <strong>18 tuổi trở lên</strong>.
                </p>
                <p class="mt-2">
                    Đối với các bạn thanh thiếu niên hoặc học sinh cần tham vấn tâm lý học đường, người đại diện hợp pháp (cha mẹ hoặc người giám hộ) cần đứng tên đặt lịch và đồng hành trong quá trình tham vấn ban đầu.
                </p>
            </div>
        </div>

    </div>

    {{-- Khối liên hệ thêm --}}
    <div class="rounded-2xl bg-[#E8F0EC]/60 border border-[#709782]/20 p-6 sm:p-8 max-w-4xl">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-[#1D4B43]">Bạn có câu hỏi nào khác chưa được giải đáp?</h3>
                <p class="text-xs sm:text-sm text-[#334155] mt-1">
                    Hãy liên hệ trực tiếp với bộ phận chăm sóc khách hàng của Mind Guide để được giải đáp tận tình.
                </p>
            </div>
            <a href="{{ route('contact') }}"
               class="rounded-xl bg-[#1D4B43] px-6 py-2.5 text-xs sm:text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs flex-shrink-0">
                Gửi câu hỏi ngay
            </a>
        </div>
    </div>

</div>
@endsection
