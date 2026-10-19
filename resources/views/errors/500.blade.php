@extends('layouts.app')
@section('title', 'Hệ thống gặp sự cố')
@section('content')
<div class="mx-auto max-w-xl px-4 py-20 text-center"><p class="text-sm font-bold text-[#709782]">500</p><h1 class="mt-2 text-3xl font-black text-[#1D4B43]">Hệ thống đang gặp sự cố</h1><p class="mt-3 text-[#4A5568]">Vui lòng thử lại sau. Nếu tình trạng tiếp diễn, hãy liên hệ Mind Guide.</p><a href="{{ url('/') }}" class="mt-7 inline-flex rounded-lg bg-[#1D4B43] px-5 py-3 font-bold text-white">Về trang chủ</a></div>
@endsection
