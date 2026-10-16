{{--
    SCR-CUS-04 — Lịch hẹn của tôi (FR-APPT-004)
    ============================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-04
    Bố cục:
      - Phân tab: "Sắp tới", "Đã hoàn thành", "Đã hủy", "Tất cả".
      - Mỗi dòng/thẻ: mã lịch, ngày giờ, chuyên viên, dịch vụ, chip trạng thái (<x-status-chip>), chip thanh toán, nút Xem chi tiết.
      - Trạng thái rỗng chuẩn mực có nút Đặt lịch.
--}}

@extends('layouts.app')

@section('title', 'Lịch Hẹn Của Tôi — Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li>
            <a href="{{ route('customer.dashboard') }}" class="hover:text-[#1D4B43] hover:underline transition">Khách hàng</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Lịch hẹn của tôi</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">

    {{-- Header tiêu đề & Nút đặt lịch mới --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
                <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
                Quản lý lịch hẹn tư vấn
            </span>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-[#0F172A]">
                Lịch hẹn của tôi
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-[#4A5568]">
                Theo dõi tiến trình, khung giờ tham vấn và trạng thái thanh toán của các buổi hẹn.
            </p>
        </div>

        <a href="{{ route('appointments.book') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-[#1D4B43] px-6 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs flex-shrink-0 self-start sm:self-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Đặt lịch hẹn mới</span>
        </a>
    </div>

    {{-- THANH TAB LỌC TRẠNG THÁI --}}
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6 text-sm font-semibold overflow-x-auto">
            <a href="{{ route('customer.appointments', ['tab' => 'upcoming']) }}"
               class="whitespace-nowrap pb-4 px-1 border-b-2 transition {{ $tab === 'upcoming' ? 'border-[#1D4B43] text-[#1D4B43] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A] hover:border-gray-300' }}">
                Sắp diễn ra
            </a>

            <a href="{{ route('customer.appointments', ['tab' => 'completed']) }}"
               class="whitespace-nowrap pb-4 px-1 border-b-2 transition {{ $tab === 'completed' ? 'border-[#1D4B43] text-[#1D4B43] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A] hover:border-gray-300' }}">
                Đã hoàn thành
            </a>

            <a href="{{ route('customer.appointments', ['tab' => 'cancelled']) }}"
               class="whitespace-nowrap pb-4 px-1 border-b-2 transition {{ $tab === 'cancelled' ? 'border-[#1D4B43] text-[#1D4B43] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A] hover:border-gray-300' }}">
                Đã hủy / Vắng mặt
            </a>

            <a href="{{ route('customer.appointments', ['tab' => 'all']) }}"
               class="whitespace-nowrap pb-4 px-1 border-b-2 transition {{ $tab === 'all' ? 'border-[#1D4B43] text-[#1D4B43] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A] hover:border-gray-300' }}">
                Tất cả lịch hẹn
            </a>
        </nav>
    </div>

    {{-- DANH SÁCH LỊCH HẸN HOẶC TRẠNG THÁI RỖNG --}}
    <div class="space-y-4">
        @forelse($appointments as $app)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-xs hover:border-[#709782] transition">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-xs font-black text-[#1D4B43] bg-[#E8F0EC] px-3 py-1 rounded-lg">
                            {{ $app->code }}
                        </span>
                        <span class="text-xs text-[#4A5568] font-medium">
                            Hình thức: <strong class="text-[#0F172A]">{{ $app->type === 'ONLINE' ? 'Trực tuyến (Online)' : 'Trực tiếp tại phòng khám' }}</strong>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-status-chip type="appointment" :status="$app->status" />
                        <x-status-chip type="payment" :status="$app->payment_status" />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    {{-- Dịch vụ & Chuyên viên --}}
                    <div class="md:col-span-5">
                        <span class="text-xs text-[#4A5568] block">Gói dịch vụ tư vấn:</span>
                        <h3 class="text-base font-bold text-[#0F172A] mt-0.5">{{ $app->service_name }}</h3>
                        <p class="text-xs text-[#709782] font-semibold mt-1">
                            Chuyên gia phụ trách: <span class="text-[#0F172A]">{{ $app->specialist_name }}</span>
                        </p>
                    </div>

                    {{-- Ngày giờ --}}
                    <div class="md:col-span-4">
                        <span class="text-xs text-[#4A5568] block">Thời gian tư vấn:</span>
                        <div class="font-bold text-sm text-[#0F172A] flex items-center gap-1.5 mt-0.5">
                            <svg class="h-4 w-4 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ date('d/m/Y', strtotime($app->date)) }}</span>
                            <span class="text-gray-300">•</span>
                            <span>{{ $app->time_slot }}</span>
                        </div>
                        <span class="text-[11px] text-[#4A5568] block mt-1">{{ $app->location ?? 'Phòng tham vấn riêng tư' }}</span>
                    </div>

                    {{-- Nút hành động --}}
                    <div class="md:col-span-3 flex justify-start md:justify-end items-center gap-2 pt-2 md:pt-0">
                        @if($app->status === 'COMPLETED')
                            <a href="{{ route('customer.review', ['appointment_id' => $app->id]) }}"
                               class="rounded-xl bg-[#E8F0EC] px-3.5 py-2 text-xs font-bold text-[#1D4B43] hover:bg-[#1D4B43] hover:text-white transition">
                                Đánh giá
                            </a>
                        @endif

                        <a href="{{ route('customer.appointments.detail', ['id' => $app->id]) }}"
                           class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-[#0F172A] hover:bg-gray-50 transition shadow-2xs">
                            Xem chi tiết →
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#E8F0EC] text-[#1D4B43] mb-3">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-[#0F172A]">Bạn chưa có lịch hẹn nào trong mục này.</h3>
                <p class="text-xs text-[#4A5568] mt-1 max-w-sm mx-auto">
                    Đặt lịch tư vấn để nhận được sự đồng hành từ các chuyên gia tâm lý giàu kinh nghiệm.
                </p>
                <a href="{{ route('appointments.book') }}"
                   class="mt-5 inline-block rounded-xl bg-[#1D4B43] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                    Đặt lịch ngay
                </a>
            </div>
        @endforelse
    </div>

</div>
@endsection
