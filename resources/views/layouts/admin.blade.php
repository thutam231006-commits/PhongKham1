@php
    /*
     * Điều hướng khu vực Quản trị — bảy mục theo đặc tả Admin Module §IV.1.
     *
     * Mỗi mục khai báo đúng tập route con để không đánh dấu nhầm mục đang mở.
     * Nhóm có nhiều trang hiển thị dropdown; nhóm một trang hiển thị liên kết thẳng.
     */
    $adminNavGroups = [
        [
            'key' => 'dashboard',
            'label' => 'Tổng quan',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard'],
            'items' => [],
        ],
        [
            'key' => 'accounts',
            'label' => 'Tài khoản & phân quyền',
            'route' => 'admin.users',
            'active' => ['admin.users', 'admin.users.*', 'admin.roles', 'admin.roles.*'],
            'items' => [
                ['label' => 'Danh sách tài khoản', 'route' => 'admin.users', 'hint' => 'Tạo, khóa, đổi vai trò'],
                ['label' => 'Vai trò và quyền', 'route' => 'admin.roles', 'hint' => 'Ma trận phân quyền RBAC'],
            ],
        ],
        [
            'key' => 'services',
            'label' => 'Dịch vụ & chuyên viên',
            'route' => 'admin.services',
            'active' => ['admin.services', 'admin.services.*', 'admin.categories', 'admin.categories.*', 'admin.specialists', 'admin.specialists.*'],
            'items' => [
                ['label' => 'Gói dịch vụ', 'route' => 'admin.services', 'hint' => 'Giá, thời lượng, trạng thái bán'],
                ['label' => 'Danh mục dịch vụ', 'route' => 'admin.categories', 'hint' => 'Nhóm dịch vụ hiển thị công khai'],
                ['label' => 'Hồ sơ chuyên viên', 'route' => 'admin.specialists', 'hint' => 'Chuyên môn, dịch vụ, nhận lịch'],
            ],
        ],
        [
            'key' => 'schedule',
            'label' => 'Lịch & cấu hình',
            'route' => 'admin.schedule',
            'active' => ['admin.schedule', 'admin.schedule.*', 'admin.settings', 'admin.settings.*', 'admin.appointments', 'admin.appointments.*'],
            'items' => [
                ['label' => 'Lịch hẹn toàn hệ thống', 'route' => 'admin.appointments', 'hint' => 'Theo dõi và hủy lịch'],
                ['label' => 'Ngày nghỉ phòng khám', 'route' => 'admin.schedule', 'hint' => 'Ngày lễ, nghỉ, chặn giờ'],
                ['label' => 'Tham số vận hành', 'route' => 'admin.settings', 'hint' => 'Đặt lịch, đổi/hủy, hoàn tiền'],
                ['label' => 'Cấu hình gửi email', 'route' => 'admin.settings.mail', 'hint' => 'Kiểm tra kết nối SMTP'],
            ],
        ],
        [
            'key' => 'moderation',
            'label' => 'Kiểm duyệt',
            'route' => 'admin.reviews',
            'active' => ['admin.reviews', 'admin.reviews.*'],
            'items' => [],
        ],
        [
            'key' => 'reports',
            'label' => 'Báo cáo',
            'route' => 'admin.reports',
            'active' => ['admin.reports', 'admin.reports.*'],
            'items' => [],
        ],
        [
            'key' => 'security',
            'label' => 'Bảo mật & Audit',
            'route' => 'admin.audit-logs',
            'active' => ['admin.audit-logs', 'admin.audit-logs.*', 'admin.payments', 'admin.payments.*', 'admin.refunds.*', 'admin.notifications', 'admin.notifications.*'],
            'items' => [
                ['label' => 'Nhật ký kiểm toán', 'route' => 'admin.audit-logs', 'hint' => 'Truy vết thao tác quản trị'],
                ['label' => 'Thanh toán & hoàn tiền', 'route' => 'admin.payments', 'hint' => 'Đối soát dòng tiền'],
                ['label' => 'Thông báo thủ công', 'route' => 'admin.notifications', 'hint' => 'Gửi thông báo theo nhóm'],
            ],
        ],
    ];

    $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị hệ thống') | Mind Guide</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="mg-app flex h-full min-h-screen flex-col bg-[#F0F7FC] text-[#102A43] antialiased">

<a href="#main-content" class="mg-skip-link">Chuyển đến nội dung chính</a>

{{-- ============================================================
     HEADER — thanh menu ngang cố định, tông xanh #2D82C4
     ============================================================ --}}
<header class="sticky top-0 z-50 bg-[#2D82C4] shadow-[0_10px_30px_rgb(18_63_104/0.18)]"
        x-data="adminNav()"
        @keydown.escape.window="closeMenu()">
    <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center gap-3">

            {{-- Thương hiệu --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex shrink-0 items-center gap-2.5 rounded-lg py-1 pr-1 text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-white/60">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 ring-1 ring-inset ring-white/25">
                    <svg class="h-5.5 w-5.5" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="M9 21 Q16 9 23 21" stroke="white" stroke-width="2.6" stroke-linecap="round" fill="none"/>
                        <circle cx="16" cy="12.5" r="3.4" fill="white"/>
                    </svg>
                </span>
                <span class="hidden sm:block">
                    <strong class="block text-[15px] font-extrabold leading-none tracking-tight">Mind Guide</strong>
                    <small class="mt-1 block text-[10px] font-bold uppercase tracking-[0.16em] text-white/65">Quản trị hệ thống</small>
                </span>
            </a>

            {{-- ── MENU NGANG (desktop) ── --}}
            <nav aria-label="Menu quản trị" class="hidden min-w-0 flex-1 items-center gap-1 lg:flex">
                @foreach($adminNavGroups as $group)
                    @php $groupActive = request()->routeIs(...$group['active']); @endphp

                    @if(empty($group['items']))
                        <a href="{{ route($group['route']) }}"
                           class="relative whitespace-nowrap rounded-lg px-3 py-2 text-[13px] font-semibold leading-5 transition-colors
                                  {{ $groupActive
                                      ? 'bg-white/20 text-white shadow-[inset_0_0_0_1px_rgb(255_255_255/0.18)]'
                                      : 'text-white/85 hover:bg-white/10 hover:text-white' }}"
                           @if($groupActive) aria-current="page" @endif>
                            {{ $group['label'] }}
                            @if($groupActive)
                                <span class="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-white" aria-hidden="true"></span>
                            @endif
                        </a>
                    @else
                        <div class="relative">
                            <button type="button"
                                    @click="toggleMenu('{{ $group['key'] }}')"
                                    class="flex items-center gap-1 whitespace-nowrap rounded-lg px-3 py-2 text-[13px] font-semibold leading-5 transition-colors
                                           {{ $groupActive
                                               ? 'bg-white/20 text-white shadow-[inset_0_0_0_1px_rgb(255_255_255/0.18)]'
                                               : 'text-white/85 hover:bg-white/10 hover:text-white' }}"
                                    :aria-expanded="menuIsOpen('{{ $group['key'] }}').toString()"
                                    aria-haspopup="true">
                                {{ $group['label'] }}
                                <svg class="h-3 w-3 transition-transform" :class="{ 'rotate-180': menuIsOpen('{{ $group['key'] }}') }"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                                </svg>
                                @if($groupActive)
                                    <span class="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-white" aria-hidden="true"></span>
                                @endif
                            </button>

                            <div x-show="menuIsOpen('{{ $group['key'] }}')"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-120"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-90"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 @click.outside="closeMenu()"
                                 class="absolute left-0 top-full z-50 mt-2 w-72 origin-top-left rounded-xl border border-[#D7E8F3] bg-white p-1.5 shadow-2xl"
                                 role="menu" aria-label="{{ $group['label'] }}">
                                @foreach($group['items'] as $item)
                                    @php $itemActive = request()->routeIs($item['route']); @endphp
                                    <a href="{{ route($item['route']) }}"
                                       class="block rounded-lg px-3 py-2.5 transition-colors {{ $itemActive ? 'bg-[#EAF4FB]' : 'hover:bg-[#F3F9FD]' }}"
                                       role="menuitem"
                                       @if($itemActive) aria-current="page" @endif>
                                        <span class="block text-sm font-bold {{ $itemActive ? 'text-[#23689C]' : 'text-[#102A43]' }}">{{ $item['label'] }}</span>
                                        <span class="mt-0.5 block text-[11px] text-[#718096]">{{ $item['hint'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            {{-- ── ĐIỀU KHIỂN BÊN PHẢI ── --}}
            <div class="ml-auto flex shrink-0 items-center gap-1.5 lg:ml-0">
                <a href="{{ route('admin.reports') }}"
                   class="hidden rounded-lg px-3 py-2 text-[13px] font-bold text-white/85 transition hover:bg-white/10 hover:text-white xl:block">
                    Xuất báo cáo
                </a>

                <a href="{{ route('notifications.index') }}"
                   class="relative rounded-full p-2 text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60"
                   aria-label="Thông báo{{ $unreadNotifications > 0 ? ' ('.$unreadNotifications.' chưa đọc)' : '' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($unreadNotifications > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">
                            {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>

                {{-- Tài khoản --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                            class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2.5 text-sm font-semibold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60"
                            aria-haspopup="true" :aria-expanded="open.toString()">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-xs font-bold uppercase ring-1 ring-inset ring-white/25">
                            {{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </span>
                        <span class="hidden max-w-[120px] truncate md:block">{{ auth()->user()->name ?? '' }}</span>
                        <svg class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': open }"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-50 mt-2 w-60 origin-top-right rounded-xl bg-white py-1 shadow-xl ring-1 ring-black/5"
                         role="menu" aria-label="Menu tài khoản">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="truncate text-sm font-bold text-[#102A43]">{{ auth()->user()->name ?? '' }}</p>
                            <p class="truncate text-xs text-[#718096]">{{ auth()->user()->email ?? '' }}</p>
                            <p class="mt-1.5 inline-flex rounded-full bg-[#EAF4FB] px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-[#23689C]">Quản trị viên</p>
                        </div>
                        <a href="{{ route('password.change') }}" class="block px-4 py-2 text-sm text-[#102A43] hover:bg-[#F0F7FC]" role="menuitem">Đổi mật khẩu</a>
                        <a href="{{ url('/') }}" class="block px-4 py-2 text-sm text-[#102A43] hover:bg-[#F0F7FC]" role="menuitem">Xem trang khách hàng</a>
                        <div class="my-1 border-t border-gray-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50" role="menuitem">Đăng xuất</button>
                        </form>
                    </div>
                </div>

                {{-- Hamburger — mobile --}}
                <button type="button"
                        @click="toggleMobile()"
                        class="rounded-lg p-2 text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60 lg:hidden"
                        aria-controls="admin-mobile-menu"
                        :aria-expanded="mobileOpen.toString()"
                        aria-label="Mở/đóng menu quản trị">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MENU DỌC CHO DI ĐỘNG ── --}}
    <div id="admin-mobile-menu"
         x-show="mobileOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="max-h-[75vh] overflow-y-auto border-t border-white/20 bg-[#276fa9] lg:hidden">
        <nav class="mx-auto max-w-[1600px] space-y-3 px-4 py-4 sm:px-6" aria-label="Menu quản trị trên di động">
            @foreach($adminNavGroups as $group)
                @php $groupActive = request()->routeIs(...$group['active']); @endphp
                <div>
                    @if(empty($group['items']))
                        <a href="{{ route($group['route']) }}"
                           class="block rounded-lg px-3 py-2.5 text-sm font-bold transition {{ $groupActive ? 'bg-white/20 text-white' : 'text-white/85 hover:bg-white/10' }}"
                           @if($groupActive) aria-current="page" @endif>
                            {{ $group['label'] }}
                        </a>
                    @else
                        <p class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-[0.14em] {{ $groupActive ? 'text-white' : 'text-white/55' }}">
                            {{ $group['label'] }}
                        </p>
                        <div class="space-y-0.5">
                            @foreach($group['items'] as $item)
                                @php $itemActive = request()->routeIs($item['route']); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="block rounded-lg px-3 py-2 text-sm transition {{ $itemActive ? 'bg-white/20 font-bold text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                   @if($itemActive) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="space-y-0.5 border-t border-white/20 pt-3">
                <a href="{{ route('notifications.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-white/80 hover:bg-white/10 hover:text-white">Thông báo</a>
                <a href="{{ route('password.change') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-white/80 hover:bg-white/10 hover:text-white">Đổi mật khẩu</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-200 hover:bg-white/10">Đăng xuất</button>
                </form>
            </div>
        </nav>
    </div>
</header>

{{-- ============================================================
     FLASH MESSAGES
     ============================================================ --}}
@if(session()->hasAny(['success', 'error', 'warning', 'info']))
<div class="mx-auto w-full max-w-[1600px] px-4 pt-4 sm:px-6 lg:px-8" aria-live="polite">
    @foreach(['success', 'error', 'warning', 'info'] as $flashType)
        @continue(! session($flashType))
        @php
            $flashStyle = [
                'success' => ['border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'M5 13l4 4L19 7'],
                'error' => ['border-red-200', 'bg-red-50', 'text-red-800', 'M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z'],
                'warning' => ['border-amber-200', 'bg-amber-50', 'text-amber-900', 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
                'info' => ['border-blue-200', 'bg-blue-50', 'text-blue-800', 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z'],
            ][$flashType];
        @endphp
        <div role="alert" class="mt-2 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm first:mt-0 {{ implode(' ', array_slice($flashStyle, 0, 3)) }}">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $flashStyle[3] }}"/>
            </svg>
            <span>{{ e(session($flashType)) }}</span>
        </div>
    @endforeach
</div>
@endif

@if($errors->any())
<div class="mx-auto w-full max-w-[1600px] px-4 pt-4 sm:px-6 lg:px-8" aria-live="polite">
    <div role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/>
        </svg>
        <div>
            <p class="font-bold">Không lưu được thay đổi</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

{{-- ============================================================
     NỘI DUNG CHÍNH
     ============================================================ --}}
<main id="main-content" class="w-full flex-1" tabindex="-1" aria-label="Nội dung chính">
    @yield('content')
</main>

<footer class="mt-auto border-t border-[#D7E8F3] bg-white">
    <div class="mx-auto flex max-w-[1600px] flex-col gap-1 px-4 py-4 text-xs text-[#718096] sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p>&copy; {{ date('Y') }} Mind Guide &middot; Khu vực quản trị nội bộ</p>
        <p>Mọi thao tác quan trọng đều được ghi vào nhật ký kiểm toán.</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>
