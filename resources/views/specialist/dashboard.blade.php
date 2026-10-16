@extends('layouts.app')

@section('title', 'Tổng quan chuyên viên')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-[#709782]">Khu vực chuyên viên</p>
            <h1 class="mt-1 text-3xl font-black text-[#1D4B43]">Xin chào, {{ $profile->user->name }}</h1>
            <p class="mt-2 text-sm text-[#4A5568]">Theo dõi lịch tư vấn và hồ sơ chuyên môn của bạn.</p>
        </div>
        <a href="{{ route('specialist.appointments') }}" class="rounded-lg bg-[#1D4B43] px-5 py-3 text-sm font-bold text-white hover:bg-[#14352F]">Xem lịch hẹn</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs text-[#4A5568]">Lịch hôm nay</p><p class="mt-2 text-3xl font-black text-[#1D4B43]">{{ $today->count() }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs text-[#4A5568]">Lịch tuần này</p><p class="mt-2 text-3xl font-black text-[#1D4B43]">{{ $week->count() }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs text-[#4A5568]">Hồ sơ đã hoàn thành</p><p class="mt-2 text-3xl font-black text-[#1D4B43]">{{ number_format($profile->completed_appointments) }}</p></div>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white" aria-labelledby="today-heading">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 id="today-heading" class="text-lg font-bold text-[#0F172A]">Lịch hôm nay</h2>
            <a href="{{ route('specialist.schedule') }}" class="text-sm font-semibold text-[#1D4B43] hover:underline">Lịch làm việc</a>
        </div>
        @forelse($today as $appointment)
            <a href="{{ route('specialist.appointments.detail', $appointment->id) }}" class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 last:border-0 hover:bg-[#F7FAFC] sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-bold text-[#0F172A]">{{ $appointment->start_at->format('H:i') }} · {{ $appointment->customer->name }}</p><p class="text-sm text-[#4A5568]">{{ $appointment->service->name }}</p></div>
                <span class="rounded-full bg-[#E8F0EC] px-3 py-1 text-xs font-bold text-[#1D4B43]">{{ $appointment->status }}</span>
            </a>
        @empty
            <p class="px-5 py-8 text-center text-sm text-[#4A5568]">Hôm nay bạn không có lịch hẹn.</p>
        @endforelse
    </section>
</div>
@endsection
