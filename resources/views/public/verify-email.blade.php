{{--
    SCR-PUB-10 — Xác thực email (FR-AUTH-006)
    ===========================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-PUB-10
    Quy định:
      - Hiển thị thông báo kiểm tra email để kích hoạt.
      - Nút gửi lại email xác thực.
--}}

@extends('layouts.app')

@section('title', 'Xác Thực Email — Mind Guide')

@section('content')
<div class="flex min-h-[calc(100vh-16rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-6 rounded-2xl bg-white p-8 border border-gray-200/80 shadow-sm text-center">

        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#EAF4FB] text-[#2D82C4]">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-black text-[#0F172A]">Xác thực tài khoản của bạn</h1>
            <p class="mt-2 text-xs sm:text-sm text-[#4A5568] leading-relaxed">
                Chúng tôi đã gửi một liên kết xác thực đến địa chỉ email bạn đã đăng ký. Vui lòng bấm vào liên kết trong thư để kích hoạt tài khoản.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="pt-4 space-y-3">
            <a href="{{ route('login') }}"
               class="block w-full rounded-xl bg-[#2D82C4] py-3 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                Đăng nhập tài khoản
            </a>

            <form method="POST" action="{{ route('verification.resend') }}" class="space-y-2">@csrf<input type="email" name="email" required placeholder="Email cần xác thực" class="w-full rounded-xl border-gray-300 text-sm"><button class="block w-full rounded-xl border border-gray-300 bg-white py-3 text-sm font-bold text-[#0F172A] hover:bg-gray-50 transition">Chưa nhận được thư? Gửi lại email</button></form>
        </div>

    </div>
</div>
@endsection
