{{--
    SCR-PUB-02 — Danh sách dịch vụ tư vấn tâm lý (FR-SERVICE-001)
    ==============================================================
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-02 + §1.1 -> §1.8 + rules.md
    Quy định:
      - Hiển thị các thẻ dịch vụ ACTIVE thuộc danh mục ACTIVE, sắp xếp theo display_order.
      - Mỗi thẻ: tên, mô tả ngắn (~120 ký tự), thời lượng (phút), giá niêm yết (VND), nút Đặt lịch & Xem chi tiết.
      - Thanh tìm kiếm từ khóa + Bộ lọc danh mục (Dropdown + Pills) + Sắp xếp tiêu chí.
      - Phân trang: 20 mục/trang chuẩn mực (SCR-PUB-02).
      - Đủ 4 trạng thái theo §1.4: Đang tải (Skeleton), Rỗng (Empty), Lỗi (Error), Thành công / Hiển thị.
      - Thiết kế Soft Medical / Healing Minimalist với tông màu chuẩn Deep Teal (#1D4B43).
--}}

@extends('layouts.app')

@section('title', 'Dịch vụ Tư vấn Tâm lý — Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Dịch vụ tư vấn</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ loading: false }">

    {{-- ── 1. TIÊU ĐỀ TRANG & GIỚI THIỆU CHUNG ── --}}
    <div class="max-w-3xl">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
            <span class="h-2 w-2 rounded-full bg-[#709782] animate-pulse"></span>
            Chăm sóc sức khỏe tinh thần chuẩn mực
        </span>
        <h1 class="mt-3 text-3xl font-black tracking-tight text-[#0F172A] sm:text-4xl">
            Dịch vụ Tư vấn & Trị liệu Tâm lý
        </h1>
        <p class="mt-3 text-base leading-relaxed text-[#334155]">
            Chúng tôi cung cấp các gói tham vấn cá nhân, trị liệu chuyên sâu và đồng hành gia đình
            được thiết kế bởi các chuyên gia tâm lý đầu ngành, đảm bảo 100% tính riêng tư và bảo mật.
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
                    <strong class="font-semibold">Đã xảy ra lỗi khi tải danh sách dịch vụ:</strong>
                    <p class="mt-0.5">{{ session('error') ?? $errorMessage ?? 'Không thể kết nối đến máy chủ.' }}</p>
                </div>
            </div>
            <a href="{{ route('services.index') }}" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700 transition flex-shrink-0">
                Thử lại
            </a>
        </div>
    @endif

    {{-- ── 3. THANH CÔNG CỤ: TÌM KIẾM, BỘ LỌC DANH MỤC & SẮP XẾP ── --}}
    <div class="mt-8 rounded-2xl bg-white p-5 shadow-xs border border-gray-200/80">
        <form method="GET" action="{{ route('services.index') }}" class="space-y-4" id="servicesFilterForm" @submit="loading = true">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-center">

                {{-- Ô tìm kiếm từ khóa theo tên / mô tả --}}
                <div class="md:col-span-6 relative">
                    <label for="search-input" class="sr-only">Tìm kiếm dịch vụ</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           id="search-input"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Tìm theo tên dịch vụ, vấn đề tâm lý hoặc mô tả..."
                           class="w-full rounded-xl border border-gray-300 bg-[#F7FAFC] pl-10 pr-4 py-2.5 text-sm
                                  text-[#0F172A] placeholder-gray-500 font-medium
                                  focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 transition">
                </div>

                {{-- Dropdown lọc theo danh mục --}}
                <div class="md:col-span-3">
                    <label for="category-select" class="sr-only">Lọc theo danh mục</label>
                    <select id="category-select"
                            name="category"
                            onchange="document.getElementById('servicesFilterForm').submit()"
                            class="w-full rounded-xl border border-gray-300 bg-[#F7FAFC] px-3.5 py-2.5 text-sm
                                   text-[#0F172A] font-medium
                                   focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 transition">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $cat)
                            @if(!empty($cat->name) && $cat->name !== 'Tất cả dịch vụ')
                                @php
                                    $val = !empty($cat->slug) ? $cat->slug : ($cat->id ?? $cat->name);
                                    $isSelected = ($category == $val) || ($category == $cat->name) || (isset($cat->id) && $category == $cat->id);
                                @endphp
                                <option value="{{ $val }}" {{ $isSelected ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Dropdown tiêu chí sắp xếp --}}
                <div class="md:col-span-3 flex gap-2">
                    <label for="sort-select" class="sr-only">Sắp xếp</label>
                    <select id="sort-select"
                            name="sort"
                            onchange="document.getElementById('servicesFilterForm').submit()"
                            class="flex-1 rounded-xl border border-gray-300 bg-[#F7FAFC] px-3.5 py-2.5 text-sm
                                   text-[#0F172A] font-medium
                                   focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 transition">
                        <option value="default" {{ $sort === 'default' ? 'selected' : '' }}>Thứ tự đề xuất</option>
                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Giá: Thấp đến cao</option>
                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Giá: Cao đến thấp</option>
                        <option value="duration_desc" {{ $sort === 'duration_desc' ? 'selected' : '' }}>Thời lượng dài nhất</option>
                        <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Tên dịch vụ: A - Z</option>
                    </select>

                    <button type="submit"
                            class="rounded-xl bg-[#1D4B43] px-4 py-2.5 text-sm font-bold text-white
                                   hover:bg-[#14352F] focus:outline-none focus:ring-2 focus:ring-[#1D4B43] focus:ring-offset-1 transition shadow-xs">
                        Tìm
                    </button>
                </div>
            </div>

            {{-- Thanh Pills chọn nhanh danh mục dịch vụ --}}
            <div class="flex flex-wrap items-center gap-1.5 pt-3 border-t border-gray-100">
                <span class="text-xs font-bold text-[#4A5568] mr-2">Danh mục:</span>

                {{-- Nút xem tất cả --}}
                <a href="{{ route('services.index', array_filter(['q' => $search, 'sort' => $sort !== 'default' ? $sort : null])) }}"
                   class="rounded-full px-3 py-1 text-xs font-semibold transition
                          {{ empty($category)
                              ? 'bg-[#1D4B43] text-white shadow-xs'
                              : 'bg-gray-100 text-[#4A5568] hover:bg-[#E8F0EC] hover:text-[#1D4B43]' }}">
                    Tất cả ({{ $services->total() }})
                </a>

                @foreach($categories as $cat)
                    @if(!empty($cat->name) && $cat->name !== 'Tất cả dịch vụ')
                        @php
                            $targetVal = !empty($cat->slug) ? $cat->slug : ($cat->id ?? $cat->name);
                            $isCatActive = ($category == $targetVal) || ($category == $cat->name) || (isset($cat->id) && $category == $cat->id);
                        @endphp
                        <a href="{{ route('services.index', array_filter(['category' => $targetVal, 'q' => $search, 'sort' => $sort !== 'default' ? $sort : null])) }}"
                           class="rounded-full px-3 py-1 text-xs font-semibold transition
                                  {{ $isCatActive
                                      ? 'bg-[#1D4B43] text-white shadow-xs'
                                      : 'bg-gray-100 text-[#4A5568] hover:bg-[#E8F0EC] hover:text-[#1D4B43]' }}">
                            {{ $cat->name }}
                        </a>
                    @endif
                @endforeach

                {{-- Nút xóa bộ lọc khi đang lọc hoặc tìm kiếm --}}
                @if(!empty($search) || !empty($category) || $sort !== 'default')
                    <a href="{{ route('services.index') }}"
                       class="ml-auto inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700 hover:underline">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Xóa bộ lọc
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ── 4. THÔNG TIN BỘ ĐẾM KẾT QUẢ TÌM KIẾM ── --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm text-[#4A5568]">
        <div>
            @if(!empty($search) || !empty($category))
                <span>Tìm thấy <strong class="text-[#0F172A]">{{ $services->total() }}</strong> dịch vụ</span>
                @if(!empty($search))
                    <span>phù hợp với từ khóa "<strong class="text-[#1D4B43]">{{ $search }}</strong>"</span>
                @endif
            @else
                <span>Hiển thị tất cả <strong class="text-[#0F172A]">{{ $services->total() }}</strong> dịch vụ tư vấn tâm lý</span>
            @endif
        </div>

        @if($services->lastPage() > 1)
            <div class="text-xs font-medium text-[#4A5568]">
                Trang {{ $services->currentPage() }} / {{ $services->lastPage() }}
            </div>
        @endif
    </div>

    {{-- ── 5. KHUNG XƯƠNG LOADING SKELETON (THEO §1.4 TRẠNG THÁI ĐANG TẢI) ── --}}
    <div x-show="loading" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" x-cloak>
        @for($i = 0; $i < 6; $i++)
            <div class="animate-pulse rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <div class="h-5 w-24 rounded-full bg-gray-200 mb-4"></div>
                <div class="h-6 w-3/4 rounded bg-gray-200 mb-3"></div>
                <div class="space-y-2 mb-6">
                    <div class="h-3.5 w-full rounded bg-gray-100"></div>
                    <div class="h-3.5 w-5/6 rounded bg-gray-100"></div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="h-4 w-20 rounded bg-gray-200"></div>
                    <div class="h-8 w-24 rounded-lg bg-gray-200"></div>
                </div>
            </div>
        @endfor
    </div>

    {{-- ── 6. DANH SÁCH THẺ DỊCH VỤ HOẶC TRẠNG THÁI RỖNG ── --}}
    <div x-show="!loading">
        @if($services->isNotEmpty())
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($services as $service)
                    <x-service-card
                        :id="$service->id"
                        :name="$service->name"
                        :category="is_object($service->category) ? ($service->category->name ?? null) : ($service->category ?? null)"
                        :description="$service->description"
                        :duration="$service->duration_minutes ?? $service->duration ?? null"
                        :price="$service->price"
                        :detailRoute="route('services.show', ['service' => $service->id])"
                        :bookRoute="route('appointments.book', ['service' => $service->id])"
                    />
                @endforeach
            </div>

            {{-- Phân trang chuẩn 20 mục / trang theo đặc tả SCR-PUB-02 --}}
            @if($services->hasPages())
                <div class="mt-10 flex justify-center">
                    {{ $services->links() }}
                </div>
            @endif

        @else
            {{-- ── TRẠNG THÁI RỖNG THEO ĐẶC TẢ §1.4 & SCR-PUB-02 ── --}}
            <div class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-xs">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#E8F0EC]">
                    <svg class="h-8 w-8 text-[#1D4B43]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-bold text-[#0F172A]">
                    @if(!empty($search) || !empty($category))
                        Không tìm thấy dịch vụ phù hợp
                    @else
                        Hiện chưa có dịch vụ nào
                    @endif
                </h3>

                <p class="mt-1.5 max-w-md text-sm text-[#4A5568]">
                    @if(!empty($search) || !empty($category))
                        Vui lòng thử tìm kiếm bằng từ khóa khác hoặc điều chỉnh lại bộ lọc danh mục.
                    @else
                        Danh sách dịch vụ đang được cập nhật. Vui lòng quay lại sau hoặc liên hệ trực tiếp với phòng khám.
                    @endif
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @if(!empty($search) || !empty($category))
                        <a href="{{ route('services.index') }}"
                           class="rounded-xl bg-[#1D4B43] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                            Xem tất cả dịch vụ
                        </a>
                    @endif
                    <a href="{{ url('/') }}"
                       class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-[#0F172A] hover:bg-gray-50 transition">
                        Về trang chủ
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- ── 7. KHỐI THÔNG TIN HỖ TRỢ & ĐỊNH HƯỚNG DỊCH VỤ ── --}}
    <div class="mt-16 rounded-2xl bg-[#E8F0EC]/60 border border-[#709782]/20 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h3 class="text-base font-bold text-[#1D4B43]">
                    Bạn chưa biết gói dịch vụ nào phù hợp với tình trạng hiện tại?
                </h3>
                <p class="text-sm text-[#334155]">
                    Đội ngũ điều phối viên Mind Guide luôn sẵn sàng lắng nghe và định hướng gói tư vấn chính xác nhất cho bạn.
                </p>
            </div>
            <a href="tel:19001567"
               class="inline-flex items-center gap-2 rounded-xl bg-[#1D4B43] px-6 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs flex-shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Gọi hỗ trợ 1900 1567
            </a>
        </div>
    </div>

</div>
@endsection
