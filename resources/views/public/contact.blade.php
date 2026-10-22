{{--
    Trang liên hệ phòng khám Mind Guide (Contact Us)
    ================================================
    Căn cứ: docs/ThietKeGiaoDien.md §1.2 & §1.5 Form và kiểm tra dữ liệu
--}}

@extends('layouts.app')

@section('title', 'Liên Hệ — Phòng Khám Tâm Lý Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Liên hệ với chúng tôi</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-12">

    {{-- Tiêu đề trang --}}
    <div class="max-w-2xl">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
            <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
            Chúng tôi luôn sẵn sàng lắng nghe bạn
        </span>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-[#0F172A] sm:text-4xl">
            Liên hệ với Mind Guide
        </h1>
        <p class="mt-2 text-sm sm:text-base leading-relaxed text-[#334155]">
            Dù bạn đang tìm kiếm thông tin dịch vụ, cần hỗ trợ đặt lịch hay muốn gửi gắm câu hỏi,
            đội ngũ điều phối viên phòng khám luôn sẵn lòng phản hồi chu đáo nhất.
        </p>
    </div>

    {{-- Layout 2 cột: Thông tin liên hệ & Biểu mẫu gửi thư --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- CỘT TRÁI (5/12): THÔNG TIN ĐỊA CHỈ, HOTLINE, GIỜ LÀM VIỆC --}}
        <div class="lg:col-span-5 space-y-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-7 shadow-xs space-y-6">
                <h2 class="text-lg font-bold text-[#0F172A] border-b border-gray-100 pb-3">
                    Thông tin phòng khám
                </h2>

                <div class="space-y-5 text-sm text-[#334155]">
                    {{-- Hotline --}}
                    <div class="flex items-start gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-[#E8F0EC] text-[#1D4B43] flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Đường dây nóng hỗ trợ:</span>
                            <a href="tel:19001567" class="text-base font-bold text-[#1D4B43] hover:underline">
                                1900 1567
                            </a>
                            <span class="text-xs text-[#709782] block font-medium">(Hỗ trợ 24/7, cước gọi miễn phí)</span>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="flex items-start gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-[#E8F0EC] text-[#1D4B43] flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Thư điện tử (Email):</span>
                            <a href="mailto:hotro@mindguide.vn" class="font-bold text-[#0F172A] hover:underline">
                                hotro@mindguide.vn
                            </a>
                            <span class="text-xs text-[#4A5568] block">Phản hồi trong vòng 24 giờ làm việc</span>
                        </div>
                    </div>

                    {{-- Địa chỉ --}}
                    <div class="flex items-start gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-[#E8F0EC] text-[#1D4B43] flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Địa chỉ cơ sở tham vấn:</span>
                            <strong class="font-bold text-[#0F172A]">Số 123 Đường Hòa Bình, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh</strong>
                        </div>
                    </div>

                    {{-- Giờ làm việc --}}
                    <div class="flex items-start gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-[#E8F0EC] text-[#1D4B43] flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Giờ tiếp nhận tham vấn:</span>
                            <strong class="font-bold text-[#0F172A]">08:00 – 20:30</strong> (Thứ Hai – Chủ Nhật, kể cả ngày lễ)
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lưu ý an toàn y tế --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 text-xs text-amber-900 leading-relaxed">
                <strong class="font-bold block mb-1">⚠ Trong trường hợp khẩn cấp:</strong>
                Mind Guide không phải đơn vị hồi sức cấp cứu tâm thần. Nếu có nguy cơ đe dọa tính mạng hoặc khủng hoảng cực độ, xin gọi ngay <a href="tel:115" class="font-bold underline text-red-700">115</a>.
            </div>

        </div>

        {{-- CỘT PHẢI (7/12): BIỂU MẪU GỬI TIN NHẮN LIÊN HỆ --}}
        <div class="lg:col-span-7">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs">
                <h2 class="text-xl font-bold text-[#0F172A]">Gửi tin nhắn cho phòng khám</h2>
                <p class="text-xs text-[#4A5568] mt-1 mb-6">
                    Mọi thông tin bạn cung cấp được bảo mật và chỉ dùng để nhân viên điều phối liên hệ giải đáp.
                </p>

                {{-- Thông báo thành công --}}
                @if(session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-bold text-[#0F172A] mb-1">
                            Họ và tên của bạn <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Nguyễn Văn A"
                               required
                               class="w-full rounded-xl border @error('name') border-red-300 bg-red-50 @else border-gray-300 bg-[#F7FAFC] @enderror px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-bold text-[#0F172A] mb-1">
                                Địa chỉ Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="email@example.com"
                                   required
                                   class="w-full rounded-xl border @error('email') border-red-300 bg-red-50 @else border-gray-300 bg-[#F7FAFC] @enderror px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20">
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-[#0F172A] mb-1">
                                Số điện thoại (tùy chọn)
                            </label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   placeholder="0912 345 678"
                                   class="w-full rounded-xl border @error('phone') border-red-300 bg-red-50 @else border-gray-300 bg-[#F7FAFC] @enderror px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20">
                            @error('phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-xs font-bold text-[#0F172A] mb-1">
                            Nội dung câu hỏi hoặc yêu cầu tư vấn <span class="text-red-500">*</span>
                        </label>
                        <textarea id="message"
                                  name="message"
                                  rows="5"
                                  required
                                  maxlength="1000"
                                  placeholder="Bạn đang quan tâm đến gói dịch vụ nào hoặc cần phòng khám giải đáp vấn đề gì?..."
                                  class="w-full rounded-xl border @error('message') border-red-300 bg-red-50 @else border-gray-300 bg-[#F7FAFC] @enderror p-4 text-sm text-[#0F172A] focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 leading-relaxed">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full sm:w-auto rounded-xl bg-[#1D4B43] px-8 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                        Gửi lời nhắn
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
