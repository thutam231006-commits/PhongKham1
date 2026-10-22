@extends('layouts.app')

@section('title', $section)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#EAF4FB] text-2xl text-[#2D82C4]">+</span>
        <h1 class="mt-4 text-2xl font-black text-[#2D82C4]">{{ $section }}</h1>
        <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-[#4A5568]">Màn hình này đã có vị trí trong hệ thống Admin và đang được chuẩn bị nghiệp vụ chi tiết theo SRS.</p>
        <a href="{{ route('admin.dashboard') }}" class="mt-6 inline-flex rounded-lg bg-[#2D82C4] px-5 py-3 text-sm font-bold text-white hover:bg-[#23689C]">Về trang chủ</a>
    </div>
</div>
@endsection
