{{--
    SCR-PUB-01 — Trang chủ Mind Guide
    ===================================
    Căn cứ: ThietKeGiaoDien.md §SCR-PUB-01 + rules.md §3
    Bố cục (trên xuống):
      1. Hero   — tiêu đề + mô tả + nút "Đặt lịch ngay"
      2. Safety — khối cảnh báo an toàn (NFR-USA-003)
      3. Dịch vụ nổi bật — ACTIVE, không hard-code (NFR-MNT-002)
      4. Chuyên viên nổi bật — ACTIVE
      5. Quy trình 3 bước đặt lịch
      (Footer do layout/app.blade.php đảm nhận)

    Dữ liệu truyền từ controller (HomeController@index):
      $featuredServices   — Collection<App\Models\Service>   (ACTIVE, có display_order)
      $featuredSpecialists— Collection<App\Models\Specialist> (ACTIVE, có avatar, rating)
--}}

@extends('layouts.app')

@section('title', 'Mind Guide — Chăm sóc sức khỏe tinh thần')

{{--
    Banner an toàn ở layout/app.blade.php đã xử lý cho trang công khai.
    Trang này KHÔNG ẩn banner (mặc định = hiển thị).
--}}

@section('content')

{{-- ══════════════════════════════════════════════════════════════
     (1) HERO — tiêu đề + mô tả ngắn + CTA
     SCR-PUB-01: "chưa đăng nhập thì chuyển đến đăng nhập rồi quay lại"
     ══════════════════════════════════════════════════════════════ --}}
<section
    class="relative overflow-hidden bg-gradient-to-b from-mg-secondary-light/70 via-mg-bg to-mg-bg border-b border-gray-100"
    aria-labelledby="hero-heading">

    {{-- Nền trang trí hình học mềm mại (Soft Medical / Healing Minimalist) --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        {{-- Vầng sáng xanh xám góc phải --}}
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-mg-secondary/15 blur-3xl"></div>
        {{-- Vầng sáng góc trái dưới --}}
        <div class="absolute -bottom-16 -left-16 h-80 w-80 rounded-full bg-mg-secondary-light/60 blur-2xl"></div>
        {{-- Họa tiết vòng tròn thanh mảnh --}}
        <div class="absolute top-1/2 left-10 -translate-y-1/2 h-48 w-48 rounded-full border border-mg-secondary/20 hidden md:block"></div>
        <div class="absolute top-1/3 right-16 h-32 w-32 rounded-full border border-mg-primary/10 hidden lg:block"></div>
    </div>

    <div class="relative mx-auto max-w-screen-xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-28">
        <div class="mx-auto mb-10 max-w-5xl overflow-hidden rounded-2xl border border-[#709782]/30 bg-white/70 shadow-sm">
            <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1800&q=85"
                 alt="Không gian phòng tư vấn tâm lý riêng tư, ấm cúng tại Mind Guide"
                 width="1800"
                 height="760"
                 fetchpriority="high"
                 onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'"
                 class="h-56 w-full object-cover object-center sm:h-72 lg:h-80">
        </div>
        <div class="mx-auto max-w-3xl text-center">

            {{-- Nhãn badge nhỏ phía trên tiêu đề --}}
            <span class="mb-5 inline-flex items-center gap-2 rounded-full bg-[#E8F0EC] px-4.5 py-1.5
                         text-sm font-bold text-[#1D4B43] border border-[#709782]/50 shadow-xs">
                <span class="h-2.5 w-2.5 rounded-full bg-[#709782] animate-pulse" aria-hidden="true"></span>
                Phòng khám Tâm lý Mind Guide — Lắng nghe & Đồng hành
            </span>

            {{-- Tiêu đề chính: màu đen than chì tối đậm (#0F172A), font-black siêu đậm và nổi bật --}}
            <h1 id="hero-heading"
                class="mt-4 text-4xl font-black leading-tight tracking-tight text-[#0F172A]
                       sm:text-5xl lg:text-6xl drop-shadow-xs">
                Sức khỏe tinh thần<br>
                <span class="text-[#1D4B43]">là nền tảng của mọi điều</span>
            </h1>

            {{-- Mô tả ngắn: màu xám đen đậm (#334155), font chữ vừa vặn, tương phản cao, dễ đọc rõ ràng trên nền sáng --}}
            <p class="mx-auto mt-6 max-w-2xl text-lg sm:text-xl font-medium leading-relaxed text-[#334155]">
                Mind Guide kết nối bạn với các chuyên viên tâm lý giàu kinh nghiệm và tận tâm.
                Đặt lịch tư vấn trực tuyến nhanh chóng, bảo mật tuyệt đối và riêng tư.
            </p>

            {{-- Nút CTA --}}
            <div class="mt-9 flex flex-wrap items-center justify-center gap-4">

                {{-- "Đặt lịch ngay" — guest thì chuyển về đăng nhập kèm tham số ?intended (SCR-PUB-01) --}}
                @guest
                    <a href="{{ route('login') }}?intended={{ urlencode(route('appointments.book')) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-[#1D4B43] px-8 py-4
                              text-base font-bold text-white shadow-md
                              hover:bg-[#14352F] focus:outline-none
                              focus:ring-2 focus:ring-[#1D4B43] focus:ring-offset-2
                              transition-all hover:-translate-y-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Đặt lịch ngay
                    </a>
                    <a href="{{ route('services.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border-2 border-[#1D4B43] px-8 py-4
                              text-base font-bold text-[#1D4B43] bg-white
                              hover:bg-[#E8F0EC] focus:outline-none
                              focus:ring-2 focus:ring-[#1D4B43] focus:ring-offset-2
                              transition-all shadow-xs">
                        Khám phá dịch vụ
                    </a>
                @else
                    <a href="{{ route('appointments.book') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-[#1D4B43] px-8 py-4
                              text-base font-bold text-white shadow-md
                              hover:bg-[#14352F] focus:outline-none
                              focus:ring-2 focus:ring-[#1D4B43] focus:ring-offset-2
                              transition-all hover:-translate-y-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Đặt lịch ngay
                    </a>
                    <a href="{{ route('services.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border-2 border-[#1D4B43] px-8 py-4
                              text-base font-bold text-[#1D4B43] bg-white
                              hover:bg-[#E8F0EC] focus:outline-none
                              focus:ring-2 focus:ring-[#1D4B43] focus:ring-offset-2
                              transition-all shadow-xs">
                        Khám phá dịch vụ
                    </a>
                @endguest
            </div>

            {{-- Số liệu tin tưởng: chữ tối đậm trên nền sáng, dễ đọc --}}
            <div class="mt-12 grid grid-cols-3 divide-x divide-gray-300 border-t border-gray-200/90 pt-8">
                <div class="px-4 text-center">
                    <p class="text-3xl font-extrabold text-[#1D4B43] sm:text-4xl">500+</p>
                    <p class="mt-1.5 text-xs font-bold text-[#334155] sm:text-sm">Khách hàng tin tưởng</p>
                </div>
                <div class="px-4 text-center">
                    <p class="text-3xl font-extrabold text-[#1D4B43] sm:text-4xl">20+</p>
                    <p class="mt-1.5 text-xs font-bold text-[#334155] sm:text-sm">Chuyên viên tâm lý</p>
                </div>
                <div class="px-4 text-center">
                    <p class="text-3xl font-extrabold text-[#1D4B43] sm:text-4xl">
                        4.9<span class="text-amber-500 text-2xl ml-0.5">★</span>
                    </p>
                    <p class="mt-1.5 text-xs font-bold text-[#334155] sm:text-sm">Đánh giá trung bình</p>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════════════
     (2) KHỐI THÔNG BÁO AN TOÀN KHÔNG DÀNH CHO TÌNH HUỐNG KHẨN CẤP
     NFR-USA-003 · SCR-PUB-01 bullet (2)
     "nội dung do phòng khám xác nhận [OQ-18]" — dùng placeholder
     ══════════════════════════════════════════════════════════════ --}}
<section
    class="border-y border-amber-200 bg-amber-50"
    aria-labelledby="safety-section-heading">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-6">

            {{-- Icon --}}
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>

            <div class="flex-1">
                <h2 id="safety-section-heading"
                    class="text-base font-semibold text-amber-900">
                    Thông tin quan trọng về dịch vụ tư vấn
                </h2>
                <div class="mt-2 space-y-2 text-sm leading-relaxed text-amber-800">
                    <p>
                        Dịch vụ tư vấn của <strong>Mind Guide</strong> được cung cấp bởi các chuyên viên tâm lý
                        có chuyên môn. Tuy nhiên, dịch vụ này <strong>không thay thế</strong> điều trị y tế,
                        chẩn đoán lâm sàng hoặc can thiệp khẩn cấp.
                    </p>
                    <p>
                        Nếu bạn hoặc người thân đang trong tình huống khủng hoảng hoặc có nguy cơ tự làm hại bản thân,
                        vui lòng gọi ngay:
                        <a href="tel:115"
                           class="font-bold underline hover:opacity-80 focus:outline-none focus:ring-1 focus:ring-amber-700 rounded"
                           aria-label="Gọi cấp cứu 115">
                            115
                        </a>
                        (cấp cứu) hoặc
                        <a href="tel:19001567"
                           class="font-bold underline hover:opacity-80 focus:outline-none focus:ring-1 focus:ring-amber-700 rounded"
                           aria-label="Đường dây hỗ trợ sức khỏe tâm thần 1900 1567">
                            1900&nbsp;1567
                        </a>
                        (hỗ trợ sức khỏe tâm thần, miễn phí 24/7).
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════════════
     (3) DỊCH VỤ NỔI BẬT
     NFR-MNT-002: lấy từ DB (ACTIVE), không hard-code.
     SCR-PUB-01: "chưa có → ẩn khối, không báo lỗi"
     ══════════════════════════════════════════════════════════════ --}}
@if(isset($featuredServices) && $featuredServices->isNotEmpty())
<section
    class="bg-mg-bg py-16 sm:py-20"
    aria-labelledby="services-heading">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">

        {{-- Tiêu đề khu vực --}}
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-block rounded-full bg-mg-secondary-light px-4 py-1 text-sm font-medium text-mg-primary">
                Dịch vụ của chúng tôi
            </span>
            <h2 id="services-heading"
                class="mt-3 text-3xl font-bold tracking-tight text-mg-text sm:text-4xl">
                Các dịch vụ tư vấn nổi bật
            </h2>
            <p class="mt-4 text-mg-text-muted leading-relaxed">
                Được thiết kế để đáp ứng đúng nhu cầu của bạn, từ tư vấn cá nhân đến hỗ trợ gia đình.
            </p>
        </div>

        {{-- Lưới dịch vụ --}}
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($featuredServices as $service)
                <x-service-card
                    :id="$service->id"
                    :name="$service->name"
                    :category="is_object($service->category) ? ($service->category->name ?? null) : ($service->category ?? null)"
                    :description="$service->description"
                    :duration="$service->duration_minutes ?? $service->duration ?? null"
                    :price="$service->price"
                    :detailRoute="route('services.show', ['service' => $service->id])"
                    :bookRoute="route('appointments.book', ['service' => $service->id])"
                    :featured="$loop->first"
                />
            @endforeach
        </div>

        {{-- Link xem tất cả --}}
        <div class="mt-10 text-center">
            <a href="{{ route('services.index') }}"
               class="inline-flex items-center gap-2 text-mg-primary font-medium hover:text-mg-primary-hover
                      focus:outline-none focus:underline transition group">
                Xem tất cả dịch vụ
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>
@endif
{{-- SCR-PUB-01: chưa có dịch vụ → ẩn khối, không báo lỗi → không có else --}}


{{-- ══════════════════════════════════════════════════════════════
     (4) CHUYÊN VIÊN NỔI BẬT
     SCR-PUB-01 bullet (4): "vài chuyên viên nổi bật (ACTIVE)"
     SCR-PUB-01: "chưa có → ẩn khối, không báo lỗi"
     ══════════════════════════════════════════════════════════════ --}}
@if(isset($featuredSpecialists) && $featuredSpecialists->isNotEmpty())
<section
    class="bg-mg-secondary-light/40 py-16 sm:py-20"
    aria-labelledby="specialists-heading">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">

        {{-- Tiêu đề --}}
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-block rounded-full bg-mg-secondary-light px-4 py-1 text-sm font-medium text-mg-primary">
                Đội ngũ chuyên viên
            </span>
            <h2 id="specialists-heading"
                class="mt-3 text-3xl font-bold tracking-tight text-mg-text sm:text-4xl">
                Gặp gỡ các chuyên viên của chúng tôi
            </h2>
            <p class="mt-4 text-mg-text-muted leading-relaxed">
                Mỗi chuyên viên đều được đào tạo chuyên sâu và có nhiều năm kinh nghiệm đồng hành cùng khách hàng.
            </p>
        </div>

        {{-- Lưới chuyên viên (3 cột cân đối) --}}
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($featuredSpecialists as $specialist)
                <x-specialist-card
                    :id="$specialist->id"
                    :name="$specialist->name"
                    :title="$specialist->title"
                    :specialization="$specialist->specialization"
                    :experience="$specialist->experience_years ?? $specialist->experience ?? null"
                    :rating="$specialist->average_rating ?? $specialist->rating ?? null"
                    :reviewCount="$specialist->visible_reviews_count ?? $specialist->review_count ?? 0"
                    :avatarUrl="$specialist->avatar_url"
                    :detailRoute="route('specialists.show', ['specialist' => $specialist->id])"
                    :bookRoute="route('appointments.book', ['specialist' => $specialist->id])"
                />
            @endforeach
        </div>

        {{-- Link xem tất cả --}}
        <div class="mt-10 text-center">
            <a href="{{ route('specialists.index') }}"
               class="inline-flex items-center gap-2 text-mg-primary font-medium hover:text-mg-primary-hover
                      focus:outline-none focus:underline transition group">
                Xem tất cả chuyên viên
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>
@endif


{{-- ══════════════════════════════════════════════════════════════
     (5) QUY TRÌNH 3 BƯỚC ĐẶT LỊCH
     SCR-PUB-01 bullet (5)
     ══════════════════════════════════════════════════════════════ --}}
<section
    class="bg-mg-bg py-16 sm:py-20"
    aria-labelledby="process-heading">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">

        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-block rounded-full bg-mg-secondary-light px-4 py-1 text-sm font-medium text-mg-primary">
                Đơn giản & Nhanh chóng
            </span>
            <h2 id="process-heading"
                class="mt-3 text-3xl font-bold tracking-tight text-mg-text sm:text-4xl">
                Đặt lịch chỉ trong 3 bước
            </h2>
        </div>

        <ol class="mt-14 grid gap-8 sm:grid-cols-3" role="list">

            {{-- Bước 1 --}}
            <li class="relative flex flex-col items-center text-center">
                {{-- Đường nối giữa các bước (desktop) --}}
                <div class="absolute left-1/2 top-8 hidden h-0.5 w-full translate-x-1/2
                            bg-gradient-to-r from-mg-secondary to-mg-secondary-light sm:block"
                     aria-hidden="true"></div>

                {{-- Số bước --}}
                <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full
                            bg-mg-primary text-2xl font-bold text-white shadow-lg
                            ring-4 ring-mg-bg">
                    1
                </div>

                {{-- Icon --}}
                <div class="mt-6 flex h-12 w-12 items-center justify-center rounded-xl bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-mg-text">
                    Chọn dịch vụ & chuyên viên
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-mg-text-muted">
                    Duyệt danh sách dịch vụ và hồ sơ chuyên viên. Đọc đánh giá từ khách hàng trước để đưa ra lựa chọn phù hợp.
                </p>
            </li>

            {{-- Bước 2 --}}
            <li class="relative flex flex-col items-center text-center">
                <div class="absolute left-1/2 top-8 hidden h-0.5 w-full translate-x-1/2
                            bg-gradient-to-r from-mg-secondary to-mg-secondary-light sm:block"
                     aria-hidden="true"></div>

                <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full
                            bg-mg-primary text-2xl font-bold text-white shadow-lg
                            ring-4 ring-mg-bg">
                    2
                </div>

                <div class="mt-6 flex h-12 w-12 items-center justify-center rounded-xl bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-mg-text">
                    Chọn ngày giờ phù hợp
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-mg-text-muted">
                    Xem lịch trống theo thời gian thực và chọn khung giờ tiện nhất cho bạn.
                    Lịch hẹn được xác nhận ngay lập tức.
                </p>
            </li>

            {{-- Bước 3 --}}
            <li class="flex flex-col items-center text-center">
                <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full
                            bg-mg-primary text-2xl font-bold text-white shadow-lg
                            ring-4 ring-mg-bg">
                    3
                </div>

                <div class="mt-6 flex h-12 w-12 items-center justify-center rounded-xl bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-mg-text">
                    Xác nhận & Thanh toán
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-mg-text-muted">
                    Xác nhận lịch hẹn và thanh toán qua chuyển khoản QR hoặc tiền mặt tại quầy.
                    Nhận nhắc nhở qua email trước buổi hẹn.
                </p>
            </li>
        </ol>

        {{-- CTA cuối section --}}
        <div class="mt-14 text-center">
            @guest
                <a href="{{ route('login') }}?intended={{ urlencode(route('appointments.book')) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-mg-primary px-8 py-4
                          text-base font-semibold text-white shadow-md
                          hover:bg-mg-primary-hover focus:outline-none
                          focus:ring-2 focus:ring-mg-primary focus:ring-offset-2
                          transition-all hover:-translate-y-0.5">
                    Bắt đầu ngay — Miễn phí đăng ký
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <p class="mt-3 text-sm text-mg-text-muted">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}"
                       class="font-medium text-mg-primary hover:underline focus:outline-none focus:underline">
                        Đăng nhập
                    </a>
                </p>
            @else
                <a href="{{ route('appointments.book') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-mg-primary px-8 py-4
                          text-base font-semibold text-white shadow-md
                          hover:bg-mg-primary-hover focus:outline-none
                          focus:ring-2 focus:ring-mg-primary focus:ring-offset-2
                          transition-all hover:-translate-y-0.5">
                    Đặt lịch tư vấn ngay
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            @endguest
        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════════════
     SECTION PHỤ — Cam kết bảo mật & riêng tư (§1.7 NFR-PRV)
     Không có trong spec nhưng hỗ trợ tin tưởng thương hiệu
     ══════════════════════════════════════════════════════════════ --}}
<section class="border-t border-gray-200 bg-white py-12" aria-label="Cam kết của chúng tôi">
    <div class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8">
        <dl class="grid grid-cols-2 gap-6 sm:grid-cols-4">

            {{-- Bảo mật --}}
            <div class="flex flex-col items-center text-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <dt class="text-sm font-semibold text-mg-text">Bảo mật tuyệt đối</dt>
                <dd class="text-xs text-mg-text-muted leading-relaxed">
                    Thông tin cá nhân và nội dung tư vấn được mã hóa, chỉ chuyên viên phụ trách xem được.
                </dd>
            </div>

            {{-- Chuyên nghiệp --}}
            <div class="flex flex-col items-center text-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <dt class="text-sm font-semibold text-mg-text">Chuyên viên được kiểm duyệt</dt>
                <dd class="text-xs text-mg-text-muted leading-relaxed">
                    Tất cả chuyên viên đều trải qua quy trình xét duyệt chuyên môn nghiêm ngặt.
                </dd>
            </div>

            {{-- Linh hoạt --}}
            <div class="flex flex-col items-center text-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <dt class="text-sm font-semibold text-mg-text">Linh hoạt thời gian</dt>
                <dd class="text-xs text-mg-text-muted leading-relaxed">
                    Đặt lịch theo khung giờ phù hợp với lịch trình của bạn, dễ dàng đổi hoặc hủy khi cần.
                </dd>
            </div>

            {{-- Hủy linh hoạt --}}
            <div class="flex flex-col items-center text-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-mg-secondary-light">
                    <svg class="h-6 w-6 text-mg-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <dt class="text-sm font-semibold text-mg-text">Hoàn tiền minh bạch</dt>
                <dd class="text-xs text-mg-text-muted leading-relaxed">
                    Chính sách hoàn tiền rõ ràng theo 3 mốc thời gian. Không phát sinh chi phí ẩn.
                </dd>
            </div>
        </dl>
    </div>
</section>

@endsection


{{-- ══════════════════════════════════════════════════════════════
     CONTROLLER GỢI Ý — HomeController@index
     ══════════════════════════════════════════════════════════════
    public function index()
    {
        // Lấy tối đa 6 dịch vụ ACTIVE, theo display_order (NFR-MNT-002)
        $featuredServices = \App\Models\Service::query()
            ->where('status', 'ACTIVE')
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->orderBy('display_order')
            ->limit(6)
            ->get();

        // Lấy tối đa 4 chuyên viên ACTIVE, ưu tiên đánh giá cao
        $featuredSpecialists = \App\Models\Specialist::query()
            ->where('status', 'ACTIVE')
            ->withCount(['reviews as visible_reviews_count' => fn($q) => $q->where('status', 'VISIBLE')])
            ->withAvg(['reviews as average_rating' => fn($q) => $q->where('status', 'VISIBLE')], 'rating')
            ->orderByDesc('average_rating')
            ->orderByDesc('visible_reviews_count')
            ->limit(4)
            ->get();

        return view('public.home', compact('featuredServices', 'featuredSpecialists'));
    }
--}}
