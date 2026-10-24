<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>document.documentElement.classList.add('js');</script>
    <title>@yield('title', 'Lễ Tân') | Mind Guide</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-[#F0F7FC] text-[#102A43] antialiased">

    {{-- ============================================================
         HEADER: Top navigation bar — xanh #2D82C4, full width
         ============================================================ --}}
    <header class="sticky top-0 z-50 bg-[#2D82C4] shadow-md" x-data="{ mobileOpen: false }">
        <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">

                {{-- Logo --}}
                <a href="{{ route('staff.dashboard') }}"
                   class="flex shrink-0 items-center gap-2.5 rounded text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-white/60">
                    <svg class="h-8 w-8 shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <circle cx="16" cy="16" r="14" fill="#6FA6CF"/>
                        <path d="M10 20 Q16 10 22 20" stroke="white" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                        <circle cx="16" cy="12" r="3" fill="white"/>
                    </svg>
                    <span>
                        <strong class="block text-base font-extrabold leading-none tracking-tight">Mind Guide</strong>
                        <small class="mt-0.5 block text-[10px] font-semibold text-white/70">Quầy Lễ Tân</small>
                    </span>
                </a>

                {{-- ── MENU ĐIỀU HƯỚNG PHÍA TRÊN — desktop ── --}}
                <nav aria-label="Menu lễ tân" class="hidden flex-1 items-center justify-center gap-1 lg:flex">
                    @php
                        $navItems = [
                            ['route' => 'staff.dashboard',              'label' => 'Tổng quan'],
                            ['route' => 'staff.appointments',           'label' => 'Quản lý lịch hẹn'],
                            ['route' => 'staff.appointments.attention', 'label' => 'Lịch cần xử lý'],
                            ['route' => 'staff.customers',              'label' => 'Khách hàng'],
                            ['route' => 'staff.payments',               'label' => 'Thanh toán'],
                        ];
                    @endphp

                    @foreach($navItems as $nav)
                        @php
                            $isActive = request()->routeIs($nav['route']) || request()->routeIs($nav['route'] . '.*');
                        @endphp
                        <a href="{{ route($nav['route']) }}"
                           class="relative rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors
                                  {{ $isActive
                                     ? 'bg-white/20 text-white'
                                     : 'text-white/85 hover:bg-white/10 hover:text-white' }}"
                           @if($isActive) aria-current="page" @endif>
                            {{ $nav['label'] }}
                            @if($isActive)
                                <span class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-px h-0.5 w-5 rounded-full bg-white"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                {{-- Right controls --}}
                <div class="flex shrink-0 items-center gap-2">

                    {{-- Notification bell --}}
                    <a href="{{ route('notifications.index') }}"
                       class="relative rounded-full p-2 text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60 transition"
                       aria-label="Thông báo">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @php $unread = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0; @endphp
                        @if($unread > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-4.5 w-4.5 min-w-[1.125rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">
                                {{ $unread > 99 ? '99+' : $unread }}
                            </span>
                        @endif
                    </a>

                    {{-- User dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                @keydown.escape.window="open = false"
                                class="flex items-center gap-2 rounded-full py-1.5 pl-2 pr-3 text-sm font-semibold text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60 transition"
                                aria-haspopup="true" :aria-expanded="open.toString()">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-xs font-bold uppercase">
                                {{ mb_substr(auth()->user()->name ?? 'L', 0, 1) }}
                            </span>
                            <span class="hidden max-w-[100px] truncate sm:block">{{ auth()->user()->name ?? '' }}</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="{'rotate-180': open}"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.outside="open = false"
                             x-cloak
                             class="absolute right-0 mt-2 w-52 origin-top-right rounded-xl bg-white py-1 shadow-xl ring-1 ring-black/5 z-50"
                             role="menu" aria-label="Menu tài khoản">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-[#1A202C]">{{ auth()->user()->name ?? '' }}</p>
                                <p class="truncate text-xs text-[#4A5568]">{{ auth()->user()->email ?? '' }}</p>
                            </div>
                            <a href="{{ route('password.change') }}"
                               class="block px-4 py-2 text-sm text-[#1A202C] hover:bg-[#F0F7FC]" role="menuitem">
                                Đổi mật khẩu
                            </a>
                            <div class="border-t border-gray-100"></div>
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

                    {{-- Hamburger — mobile --}}
                    <button @click="mobileOpen = !mobileOpen"
                            class="lg:hidden rounded-md p-2 text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/60"
                            aria-label="Mở/đóng menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="mobileOpen"  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-cloak
             class="border-t border-white/20 lg:hidden">
            <nav class="mx-auto max-w-screen-xl space-y-1 px-4 py-3" aria-label="Menu di động lễ tân">
                @foreach($navItems as $nav)
                    @php $isActive = request()->routeIs($nav['route']) || request()->routeIs($nav['route'] . '.*'); @endphp
                    <a href="{{ route($nav['route']) }}"
                       class="block rounded-lg px-3 py-2.5 text-sm font-semibold transition
                              {{ $isActive ? 'bg-white/20 text-white' : 'text-white/85 hover:bg-white/10 hover:text-white' }}"
                       @if($isActive) aria-current="page" @endif>
                        {{ $nav['label'] }}
                    </a>
                @endforeach
                <div class="border-t border-white/20 pt-2 mt-2 space-y-1">
                    <a href="{{ route('notifications.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white">Thông báo</a>
                    <a href="{{ route('password.change') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-white/85 hover:bg-white/10 hover:text-white">Đổi mật khẩu</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left rounded-lg px-3 py-2.5 text-sm font-semibold text-red-300 hover:bg-white/10">Đăng xuất</button>
                    </form>
                </div>
            </nav>
        </div>
    </header>

    {{-- ============================================================
         FLASH MESSAGES
         ============================================================ --}}
    @if(session()->hasAny(['success', 'error', 'warning', 'info']))
    <div class="mx-auto w-full max-w-screen-xl px-4 sm:px-6 lg:px-8 pt-4 space-y-2" aria-live="polite">
        @if(session('success'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ e(session('success')) }}</span>
        </div>
        @endif
        @if(session('error'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/></svg>
            <span>{{ e(session('error')) }}</span>
        </div>
        @endif
        @if(session('warning'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>{{ e(session('warning')) }}</span>
        </div>
        @endif
        @if(session('info'))
        <div role="alert" class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/></svg>
            <span>{{ e(session('info')) }}</span>
        </div>
        @endif
    </div>
    @endif

    {{-- ============================================================
         MAIN CONTENT
         ============================================================ --}}
    <main id="main-content" class="flex-1 w-full min-h-screen" tabindex="-1" aria-label="Nội dung chính">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
