<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>document.documentElement.classList.add('js');</script>

    {{-- Tiêu đề trang — mỗi view con nên @section('title', '...') --}}
    <title>@yield('title', 'Mind Guide') | Phòng khám Tâm lý</title>

    {{-- Vite: Tailwind CSS + App JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Slot CSS bổ sung từ view con --}}
    @stack('styles')
</head>

{{--
    Nền toàn trang: mg-bg = #F5FAFE (Warm Off-White, NFR thiết kế)
    Text chính:     mg-text = #1A202C (Dark Slate)
--}}
<body class="mg-app h-full flex flex-col bg-[#F5FAFE] text-[#102A43] antialiased">
    <a href="#main-content" class="mg-skip-link">Chuyển đến nội dung chính</a>

    {{-- =========================================================
         HEADER (SCR chung — mục 1.2 ThietKeGiaoDien.md)
         Nền: mg-primary (#2D82C4 — Deep Teal)
         ========================================================= --}}
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
    @endphp

    <header class="mg-site-header sticky top-0 z-50 bg-[#2D82C4] text-white">
        @if (! $isAdmin)
            {{-- Hàng tiện ích theo cấu trúc cổng thông tin: thương hiệu, tìm kiếm, hỗ trợ và đặt lịch. --}}
            <div class="border-b border-[#E5F0F7] bg-white text-[#2D82C4]">
                <div class="mx-auto flex min-h-[76px] max-w-screen-xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}"
                       class="flex shrink-0 items-center gap-2 rounded focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]"
                       aria-label="Mind Guide — Về trang chủ">
                        <svg class="h-9 w-9" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                            <circle cx="16" cy="16" r="14" fill="#6FA6CF" />
                            <path d="M10 20 Q16 10 22 20" stroke="white" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                            <circle cx="16" cy="12" r="3" fill="white"/>
                        </svg>
                        <span>
                            <strong class="block text-xl font-extrabold leading-none tracking-tight">Mind Guide</strong>
                            <small class="mt-1 hidden text-[11px] font-semibold text-[#52637A] sm:block">Tâm lý và sức khỏe tinh thần</small>
                        </span>
                    </a>

                    <form action="{{ route('services.index') }}" method="GET" class="ml-auto hidden min-w-0 max-w-sm flex-1 md:flex">
                        <label for="site-search" class="sr-only">Tìm kiếm dịch vụ</label>
                        <div class="flex w-full items-center rounded-full border border-[#D7E8F3] bg-[#F5FAFE] px-4 shadow-sm focus-within:border-[#6FA6CF] focus-within:ring-2 focus-within:ring-[#6FA6CF]/30">
                            <input id="site-search" name="q" type="search" value="{{ request('q') }}"
                                   placeholder="Tìm kiếm dịch vụ"
                                   class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm text-[#1A202C] placeholder:text-[#718096] focus:outline-none focus:ring-0">
                            <button type="submit" class="rounded-full p-1 text-[#2D82C4] hover:bg-[#EAF4FB]" aria-label="Tìm kiếm">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <div class="hidden items-center gap-3 lg:flex">
                        <svg class="h-7 w-7 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5.5A2.5 2.5 0 0 1 5.5 3H7l2 5-2 1.5A13 13 0 0 0 14.5 17L16 15l5 2v1.5a2.5 2.5 0 0 1-2.5 2.5C10.492 21 3 13.508 3 5.5Z"/>
                        </svg>
                        <div class="leading-tight">
                            <span class="block text-xs text-[#4A5568]">Tư vấn và hỗ trợ</span>
                            <strong class="text-lg text-[#2D82C4]">1900 1567</strong>
                        </div>
                    </div>

                    <a href="{{ route('appointments.book') }}"
                       class="inline-flex shrink-0 rounded-full border-2 border-[#2D82C4] px-3 py-2 text-xs font-extrabold text-[#2D82C4] transition hover:bg-[#2D82C4] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF] sm:px-4 sm:py-2 sm:text-sm">
                        Đặt lịch
                    </a>
                </div>
            </div>
        @endif

        <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
            <div class="flex {{ $isAdmin ? 'h-16' : 'min-h-16' }} items-center justify-between gap-4">

                {{-- Logo / Tên thương hiệu --}}
                <a href="{{ url('/') }}"
                   class="{{ $isAdmin ? 'flex' : 'hidden' }} items-center gap-2 rounded text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#2D82C4]"
                   aria-label="Mind Guide — Về trang chủ">
                    {{-- Thay bằng <img src="..." alt="Mind Guide"> khi có file logo --}}
                    <svg class="h-8 w-8 flex-shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <circle cx="16" cy="16" r="14" fill="#6FA6CF" />
                        <path d="M10 20 Q16 10 22 20" stroke="white" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                        <circle cx="16" cy="12" r="3" fill="white"/>
                    </svg>
                    <span class="text-xl font-semibold tracking-tight hidden sm:inline">Mind Guide</span>
                </a>

                {{-- ── MENU ĐIỀU HƯỚNG THEO VAI TRÒ (mục 1.9) ── --}}
                {{-- Desktop nav --}}
                <nav aria-label="Menu chính" class="hidden flex-1 items-center justify-center gap-2 lg:flex">

                    @guest
                        {{-- Vai trò: Khách (Guest) --}}
                        <x-nav-link href="{{ url('/') }}">Trang chủ</x-nav-link>
                        @php
                            try {
                                $serviceMenuItems = \App\Models\Service::query()->where('status', 'ACTIVE')->orderBy('display_order')->limit(9)->get(['id', 'name']);
                            } catch (\Throwable) {
                                $serviceMenuItems = collect();
                            }
                        @endphp
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @focusin="open = true" @focusout="open = false">
                            <button type="button" @keydown.escape="open = false" class="flex items-center gap-1 rounded-lg px-4 py-2.5 text-sm font-semibold text-white/90 transition hover:bg-[#23689C]" :aria-expanded="open.toString()">
                                Dịch vụ <span class="text-xs transition-transform" :class="{ 'rotate-180': open }" aria-hidden="true">⌄</span>
                            </button>
                            <div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-[60] mt-2 w-80 rounded-xl border border-[#D7E8F3] bg-white p-2 shadow-2xl">
                                <a href="{{ route('services.index') }}" class="mb-1 block rounded-lg px-4 py-3 text-sm font-black text-[#2D82C4] hover:bg-[#EAF4FB]">Xem tất cả 9 gói dịch vụ</a>
                                @foreach($serviceMenuItems as $menuService)
                                    <a href="{{ route('services.show', ['service' => $menuService->id]) }}" class="block rounded-lg px-4 py-2.5 text-sm font-semibold text-[#334E68] hover:bg-[#EAF4FB] hover:text-[#2D82C4]">{{ $menuService->name }}</a>
                                @endforeach
                            </div>
                        </div>
                        <x-nav-link href="{{ route('specialists.index') }}">Chuyên viên</x-nav-link>
                        <x-nav-link href="{{ route('login') }}">Đăng nhập</x-nav-link>
                        <a href="{{ route('register') }}"
                           class="ml-2 rounded-lg bg-[#6FA6CF] px-5 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-[#5A94BC] focus:outline-none focus:ring-2 focus:ring-white transition">
                            Đăng ký
                        </a>
                    @endguest

                    @auth
                        @php $role = auth()->user()->role ?? null; @endphp

                        {{-- Customer --}}
                        @if ($role === 'customer')
                            <x-nav-link href="{{ route('customer.dashboard') }}">Trang chủ</x-nav-link>
                            <x-nav-link href="{{ route('appointments.book') }}">Đặt lịch</x-nav-link>
                            <x-nav-link href="{{ route('customer.appointments') }}">Lịch hẹn của tôi</x-nav-link>
                            <x-nav-link href="{{ route('customer.profile') }}">Hồ sơ cá nhân</x-nav-link>
                        @endif

                        {{-- Specialist --}}
                        @if ($role === 'specialist')
                            <x-nav-link href="{{ route('specialist.dashboard') }}">Trang chủ</x-nav-link>
                            <x-nav-link href="{{ route('specialist.appointments') }}">Lịch hẹn</x-nav-link>
                            <x-nav-link href="{{ route('specialist.schedule') }}">Lịch làm việc</x-nav-link>
                            <x-nav-link href="{{ route('specialist.leaves') }}">Ngày nghỉ và chặn giờ</x-nav-link>
                        @endif

                        {{-- Staff --}}
                        @if ($role === 'staff')
                            <x-nav-link href="{{ route('staff.dashboard') }}">Trang chủ</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments') }}">Lịch hẹn</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments.pending') }}">Điều phối lịch</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments.attention') }}">Lịch cần xử lý</x-nav-link>
                            <x-nav-link href="{{ route('staff.appointments.book') }}">Đặt lịch hộ</x-nav-link>
                            <x-nav-link href="{{ route('staff.customers') }}">Khách hàng</x-nav-link>
                            <x-nav-link href="{{ route('staff.payments') }}">Thanh toán & hoàn tiền</x-nav-link>
                        @endif

                        {{-- Admin --}}
                        @if ($role === 'admin')
                            <div x-data="{ open: null }" @click.outside="open = null" class="hidden lg:flex items-center gap-1">
                                <a href="{{ route('admin.dashboard') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-white/90 hover:bg-[#23689C]">Trang chủ</a>
                                @foreach ([
                                    'operations' => ['label' => 'Vận hành', 'items' => [['Lịch hẹn', 'admin.appointments'], ['Lịch và ngày lễ', 'admin.schedule'], ['Thanh toán và hoàn tiền', 'admin.payments'], ['Kiểm duyệt đánh giá', 'admin.reviews'], ['Thông báo', 'admin.notifications']]],
                                    'catalog' => ['label' => 'Danh mục', 'items' => [['Chuyên viên', 'admin.specialists'], ['Dịch vụ', 'admin.services'], ['Danh mục dịch vụ', 'admin.categories'], ['Người dùng', 'admin.users']]],
                                    'analysis' => ['label' => 'Phân tích', 'items' => [['Báo cáo', 'admin.reports']]],
                                    'system' => ['label' => 'Hệ thống', 'items' => [['Cấu hình', 'admin.settings'], ['Vai trò và quyền', 'admin.roles'], ['Nhật ký audit', 'admin.audit-logs']]],
                                ] as $key => $group)
                                    <div class="relative">
                                        <button type="button" @click="open = open === '{{ $key }}' ? null : '{{ $key }}'" class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-semibold text-white/90 hover:bg-[#23689C]">
                                            {{ $group['label'] }} <span aria-hidden="true">⌄</span>
                                        </button>
                                        <div x-show="open === '{{ $key }}'" x-cloak class="absolute left-0 top-full z-50 mt-1 w-56 rounded-lg border border-gray-200 bg-white p-1 shadow-xl">
                                            @foreach ($group['items'] as $item)
                                                <a href="{{ route($item[1]) }}" class="block rounded-md px-3 py-2 text-sm font-medium text-[#1A202C] hover:bg-[#EAF4FB] hover:text-[#2D82C4]">{{ $item[0] }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endauth
                </nav>

                {{-- ── CÁC ĐIỀU KHIỂN BÊN PHẢI HEADER ── --}}
                <div class="flex items-center gap-3 {{ $isAdmin ? '' : 'mr-auto lg:mr-0' }}">

                    @auth
                        {{-- Chuông thông báo kèm số chưa đọc (mục 1.2) --}}
                        <a href="{{ route('notifications.index') }}"
                           class="relative rounded-full p-2 text-white hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-white transition"
                           aria-label="Thông báo">
                            {{-- Icon chuông --}}
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            {{-- Badge số chưa đọc — ẩn khi = 0 --}}
                            @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
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
                                    class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium text-white hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-white transition"
                                    aria-haspopup="true" :aria-expanded="open.toString()">
                                <span class="hidden sm:block max-w-[120px] truncate" aria-hidden="true">
                                    {{ auth()->user()->name }}
                                </span>
                                {{-- Avatar fallback chữ cái đầu --}}
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#6FA6CF] text-sm font-bold uppercase"
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
                                       class="block px-4 py-2 text-sm text-[#1A202C] hover:bg-[#F5FAFE]" role="menuitem">
                                        Hồ sơ cá nhân
                                    </a>
                                @endif

                                {{-- Đổi mật khẩu --}}
                                <a href="{{ route('password.change') }}"
                                   class="block px-4 py-2 text-sm text-[#1A202C] hover:bg-[#F5FAFE]" role="menuitem">
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
                    <button class="mr-auto lg:hidden rounded-md p-2 text-white hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-white transition"
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
             class="lg:hidden border-t border-[#23689C]"
             x-data="{ open: false }"
             @toggle-mobile-menu.window="open = !open"
             x-show="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2">
            <nav class="mx-auto max-w-screen-xl space-y-1 px-4 py-4" aria-label="Menu di động">

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
                        <x-mobile-nav-link href="{{ route('customer.dashboard') }}">Trang chủ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('appointments.book') }}">Đặt lịch</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('customer.appointments') }}">Lịch hẹn của tôi</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('customer.profile') }}">Hồ sơ cá nhân</x-mobile-nav-link>
                    @endif

                    @if ($role === 'specialist')
                        <x-mobile-nav-link href="{{ route('specialist.dashboard') }}">Trang chủ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('specialist.appointments') }}">Lịch hẹn</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('specialist.schedule') }}">Lịch làm việc</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('specialist.leaves') }}">Ngày nghỉ và chặn giờ</x-mobile-nav-link>
                    @endif

                    @if ($role === 'staff')
                        <x-mobile-nav-link href="{{ route('staff.dashboard') }}">Trang chủ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments') }}">Lịch hẹn</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments.pending') }}">Điều phối lịch</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments.attention') }}">Lịch cần xử lý</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.appointments.book') }}">Đặt lịch hộ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.customers') }}">Khách hàng</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('staff.payments') }}">Thanh toán & hoàn tiền</x-mobile-nav-link>
                    @endif

                    @if ($role === 'admin')
                        <x-mobile-nav-link href="{{ route('admin.dashboard') }}">Trang chủ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.appointments') }}">Lịch hẹn</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.schedule') }}">Lịch và ngày lễ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.payments') }}">Thanh toán & hoàn tiền</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.reviews') }}">Kiểm duyệt đánh giá</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.notifications') }}">Thông báo thủ công</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.specialists') }}">Chuyên viên</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.services') }}">Dịch vụ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.categories') }}">Danh mục dịch vụ</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.users') }}">Người dùng</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.reports') }}">Báo cáo</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.settings') }}">Cấu hình</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.roles') }}">Vai trò và quyền</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('admin.audit-logs') }}">Nhật ký audit</x-mobile-nav-link>
                    @endif

                    {{-- Thông báo + Đổi MK + Đăng xuất trên mobile --}}
                    <div class="border-t border-[#23689C] pt-3 mt-3 space-y-1">
                        <x-mobile-nav-link href="{{ route('notifications.index') }}">Thông báo</x-mobile-nav-link>
                        <x-mobile-nav-link href="{{ route('password.change') }}">Đổi mật khẩu</x-mobile-nav-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left rounded-md px-3 py-2 text-sm font-medium text-red-300 hover:bg-[#23689C] hover:text-red-200 transition">
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
         Nền: bg-[#F5FAFE] (mg-bg) — kế thừa từ <body>
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
    {{--
        Bố cục 3 cột trên nền xanh navy (theo thiết kế tham chiếu):
          Cột 1 — Thương hiệu: logo, mô tả, nút Đặt lịch, mạng xã hội
          Cột 2 — Hỗ trợ: các liên kết điều hướng pháp lý & thông tin
          Cột 3 — Liên hệ: địa chỉ, hotline, email, giờ làm việc (có icon)
    --}}
    <footer class="mt-auto bg-[#2D3E50] text-white">

        {{-- Đường nhấn màu phía trên footer --}}
        <div class="h-1 w-full bg-gradient-to-r from-[#2D82C4] via-[#6FA6CF] to-[#2D82C4]" aria-hidden="true"></div>

        <div class="mx-auto max-w-screen-xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-12 lg:gap-8">

                {{-- ── CỘT 1: THƯƠNG HIỆU ── --}}
                <div class="lg:col-span-5">
                    <a href="{{ url('/') }}"
                       class="group inline-flex items-center gap-3 rounded focus:outline-none focus:ring-2 focus:ring-[#6FA6CF] focus:ring-offset-4 focus:ring-offset-[#2D3E50]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#2D82C4] to-[#6FA6CF] shadow-lg shadow-black/30 transition group-hover:scale-105">
                            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                <path d="M10 20 Q16 10 22 20" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                                <circle cx="16" cy="12" r="3" fill="white"/>
                            </svg>
                        </span>
                        <span>
                            <strong class="block text-2xl font-extrabold leading-none tracking-tight">Mind Guide</strong>
                            <small class="mt-1.5 block text-[11px] font-semibold uppercase tracking-[0.14em] text-[#9FC4DF]">
                                Tâm lý &amp; sức khỏe tinh thần
                            </small>
                        </span>
                    </a>

                    <p class="mt-5 max-w-md text-sm leading-relaxed text-[#C3D3E2]">
                        Phòng khám tâm lý đồng hành cùng bạn trên hành trình chăm sóc sức khỏe tinh thần —
                        lắng nghe thấu hiểu, đồng hành bền bỉ.
                    </p>

                    <a href="{{ route('appointments.book') }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#2D82C4] px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-black/30 transition hover:-translate-y-0.5 hover:bg-[#23689C] hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#6FA6CF] focus:ring-offset-2 focus:ring-offset-[#2D3E50]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                        </svg>
                        Đặt lịch tư vấn
                    </a>

                    {{-- Mạng xã hội --}}
                    <div class="mt-8">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#8FA9BE]">Kết nối với chúng tôi</p>
                        <div class="mt-3 flex items-center gap-3">
                            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook của Mind Guide"
                               class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white/80 ring-1 ring-inset ring-white/10 transition hover:-translate-y-0.5 hover:bg-[#2D82C4] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 22v-8h3l1-4h-4V8c0-1.1.3-1.8 1.9-1.8H17V2.6C16.7 2.6 15.5 2.5 14.2 2.5c-2.9 0-4.9 1.8-4.9 5.1V10H6v4h3.3v8H13z"/></svg>
                            </a>
                            <a href="https://zalo.me" target="_blank" rel="noopener noreferrer" aria-label="Zalo của Mind Guide"
                               class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white/80 ring-1 ring-inset ring-white/10 transition hover:-translate-y-0.5 hover:bg-[#2D82C4] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.9-.9L3 20.5l1.5-4.4A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5z"/><path stroke-linecap="round" d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>
                            </a>
                            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram của Mind Guide"
                               class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white/80 ring-1 ring-inset ring-white/10 transition hover:-translate-y-0.5 hover:bg-[#2D82C4] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.1" fill="currentColor" stroke="none"/></svg>
                            </a>
                            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube của Mind Guide"
                               class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white/80 ring-1 ring-inset ring-white/10 transition hover:-translate-y-0.5 hover:bg-[#2D82C4] hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 7.2a2.8 2.8 0 0 0-2-2C18.7 4.7 12 4.7 12 4.7s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.8 2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.8zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ── CỘT 2: HỖ TRỢ ── --}}
                <div class="lg:col-span-3">
                    <h2 class="flex items-center gap-2.5 text-sm font-extrabold uppercase tracking-[0.14em]">
                        <span class="h-4 w-1 shrink-0 rounded-full bg-[#6FA6CF]" aria-hidden="true"></span>
                        Hỗ trợ
                    </h2>

                    <ul class="mt-5 space-y-3.5">
                        @foreach ([
                            ['Về Mind Guide', 'about'],
                            ['Câu hỏi thường gặp', 'faq'],
                            ['Gửi tin nhắn cho phòng khám', 'contact'],
                            ['Chính sách quyền riêng tư', 'privacy'],
                            ['Điều khoản sử dụng', 'terms'],
                        ] as [$footerLinkLabel, $footerLinkRoute])
                            <li>
                                <a href="{{ route($footerLinkRoute) }}"
                                   class="group inline-flex items-center gap-2 rounded text-sm text-[#C3D3E2] transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                    {{ $footerLinkLabel }}
                                    <svg class="h-3.5 w-3.5 text-[#6FA6CF] opacity-50 transition group-hover:translate-x-1 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </li>
                        @endforeach

                        @guest
                            <li>
                                <a href="{{ route('specialists.index') }}"
                                   class="group inline-flex items-center gap-2 rounded text-sm text-[#C3D3E2] transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                    Chuyên viên tâm lý
                                    <svg class="h-3.5 w-3.5 text-[#6FA6CF] opacity-50 transition group-hover:translate-x-1 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('customer.appointments') }}"
                                   class="group inline-flex items-center gap-2 rounded text-sm text-[#C3D3E2] transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                    Lịch hẹn của tôi
                                    <svg class="h-3.5 w-3.5 text-[#6FA6CF] opacity-50 transition group-hover:translate-x-1 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </li>
                        @endguest
                    </ul>
                </div>

                {{-- ── CỘT 3: LIÊN HỆ ── --}}
                <div class="lg:col-span-4">
                    <h2 class="flex items-center gap-2.5 text-sm font-extrabold uppercase tracking-[0.14em]">
                        <span class="h-4 w-1 shrink-0 rounded-full bg-[#6FA6CF]" aria-hidden="true"></span>
                        Liên hệ
                    </h2>

                    <address class="mt-5 not-italic">
                        <ul class="space-y-5">
                            {{-- Địa chỉ --}}
                            <li class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-[#8FC7F0] ring-1 ring-inset ring-white/10">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/>
                                        <circle cx="12" cy="10" r="2.5"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-[#8FA9BE]">Địa chỉ</span>
                                    <p class="mt-1 text-sm font-bold text-white">Nguyễn Chí Thanh, Hà Nội</p>
                                </div>
                            </li>

                            {{-- Hotline --}}
                            <li class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-[#8FC7F0] ring-1 ring-inset ring-white/10">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5.5A2.5 2.5 0 0 1 5.5 3H7l2 5-2 1.5A13 13 0 0 0 14.5 17L16 15l5 2v1.5a2.5 2.5 0 0 1-2.5 2.5C10.492 21 3 13.508 3 5.5z"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-[#8FA9BE]">Hotline liên hệ</span>
                                    <a href="tel:0967283126"
                                       class="mt-1 inline-flex items-center gap-2 rounded text-sm font-bold text-white transition hover:text-[#8FC7F0] focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                        0967283126
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#2D82C4]" aria-hidden="true">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                                            </svg>
                                        </span>
                                    </a>
                                </div>
                            </li>

                            {{-- Email --}}
                            <li class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-[#8FC7F0] ring-1 ring-inset ring-white/10">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-[#8FA9BE]">Email</span>
                                    <a href="mailto:hotro@mindguide.vn"
                                       class="mt-1 block truncate rounded text-sm font-bold text-white transition hover:text-[#8FC7F0] focus:outline-none focus:ring-2 focus:ring-[#6FA6CF]">
                                        hotro@mindguide.vn
                                    </a>
                                </div>
                            </li>

                            {{-- Giờ làm việc --}}
                            <li class="flex gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-[#8FC7F0] ring-1 ring-inset ring-white/10">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3.5 2"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-[#8FA9BE]">Giờ làm việc</span>
                                    <p class="mt-1 text-sm font-bold text-white">08:00 - 21:00 &middot; Mỗi ngày</p>
                                </div>
                            </li>
                        </ul>
                    </address>
                </div>
            </div>

            {{-- ── THÔNG BÁO AN TOÀN Ở FOOTER (NFR-USA-003, mục 1.2) ── --}}
            {{--
                "Dịch vụ này không dành cho tình huống khẩn cấp" — luôn hiển thị
                ngay cả khi banner trên đã bị đóng.
            --}}
            <div class="mt-12 flex gap-4 rounded-2xl border border-[#F5B978]/25 bg-[#F5B978]/10 px-5 py-4">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#F5B978]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
                <p class="text-xs leading-relaxed text-[#F7DCC2]">
                    <strong class="font-extrabold text-white">Lưu ý quan trọng:</strong>
                    Dịch vụ tư vấn của Mind Guide <strong>không</strong> thay thế can thiệp y tế khẩn cấp.
                    Nếu bạn hoặc người thân đang trong tình trạng nguy hiểm, hãy gọi ngay
                    <a href="tel:115" class="font-extrabold text-white underline decoration-2 underline-offset-2 hover:text-[#F5B978]">115</a>
                    (cấp cứu) hoặc
                    <a href="tel:19001567" class="font-extrabold text-white underline decoration-2 underline-offset-2 hover:text-[#F5B978]">1900&nbsp;1567</a>
                    (hỗ trợ sức khỏe tâm thần, miễn phí, 24/7).
                </p>
            </div>

            {{-- Copyright --}}
            <div class="mt-8 border-t border-white/10 pt-7">
                <div class="flex flex-col items-center justify-between gap-3 text-xs text-[#9FB3C6] sm:flex-row">
                    <p>&copy; {{ date('Y') }} Mind Guide. Mọi quyền được bảo lưu.</p>
                    <p class="text-center sm:text-right">
                        Giấy phép hoạt động số 1234/GH-UB/NĐ &middot; Cơ sở tại Nguyễn Chí Thanh, Hà Nội
                    </p>
                </div>
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
