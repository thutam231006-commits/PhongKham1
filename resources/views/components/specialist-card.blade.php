{{--
    x-specialist-card — Thẻ hiển thị chuyên viên tư vấn
    ------------------------------------------------------
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-04, §SCR-PUB-05, §1.7 bảo mật
    Quy tắc bảo mật:
      • KHÔNG hiển thị: license_number, internal_note, email, SĐT chuyên viên.
      • Đánh giá chỉ tính từ đánh giá VISIBLE.
      • Điểm đánh giá kèm số lượt (tránh misleading khi ít dữ liệu).

    Props:
      @type  string|int   $id             — specialist ID
      @type  string       $name           — Họ tên
      @type  string|null  $title          — Chức danh (vd. "Chuyên viên Tâm lý lâm sàng")
      @type  string|null  $specialization — Chuyên môn (vd. "Trầm cảm, Lo âu")
      @type  int|null     $experience     — Số năm kinh nghiệm
      @type  float|null   $rating         — Điểm TB (1.0–5.0), null = chưa có
      @type  int          $reviewCount    — Số lượt đánh giá VISIBLE
      @type  string|null  $avatarUrl      — URL ảnh đại diện; null = ảnh mặc định
      @type  string       $detailRoute    — Route xem chi tiết chuyên viên
      @type  string       $bookRoute      — Route đặt lịch với chuyên viên này

    Ví dụ:
      <x-specialist-card
          :id="$specialist->id"
          :name="$specialist->name"
          :title="$specialist->title"
          :specialization="$specialist->specialization"
          :experience="$specialist->experience_years"
          :rating="$specialist->average_rating"
          :reviewCount="$specialist->visible_reviews_count"
          :avatarUrl="$specialist->avatar_url"
          :detailRoute="route('specialists.show', $specialist)"
          :bookRoute="route('appointments.book', ['specialist' => $specialist->id])"
      />
--}}

@props([
    'id'             => null,
    'name'           => '',
    'title'          => null,
    'specialization' => null,
    'experience'     => null,
    'rating'         => null,
    'reviewCount'    => 0,
    'avatarUrl'      => null,
    'detailRoute'    => '#',
    'bookRoute'      => '#',
])

@php
    $specialistImages = [
        1 => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=640&q=85',
        2 => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=640&q=85',
        3 => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=640&q=85',
        4 => 'https://images.unsplash.com/photo-1544725176-7c40e5a71c5e?auto=format&fit=crop&w=640&q=85',
        5 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=640&q=85',
        6 => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=640&q=85',
    ];

    // Ảnh mặc định khi chưa có avatar hoặc CDN không tải được.
    $avatar = $avatarUrl ?: ($specialistImages[(int) $id] ?? asset('images/default-avatar.svg'));

    // Định dạng kinh nghiệm
    $expLabel = $experience !== null
        ? ($experience === 0 ? 'Mới tốt nghiệp' : $experience . ' năm kinh nghiệm')
        : null;

    // Giới hạn hiển thị sao: 1–5
    $ratingClamped = $rating !== null ? max(1.0, min(5.0, (float) $rating)) : null;

    // Hàm render sao — tách ra để dễ đọc
    // Dùng inline PHP vì Blade không có helper sao sẵn
    $fullStars  = $ratingClamped !== null ? floor($ratingClamped) : 0;
    $halfStar   = $ratingClamped !== null && ($ratingClamped - $fullStars) >= 0.5;
    $emptyStars = max(0, 5 - $fullStars - ($halfStar ? 1 : 0));
@endphp

<article
    class="group flex flex-col rounded-xl border border-[#D7E8F3] bg-white transition
           hover:-translate-y-1 hover:border-[#6FA6CF] hover:shadow-lg focus-within:ring-2 focus-within:ring-[#2D82C4]"
    aria-label="Chuyên viên: {{ e($name) }}"
    {{ $attributes }}>

    {{-- ── Phần ảnh + thông tin chính ── --}}
    <div class="flex items-start gap-4 p-5">

        {{-- Ảnh đại diện --}}
        <a href="{{ $detailRoute }}"
           class="flex-shrink-0 focus:outline-none"
           tabindex="-1"
           aria-hidden="true">
            <img src="{{ $avatar }}"
                 alt="Ảnh chuyên viên {{ e($name) }}"
                 width="72"
                 height="72"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'"
                 class="h-[72px] w-[72px] rounded-full object-cover ring-4 ring-[#EAF4FB] transition duration-500 group-hover:scale-105">
        </a>

        {{-- Thông tin văn bản --}}
        <div class="min-w-0 flex-1">

            {{-- Họ tên (link đến trang chi tiết) --}}
            <h3 class="text-base font-semibold leading-snug text-[#1A202C]">
                <a href="{{ $detailRoute }}"
                   class="hover:text-[#2D82C4] focus:outline-none focus:underline transition line-clamp-1">
                    {{ e($name) }}
                </a>
            </h3>

            {{-- Chức danh --}}
            @if ($title)
            <p class="mt-0.5 text-sm text-[#4A5568] line-clamp-1">
                {{ e($title) }}
            </p>
            @endif

            {{-- Chuyên môn --}}
            @if ($specialization)
            <p class="mt-1 text-xs text-[#4A5568] line-clamp-2 leading-relaxed">
                <span class="font-medium text-[#1A202C]">Chuyên môn:</span>
                {{ e($specialization) }}
            </p>
            @endif

            {{-- Kinh nghiệm --}}
            @if ($expLabel)
            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-[#4A5568]">
                <svg class="h-3.5 w-3.5 flex-shrink-0 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>{{ $expLabel }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Đánh giá sao (SCR-PUB-04: chỉ tính VISIBLE, kèm số lượt) ── --}}
    <div class="border-t border-gray-100 px-5 py-3">
        @if ($ratingClamped !== null && $reviewCount > 0)
        <div class="flex items-center gap-2"
             aria-label="Đánh giá {{ number_format($ratingClamped, 1) }} trên 5 sao, {{ $reviewCount }} lượt">

            {{-- Sao đầy --}}
            @for ($i = 0; $i < $fullStars; $i++)
            <svg class="h-4 w-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            @endfor

            {{-- Nửa sao --}}
            @if ($halfStar)
            <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true">
                <defs>
                    <linearGradient id="half-{{ $id }}">
                        <stop offset="50%" stop-color="#FBBF24"/>
                        <stop offset="50%" stop-color="#D1D5DB"/>
                    </linearGradient>
                </defs>
                <path fill="url(#half-{{ $id }})"
                      d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            @endif

            {{-- Sao rỗng --}}
            @for ($i = 0; $i < $emptyStars; $i++)
            <svg class="h-4 w-4 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            @endfor

            {{-- Điểm số + số lượt --}}
            <span class="text-sm font-semibold text-[#1A202C]">
                {{ number_format($ratingClamped, 1) }}
            </span>
            <span class="text-xs text-[#4A5568]">
                ({{ number_format($reviewCount, 0, ',', '.') }} đánh giá)
            </span>
        </div>

        @else
        {{-- Chưa có đánh giá — không fake số --}}
        <p class="text-xs text-[#4A5568] italic">Chưa có đánh giá</p>
        @endif
    </div>

    {{-- ── Khu vực hành động ── --}}
    <div class="flex items-center gap-2 border-t border-gray-100 px-5 py-3">

        {{-- Nút đặt lịch (CTA chính) --}}
        <a href="{{ $bookRoute }}"
           class="flex-1 rounded-full bg-[#2D82C4] px-4 py-2.5 text-center text-sm font-bold text-white
                  hover:bg-[#23689C] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition"
           aria-label="Đặt lịch với chuyên viên {{ e($name) }}">
            Đặt lịch
        </a>

        {{-- Nút xem chi tiết --}}
        <a href="{{ $detailRoute }}"
           class="rounded-full border border-[#2D82C4] px-4 py-2.5 text-center text-sm font-bold text-[#2D82C4]
                  hover:bg-[#EAF4FB] focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-2 transition"
           aria-label="Xem hồ sơ chuyên viên {{ e($name) }}">
            Hồ sơ
        </a>
    </div>

    {{-- Slot tùy chỉnh --}}
    {{ $slot }}
</article>
