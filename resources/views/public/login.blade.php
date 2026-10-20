{{--
    SCR-PUB-06 — Đăng nhập tài khoản (FR-AUTH-002)
    ===============================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-PUB-06 + §1.7 Bảo mật
    Quy định:
      - Email*, Mật khẩu* (có nút hiện/ẩn mật khẩu).
      - Liên kết "Quên mật khẩu?", "Chưa có tài khoản? Đăng ký".
      - Xử lý tham số ?intended (ví dụ redirect về đặt lịch sau khi đăng nhập).
      - Thông báo lỗi trung tính theo §1.7 ("Email hoặc mật khẩu không đúng").
--}}

@extends('layouts.app')

@section('title', 'Đăng Nhập — Phòng Khám Tâm Lý Mind Guide')

@section('content')
<div class="flex min-h-[calc(100vh-16rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8 rounded-2xl bg-white p-8 border border-gray-200/80 shadow-sm"
         x-data="{ showPassword: false, email: '{{ old('email') }}', password: '' }">

        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4]">
                <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
                Cổng thông tin Mind Guide
            </span>
            <h1 class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-[#0F172A]">
                Đăng nhập tài khoản
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-[#4A5568]">
                Quản lý lịch hẹn, theo dõi hồ sơ tư vấn và nhận thông báo mới nhất.
            </p>
        </div>

        {{-- Lỗi đăng nhập trung tính theo §1.7 --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Không thể đăng nhập</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Khối thông tin tài khoản mẫu để trải nghiệm --}}
        <div class="rounded-xl border border-teal-200 bg-[#EAF4FB]/60 p-4 text-xs text-[#2D82C4]">
            <div class="flex items-center justify-between mb-2">
                <span class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tài khoản khách hàng demo
                </span>
                <button type="button"
                        @click="email = 'customer@mindguide.vn'; password = 'MindGuide@2026'"
                        class="text-[11px] font-bold text-[#2D82C4] hover:underline bg-white px-2 py-0.5 rounded border border-teal-300 shadow-xs cursor-pointer">
                    ⚡ Điền nhanh
                </button>
            </div>
            <div class="space-y-1 font-mono text-[11px] text-[#0F172A]">
                <div>Email: <strong class="select-all text-[#2D82C4]">customer@mindguide.vn</strong></div>
                <div>Mật khẩu: <strong class="select-all text-[#2D82C4]">MindGuide@2026</strong></div>
            </div>
        </div>

        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
            @csrf
            @if(!empty($intended))
                <input type="hidden" name="intended" value="{{ $intended }}">
            @endif

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs font-bold text-[#0F172A] mb-1.5">
                    Địa chỉ Email <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       x-model="email"
                       required
                       autofocus
                       placeholder="email@example.com"
                       class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            {{-- Mật khẩu có nút ẩn/hiện --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold text-[#0F172A]">
                        Mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#2D82C4] hover:underline">
                        Quên mật khẩu?
                    </a>
                </div>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'"
                           id="password"
                           name="password"
                           x-model="password"
                           required
                           placeholder="••••••••"
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
            </div>

            {{-- Nút Đăng nhập --}}
            <button type="submit"
                    class="w-full rounded-xl bg-[#2D82C4] py-3 text-sm font-bold text-white hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition shadow-xs">
                Đăng nhập
            </button>
        </form>

        {{-- Chuyển sang Đăng ký --}}
        <div class="text-center pt-4 border-t border-gray-100 text-xs text-[#4A5568]">
            <span>Chưa có tài khoản tại Mind Guide?</span>
            <a href="{{ route('register') }}" class="font-bold text-[#2D82C4] hover:underline ml-1">
                Đăng ký tài khoản mới
            </a>
        </div>

    </div>
</div>
@endsection
