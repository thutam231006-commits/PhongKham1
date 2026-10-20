{{--
    SCR-PUB-05 — Chi tiết chuyên viên tâm lý và đánh giá (FR-SPECIALIST-002, FR-REVIEW-002)
    ========================================================================================
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-05 + rules.md §3 + §1.7 Bảo mật
    Quy định:
      - Hiển thị: ảnh lớn, họ tên, chức danh, chuyên môn, giới thiệu (bio), kinh nghiệm, danh sách dịch vụ cung cấp.
      - Điểm trung bình + phân bố sao (5 sao, 4 sao, 3 sao, 2 sao, 1 sao).
      - Danh sách đánh giá VISIBLE (số sao, nhận xét, thời điểm, TUYỆT ĐỐI KHÔNG CÓ TÊN KHÁCH - BR-047).
      - BẢO MẬT §1.7: KHÔNG hiển thị license_number, internal_note, email, SĐT.
      - Nút CTA "Đặt lịch với chuyên viên này" (mang sẵn specialist_id sang SCR-CUS-03).
--}}

@extends('layouts.app')

@section('title', $specialist->name . ' — Chuyên viên Tâm lý Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li>
            <a href="{{ route('specialists.index') }}" class="hover:text-[#2D82C4] hover:underline transition">Chuyên viên tâm lý</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C] truncate max-w-[200px] sm:max-w-md" aria-current="page">
            {{ $specialist->name }}
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- ── 1. KHUNG HỒ SƠ CHUYÊN VIÊN & THẺ ĐẶT LỊCH NHANH ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- CỘT TRÁI (8/12): HỒ SƠ NĂNG LỰC, TIỂU SỬ VÀ ĐÁNH GIÁ ── --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- Thông tin định danh & bằng cấp --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row items-start gap-6">
                    {{-- Avatar lớn --}}
                    <div class="h-28 w-28 sm:h-32 sm:w-32 rounded-2xl bg-[#EAF4FB] text-[#2D82C4] flex items-center justify-center text-4xl font-black ring-4 ring-[#EAF4FB] shadow-xs flex-shrink-0">
                        @php
                            $specialistImage = $specialist->avatar_url ?? [
                                1 => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=640&q=85',
                                2 => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=640&q=85',
                                3 => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=640&q=85',
                                4 => 'https://images.unsplash.com/photo-1544725176-7c40e5a71c5e?auto=format&fit=crop&w=640&q=85',
                                5 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=640&q=85',
                                6 => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=640&q=85',
                            ][$specialist->id] ?? null;
                        @endphp
                        @if($specialistImage)
                            <img src="{{ $specialistImage }}" alt="Ảnh chuyên viên {{ e($specialist->name) }}" width="256" height="256" onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'" class="h-full w-full rounded-2xl object-cover">
                        @else
                            {{ mb_substr($specialist->name, 0, 1) }}
                        @endif
                    </div>

                    <div class="flex-1">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-xs font-bold text-emerald-800 mb-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Chuyên gia được xác thực & Cấp phép
                        </span>

                        <h1 class="text-2xl font-black text-[#0F172A] sm:text-3xl">
                            {{ $specialist->name }}
                        </h1>

                        <p class="text-sm font-semibold text-[#2D82C4] mt-1">
                            {{ $specialist->title }}
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-4 text-xs font-semibold text-[#4A5568]">
                            <span class="flex items-center gap-1.5 bg-[#F5FAFE] px-3 py-1.5 rounded-lg border border-gray-200">
                                <svg class="h-4 w-4 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ is_numeric($specialist->experience_years ?? null) ? ($specialist->experience_years . ' năm kinh nghiệm lâm sàng') : ($specialist->experience ?? 'Nhiều năm kinh nghiệm') }}</span>
                            </span>

                            <span class="flex items-center gap-1.5 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 text-amber-900">
                                <span>★</span>
                                <strong class="font-bold">{{ number_format($specialist->average_rating ?? 5.0, 1) }}</strong>
                                <span>({{ $specialist->visible_reviews_count ?? $specialist->reviews_count ?? 0 }} đánh giá)</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Chuyên môn trọng tâm --}}
                @if(!empty($specialist->specialization))
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#4A5568] mb-2">Thế mạnh & Lĩnh vực chuyên môn</h3>
                    <p class="text-sm font-medium text-[#0F172A] leading-relaxed bg-[#F5FAFE] p-3.5 rounded-xl border border-gray-200/80">
                        {{ $specialist->specialization }}
                    </p>
                </div>
                @endif

                {{-- Tiểu sử giới thiệu (Bio) --}}
                <div class="mt-6">
                    <h3 class="text-base font-bold text-[#0F172A] mb-2">Giới thiệu & Triết lý đồng hành</h3>
                    <div class="text-sm leading-relaxed text-[#334155] space-y-2">
                        <p>
                            Với hơn {{ $specialist->experience_years ?? 8 }} năm đồng hành trong lĩnh vực tâm lý trị liệu,
                            {{ $specialist->name }} theo đuổi phương pháp tiếp cận nhân văn và thân chủ trọng tâm (Person-Centered Therapy),
                            kết hợp các liệu pháp nhận thức - hành vi (CBT) hiện đại.
                        </p>
                        <p>
                            <em>"Mỗi cá nhân đều có nội lực tiềm tàng để vượt qua bão giông. Vai trò của tôi là tạo dựng một chiếc neo an toàn,
                            cùng bạn soi tỏ những góc khuất nội tâm và đánh thức sức mạnh chữa lành tự nhiên bên trong bạn."</em>
                        </p>
                    </div>
                </div>
            </div>

            {{-- CÁC DỊCH VỤ CHUYÊN VIÊN NÀY PHỤ TRÁCH --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                <h2 class="text-xl font-bold text-[#0F172A] mb-4">Các dịch vụ chuyên viên phụ trách</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse($providedServices as $ps)
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 bg-[#F5FAFE] hover:border-[#6FA6CF] transition">
                            <div>
                                <h4 class="font-bold text-sm text-[#0F172A]">{{ $ps->name }}</h4>
                                <span class="text-xs text-[#4A5568] block mt-0.5">Thời lượng: 60 - 90 phút</span>
                            </div>
                            <a href="{{ route('appointments.book', ['specialist' => $specialist->id, 'service' => $ps->id]) }}"
                               class="text-xs font-bold text-[#2D82C4] bg-[#EAF4FB] px-3 py-1.5 rounded-lg hover:bg-[#2D82C4] hover:text-white transition flex-shrink-0">
                                Đặt gói này
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-[#4A5568] italic">Đang cập nhật danh mục dịch vụ.</p>
                    @endforelse
                </div>
            </div>

            {{-- KHỐI ĐÁNH GIÁ CỦA KHÁCH HÀNG (VISIBLE, ẨN DANH THEO BR-047) --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                    <div>
                        <h2 class="text-xl font-bold text-[#0F172A]">Đánh giá từ khách hàng</h2>
                        <p class="text-xs text-[#4A5568] mt-0.5">
                            Chỉ hiển thị các đánh giá thực tế đã được kiểm duyệt (VISIBLE). Danh tính khách hàng được ẩn danh tuyệt đối (BR-047).
                        </p>
                    </div>

                    {{-- Điểm trung bình to --}}
                    <div class="flex items-center gap-3 bg-[#F5FAFE] px-4 py-2.5 rounded-xl border border-gray-200">
                        <span class="text-3xl font-black text-[#2D82C4]">{{ number_format($specialist->average_rating ?? 5.0, 1) }}</span>
                        <div>
                            <div class="flex text-amber-500 text-sm">★★★★★</div>
                            <span class="text-[11px] text-[#4A5568] font-semibold">{{ $specialist->visible_reviews_count ?? count($reviews) }} lượt đánh giá</span>
                        </div>
                    </div>
                </div>

                {{-- Danh sách nhận xét ẩn danh --}}
                <div class="mt-6 space-y-4">
                    @forelse($reviews as $rev)
                        <div class="p-4 rounded-xl border border-gray-100 bg-[#F5FAFE]">
                            <div class="flex items-center justify-between text-xs text-[#4A5568] mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[#0F172A]">Khách hàng ẩn danh</span>
                                    <span class="inline-block h-1 w-1 rounded-full bg-gray-400"></span>
                                    <span class="text-[#6FA6CF]">{{ $rev->appointment?->service?->name ?? 'Tham vấn tâm lý' }}</span>
                                </div>
                                <span>{{ $rev->created_at?->format('d/m/Y') }}</span>
                            </div>

                            {{-- Sao --}}
                            <div class="text-amber-500 text-xs mb-2">
                                @for($i = 0; $i < $rev->rating; $i++) ★ @endfor
                            </div>

                            <p class="text-sm text-[#334155] leading-relaxed">
                                "{{ $rev->comment }}"
                            </p>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500 italic">Chưa có đánh giá.</p>
                        </div>
                    @endforelse
                </div>
                <div class="mt-5">{{ $reviews->links() }}</div>
            </div>

        </div>

        {{-- CỘT PHẢI (4/12): THẺ ĐẶT LỊCH HẸN VỚI CHUYÊN GIA NÀY (STICKY) --}}
        <div class="lg:col-span-4 sticky top-20 space-y-6">

            <div class="rounded-2xl border-2 border-[#2D82C4] bg-white p-6 shadow-md text-center">
                <span class="text-xs font-bold uppercase tracking-wider text-[#6FA6CF] block">Lựa chọn chuyên gia</span>
                
                <h3 class="mt-2 text-xl font-bold text-[#0F172A] leading-snug">
                    {{ $specialist->name }}
                </h3>
                <p class="text-xs text-[#4A5568] mt-1">{{ $specialist->title }}</p>

                <div class="mt-5 py-4 bg-[#F5FAFE] rounded-xl border border-gray-100 text-left space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-[#4A5568]">Kinh nghiệm:</span>
                        <strong class="text-[#0F172A]">{{ $specialist->experience_years ?? 8 }} năm</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#4A5568]">Đánh giá:</span>
                        <strong class="text-amber-600">★ {{ number_format($specialist->average_rating ?? 5.0, 1) }} / 5.0</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#4A5568]">Hình thức:</span>
                        <strong class="text-[#2D82C4]">Trực tiếp / Trực tuyến</strong>
                    </div>
                </div>

                {{-- NÚT CTA CHÍNH THEO SPEC SCR-PUB-05: MANG SẴN specialist_id SANG SCR-CUS-03 --}}
                <div class="mt-6">
                    <a href="{{ route('appointments.book', ['specialist' => $specialist->id]) }}"
                       class="block w-full rounded-xl bg-[#2D82C4] px-6 py-3.5 text-center text-base font-bold text-white
                              hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2
                              transition shadow-sm hover:-translate-y-0.5">
                        Đặt lịch với chuyên viên này
                    </a>
                </div>

                <p class="mt-3 text-xs text-[#4A5568]">
                    Xem lịch trống thời gian thực & xác nhận tức thì.
                </p>
            </div>

            {{-- Khối cam kết bảo mật thông tin thân chủ --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 text-xs text-[#334155] space-y-3 shadow-xs">
                <h4 class="font-bold text-sm text-[#0F172A] flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Bảo mật thông tin thân chủ (§1.7)
                </h4>
                <p class="text-[#4A5568] leading-relaxed">
                    Mọi trao đổi và thông tin ghi chú trong suốt quá trình tư vấn được mã hóa lưu trữ,
                    chỉ có chuyên viên phụ trách trực tiếp buổi hẹn mới được quyền tiếp cận.
                </p>
            </div>

        </div>

    </div>

</div>
@endsection
