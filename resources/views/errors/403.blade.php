@extends('layouts.app')
@section('title', 'Không có quyền truy cập')
@section('content')
<div class="mx-auto max-w-xl px-4 py-20 text-center"><p class="text-sm font-bold text-[#709782]">403</p><h1 class="mt-2 text-3xl font-black text-[#1D4B43]">Bạn không có quyền truy cập trang này</h1><p class="mt-3 text-[#4A5568]">Tài khoản hiện tại không được phép xem hoặc thao tác trên nội dung này.</p><a href="{{ url('/') }}" class="mt-7 inline-flex rounded-lg bg-[#1D4B43] px-5 py-3 font-bold text-white">Về trang chủ</a></div>
@endsection
