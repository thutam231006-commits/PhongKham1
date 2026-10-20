{{--
    x-service-card — Thẻ hiển thị dịch vụ tư vấn
    ------------------------------------------------
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-02, §SCR-PUB-03, §SCR-PUB-01
    NFR-MNT-002: không hard-code dữ liệu dịch vụ.
    NFR-USA-001: giao diện tiếng Việt.

    Props:
      @type  string       $id           — service ID (dùng cho route đặt lịch)
      @type  string       $name         — Tên dịch vụ (escape tự động)
      @type  string|null  $category     — Tên danh mục (tùy chọn)
      @type  string|null  $description  — Mô tả ngắn (cắt ≤120 ký tự theo spec)
      @type  int|null     $duration     — Thời lượng (phút)
      @type  int|null     $price        — Giá dịch vụ (VND); null = ẩn dòng giá
      @type  string       $bookRoute    — Route đặt lịch (mặc định: appointments.book)
      @type  string       $detailRoute  — Route xem chi tiết
      @type  bool         $showDetail   — Có hiển thị nút "Xem chi tiết" không
      @type  bool         $featured     — Dịch vụ nổi bật (viền primary)

    Ví dụ:
      <x-service-card
          :id="$service->id"
          :name="$service->name"
          :category="$service->category->name"
          :description="$service->description"
          :duration="$service->duration_minutes"
          :price="$service->price"
          :detailRoute="route('services.show', $service)"
          :bookRoute="route('appointments.book', ['service' => $service->id])"
      />
--}}

@props([
    'id'          => null,
    'name'        => '',
    'category'    => null,
    'description' => null,
    'duration'    => null,
    'price'       => null,      {{-- null = dịch vụ không niêm yết giá --}}
    'bookRoute'   => '#',
    'detailRoute' => '#',
    'showDetail'  => true,
    'featured'    => false,
])

@php
    $serviceImages = [
        1 => 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1200&q=85',
        2 => 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?auto=format&fit=crop&w=1200&q=85',
        3 => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=85',
        4 => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85',
        5 => 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1200&q=85',
        6 => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=85',
    ];
    $serviceImage = $serviceImages[(int) $id] ?? $serviceImages[1];

    // Cắt mô tả ≤120 ký tự theo spec SCR-PUB-02
    $shortDesc = $description
        ? (mb_strlen($description) > 120
            ? mb_substr($description, 0, 117) . '…'
            : $description)
        : null;

    // Định dạng tiền VND: dấu chấm ngăn nghìn + hậu tố "đ" (mục 1.1)
    $priceFormatted = ($price !== null)
        ? number_format($price, 0, ',', '.') . ' đ'
        : null;

    // Định dạng thời lượng
    $durationLabel = null;
    if ($duration) {
        if ($duration >= 60) {
            $h  = intdiv($duration, 60);
            $m  = $duration % 60;
            $durationLabel = $m > 0 ? "{$h} giờ {$m} phút" : "{$h} giờ";
        } else {
            $durationLabel = "{$duration} phút";
        }
    }

    $borderClass = $featured
        ? 'border-[#2D82C4] border-2 shadow-md'
        : 'border-gray-200 hover:border-[#6FA6CF] hover:shadow-sm';
@endphp

<article
    class="mg-reveal group flex flex-col overflow-hidden rounded-xl border bg-white transition hover:-translate-y-1 {{ $borderClass }}"
    aria-label="Dịch vụ: {{ e($name) }}"
    {{ $attributes }}>

    <a href="{{ $detailRoute }}" class="block overflow-hidden rounded-t-xl bg-[#EAF4FB]" aria-label="Xem hình ảnh {{ e($name) }}">
        <img src="{{ $serviceImage }}"
             alt="Hình minh họa dịch vụ {{ e($name) }}"
             width="1200"
             height="720"
             loading="lazy"
             onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'"
             class="h-40 w-full object-cover transition duration-700 group-hover:scale-105">
    </a>

    {{-- Nhãn danh mục --}}
    @if ($category)
    <div class="px-5 pt-4">
        <span class="inline-block rounded-full bg-[#EAF4FB] px-2.5 py-0.5 text-xs font-medium text-[#2D82C4]">
            {{ e($category) }}
        </span>
    </div>
    @endif

    {{-- Nội dung chính --}}
    <div class="flex flex-1 flex-col gap-3 px-5 py-4">

        {{-- Tên dịch vụ --}}
        <h3 class="text-base font-semibold leading-snug text-[#1A202C] line-clamp-2">
            {{ e($name) }}
        </h3>

        {{-- Mô tả ngắn --}}
        @if ($shortDesc)
        <p class="text-sm text-[#4A5568] leading-relaxed flex-1">
            {{ e($shortDesc) }}
        </p>
        @else
        <div class="flex-1"></div>
        @endif

        {{-- Meta: thời lượng + giá --}}
        <dl class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">

            @if ($durationLabel)
            <div class="flex items-center gap-1.5 text-[#4A5568]">
                {{-- Icon đồng hồ --}}
                <svg class="h-4 w-4 flex-shrink-0 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <dt class="sr-only">Thời lượng</dt>
                <dd>{{ $durationLabel }}</dd>
            </div>
            @endif

            @if ($priceFormatted)
            <div class="flex items-center gap-1.5">
                {{-- Icon tiền --}}
                <svg class="h-4 w-4 flex-shrink-0 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <dt class="sr-only">Giá dịch vụ</dt>
                <dd class="font-semibold text-[#2D82C4]">{{ $priceFormatted }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Khu vực hành động --}}
    <div class="flex items-center gap-2 border-t border-gray-100 px-5 py-3">

        {{-- Nút "Đặt lịch" (CTA chính) --}}
        <a href="{{ $bookRoute }}"
           class="flex-1 rounded-full bg-[#2D82C4] px-4 py-2.5 text-center text-sm font-bold text-white
                  hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition"
           aria-label="Đặt lịch dịch vụ {{ e($name) }}">
            Đặt lịch
        </a>

        {{-- Nút "Xem chi tiết" (phụ) --}}
        @if ($showDetail)
        <a href="{{ $detailRoute }}"
           class="rounded-full border border-[#2D82C4] px-4 py-2.5 text-center text-sm font-bold text-[#2D82C4]
                  hover:bg-[#EAF4FB] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition"
           aria-label="Xem chi tiết dịch vụ {{ e($name) }}">
            Chi tiết
        </a>
        @endif
    </div>

    {{-- Slot bổ sung (ví dụ: danh sách chuyên viên cung cấp) --}}
    {{ $slot }}
</article>
