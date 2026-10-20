{{--
    SCR-PUB-04 — Danh sách chuyên viên tâm lý (FR-SPECIALIST-002)
    ==============================================================
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-04 + §1.1 -> §1.8 + rules.md
    Quy định:
      - Thẻ chuyên viên: ảnh (fallback default avatar), họ tên, chức danh, chuyên môn, số năm kinh nghiệm.
      - Điểm đánh giá trung bình (chỉ tính đánh giá VISIBLE) kèm số lượt.
      - Bộ lọc theo dịch vụ tư vấn (Dropdown + Quick tags).
      - BẢO MẬT TUYỆT ĐỐI §1.7: KHÔNG hiển thị license_number, internal_note, email, SĐT chuyên viên.
      - Đủ 4 trạng thái theo §1.4: Đang tải (Skeleton), Rỗng ("Chưa có chuyên viên phù hợp."), Lỗi, Hiển thị.
      - Giao diện Soft Medical / Healing Minimalist với tông màu chuẩn Deep Teal (#2D82C4).
--}}

@extends('layouts.app')

@section('title', 'Đội ngũ Chuyên viên Tâm lý | Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Chuyên viên tâm lý</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ loading: false }">

    {{-- ── 1. TIÊU ĐỀ TRANG & GIỚI THIỆU ── --}}
    <div class="mg-reveal max-w-3xl">
        <p class="mg-section-kicker">Chuyên gia giàu y đức và kinh nghiệm lâm sàng</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-[#0F172A] sm:text-4xl">
            Đội ngũ Chuyên viên Tâm lý
        </h1>
        <p class="mt-3 text-base leading-relaxed text-[#334155]">
            Tất cả chuyên viên tại Mind Guide đều có bằng cấp chuyên ngành tâm lý học, được đào tạo bài bản
            về đạo đức nghề nghiệp và cam kết bảo mật 100% nội dung buổi tham vấn.
        </p>
    </div>

    {{-- ── 2. TRẠNG THÁI LỖI (NẾU CÓ THEO §1.4) ── --}}
    @if(session('error') || isset($errorMessage))
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 flex items-start justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-2.5">
                <svg class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <strong class="font-semibold">Đã xảy ra lỗi khi tải danh sách chuyên viên:</strong>
                    <p class="mt-0.5">{{ session('error') ?? $errorMessage ?? 'Không thể kết nối đến máy chủ.' }}</p>
                </div>
            </div>
            <a href="{{ route('specialists.index') }}" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700 transition flex-shrink-0">
                Thử lại
            </a>
        </div>
    @endif

    {{-- ── 3. BỘ LỌC CHUYÊN VIÊN THEO DỊCH VỤ CUNG CẤP ── --}}
    <div class="mt-8 rounded-2xl bg-white p-5 shadow-xs border border-gray-200/80">
        <form method="GET" action="{{ route('specialists.index') }}" class="space-y-4" id="specialistFilterForm" @submit="loading = true">
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <div class="w-full sm:flex-1">
                    <label for="service-filter" class="block text-xs font-bold text-[#4A5568] mb-1.5">
                        Lọc chuyên viên theo dịch vụ phụ trách:
                    </label>
                    <div class="relative">
                        <select id="service-filter"
                                name="service"
                                onchange="document.getElementById('specialistFilterForm').submit()"
                                class="w-full rounded-xl border border-gray-300 bg-[#F5FAFE] px-3.5 py-2.5 text-sm font-medium text-[#0F172A]
                                       focus:border-[#2D82C4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20 transition">
                            <option value="">Tất cả dịch vụ tư vấn</option>
                            @foreach($services as $s)
                                <option value="{{ $s->id }}" {{ $serviceFilter == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 w-full sm:w-auto sm:self-end">
                    <button type="submit"
                            class="flex-1 sm:flex-none rounded-xl bg-[#2D82C4] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                        Lọc chuyên viên
                    </button>
                    @if(!empty($serviceFilter))
                        <a href="{{ route('specialists.index') }}"
                           class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Xóa lọc
                        </a>
                    @endif
                </div>
            </div>

            {{-- Danh mục dịch vụ chọn nhanh --}}
            <div class="flex flex-wrap items-center gap-1.5 pt-3 border-t border-gray-100">
                <span class="text-xs font-bold text-[#4A5568] mr-2">Dịch vụ nổi bật:</span>

                <a href="{{ route('specialists.index') }}"
                   class="rounded-full px-3 py-1 text-xs font-semibold transition
                          {{ empty($serviceFilter)
                              ? 'bg-[#2D82C4] text-white shadow-xs'
                              : 'bg-gray-100 text-[#4A5568] hover:bg-[#EAF4FB] hover:text-[#2D82C4]' }}">
                    Tất cả chuyên viên
                </a>

                @foreach($services->take(5) as $s)
                    <a href="{{ route('specialists.index', ['service' => $s->id]) }}"
                       class="rounded-full px-3 py-1 text-xs font-semibold transition
                              {{ $serviceFilter == $s->id
                                  ? 'bg-[#2D82C4] text-white shadow-xs'
                                  : 'bg-gray-100 text-[#4A5568] hover:bg-[#EAF4FB] hover:text-[#2D82C4]' }}">
                        {{ $s->name }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    {{-- ── 4. SỐ LƯỢNG CHUYÊN VIÊN TÌM THẤY ── --}}
    @php
        $totalSpecialists = method_exists($specialists, 'total') ? $specialists->total() : $specialists->count();
    @endphp
    <div class="mt-6 flex items-center justify-between text-sm text-[#4A5568]">
        <div>
            @if(!empty($serviceFilter))
                <span>Tìm thấy <strong class="text-[#0F172A]">{{ $totalSpecialists }}</strong> chuyên viên thuộc dịch vụ đã chọn</span>
            @else
                <span>Hiển thị tất cả <strong class="text-[#0F172A]">{{ $totalSpecialists }}</strong> chuyên viên tâm lý giàu kinh nghiệm</span>
            @endif
        </div>
    </div>

    {{-- ── 5. KHUNG XƯƠNG LOADING SKELETON (THEO §1.4) ── --}}
    <div x-show="loading" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" x-cloak>
        @for($i = 0; $i < 3; $i++)
            <div class="animate-pulse rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <div class="flex items-center gap-4 mb-4">
                    <div class="h-16 w-16 rounded-full bg-gray-200 flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-5 w-3/4 rounded bg-gray-200"></div>
                        <div class="h-3.5 w-1/2 rounded bg-gray-100"></div>
                    </div>
                </div>
                <div class="space-y-2 mb-4">
                    <div class="h-3.5 w-full rounded bg-gray-100"></div>
                    <div class="h-3.5 w-4/5 rounded bg-gray-100"></div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="h-4 w-24 rounded bg-gray-200"></div>
                    <div class="h-8 w-24 rounded-lg bg-gray-200"></div>
                </div>
            </div>
        @endfor
    </div>

    {{-- ── 6. LƯỚI CHUYÊN VIÊN HOẶC TRẠNG THÁI RỖNG ── --}}
    <div x-show="!loading">
        @if($specialists->isNotEmpty())
            <div class="mg-stagger mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($specialists as $specialist)
                    <x-specialist-card
                        style="--mg-index: {{ $loop->index }}"
                        :id="$specialist->id"
                        :name="$specialist->name"
                        :title="$specialist->title"
                        :specialization="$specialist->specialization"
                        :experience="$specialist->experience_years ?? $specialist->experience ?? null"
                        :rating="$specialist->average_rating ?? $specialist->rating ?? null"
                        :reviewCount="$specialist->visible_reviews_count ?? $specialist->reviews_count ?? 0"
                        :avatarUrl="$specialist->avatar_url"
                        :detailRoute="route('specialists.show', ['specialist' => $specialist->id])"
                        :bookRoute="route('appointments.book', ['specialist' => $specialist->id])"
                    />
                @endforeach
            </div>

            {{-- Phân trang nếu controller hỗ trợ paginator --}}
            @if(method_exists($specialists, 'hasPages') && $specialists->hasPages())
                <div class="mt-10 flex justify-center">
                    {{ $specialists->links() }}
                </div>
            @endif

        @else
            {{-- ── TRẠNG THÁI RỖNG THEO ĐẶC TẢ §1.4 & SCR-PUB-04 ── --}}
            <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-xs">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#EAF4FB]">
                    <svg class="h-8 w-8 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-bold text-[#0F172A]">Chưa có chuyên viên phù hợp</h3>
                <p class="mt-1.5 max-w-md text-sm text-[#4A5568]">
                    Hiện tại chưa có chuyên viên nào phù hợp với bộ lọc bạn đã chọn. Vui lòng chọn dịch vụ khác hoặc xem toàn bộ danh sách chuyên gia của chúng tôi.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('specialists.index') }}"
                       class="rounded-xl bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                        Xem tất cả chuyên viên
                    </a>
                    <a href="{{ url('/') }}"
                       class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-[#0F172A] hover:bg-gray-50 transition">
                        Về trang chủ
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- ── 7. KHỐI THÔNG TIN HỖ TRỢ & KẾT NỐI CHUYÊN GIA ── --}}
    <div class="mt-16 rounded-2xl bg-[#EAF4FB]/60 border border-[#6FA6CF]/20 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h3 class="text-base font-bold text-[#2D82C4]">
                    Cần hỗ trợ lựa chọn chuyên viên phù hợp?
                </h3>
                <p class="text-sm text-[#334155]">
                    Chúng tôi sẽ kết nối bạn với chuyên viên có thế mạnh chuyên môn tương thích nhất với nguyện vọng của bạn.
                </p>
            </div>
            <a href="tel:19001567"
               class="inline-flex items-center gap-2 rounded-xl bg-[#2D82C4] px-6 py-3 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs flex-shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Tư vấn nhanh 1900 1567
            </a>
        </div>
    </div>

</div>
@endsection
