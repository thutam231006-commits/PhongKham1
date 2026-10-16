{{--
    SCR-PUB-08 — Quên mật khẩu (FR-AUTH-004)
    ==========================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-PUB-08 + §1.7 Bảo mật
    Quy định:
      - Trường: Email*.
      - Hành động: Gửi liên kết đặt lại.
      - Kết quả luôn trung tính: "Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu."
--}}

@extends('layouts.app')

@section('title', 'Quên Mật Khẩu — Mind Guide')

@section('content')
<div class="flex min-h-[calc(100vh-16rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8 rounded-2xl bg-white p-8 border border-gray-200/80 shadow-sm">

        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
                <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
                Khôi phục truy cập
            </span>
            <h1 class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-[#0F172A]">
                Quên mật khẩu?
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-[#4A5568] leading-relaxed">
                Nhập địa chỉ email đăng ký tài khoản của bạn để nhận liên kết thiết lập lại mật khẩu an toàn.
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-800 leading-relaxed">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold text-[#0F172A] mb-1.5">
                    Địa chỉ Email của bạn <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       placeholder="email@example.com"
                       class="w-full rounded-xl border border-gray-300 bg-[#F7FAFC] px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 transition">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-[#1D4B43] py-3 text-sm font-bold text-white hover:bg-[#14352F] focus:outline-none focus:ring-2 focus:ring-[#1D4B43] focus:ring-offset-2 transition shadow-xs">
                Gửi liên kết khôi phục
            </button>
        </form>

        <div class="text-center pt-4 border-t border-gray-100 text-xs text-[#4A5568]">
            <a href="{{ route('login') }}" class="font-bold text-[#1D4B43] hover:underline flex items-center justify-center gap-1">
                <span>←</span> Quay lại trang đăng nhập
            </a>
        </div>

    </div>
</div>
@endsection
