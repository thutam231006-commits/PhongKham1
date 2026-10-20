@extends('layouts.app')
@section('title', 'Phiên đã hết hạn')
@section('content')
<div class="mx-auto max-w-xl px-4 py-20 text-center"><h1 class="text-3xl font-black text-[#2D82C4]">Phiên thao tác đã hết hạn</h1><p class="mt-3 text-[#4A5568]">Vui lòng tải lại trang và thực hiện lại thao tác.</p><a href="{{ url()->previous() }}" class="mt-7 inline-flex rounded-lg bg-[#2D82C4] px-5 py-3 font-bold text-white">Tải lại trang</a></div>
@endsection
