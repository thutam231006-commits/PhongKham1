{{--
    SCR-PUB-09 — Đặt lại mật khẩu (FR-AUTH-004)
    ============================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-PUB-09
    Quy định:
      - Token một lần từ URL.
      - Mật khẩu mới*, Xác nhận mật khẩu* (quy tắc BR-012: >= 8 ký tự).
--}}

@extends('layouts.app')

@section('title', 'Đặt Lại Mật Khẩu — Mind Guide')

@section('content')
<div class="flex min-h-[calc(100vh-16rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8 rounded-2xl bg-white p-8 border border-gray-200/80 shadow-sm">

        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4]">
                <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
                Cập nhật mật khẩu mới
            </span>
            <h1 class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-[#0F172A]">
                Thiết lập mật khẩu
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-[#4A5568]">
                Vui lòng nhập mật khẩu mới bảo mật cho tài khoản của bạn.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Địa chỉ Email <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ $email ?? old('email') }}"
                       required
                       class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Mật khẩu mới <span class="text-red-500">*</span> (tối thiểu 8 ký tự)
                </label>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       minlength="8"
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-[#0F172A] mb-1">
                    Xác nhận mật khẩu mới <span class="text-red-500">*</span>
                </label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       required
                       minlength="8"
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-[#2D82C4] py-3 text-sm font-bold text-white hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition shadow-xs mt-2">
                Cập nhật mật khẩu
            </button>
        </form>

        <div class="text-center pt-4 border-t border-gray-100 text-xs text-[#4A5568]">
            <a href="{{ route('login') }}" class="font-bold text-[#2D82C4] hover:underline">
                Về đăng nhập
            </a>
        </div>

    </div>
</div>
@endsection
