@extends('layouts.app')

@section('title', 'Dịch vụ tư vấn tâm lý - Mind Guide')

@section('content')
<div class="bg-[#F5FAFE]">
    <section class="relative overflow-hidden bg-[#2D82C4] text-white">
        <div class="mx-auto grid max-w-screen-2xl lg:grid-cols-[0.9fr_1.1fr]">
            <div class="flex items-center px-5 py-14 sm:px-10 lg:px-16 xl:px-24">
                <div><p class="text-xs font-black uppercase tracking-[0.14em] text-[#D0E6F2]">Danh mục Mind Guide</p><h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Chọn đúng nơi để bắt đầu</h1><p class="mt-5 max-w-xl text-base leading-7 text-[#DDEEF7]">Mỗi gói tư vấn được thiết kế cho một nhu cầu cụ thể, với thông tin rõ ràng và lộ trình đồng hành riêng tư.</p></div>
            </div>
            <div class="relative min-h-[280px] overflow-hidden sm:min-h-[360px]"><img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1600&q=88" alt="Không gian tư vấn Mind Guide" class="h-full w-full object-cover opacity-90"><div class="absolute inset-0 bg-[#2D82C4]/20"></div></div>
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl px-5 py-12 sm:px-8 lg:px-10">
        @if(session('error'))<div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>@endif
        <div class="flex flex-wrap gap-2 border-b border-[#D7E8F3] pb-5"><a href="{{ route('services.index') }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $category === '' ? 'bg-[#2D82C4] text-white' : 'bg-white text-[#52637A] hover:bg-[#EAF4FB]' }}">Tất cả gói</a>@foreach($categories as $cat)<a href="{{ route('services.index', ['category' => $cat->id]) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ (string) $category === (string) $cat->id ? 'bg-[#2D82C4] text-white' : 'bg-white text-[#52637A] hover:bg-[#EAF4FB]' }}">{{ $cat->name }}</a>@endforeach</div>
        <div class="mg-reveal mt-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="mg-section-kicker">{{ $services->total() }} lựa chọn đồng hành</p><h2 class="mt-2 text-3xl font-extrabold text-[#102A43]">Các gói tư vấn tâm lý</h2></div><form method="GET" class="flex gap-2"><input type="search" name="q" value="{{ $search }}" placeholder="Tìm dịch vụ" class="min-w-0 flex-1 rounded-full border-[#C9DFEE] bg-white px-4 py-2 text-sm sm:w-64"><button class="rounded-full bg-[#2D82C4] px-5 py-2 text-sm font-bold text-white hover:bg-[#23689C]">Tìm</button></form></div>
        <div class="mg-stagger mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse($services as $service)
                <article style="--mg-index: {{ $loop->index }}" class="group flex flex-col overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white transition hover:-translate-y-1 hover:border-[#6FA6CF] hover:shadow-xl hover:shadow-[#2D82C4]/10">
                    <div class="relative h-48 overflow-hidden bg-[#EAF4FB]"><img src="{{ $service->image_url ?? 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1000&q=82' }}" alt="{{ $service->name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105"></div>
                    <div class="flex flex-1 flex-col p-6"><p class="text-xs font-extrabold uppercase tracking-[0.06em] text-[#2D82C4]">{{ $service->category->name }}</p><h3 class="mt-2 text-xl font-extrabold leading-snug text-[#102A43]">{{ $service->name }}</h3><p class="mt-3 flex-1 text-sm leading-6 text-[#52637A]">{{ $service->description }}</p><div class="mt-5 flex items-center justify-between border-t border-[#D7E8F3] pt-4 text-sm"><span class="text-[#52637A]">{{ $service->duration_minutes }} phút</span><strong class="text-[#2D82C4]">{{ number_format($service->price,0,',','.') }} VNĐ</strong></div><div class="mt-5 grid grid-cols-2 gap-2"><a href="{{ route('services.show', $service) }}" class="rounded-full border border-[#2D82C4] px-3 py-2.5 text-center text-xs font-extrabold text-[#2D82C4] hover:bg-[#EAF4FB]">Xem thông tin</a><a href="{{ route('appointments.book', ['service' => $service->id]) }}" class="rounded-full bg-[#2D82C4] px-3 py-2.5 text-center text-xs font-extrabold text-white hover:bg-[#23689C]">Đặt lịch ngay</a></div></div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed bg-white p-12 text-center text-[#52637A]">Chưa có gói dịch vụ phù hợp.</div>
            @endforelse
        </div>
        <div class="mt-10">{{ $services->links() }}</div>
    </div>
</div>
@endsection
