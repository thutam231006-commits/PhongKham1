@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">
        <h1 class="text-3xl font-black text-[#2D82C4]">Đổi mật khẩu</h1>

        @if (session('success'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}"
              class="mt-6 space-y-4 rounded-xl border border-gray-200 bg-white p-6">
            @csrf
            @method('PUT')

            <label class="block text-sm font-bold text-[#334155]">
                Mật khẩu hiện tại
                <input type="password" name="current_password" required
                       class="mt-2 w-full rounded-lg border-gray-300">
            </label>

            <label class="block text-sm font-bold text-[#334155]">
                Mật khẩu mới
                <input type="password" name="password" required
                       class="mt-2 w-full rounded-lg border-gray-300">
            </label>

            <label class="block text-sm font-bold text-[#334155]">
                Xác nhận mật khẩu mới
                <input type="password" name="password_confirmation" required
                       class="mt-2 w-full rounded-lg border-gray-300">
            </label>

            <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-3 text-sm font-bold text-white hover:bg-[#23689C]">
                Cập nhật mật khẩu
            </button>
        </form>
    </div>
@endsection
