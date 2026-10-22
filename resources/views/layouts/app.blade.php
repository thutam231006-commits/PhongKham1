<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Tiêu đề trang — mỗi view con nên @section('title', '...') --}}
    <title>@yield('title', 'Mind Guide') — Phòng Khám Tâm Lý</title>

    {{-- Vite: Tailwind CSS + App JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Slot CSS bổ sung từ view con --}}
    @stack('styles')
</head>

{{--
    Nền toàn trang: mg-bg = #F7FAFC (Warm Off-White, NFR thiết kế)
    Text chính:     mg-text = #1A202C (Dark Slate)
--}}
<body class="h-full flex flex-col bg-[#F7FAFC] text-[#1A202C] antialiased">

    {{-- =========================================================
         HEADER (SCR chung — mục 1.2 ThietKeGiaoDien.md)
         Nền: mg-primary (#1D4B43 — Deep Teal)
         ========================================================= --}}
    <header class="bg-[#1D4B43] text-white shadow-md sticky top-0 z-50">
        <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">

                {{-- Logo / Tên thương hiệu --}}
                <a href="{{ url('/') }}"
                   class="flex items-center gap-2 text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#1D4B43] rounded"
                   aria-label="Mind Guide — Về trang chủ">
                    {{-- Thay bằng <img src="..." alt="Mind Guide"> khi có file logo --}}
                    <svg class="h-8 w-8 flex-shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <circle cx="16" cy="16" r="14" fill="#709782" />
                        <path d="M10 20 Q16 10 22 20" stroke="white" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                        <circle cx="16" cy="12" r="3" fill="white"/>
                    </svg>
                    <span class="text-xl font-semibold tracking-tight hidden sm:inline">Mind Guide</span>
                </a>

                {{-- ── MENU ĐIỀU HƯỚNG THEO VAI TRÒ (mục 1.9) ── --}}
                {{-- Desktop nav --}}
                <nav aria-label="Menu chính" class="hidden lg:flex items-center gap-1">

                    @guest
                        {{-- Vai trò: Khách (Guest) --}}
                        <x-nav-link href="{{ url('/') }}">Trang chủ</x-nav-link>
                        <x-nav-link href="{{ route('services.index') }}">Dịch vụ</x-nav-link>
                        <x-nav-link href="{{ route('specialists.index') }}">Chuyên viên</x-nav-link>
                        <x-nav-link href="{{ route('login') }}">Đăng nhập</x-nav-link>
                        <a href="{{ route('register') }}"
                           class="ml-2 rounded-md bg-[#709782] px-4 py-2 text-sm font-medium text-white hover:bg-[#5a7d6b] focus:outline-none focus:ring-2 focus:ring-white transition">
                            Đăng ký
                        </a>
                    @endguest

                    @auth
                        @php $role = auth()->user()->role ?? null; @endphp

                        {{-- Customer --}}
                        @if ($role === 'customer')
                            <x-nav-link href="{{ route('customer.dashboard') }}">Tổng quan</x-nav-link>
                            <x-nav-link href="{{ route('appointments.book') }}">Đặt lịch</x-nav-link>
                            <x-nav-link href="{{ route('customer.appointments') }}">Lịch hẹn của tôi</x-nav-link>
                            <x-nav-link href="{{ route('customer.profile') }}">Hồ sơ cá nhân</x-nav-link>
                        @endif

                        {{-- Specialist --}}
                        @if ($role === 'specialist')
                            <x-nav-link href="{{ route('specialist.dashboard') }}">Tổng quan</x-nav-link>
                            <x-nav-link href="{{ route('specialist.appointments') }}">Lịch hẹn</x-nav-link>
                            <x-nav-link href="{{ route('specialist.schedule') }}">Lịch làm việc</x-nav-link>
                            <x-nav-link href="{{ route('specialist.leaves') }}">Ngày nghỉ và chặn giờ</x-nav-link>
                        @endif

                        {{-- Staff --}}
                        @if ($role === 'staff')
                            <x-nav-link href="{{ route('staff.dashboard') }}">Tổng quan</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments') }}">Lịch hẹn</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments.pending') }}">Điều phối lịch</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments.book') }}">Đặt lịch hộ</x-nav-link>
                            <x-nav-link href="{{ route('staff.customers') }}">Khách hàng</x-nav-link>
                            <x-nav-link href="{{ route('staff.payments') }}">Thanh toán & hoàn tiền</x-nav-link>
                        @endif

                        {{-- Admin --}}
                        @if ($role === 'admin')
                            <x-nav-link href="{{ route('admin.dashboard') }}">Tổng quan</x-nav-link>
                            <x-nav-link href="{{ route('admin.users') }}">Người dùng</x-nav-link>
                            <x-nav-link href="{{ route('admin.specialists') }}">Chuyên viên</x-nav-link>
                            <x-nav-link href="{{ route('admin.services') }}">Dịch vụ</x-nav-link>
                            <x-nav-link href="{{ route('admin.appointments') }}">Lịch hẹn</x-nav-link>
                            <x-nav-link href="{{ route('admin.payments') }}">Thanh toán & hoàn tiền</x-nav-link>
                            <x-nav-link href="{{ route('admin.reports') }}">Báo cáo</x-nav-link>
                            <x-nav-link href="{{ route('admin.settings') }}">Cấu hình</x-nav-link>
                        @endif
                    @endauth
                </nav>

                {{-- ── CÁC ĐIỀU KHIỂN BÊN PHẢI HEADER ── --}}
                <div class="flex items-center gap-3">

                    @auth
                        {{-- Chuông thông báo kèm số chưa đọc (mục 1.2) --}}
                        <a href="{{ route('notifications.index') }}"
                           class="relative rounded-full p-2 text-white hover:bg-[#14352F] focus:outline-none focus:ring-2 focus:ring-white transition"
                           aria-label="Thông báo">
                            {{-- Icon chuông --}}
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            {{-- Badge số chưa đọc — ẩn khi = 0 --}}
                            @php $unreadCount = auth()->user()->unreadNotificationsCount ?? 0; @endphp
                            @if ($unreadCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold leading-none text-white"
                                      aria-label="{{ $unreadCount }} thông báo chưa đọc">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </a>

                        {{-- Dropdown Hồ sơ / Đổi mật khẩu / Đăng xuất --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    @keydown.escape="open = false"
                                    class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium text-white hover:bg-[#14352F] focus:outline-none focus:ring-2 focus:ring-white transition"
                                    aria-haspopup="true" :aria-expanded="open.toString()">
                                <span class="hidden sm:block max-w-[120px] truncate" aria-hidden="true">
                                    {{ auth()->user()->name }}
                                </span>
                                {{-- Avatar fallback chữ cái đầu --}}
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#709782] text-sm font-bold uppercase"
                                      aria-hidden="true">
                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                </span>
                                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Dropdown panel --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="open = false"
                                 class="absolute right-0 mt-2 w-52 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                                 role="menu" aria-label="Menu tài khoản">

                                {{-- Tên + email (chỉ đọc) --}}
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-[#1A202C]">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-[#4A5568]">{{ auth()->user()->email }}</p>
                                </div>

                                {{-- Hồ sơ cá nhân --}}
                                @if (auth()->user()->role === 'customer')
                                    <a href="{{ route('customer.profile') }}"
                                       class="block px-4 py-2 text-sm text-[#1A202C] hover:bg-[#F7FAFC]" role="menuitem">
                                        Hồ sơ cá nhân
                                    </a>
                                @endif

                                {{-- Đổi mật khẩu --}}
                                <a href="{{ route('password.change') }}"
                                   class="block px-4 py-2 text-sm text-[#1A202C] hover:bg-[#F7FAFC]" role="menuitem">
                                    Đổi mật khẩu
                                </a>

                                <div class="border-t border-gray-100"></div>

                                {{-- Đăng xuất --}}
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 focus:outline-none"
                                            role="menuitem">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth

                    {{-- Nút hamburger — mobile --}}
                    <button class="lg:hidden rounded-md p-2 text-white hover:bg-[#14352F] focus:outline-none focus:ring-2 focus:ring-white transition"
                            x-data
                            @click="$dispatch('toggle-mobile-menu')"
                            aria-controls="mobile-menu"
                            aria-label="Mở/đóng menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── MOBILE MENU ── --}}
        <div id="mobile-menu"
             class="lg:hidden border-t border-[#14352F]"
             x-data="{ open: false }"
             @toggle-mobile-menu.window="open = !open"
             x-show="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2">
            <nav class="px-4 py-3 space-y-1" aria-label="Menu di động">

                @guest
                    <x-mobile-nav-link href="{{ url('/') }}">Trang chủ</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('services.index') }}">Dịch vụ</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('specialists.index') }}">Chuyên viên</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('login') }}">Đăng nhập</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('register') }}">Đăng ký</x-mobile-nav-link>
                @endguest

                @auth
                    @php $role = auth()->user()->role ?? null; @endphp

                    @if ($role === 'customer')
                        <x-mobile-nav-link href="{{ route('customer.dashboard') }}">Tổng quan</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('appointments.book') }}">Đặt lịch</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('customer.appointments') }}">Lịch hẹn của tôi</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('customer.profile') }}">Hồ sơ cá nhân</x-mobile-nav-link>
                    @endif

                    @if ($role === 'specialist')
                        <x-mobile-nav-link href="{{ route('specialist.dashboard') }}">Tổng quan</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('specialist.appointments') }}">Lịch hẹn</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('specialist.schedule') }}">Lịch làm việc</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('specialist.leaves') }}">Ngày nghỉ và chặn giờ</x-mobile-nav-link>
                    @endif

                    @if ($role === 'staff')
                        <x-mobile-nav-link href="{{ route('staff.dashboard') }}">Tổng quan</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments') }}">Lịch hẹn</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments.pending') }}">Điều phối lịch</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments.book') }}">Đặt lịch hộ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.customers') }}">Khách hàng</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.payments') }}">Thanh toán & hoàn tiền</x-mobile-nav-link>
                    @endif

                    @if ($role === 'admin')
                        <x-mobile-nav-link href="{{ route('admin.dashboard') }}">Tổng quan</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.users') }}">Người dùng</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.specialists') }}">Chuyên viên</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.services') }}">Dịch vụ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.appointments') }}">Lịch hẹn</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.payments') }}">Thanh toán & hoàn tiền</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.reports') }}">Báo cáo</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.settings') }}">Cấu hình</x-mobile-nav-link>
                    @endif

                    {{-- Thông báo + Đổi MK + Đăng xuất trên mobile --}}
                    <div class="border-t border-[#14352F] pt-3 mt-3 space-y-1">
                        <x-mobile-nav-link href="{{ route('notifications.index') }}">Thông báo</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('password.change') }}">Đổi mật khẩu</x-mobile-nav-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left rounded-md px-3 py-2 text-sm font-medium text-red-300 hover:bg-[#14352F] hover:text-red-200 transition">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                @endauth
            </nav>
        </div>
    </header>

    {{-- =========================================================
         BANNER CẢNH BÁO AN TOÀN KHẨN CẤP (NFR-USA-003)
         Quy định: #FFEDD5 bg · #7C2D12 text · Hotline 115 / 1900 1567
         Hiển thị NGAY dưới Header trên mọi trang công khai và
         trang customer — ẩn với Staff/Admin nội bộ (tùy chọn).
         ========================================================= --}}
    @php
        $showSafetyBanner = true;
        // Ẩn banner trên các trang thuần nội bộ (Staff / Admin)
        // để giảm nhiễu; vẫn giữ trên public và customer.
        if (auth()->check() && in_array(auth()->user()->role, ['staff', 'admin'])) {
            $showSafetyBanner = false;
        }
        // View con có thể ghi đè: @section('show_safety_banner', false)
        $showSafetyBanner = $showSafetyBanner && (bool) View::yieldContent('show_safety_banner', true);
    @endphp

    @if ($showSafetyBanner)
    {{--
        Emergency Safety Banner — NFR-USA-003
        Nền: #FFEDD5  •  Chữ: #7C2D12
        Hotline chính: 115  •  Hotline tư vấn: 1900 1567
    --}}
    <div role="alert"
         aria-live="polite"
         aria-label="Thông báo an toàn"
         class="w-full border-b border-[#FED7AA] bg-[#FFEDD5] px-4 py-3">
        <div class="mx-auto flex max-w-screen-xl flex-wrap items-center justify-between gap-2">

            {{-- Icon cảnh báo + nội dung chính --}}
            <div class="flex items-start gap-3">
                {{-- Icon cảnh báo (không phải màu đỏ — tránh gây hoảng loạn theo mục 3 bảng màu) --}}
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-[#C2410C]" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>

                <div>
                    <p class="text-sm font-semibold text-[#7C2D12]">
                        Nếu bạn đang trong tình huống nguy hiểm hoặc cần hỗ trợ khẩn cấp:
                    </p>
                    <p class="mt-0.5 text-sm text-[#7C2D12]">
                        Gọi ngay
                        <a href="tel:115"
                           class="font-bold underline decoration-2 underline-offset-2 hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-[#7C2D12] rounded"
                           aria-label="Gọi đường dây khẩn cấp 115">115</a>
                        (cấp cứu)
                        hoặc đường dây hỗ trợ sức khỏe tâm thần
                        <a href="tel:19001567"
                           class="font-bold underline decoration-2 underline-offset-2 hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-[#7C2D12] rounded"
                           aria-label="Gọi đường dây hỗ trợ tâm thần 1900 1567">1900&nbsp;1567</a>
                        (miễn phí, 24/7).
                    </p>
                    <p class="mt-1 text-xs text-[#9A3412]">
                        Dịch vụ tư vấn của chúng tôi <strong>không</strong> thay thế can thiệp khẩn cấp.
                    </p>
                </div>
            </div>

            {{-- Nút đóng banner (không xóa khỏi DOM — chỉ ẩn trong session tab) --}}
            <button type="button"
                    aria-label="Đóng thông báo an toàn"
                    class="rounded p-1 text-[#7C2D12] hover:bg-[#FED7AA] focus:outline-none focus:ring-2 focus:ring-[#7C2D12] transition"
                    onclick="
                        this.closest('[role=alert]').setAttribute('hidden', '');
                        sessionStorage.setItem('mg_safety_dismissed', '1');
                    ">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    {{-- Khôi phục trạng thái đóng nếu người dùng đã đóng trước đó trong tab này --}}
    <script>
        (function () {
            if (sessionStorage.getItem('mg_safety_dismissed') === '1') {
                var el = document.querySelector('[role="alert"][aria-label="Thông báo an toàn"]');
                if (el) el.setAttribute('hidden', '');
            }
        })();
    </script>
    @endif

    {{-- =========================================================
         FLASH MESSAGES (Toast / Session notifications)
         Success, Error, Warning, Info — đặt ngay dưới banner
         ========================================================= --}}
    @if (session()->hasAny(['success', 'error', 'warning', 'info']))
    <div class="mx-auto w-full max-w-screen-xl px-4 sm:px-6 lg:px-8 pt-4 space-y-2" aria-live="polite">

        @if (session('success'))
        <div role="alert" class="flex items-start gap-3 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm text-[#14532D]">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ e(session('success')) }}</span>
        </div>
        @endif

        @if (session('error'))
        <div role="alert" class="flex items-start gap-3 rounded-lg border border-[#FCA5A5] bg-[#FEF2F2] px-4 py-3 text-sm text-[#7F1D1D]">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/>
            </svg>
            <span>{{ e(session('error')) }}</span>
        </div>
        @endif

        @if (session('warning'))
        <div role="alert" class="flex items-start gap-3 rounded-lg border border-[#FDE68A] bg-[#FFFBEB] px-4 py-3 text-sm text-[#78350F]">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>{{ e(session('warning')) }}</span>
        </div>
        @endif

        @if (session('info'))
        <div role="alert" class="flex items-start gap-3 rounded-lg border border-[#BFDBFE] bg-[#EFF6FF] px-4 py-3 text-sm text-[#1E40AF]">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
            </svg>
            <span>{{ e(session('info')) }}</span>
        </div>
        @endif
    </div>
    @endif

    {{-- =========================================================
         VÙNG NỘI DUNG CHÍNH
         Nền: bg-[#F7FAFC] (mg-bg) — kế thừa từ <body>
         ========================================================= --}}
    <main id="main-content"
          class="flex-1 w-full"
          tabindex="-1"
          aria-label="Nội dung chính">

        {{-- Skip-link target — điều hướng bàn phím (NFR-ACC-001) --}}

        {{-- Breadcrumb tùy chọn — view con push vào slot này --}}
        @hasSection('breadcrumb')
        <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8 pt-4">
            @yield('breadcrumb')
        </div>
        @endif

        {{-- ── NỘI DUNG CHÍNH — view con yield vào đây ── --}}
        @yield('content')
    </main>

    {{-- =========================================================
         FOOTER CÔNG KHAI (mục 1.2 ThietKeGiaoDien.md)
         Chỉ hiển thị với trang công khai và customer.
         Staff / Admin thường không cần footer công khai.
         ========================================================= --}}
    @php
        $showPublicFooter = true;
        if (auth()->check() && in_array(auth()->user()->role, ['staff', 'admin'])) {
            $showPublicFooter = false;
        }
        $showPublicFooter = $showPublicFooter && (bool) View::yieldContent('show_footer', true);
    @endphp

    @if ($showPublicFooter)
    <footer class="mt-auto border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Thương hiệu --}}
                <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 text-[#1D4B43] font-semibold text-lg">
                        <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                            <circle cx="16" cy="16" r="14" fill="#709782" />
                            <path d="M10 20 Q16 10 22 20" stroke="white" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                            <circle cx="16" cy="12" r="3" fill="white"/>
                        </svg>
                        Mind Guide
                    </a>
                    <p class="mt-3 text-sm text-[#4A5568] leading-relaxed">
                        Phòng khám tâm lý — đồng hành cùng bạn trên hành trình chăm sóc sức khỏe tinh thần.
                    </p>
                </div>

                {{-- Liên kết nhanh --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A202C]">Khám phá</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="{{ route('services.index') }}" class="text-sm text-[#4A5568] hover:text-[#1D4B43] transition">Dịch vụ</a></li>
                        <li><a href="{{ route('specialists.index') }}" class="text-sm text-[#4A5568] hover:text-[#1D4B43] transition">Chuyên viên</a></li>
                        <li><a href="{{ route('appointments.book') }}" class="text-sm text-[#4A5568] hover:text-[#1D4B43] transition">Đặt lịch</a></li>
                    </ul>
                </div>

                {{-- Liên hệ phòng khám --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A202C]">Liên hệ</h3>
                    <address class="mt-4 not-italic space-y-2 text-sm text-[#4A5568]">
                        <p>Địa chỉ: [Địa chỉ phòng khám]</p>
                        <p>
                            Điện thoại:
                            <a href="tel:" class="hover:text-[#1D4B43] transition">[SĐT phòng khám]</a>
                        </p>
                        <p>
                            Email:
                            <a href="mailto:" class="hover:text-[#1D4B43] transition">[Email phòng khám]</a>
                        </p>
                        <p>Giờ làm việc: [Giờ làm việc]</p>
                    </address>
                </div>

                {{-- Pháp lý --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A202C]">Pháp lý</h3>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="{{ route('privacy') }}"
                               class="text-sm text-[#4A5568] hover:text-[#1D4B43] transition">
                                Chính sách quyền riêng tư
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('terms') }}"
                               class="text-sm text-[#4A5568] hover:text-[#1D4B43] transition">
                                Điều khoản sử dụng
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ── THÔNG BÁO AN TOÀN Ở FOOTER (NFR-USA-003, mục 1.2) ── --}}
            {{--
                "Dịch vụ này không dành cho tình huống khẩn cấp" — luôn hiển thị
                ngay cả khi banner trên đã bị đóng.
            --}}
            <div class="mt-8 rounded-lg border border-[#FED7AA] bg-[#FFEDD5] px-4 py-3">
                <p class="text-xs text-[#7C2D12] leading-relaxed">
                    <strong>⚠ Lưu ý quan trọng:</strong>
                    Dịch vụ tư vấn của Mind Guide <strong>không</strong> thay thế can thiệp y tế khẩn cấp.
                    Nếu bạn hoặc người thân đang trong tình trạng nguy hiểm, hãy gọi ngay
                    <a href="tel:115" class="font-bold underline hover:opacity-80">115</a>
                    (cấp cứu) hoặc
                    <a href="tel:19001567" class="font-bold underline hover:opacity-80">1900&nbsp;1567</a>
                    (hỗ trợ sức khỏe tâm thần, miễn phí, 24/7).
                </p>
            </div>

            {{-- Copyright --}}
            <div class="mt-6 border-t border-gray-100 pt-6 text-center">
                <p class="text-xs text-[#4A5568]">
                    &copy; {{ date('Y') }} Mind Guide. Mọi quyền được bảo lưu.
                </p>
            </div>
        </div>
    </footer>
    @endif

    {{-- Skip-link (điều hướng bàn phím — WCAG, NFR-ACC-001) --}}
    {{-- Đặt ngay trước </body> để JS không cần delay --}}

    {{-- Slot JS bổ sung từ view con --}}
    @stack('scripts')

</body>
</html>
