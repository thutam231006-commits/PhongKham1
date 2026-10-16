{{--
    SCR-CUS-01 — Tổng quan khách hàng (Dashboard) (FR-DASH-002)
    =============================================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-01
    Bố cục:
      - Khối 1: Lịch hẹn sắp tới (tối đa 3 lịch gần nhất, chip trạng thái & thanh toán).
      - Khối 2: Thông báo mới (5 mục) + liên kết xem tất cả.
      - Khối 3: Nút "Đặt lịch mới" & Lối tắt chức năng nhanh.
      - Trạng thái rỗng chuẩn mực khi chưa có lịch hẹn.
--}}

@extends('layouts.app')

@section('title', 'Tổng Quan Khách Hàng — Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Tổng quan tài khoản</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">

    {{-- ── 1. LỜI CHÀO & TÓM TẮT TRẠNG THÁI ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 sm:p-8 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
                <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
                Mã khách hàng: {{ $user->code }}
            </span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-black text-[#0F172A]">
                Xin chào, {{ $user->name }}
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-[#4A5568]">
                Chào mừng bạn quay trở lại. Hãy cùng Mind Guide duy trì sự an yên và cân bằng nội tâm mỗi ngày.
            </p>
        </div>

        {{-- Lối tắt Đặt lịch mới ngay lập tức --}}
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('appointments.book') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-[#1D4B43] px-6 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Đặt lịch tư vấn mới</span>
            </a>
        </div>
    </div>

    {{-- ── 2. CÁC THẺ THỐNG KÊ NHANH ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-[#E8F0EC] flex items-center justify-center text-[#1D4B43]">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <span class="text-xs text-[#4A5568] block">Lịch hẹn sắp tới</span>
                <strong class="text-2xl font-black text-[#0F172A]">{{ $upcomingAppointments->count() }}</strong>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-800">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <span class="text-xs text-[#4A5568] block">Buổi tư vấn hoàn thành</span>
                <strong class="text-2xl font-black text-[#0F172A]">{{ $stats['completed_appointments'] }}</strong>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-800">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
            <div>
                <span class="text-xs text-[#4A5568] block">Đánh giá cần hoàn tất</span>
                <strong class="text-2xl font-black text-amber-700">{{ $stats['pending_reviews'] }}</strong>
            </div>
        </div>
    </div>

    {{-- ── 3. KHỐI NỘI DUNG CHÍNH (2 CỘT) ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- CỘT TRÁI (8/12): LỊCH HẸN SẮP TỚI (SCR-CUS-01 KHỐI 1) --}}
        <div class="lg:col-span-8 space-y-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-7 shadow-xs">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-lg font-bold text-[#0F172A]">Lịch hẹn sắp tới</h2>
                        <p class="text-xs text-[#4A5568] mt-0.5">Các buổi tham vấn đang hoạt động gần nhất của bạn.</p>
                    </div>
                    <a href="{{ route('customer.appointments') }}" class="text-xs font-bold text-[#1D4B43] hover:underline">
                        Xem tất cả lịch hẹn →
                    </a>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($upcomingAppointments as $app)
                        <div class="rounded-2xl border border-gray-200/90 bg-[#F7FAFC] p-5 hover:border-[#709782] transition shadow-2xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200/80 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-[#1D4B43] bg-[#E8F0EC] px-2.5 py-0.5 rounded-md">
                                        {{ $app->code }}
                                    </span>
                                    <span class="text-xs font-semibold text-[#4A5568]">
                                        Hình thức: <strong class="text-[#0F172A]">{{ $app->type === 'ONLINE' ? 'Trực tuyến (Online)' : 'Trực tiếp tại phòng khám' }}</strong>
                                    </span>
                                </div>

                                {{-- Chip trạng thái lịch và thanh toán --}}
                                <div class="flex items-center gap-2">
                                    <x-status-chip type="appointment" :status="$app->status" />
                                    <x-status-chip type="payment" :status="$app->payment_status" />
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-xs text-[#4A5568] block">Gói dịch vụ:</span>
                                    <h4 class="font-bold text-[#0F172A] mt-0.5">{{ $app->service_name }}</h4>
                                    <p class="text-xs text-[#709782] mt-0.5">Chuyên gia: <strong>{{ $app->specialist_name }}</strong></p>
                                </div>

                                <div>
                                    <span class="text-xs text-[#4A5568] block">Thời gian tư vấn:</span>
                                    <div class="font-bold text-[#0F172A] flex items-center gap-1.5 mt-0.5">
                                        <svg class="h-4 w-4 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>{{ date('d/m/Y', strtotime($app->date)) }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span>{{ $app->time_slot }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Nút điều khiển --}}
                            <div class="mt-5 pt-3 border-t border-gray-200/80 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    @if($app->type === 'ONLINE' && $app->status === 'CONFIRMED')
                                        <span class="text-xs text-emerald-700 font-semibold flex items-center gap-1">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Phòng họp video đã sẵn sàng
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($app->status === 'PENDING')
                                        <a href="{{ route('appointments.book', ['service' => 1]) }}"
                                           class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 transition">
                                            Thanh toán ngay
                                        </a>
                                    @endif

                                    <a href="{{ route('customer.appointments.detail', ['id' => $app->id]) }}"
                                       class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-[#0F172A] hover:bg-gray-50 transition">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        {{-- Trạng thái rỗng --}}
                        <div class="p-8 text-center bg-[#F7FAFC] rounded-2xl border border-dashed border-gray-300">
                            <p class="text-sm font-semibold text-[#0F172A]">Bạn chưa có lịch hẹn sắp tới nào.</p>
                            <p class="text-xs text-[#4A5568] mt-1">Hãy bắt đầu lựa chọn chuyên gia và thời gian thích hợp để chăm sóc sức khỏe tinh thần của bạn.</p>
                            <a href="{{ route('appointments.book') }}" class="mt-4 inline-block rounded-xl bg-[#1D4B43] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                                Đặt lịch tư vấn ngay
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- LỐI TẮT CHỨC NĂNG NHANH (SCR-CUS-01 KHỐI 3) --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('services.index') }}" class="p-4 rounded-xl border border-gray-200 bg-white hover:border-[#709782] transition shadow-2xs block">
                    <span class="text-xs font-bold text-[#1D4B43] block">Khám phá</span>
                    <h4 class="font-bold text-sm text-[#0F172A] mt-1">Danh mục dịch vụ</h4>
                    <p class="text-xs text-[#4A5568] mt-1">Xem đầy đủ 9 gói tư vấn chuyên sâu của phòng khám.</p>
                </a>

                <a href="{{ route('customer.medical-history') }}" class="p-4 rounded-xl border border-gray-200 bg-white hover:border-[#709782] transition shadow-2xs block">
                    <span class="text-xs font-bold text-[#1D4B43] block">Hồ sơ</span>
                    <h4 class="font-bold text-sm text-[#0F172A] mt-1">Lịch sử & Lời dặn</h4>
                    <p class="text-xs text-[#4A5568] mt-1">Xem ghi chú tóm tắt và bài tập từ chuyên viên.</p>
                </a>

                <a href="{{ route('customer.review') }}" class="p-4 rounded-xl border border-gray-200 bg-white hover:border-[#709782] transition shadow-2xs block">
                    <span class="text-xs font-bold text-[#1D4B43] block">Phản hồi</span>
                    <h4 class="font-bold text-sm text-[#0F172A] mt-1">Đánh giá chuyên gia</h4>
                    <p class="text-xs text-[#4A5568] mt-1">Gửi nhận xét ẩn danh sau buổi tham vấn.</p>
                </a>
            </div>

        </div>

        {{-- CỘT PHẢI (4/12): THÔNG BÁO MỚI (SCR-CUS-01 KHỐI 2) & THÔNG TIN HỖ TRỢ --}}
        <div class="lg:col-span-4 space-y-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-gray-100 mb-4">
                    <h3 class="text-base font-bold text-[#0F172A] flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span>Thông báo mới</span>
                    </h3>
                </div>

                <div class="divide-y divide-gray-100 text-xs">
                    @forelse($notifications as $noti)
                        <div class="py-3">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="font-bold text-[#0F172A] truncate">{{ $noti->title }}</span>
                                <span class="text-[10px] text-gray-400 flex-shrink-0">{{ $noti->time }}</span>
                            </div>
                            <p class="text-[#4A5568] leading-relaxed">{{ $noti->content }}</p>
                        </div>
                    @empty
                        <p class="text-gray-400 italic py-3 text-center">Không có thông báo mới.</p>
                    @endforelse
                </div>
            </div>

            {{-- Cảnh báo an toàn y tế --}}
            <div class="rounded-2xl border border-[#FED7AA] bg-[#FFEDD5] p-5 text-xs text-[#7C2D12] space-y-2">
                <strong class="font-bold text-sm block">Cần hỗ trợ khẩn cấp?</strong>
                <p class="leading-relaxed">
                    Dịch vụ tham vấn không thay thế can thiệp cấp cứu. Trong trường hợp nguy hiểm, xin hãy gọi ngay:
                </p>
                <div class="flex items-center gap-3 pt-1 font-bold text-sm">
                    <a href="tel:115" class="underline text-red-800">115 (Cấp cứu)</a>
                    <span>•</span>
                    <a href="tel:19001567" class="underline text-[#7C2D12]">1900 1567 (Tâm thần)</a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
