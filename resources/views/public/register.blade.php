{{--
    SCR-PUB-07 — Đăng ký tài khoản (FR-AUTH-001)
    =============================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-PUB-07 + §1.7 Bảo mật + NFR-PRV-003
    Quy định:
      - Họ và tên* (2–100 ký tự).
      - Email* (email duy nhất, <= 255).
      - Mật khẩu* (BR-012, gợi ý mật khẩu mạnh) + Xác nhận*.
      - Ngày sinh* (tuổi >= 18).
      - Số điện thoại (tùy chọn, 9–15 chữ số).
      - Checkbox đồng ý Chính sách quyền riêng tư* (bắt buộc).
      - NFR-PRV-003: TUYỆT ĐỐI KHÔNG thu thập giới tính, địa chỉ, liên hệ khẩn cấp, bệnh sử.
--}}

@extends('layouts.app')

@section('title', 'Đăng Ký Tài Khoản — Phòng Khám Tâm Lý Mind Guide')

@section('content')
<div class="flex min-h-[calc(100vh-16rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-lg space-y-8 rounded-2xl bg-white p-8 border border-gray-200/80 shadow-sm"
         x-data="{ showPassword: false, showConfirmPassword: false }">

        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4]">
                <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
                Thành viên Mind Guide
            </span>
            <h1 class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-[#0F172A]">
                Đăng ký tài khoản mới
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-[#4A5568]">
                Bắt đầu hành trình chăm sóc sức khỏe tinh thần được cá nhân hóa và bảo mật.
            </p>
        </div>

        {{-- Hiển thị lỗi --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Vui lòng kiểm tra lại thông tin:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf

            {{-- Họ và tên --}}
            <div>
                <label for="name" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Họ và tên của bạn <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       minlength="2"
                       maxlength="100"
                       placeholder="Ví dụ: Nguyễn Văn An"
                       class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Địa chỉ Email <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       maxlength="255"
                       placeholder="email@example.com"
                       class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            {{-- Ngày sinh & Số điện thoại --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="dob" class="block text-xs font-bold text-[#0F172A] mb-1">
                        Ngày sinh <span class="text-red-500">*</span> (từ 18 tuổi)
                    </label>
                    <input type="date"
                           id="dob"
                           name="dob"
                           value="{{ old('dob') }}"
                           required
                           max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                           class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm font-semibold text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
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
                           class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
                </div>
            </div>

            {{-- Mật khẩu --}}
            <div>
                <label for="password" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Mật khẩu <span class="text-red-500">*</span> (tối thiểu 8 ký tự, gồm chữ hoa, chữ thường, số & ký tự đặc biệt)
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'"
                           id="password"
                           name="password"
                           required
                           minlength="8"
                           placeholder="Ví dụ: Pass@1234"
                           class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] pl-4 pr-11 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-[#2D82C4] focus:outline-none">
                        <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-[11px] text-gray-500">Mật khẩu cần tối thiểu 8 ký tự, bao gồm ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt.</p>
            </div>

            {{-- Xác nhận mật khẩu --}}
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Xác nhận mật khẩu <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showConfirmPassword ? 'text' : 'password'"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           minlength="8"
                           placeholder="••••••••"
                           class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] pl-4 pr-11 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
                    <button type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-[#2D82C4] focus:outline-none">
                        <svg x-show="!showConfirmPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirmPassword" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Tích đồng ý chính sách quyền riêng tư --}}
            <div class="pt-2">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox"
                           name="agree_terms"
                           required
                           class="mt-1 h-4 w-4 rounded border-gray-300 text-[#2D82C4] focus:ring-[#2D82C4]">
                    <span class="text-xs text-[#334155] leading-relaxed">
                        Tôi xác nhận trên 18 tuổi và đồng ý với
                        <a href="{{ route('privacy') }}" target="_blank" class="font-bold text-[#2D82C4] underline">Chính sách quyền riêng tư</a> cùng
                        <a href="{{ route('terms') }}" target="_blank" class="font-bold text-[#2D82C4] underline">Điều khoản sử dụng</a> của Mind Guide.
                    </span>
                </label>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-[#2D82C4] py-3 text-sm font-bold text-white hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition shadow-xs mt-4">
                Đăng ký tài khoản
            </button>
        </form>

        <div class="text-center pt-4 border-t border-gray-100 text-xs text-[#4A5568]">
            <span>Đã có tài khoản tại Mind Guide?</span>
            <a href="{{ route('login') }}" class="font-bold text-[#2D82C4] hover:underline ml-1">
                Đăng nhập ngay
            </a>
        </div>

    </div>
</div>
@endsection
