@extends('layouts.app')

@section('title', 'Mind Guide - Chăm sóc sức khỏe tinh thần')

@section('content')
<div class="bg-[#F5FAFE]">
    <section class="overflow-hidden border-b border-[#D7E8F3] bg-white" aria-labelledby="hero-heading">
        <div class="mx-auto grid max-w-screen-2xl lg:min-h-[620px] lg:grid-cols-[0.9fr_1.1fr]">
            <div class="flex items-center px-5 py-14 sm:px-10 sm:py-16 lg:px-16 xl:px-24">
                <div class="w-full min-w-0 max-w-xl">
                    <p class="mg-section-kicker mg-reveal">Phòng khám tâm lý Mind Guide</p>
                    <h1 id="hero-heading" class="mg-reveal mg-reveal-delay-1 mt-4 text-4xl font-extrabold leading-[1.08] text-[#102A43] sm:text-5xl lg:text-[3.65rem]">
                        Một nơi an toàn để bạn được <span class="text-[#2D82C4]">lắng nghe.</span>
                    </h1>
                    <p class="mg-reveal mg-reveal-delay-2 mt-6 max-w-lg text-base leading-7 text-[#52637A] sm:text-lg">
                        Kết nối đúng chuyên viên, đặt lịch riêng tư và bắt đầu chăm sóc sức khỏe tinh thần theo nhịp của bạn.
                    </p>
                    <div class="mg-reveal mg-reveal-delay-3 mt-8 flex flex-col gap-3 sm:flex-row">
                        @guest
                            <a href="{{ route('login') }}?intended={{ urlencode(route('appointments.book')) }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-[#2D82C4] px-7 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#2D82C4]/15 hover:-translate-y-0.5 hover:bg-[#23689C]">
                                Đặt lịch tư vấn
                            </a>
                        @else
                            <a href="{{ route('appointments.book') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-[#2D82C4] px-7 py-3 text-sm font-extrabold text-white shadow-lg shadow-[#2D82C4]/15 hover:-translate-y-0.5 hover:bg-[#23689C]">
                                Đặt lịch tư vấn
                            </a>
                        @endguest
                        <a href="{{ route('services.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full border-2 border-[#2D82C4] px-7 py-3 text-sm font-extrabold text-[#2D82C4] hover:bg-[#EAF4FB]">
                            Khám phá dịch vụ
                        </a>
                    </div>
                    <div class="mg-reveal mt-10 grid grid-cols-3 gap-3 border-t border-[#D7E8F3] pt-5 text-sm text-[#52637A]">
                        <span><strong class="block text-[#236FA8]">Riêng tư</strong>Bảo mật hồ sơ</span>
                        <span><strong class="block text-[#236FA8]">Linh hoạt</strong>Trực tiếp, online</span>
                        <span><strong class="block text-[#236FA8]">Rõ ràng</strong>Chi phí minh bạch</span>
                    </div>
                </div>
            </div>

            <div
                class="relative min-h-[390px] overflow-hidden bg-[#DCEFFA] lg:min-h-full"
                x-data="heroSlider"
                @mouseenter="stop()"
                @mouseleave="start()">
                <template x-for="(slide, index) in slides" :key="slide.src">
                    <img
                        x-show="active === index"
                        x-transition:enter="transition duration-700 ease-out"
                        x-transition:enter-start="opacity-0 scale-[1.04]"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition duration-500 ease-in"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        :src="slide.src"
                        :alt="slide.alt"
                        width="1800"
                        height="1200"
                        class="mg-hero-image absolute inset-0 h-full w-full object-cover"
                        :fetchpriority="index === 0 ? 'high' : 'auto'">
                </template>

                <div class="absolute inset-x-5 bottom-5 max-w-sm rounded-2xl border border-white/70 bg-white/92 p-5 text-[#102A43] shadow-2xl backdrop-blur-md sm:inset-x-8 sm:bottom-8">
                    <p class="text-xs font-extrabold uppercase tracking-[0.08em] text-[#2D82C4]">Đồng hành riêng tư</p>
                    <p class="mt-2 text-lg font-extrabold leading-snug">Bạn không cần đi qua những ngày khó khăn một mình.</p>
                    <div class="mt-4 flex gap-2" aria-label="Chọn ảnh giới thiệu">
                        <template x-for="(_, index) in slides" :key="index">
                            <button type="button" @click="active = index" class="h-2 rounded-full transition-all" :class="active === index ? 'w-7 bg-[#2D82C4]' : 'w-2 bg-[#A8CBE5]'" :aria-label="'Hiển thị ảnh ' + (index + 1)"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-screen-xl px-5 py-16 sm:px-8 lg:px-10 lg:py-20" aria-labelledby="service-heading">
        <div class="mg-reveal max-w-2xl">
            <p class="mg-section-kicker">Chăm sóc đúng nhu cầu</p>
            <h2 id="service-heading" class="mt-2 text-3xl font-extrabold text-[#102A43] sm:text-4xl">Dịch vụ tư vấn phù hợp với bạn</h2>
            <p class="mt-3 leading-7 text-[#52637A]">Mỗi gói có mục tiêu, thời lượng và chi phí rõ ràng để bạn dễ lựa chọn.</p>
        </div>

        <div class="mg-stagger mt-9 grid gap-5 md:grid-cols-3">
            @foreach($featuredServices as $service)
                <article style="--mg-index: {{ $loop->index }}" class="group overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white transition hover:-translate-y-1 hover:border-[#6FA6CF] hover:shadow-xl hover:shadow-[#2D82C4]/10">
                    <a href="{{ route('services.show', ['service' => $service->id]) }}" class="block h-48 overflow-hidden bg-[#EAF4FB]">
                        <img src="{{ $service->image_url ?? 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1000&q=82' }}" alt="{{ $service->name }}" width="1000" height="667" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                    </a>
                    <div class="p-6">
                        <p class="text-xs font-extrabold uppercase tracking-[0.06em] text-[#2D82C4]">{{ $service->category?->name ?? 'Tư vấn tâm lý' }}</p>
                        <h3 class="mt-2 text-xl font-extrabold leading-snug text-[#102A43]">{{ $service->name }}</h3>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-[#52637A]">{{ $service->description }}</p>
                        <a href="{{ route('services.show', ['service' => $service->id]) }}" class="mg-link-arrow mt-5 inline-block text-sm font-extrabold text-[#2D82C4]">Tìm hiểu thêm</a>
                    </div>
                </article>
            @endforeach
        </div>

        <a href="{{ route('services.index') }}" class="mg-link-arrow mt-7 inline-flex text-sm font-extrabold text-[#2D82C4]">Xem tất cả dịch vụ</a>
    </section>

    <section class="border-y border-[#D7E8F3] bg-[#EAF4FB]" aria-labelledby="specialist-heading">
        <div class="mx-auto grid max-w-screen-xl gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[0.72fr_1.28fr] lg:items-center lg:px-10 lg:py-20">
            <div class="mg-reveal">
                <p class="mg-section-kicker">Đội ngũ chuyên môn</p>
                <h2 id="specialist-heading" class="mt-2 text-3xl font-extrabold text-[#102A43] sm:text-4xl">Gặp đúng người đồng hành</h2>
                <p class="mt-4 leading-7 text-[#52637A]">Xem kinh nghiệm, chuyên môn và đánh giá trước khi chọn chuyên viên phù hợp.</p>
                <a href="{{ route('specialists.index') }}" class="mt-6 inline-flex rounded-full border-2 border-[#2D82C4] px-6 py-3 text-sm font-extrabold text-[#2D82C4] hover:bg-white">Xem đội ngũ</a>
            </div>

            <div class="mg-stagger grid gap-4 sm:grid-cols-3">
                @foreach($featuredSpecialists as $specialist)
                    <a href="{{ route('specialists.show', $specialist->id) }}" style="--mg-index: {{ $loop->index }}" class="group bg-white p-5 shadow-sm ring-1 ring-[#C9DFEE] transition hover:-translate-y-1 hover:shadow-lg">
                        <img src="{{ $specialist->avatar_url ?? 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=500&q=82' }}" alt="{{ $specialist->name }}" width="500" height="500" loading="lazy" class="aspect-square w-full object-cover">
                        <h3 class="mt-4 font-extrabold leading-snug text-[#102A43] group-hover:text-[#2D82C4]">{{ $specialist->name }}</h3>
                        <p class="mt-1 text-xs leading-5 text-[#52637A]">{{ $specialist->title }}</p>
                        <p class="mt-4 border-t border-[#D7E8F3] pt-3 text-xs font-semibold text-[#52637A]">{{ $specialist->experience_years ?? 0 }} năm kinh nghiệm</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-screen-xl px-5 py-16 sm:px-8 lg:px-10 lg:py-20" aria-labelledby="process-heading">
        <div class="mg-reveal max-w-2xl">
            <p class="mg-section-kicker">Quy trình rõ ràng</p>
            <h2 id="process-heading" class="mt-2 text-3xl font-extrabold text-[#102A43] sm:text-4xl">Bắt đầu chỉ với ba bước</h2>
            <p class="mt-3 leading-7 text-[#52637A]">Từ chọn nhu cầu đến xác nhận lịch hẹn, mọi thông tin được giữ xuyên suốt.</p>
        </div>

        <ol class="mg-stagger mt-10 grid gap-6 md:grid-cols-3">
            @foreach([['01', 'Chọn nhu cầu', 'Chọn gói tư vấn phù hợp với điều bạn đang quan tâm.'], ['02', 'Chọn chuyên viên', 'Xem hồ sơ và chọn người bạn cảm thấy tin tưởng.'], ['03', 'Chọn thời gian', 'Chọn lịch trống, ghi chú và xác nhận cuộc hẹn.']] as $item)
                <li style="--mg-index: {{ $loop->index }}" class="relative border-l-4 border-[#6FA6CF] bg-white p-6 shadow-sm">
                    <span class="text-sm font-extrabold text-[#2D82C4]">Bước {{ $item[0] }}</span>
                    <h3 class="mt-4 text-lg font-extrabold text-[#102A43]">{{ $item[1] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-[#52637A]">{{ $item[2] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="border-t border-[#D7E8F3] bg-white px-5 py-16 sm:px-10 lg:px-20">
        <div class="mg-reveal mx-auto flex max-w-screen-lg flex-col items-start justify-between gap-7 md:flex-row md:items-center">
            <div class="max-w-2xl">
                <p class="mg-section-kicker">Một bước nhỏ hôm nay</p>
                <h2 class="mt-2 text-3xl font-extrabold text-[#102A43] sm:text-4xl">Bạn xứng đáng được chăm sóc.</h2>
                <p class="mt-3 leading-7 text-[#52637A]">Bắt đầu bằng một cuộc trò chuyện an toàn, riêng tư và không phán xét.</p>
            </div>
            <a href="{{ route('appointments.book') }}" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-full bg-[#2D82C4] px-7 py-3 text-sm font-extrabold text-white hover:-translate-y-0.5 hover:bg-[#23689C]">Đặt lịch ngay</a>
        </div>
    </section>
</div>
@endsection
