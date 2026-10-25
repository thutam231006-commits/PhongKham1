{{--
    Kích hoạt tài khoản bằng mã xác thực 6 số (thay thế cho bấm link trong email)
    ======================================================================
    - Nhập email + mã 6 số + đặt mật khẩu.
    - Có nút gửi lại mã.
--}}

@extends('layouts.app')

@section('title', 'Nhập Mã Kích Hoạt — Mind Guide')

@section('content')
<div class="flex min-h-[calc(100vh-16rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-6 rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">

        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4]">
                <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
                Kích hoạt tài khoản
            </span>
            <h1 class="mt-3 text-2xl font-black text-[#0F172A]">Nhập mã xác thực</h1>
            <p class="mt-2 text-xs leading-relaxed text-[#4A5568] sm:text-sm">
                Mã 6 số đã được gửi kèm email kích hoạt. Nhập mã để kích hoạt tài khoản và đặt mật khẩu riêng.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-800">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('account.activation.code.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold text-[#0F172A]">Email đăng ký <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus
                       class="w-full rounded-xl border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            <div>
                <label for="code" class="mb-1.5 block text-xs font-bold text-[#0F172A]">Mã xác thực 6 số <span class="text-red-500">*</span></label>
                <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                       placeholder="000000" value="{{ old('code') }}"
                       class="w-full rounded-xl border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-center text-2xl font-black tracking-[0.5em] text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
                <p class="mt-1.5 text-xs text-[#718096]">Mã có hiệu lực trong {{ config('auth.verification.otp_expire', 15) }} phút và chỉ dùng được một lần.</p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-xs font-bold text-[#0F172A]">Mật khẩu mới <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" required
                       class="w-full rounded-xl border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
                <p class="mt-1.5 text-xs text-[#718096]">Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường, số và ký hiệu.</p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-xs font-bold text-[#0F172A]">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full rounded-xl border-gray-300 bg-[#F5FAFE] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-[#2D82C4] py-3 text-sm font-bold text-white shadow-xs transition hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2">
                Kích hoạt tài khoản
            </button>
        </form>

        <div class="space-y-3 border-t border-gray-100 pt-4 text-center">
            <form method="POST" action="{{ route('account.activation.code.resend') }}">
                @csrf
                <input type="hidden" name="email" value="{{ old('email', $email) }}">
                <button type="submit" class="text-xs font-bold text-[#2D82C4] hover:underline">
                    Chưa nhận được mã? Gửi lại mã xác thực
                </button>
            </form>
            <a href="{{ route('login') }}" class="block text-xs text-[#718096] hover:underline">← Quay lại đăng nhập</a>
        </div>

    </div>
</div>
@endsection
