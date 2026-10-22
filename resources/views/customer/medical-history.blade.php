{{--
    Hồ sơ tư vấn & Lời dặn chuyên viên (Medical History / Consultation Notes)
    ========================================================================
    Căn cứ: docs/ThietKeGiaoDien.md §1.7 Bảo mật và hồ sơ tư vấn
    Đặc tính:
      - Chỉ hiển thị các buổi tư vấn COMPLETED.
      - Tóm tắt buổi tham vấn, khuyến nghị và bài tập thực hành từ chuyên viên (chỉ đọc).
      - Cam kết bảo mật riêng tư tuyệt đối.
--}}

@extends('layouts.app')

@section('title', 'Lịch Sử Tư Vấn & Lời Dặn — Mind Guide')

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
        <li class="font-semibold text-[#1A202C]" aria-current="page">Lịch sử tư vấn & Lời dặn</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">

    {{-- Tiêu đề trang --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
                <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
                Hồ sơ sức khỏe tinh thần (§1.7 bảo mật)
            </span>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-[#0F172A]">
                Lịch sử tư vấn & Lời dặn chuyên gia
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-[#4A5568]">
                Xem lại ghi nhận tổng quát, lời khuyên và bài tập thực hành do chuyên viên tâm lý gửi gắm sau mỗi buổi hẹn.
            </p>
        </div>
    </div>

    {{-- Cam kết bảo mật --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5 flex items-start gap-3.5 text-xs text-emerald-950">
        <svg class="h-5 w-5 text-emerald-700 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <div class="leading-relaxed">
            <strong class="font-bold">Bảo vệ quyền riêng tư (§1.7):</strong> Các nội dung ghi chép này được mã hóa bảo mật. Chỉ bạn và chuyên viên phụ trách buổi tham vấn mới có quyền truy cập hồ sơ này.
        </div>
    </div>

    {{-- Danh sách hồ sơ các buổi tư vấn --}}
    <div class="space-y-6">
        @forelse($records as $rec)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs hover:border-[#709782] transition space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-black text-[#1D4B43] bg-[#E8F0EC] px-3 py-1 rounded-lg">
                                {{ $rec->appointment_code }}
                            </span>
                            <span class="text-xs text-[#4A5568]">Ngày tư vấn: <strong class="text-[#0F172A]">{{ $rec->date }}</strong> ({{ $rec->time_slot }})</span>
                        </div>
                        <h3 class="text-lg font-bold text-[#0F172A] mt-2">{{ $rec->service_name }}</h3>
                        <p class="text-xs text-[#709782] font-semibold mt-0.5">Chuyên gia: {{ $rec->specialist_name }}</p>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 self-start sm:self-auto border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Đã hoàn thành
                    </span>
                </div>

                {{-- Khối tóm tắt và lời khuyên --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs sm:text-sm">
                    {{-- Ghi nhận từ chuyên viên --}}
                    <div class="rounded-xl border border-gray-200/80 bg-[#F7FAFC] p-4 space-y-1.5">
                        <span class="text-xs font-bold text-[#1D4B43] uppercase tracking-wider block">1. Nhận định từ chuyên gia</span>
                        <p class="text-[#334155] leading-relaxed">
                            {{ $rec->summary }}
                        </p>
                    </div>

                    {{-- Khuyến nghị & Bài tập thực hành --}}
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 space-y-1.5">
                        <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider block">2. Bài tập & Hướng rèn luyện</span>
                        <p class="text-[#334155] leading-relaxed">
                            {{ $rec->recommendation }}
                        </p>
                        @if(!empty($rec->homework))
                            <div class="mt-2 pt-2 border-t border-emerald-100/80">
                                <span class="font-bold text-emerald-900">Bài tập về nhà:</span>
                                <span class="text-emerald-950 ml-1">{{ $rec->homework }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm font-semibold text-[#0F172A]">Chưa có hồ sơ tư vấn hoàn thành.</p>
                <p class="text-xs text-[#4A5568] mt-1">Sau khi hoàn thành buổi tham vấn đầu tiên, tóm tắt và lời khuyên của chuyên viên sẽ hiển thị tại đây.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
