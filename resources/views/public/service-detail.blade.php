{{--
    SCR-PUB-03 — Chi tiết dịch vụ tư vấn tâm lý (FR-SERVICE-001)
    ==============================================================
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-03 + rules.md §3
    Quy định:
      - Hiển thị: tên, danh mục, mô tả đầy đủ, thời lượng, giá niêm yết (VND).
      - Danh sách chuyên viên cung cấp dịch vụ (thẻ liên kết).
      - Nút CTA "Đặt lịch dịch vụ này" (mang sẵn service_id sang SCR-CUS-03).
      - Trạng thái lỗi 404 nếu dịch vụ không tồn tại hoặc INACTIVE.
      - Thiết kế Soft Medical / Healing Minimalist (Deep Teal #2D82C4, Sage Green #6FA6CF).
--}}

@extends('layouts.app')

@section('title', $service->name . ' — Dịch vụ Tư vấn Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li>
            <a href="{{ route('services.index') }}" class="hover:text-[#2D82C4] hover:underline transition">Dịch vụ tư vấn</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C] truncate max-w-[200px] sm:max-w-md" aria-current="page">
            {{ $service->name }}
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- ── 1. KHUNG HEADER CHI TIẾT DỊCH VỤ & KHỐI ĐẶT LỊCH NHANH ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- CỘT TRÁI (8/12): THÔNG TIN DỊCH VỤ CHUYÊN SÂU --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- Thông tin cơ bản --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                @php
                    $serviceImage = $service->image_url ?? [
                        1 => 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1400&q=85',
                        2 => 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?auto=format&fit=crop&w=1400&q=85',
                        3 => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1400&q=85',
                        4 => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=85',
                        5 => 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1400&q=85',
                        6 => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1400&q=85',
                    ][$service->id] ?? 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1400&q=85';
                @endphp
                <img src="{{ $serviceImage }}"
                     alt="Không gian và hoạt động {{ e($service->name) }}"
                     width="1400"
                     height="560"
                     onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'"
                     class="mb-6 h-56 w-full rounded-xl object-cover sm:h-72">
                @php
                    $catName = is_object($service->category ?? null) ? ($service->category->name ?? 'Tư vấn tâm lý') : ($service->category ?? 'Tư vấn tâm lý');
                @endphp
                <span class="inline-block rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4] mb-3">
                    {{ $catName }}
                </span>

                <h1 class="text-2xl font-black text-[#0F172A] sm:text-4xl leading-tight">
                    {{ $service->name }}
                </h1>

                {{-- Thông số thời lượng & Giá --}}
                <div class="mt-6 flex flex-wrap items-center gap-6 py-4 border-y border-gray-100 text-sm">
                    <div class="flex items-center gap-2 text-[#4A5568]">
                        <div class="h-9 w-9 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Thời lượng buổi tư vấn:</span>
                            <strong class="text-sm font-bold text-[#0F172A]">{{ $service->duration_minutes ?? $service->duration ?? 60 }} phút</strong>
                        </div>
                    </div>

                    @if(!empty($service->price))
                    <div class="flex items-center gap-2 text-[#4A5568]">
                        <div class="h-9 w-9 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Chi phí niêm yết:</span>
                            <strong class="text-base font-black text-[#2D82C4]">{{ number_format($service->price, 0, ',', '.') }} đ</strong>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center gap-2 text-[#4A5568]">
                        <div class="h-9 w-9 rounded-xl bg-[#EAF4FB] flex items-center justify-center text-[#2D82C4]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-[#4A5568] block">Tính bảo mật:</span>
                            <strong class="text-sm font-bold text-emerald-800">Riêng tư 100%</strong>
                        </div>
                    </div>
                </div>

                {{-- Mô tả đầy đủ --}}
                <div class="mt-6 prose prose-slate max-w-none text-sm sm:text-base leading-relaxed text-[#334155]">
                    @if($service->introduction)
                        <h3 class="text-lg font-bold text-[#0F172A] mb-2">Giới thiệu về gói dịch vụ</h3>
                        <p class="whitespace-pre-line">{{ $service->introduction }}</p>
                    @else
                        <h3 class="text-lg font-bold text-[#0F172A] mb-2">Giới thiệu về gói dịch vụ</h3>
                        <p class="whitespace-pre-line">{{ $service->description }}</p>
                    @endif

                    @if($service->target_audience)
                        <h3 class="mt-6 text-lg font-bold text-[#0F172A] mb-2">Gói này phù hợp với</h3>
                        <p class="whitespace-pre-line">{{ $service->target_audience }}</p>
                    @endif
                </div>

                {{-- Dấu hiệu nhận biết --}}
                @if($service->symptoms && count($service->symptoms) > 0)
                    <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50/60 p-6">
                        <h3 class="flex items-center gap-2 text-base font-bold text-amber-900">
                            <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Dấu hiệu nhận biết
                        </h3>
                        <ul class="mt-3 grid gap-2 sm:grid-cols-2 text-sm text-amber-900">
                            @foreach($service->symptoms as $symptom)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>
                                    <span>{{ $symptom }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Cam kết / lợi ích mang lại --}}
                @if($service->benefits && count($service->benefits) > 0)
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-6">
                        <h3 class="flex items-center gap-2 text-base font-bold text-emerald-900">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Cam kết khi đồng hành cùng Mind Guide
                        </h3>
                        <ul class="mt-3 space-y-2 text-sm text-emerald-900">
                            @foreach($service->benefits as $benefit)
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>{{ $benefit }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- QUY TRÌNH ĐIỀU TRỊ THEO TỪNG BUỔI --}}
            @if($service->session_process)
                <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                    <h2 class="text-lg font-bold text-[#0F172A] mb-4">Lộ trình đồng hành theo từng buổi</h2>
                    <div class="space-y-4 text-sm sm:text-base leading-relaxed text-[#334155]">
                        @foreach(explode("\n\n", $service->session_process) as $step)
                            <div class="flex gap-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#2D82C4]" aria-hidden="true"></span>
                                <p class="whitespace-pre-line">{{ trim($step) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quy trình làm việc chuẩn trong buổi tư vấn --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                <h2 class="text-lg font-bold text-[#0F172A] mb-4">Quy trình làm việc chuẩn trong buổi tư vấn</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-gray-200 bg-[#F5FAFE] p-4 text-center">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#2D82C4] text-white font-bold text-sm mb-2">1</span>
                        <h4 class="font-bold text-[#0F172A] text-sm">Lắng nghe & Khám phá</h4>
                        <p class="text-xs text-[#4A5568] mt-1">Chuyên gia lắng nghe câu chuyện của bạn trong không gian ấm áp, tôn trọng và không phán xét.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-[#F5FAFE] p-4 text-center">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#2D82C4] text-white font-bold text-sm mb-2">2</span>
                        <h4 class="font-bold text-[#0F172A] text-sm">Phân tích & Nhận diện</h4>
                        <p class="text-xs text-[#4A5568] mt-1">Cùng tìm ra căn nguyên gốc rễ của lo âu, bế tắc hoặc rạn nứt trong các mối quan hệ.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-[#F5FAFE] p-4 text-center">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#2D82C4] text-white font-bold text-sm mb-2">3</span>
                        <h4 class="font-bold text-[#0F172A] text-sm">Kế hoạch can thiệp</h4>
                        <p class="text-xs text-[#4A5568] mt-1">Thống nhất mục tiêu, thực hành kỹ năng phù hợp và xây dựng lộ trình đồng hành tiếp theo.</p>
                    </div>
                </div>
            </div>

            {{-- DANH SÁCH CHUYÊN VIÊN PHỤ TRÁCH DỊCH VỤ NÀY --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-[#0F172A]">Chuyên gia phụ trách dịch vụ này</h2>
                        <p class="text-xs text-[#4A5568] mt-0.5">Các chuyên viên có năng lực và được đào tạo bài bản cho gói tư vấn này.</p>
                    </div>
                    <a href="{{ route('specialists.index', ['service' => $service->id]) }}" class="text-xs font-bold text-[#2D82C4] hover:underline">
                        Xem tất cả chuyên gia →
                    </a>
                </div>

                @if($specialists->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($specialists as $spc)
                            <div class="flex items-start gap-3.5 p-4 rounded-xl border border-gray-200 bg-[#F5FAFE] hover:border-[#6FA6CF] transition">
                                <img src="{{ $spc->avatar_url ?? asset('images/default-avatar.svg') }}"
                                     alt="Ảnh chuyên gia {{ e($spc->name) }}"
                                     width="96"
                                     height="96"
                                     loading="lazy"
                                     onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'"
                                     class="h-12 w-12 rounded-full object-cover ring-2 ring-[#2D82C4]/20 flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-[#0F172A] truncate">
                                        <a href="{{ route('specialists.show', ['specialist' => $spc->id]) }}" class="hover:text-[#2D82C4]">
                                            {{ $spc->name }}
                                        </a>
                                    </h4>
                                    <p class="text-xs text-[#4A5568] mt-0.5 truncate">{{ $spc->title }}</p>
                                    <div class="mt-2 flex items-center justify-between text-xs">
                                        <span class="text-amber-600 font-semibold">★ {{ number_format($spc->average_rating ?? 5.0, 1) }}</span>
                                        <a href="{{ route('appointments.book', ['service' => $service->id, 'specialist' => $spc->id]) }}"
                                           class="font-bold text-[#2D82C4] hover:underline bg-[#EAF4FB] px-2.5 py-1 rounded-md">
                                            Đặt lịch với chuyên gia
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[#4A5568] italic py-4">Hiện danh sách chuyên viên đang được cập nhật thêm.</p>
                @endif
            </div>

        </div>

        {{-- CỘT PHẢI (4/12): THẺ ĐẶT LỊCH NGAY & CHÍNH SÁCH BẢO VỆ THÂN CHỦ (STICKY) --}}
        <div class="lg:col-span-4 sticky top-20 space-y-6">

            {{-- Thẻ Call-To-Action Đặt lịch --}}
            <div class="rounded-2xl border-2 border-[#2D82C4] bg-white p-6 shadow-md text-center">
                <span class="text-xs font-bold uppercase tracking-wider text-[#6FA6CF] block">Bắt đầu hành trình chăm sóc tâm trí</span>
                
                <h3 class="mt-2 text-xl font-bold text-[#0F172A] leading-snug">
                    {{ $service->name }}
                </h3>

                @if(!empty($service->price))
                <div class="mt-4 py-3 bg-[#F5FAFE] rounded-xl border border-gray-100">
                    <span class="text-xs text-[#4A5568] block">Mức giá trọn gói buổi hẹn:</span>
                    <span class="text-3xl font-black text-[#2D82C4] block mt-0.5">
                        {{ number_format($service->price, 0, ',', '.') }} đ
                    </span>
                    <span class="text-[11px] text-[#4A5568] block mt-0.5">({{ $service->duration_minutes ?? 60 }} phút tham vấn riêng tư)</span>
                </div>
                @endif

                {{-- NÚT CTA CHÍNH THEO SPEC SCR-PUB-03: MANG SẴN service_id SANG SCR-CUS-03 --}}
                <div class="mt-6">
                    <a href="{{ route('appointments.book', ['service' => $service->id]) }}"
                       class="block w-full rounded-xl bg-[#2D82C4] px-6 py-3.5 text-center text-base font-bold text-white
                              hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2
                              transition shadow-sm hover:-translate-y-0.5">
                        Đặt lịch dịch vụ này ngay
                    </a>
                </div>

                <p class="mt-3 text-xs text-[#4A5568]">
                    Hỗ trợ hủy hoặc đổi lịch dễ dàng trước 24 giờ.
                </p>
            </div>

            {{-- Khối thông tin cam kết & chính sách hoàn tiền --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 text-xs text-[#334155] space-y-3 shadow-xs">
                <h4 class="font-bold text-sm text-[#0F172A] flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Quyền lợi của bạn tại Mind Guide
                </h4>
                <ul class="space-y-2 leading-relaxed text-[#4A5568]">
                    <li>• <strong>Bảo mật 100%:</strong> Toàn bộ câu chuyện được giữ kín tuyệt đối theo tiêu chuẩn đạo đức nghề nghiệp tâm lý.</li>
                    <li>• <strong>Linh hoạt thời gian:</strong> Dễ dàng chọn khung giờ sáng, chiều hoặc tối theo lịch cá nhân.</li>
                    <li>• <strong>Hoàn tiền minh bạch:</strong> Hoàn 100% nếu bạn hủy trước giờ hẹn từ 24h trở lên.</li>
                </ul>
            </div>

        </div>

    </div>

    {{-- ── 2. DỊCH VỤ LIÊN QUAN CÙNG DANH MỤC ── --}}
    @if(isset($relatedServices) && $relatedServices->isNotEmpty())
        <div class="mt-16 pt-12 border-t border-gray-200">
            <h2 class="text-2xl font-bold text-[#0F172A] mb-6">Dịch vụ tư vấn cùng danh mục</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($relatedServices as $rel)
                    <x-service-card
                        :id="$rel->id"
                        :name="$rel->name"
                        :category="is_object($rel->category) ? ($rel->category->name ?? null) : ($rel->category ?? null)"
                        :description="$rel->description"
                        :duration="$rel->duration_minutes ?? $rel->duration ?? null"
                        :price="$rel->price"
                        :image="$rel->image_url"
                        :detailRoute="route('services.show', ['service' => $rel->id])"
                        :bookRoute="route('appointments.book', ['service' => $rel->id])"
                    />
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
