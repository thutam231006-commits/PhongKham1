@extends('layouts.app')
@section('title', 'Không tìm thấy')
@section('content')
<div class="mx-auto max-w-xl px-4 py-20 text-center"><p class="text-sm font-bold text-[#6FA6CF]">404</p><h1 class="mt-2 text-3xl font-black text-[#2D82C4]">Không tìm thấy nội dung bạn yêu cầu</h1><p class="mt-3 text-[#4A5568]">Nội dung có thể đã được chuyển, không tồn tại hoặc không thuộc phạm vi truy cập của bạn.</p><a href="{{ url('/') }}" class="mt-7 inline-flex rounded-lg bg-[#2D82C4] px-5 py-3 font-bold text-white">Về trang chủ</a></div>
@endsection
