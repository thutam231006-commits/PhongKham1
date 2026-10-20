{{--
    Giới thiệu phòng khám tâm lý Mind Guide (About Us)
    ==================================================
    Phong cách: Soft Medical / Healing Minimalist (#2D82C4, #6FA6CF, #F5FAFE)
--}}

@extends('layouts.app')

@section('title', 'Về Chúng Tôi — Phòng Khám Tâm Lý Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Giới thiệu về Mind Guide</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-16">

    {{-- ── 1. HERO GIỚI THIỆU ── --}}
    <div class="max-w-3xl">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4]">
            <span class="h-2 w-2 rounded-full bg-[#6FA6CF] animate-pulse"></span>
            Không gian an toàn cho tâm hồn của bạn
        </span>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-[#0F172A] sm:text-5xl leading-tight">
            Đồng hành cùng bạn trên hành trình chữa lành và tìm lại an yên
        </h1>
        <p class="mt-4 text-base sm:text-lg leading-relaxed text-[#334155]">
            Mind Guide được thành lập với niềm tin mãnh liệt rằng chăm sóc sức khỏe tinh thần cũng quan trọng như chăm sóc thể chất.
            Chúng tôi tạo dựng một môi trường tham vấn chuyên nghiệp, bảo mật tuyệt đối, không định kiến và hoàn toàn lấy thân chủ làm trung tâm.
        </p>
    </div>

    {{-- ── 2. CÁC GIÁ TRỊ CỐT LÕI (CORE VALUES) ── --}}
    <div>
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-wider text-[#6FA6CF]">Kim chỉ nam hoạt động</span>
            <h2 class="mt-2 text-2xl font-black text-[#0F172A] sm:text-3xl">4 Giá trị cốt lõi tại Mind Guide</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Giá trị 1 --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs hover:border-[#6FA6CF] transition">
                <div class="h-12 w-12 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4] mb-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-[#0F172A]">Thấu cảm & Không phán xét</h3>
                <p class="mt-2 text-xs leading-relaxed text-[#4A5568]">
                    Lắng nghe sâu sắc với sự trân trọng mọi cung bậc cảm xúc, tạo không gian an tâm để bạn tự do trải lòng.
                </p>
            </div>

            {{-- Giá trị 2 --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs hover:border-[#6FA6CF] transition">
                <div class="h-12 w-12 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4] mb-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-[#0F172A]">Bảo mật tuyệt đối (§1.7)</h3>
                <p class="mt-2 text-xs leading-relaxed text-[#4A5568]">
                    Mọi hồ sơ và nội dung buổi hẹn được mã hóa an toàn, tuân thủ nghiêm ngặt tiêu chuẩn đạo đức tâm lý.
                </p>
            </div>

            {{-- Giá trị 3 --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs hover:border-[#6FA6CF] transition">
                <div class="h-12 w-12 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4] mb-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-[#0F172A]">Chuyên môn được kiểm duyệt</h3>
                <p class="mt-2 text-xs leading-relaxed text-[#4A5568]">
                    Đội ngũ chuyên viên có văn bằng thạc sĩ, tiến sĩ và chứng chỉ hành nghề được xét duyệt khắt khe.
                </p>
            </div>

            {{-- Giá trị 4 --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs hover:border-[#6FA6CF] transition">
                <div class="h-12 w-12 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4] mb-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-[#0F172A]">Phương pháp thực chứng</h3>
                <p class="mt-2 text-xs leading-relaxed text-[#4A5568]">
                    Ứng dụng các liệu pháp đã được chứng minh khoa học: CBT, ACT, Tâm lý học tích cực và Chánh niệm lâm sàng.
                </p>
            </div>
        </div>
    </div>

    {{-- ── 3. KHỐI THỐNG KÊ NIỀM TIN ── --}}
    <div class="rounded-3xl bg-[#2D82C4] p-8 sm:p-12 text-white text-center">
        <div class="max-w-2xl mx-auto mb-8">
            <h2 class="text-2xl sm:text-3xl font-black">Những con số biết nói</h2>
            <p class="text-sm text-emerald-100 mt-2">Được xây dựng từ sự tin cậy và tiến bộ thực tế của hàng trăm thân chủ mỗi tháng.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-emerald-800">
            <div class="pt-4 sm:pt-0">
                <span class="text-3xl sm:text-5xl font-black text-white">500+</span>
                <p class="text-xs text-emerald-200 mt-1 font-semibold">Thân chủ được đồng hành</p>
            </div>
            <div class="pt-4 sm:pt-0">
                <span class="text-3xl sm:text-5xl font-black text-white">20+</span>
                <p class="text-xs text-emerald-200 mt-1 font-semibold">Chuyên gia tâm lý</p>
            </div>
            <div class="pt-4 sm:pt-0">
                <span class="text-3xl sm:text-5xl font-black text-white">98%</span>
                <p class="text-xs text-emerald-200 mt-1 font-semibold">Mức độ hài lòng</p>
            </div>
            <div class="pt-4 sm:pt-0">
                <span class="text-3xl sm:text-5xl font-black text-amber-300">4.9 ★</span>
                <p class="text-xs text-emerald-200 mt-1 font-semibold">Đánh giá trung bình</p>
            </div>
        </div>
    </div>

    {{-- ── 4. LỜI KÊU GỌI HÀNH ĐỘNG (CTA) ── --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center max-w-2xl mx-auto shadow-xs">
        <h2 class="text-2xl font-black text-[#0F172A]">Bạn đã sẵn sàng lắng nghe bản thân?</h2>
        <p class="text-sm text-[#4A5568] mt-2 leading-relaxed">
            Đừng để những áp lực tinh thần đè nặng một mình. Hãy để các chuyên gia tại Mind Guide chia sẻ cùng bạn ngay hôm nay.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-4">
            <a href="{{ route('appointments.book') }}" class="rounded-xl bg-[#2D82C4] px-8 py-3.5 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                Đặt lịch tư vấn ngay
            </a>
            <a href="{{ route('services.index') }}" class="rounded-xl border border-gray-300 bg-white px-8 py-3.5 text-sm font-bold text-[#0F172A] hover:bg-gray-50 transition">
                Xem các gói dịch vụ
            </a>
        </div>
    </div>

</div>
@endsection
