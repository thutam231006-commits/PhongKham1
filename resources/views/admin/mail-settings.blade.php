{{--
    Cấu hình & chẩn đoán gửi email (quản trị viên)
    =================================================
    - Hiển thị trạng thái cấu hình SMTP hiện tại.
    - Gửi email thử tới một địa chỉ để xác nhận kết nối thật.
--}}
@extends('layouts.app')

@section('title', 'Cấu hình gửi email')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

    <div>
        <h1 class="text-3xl font-black text-[#2D82C4]">Cấu hình gửi email</h1>
        <p class="mt-2 text-sm text-[#4A5568]">Kiểm tra hệ thống có gửi được email cho khách hay không trước khi triển khai chính thức.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <section class="rounded-xl border border-[#D7E8F3] bg-white p-6">
        <h2 class="text-lg font-black text-[#102A43]">Trạng thái hiện tại</h2>

        <div class="mt-4 rounded-lg p-4 text-sm {{ $configuration['ok'] ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900' }}">
            {{ $configuration['message'] }}
        </div>

        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach($configuration['details'] as $label => $value)
                <div class="rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-3">
                    <dt class="text-xs font-bold uppercase text-[#718096]">{{ $label }}</dt>
                    <dd class="mt-1 break-all text-sm font-bold text-[#102A43]">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-3">
                <dt class="text-xs font-bold uppercase text-[#718096]">Mailer</dt>
                <dd class="mt-1 text-sm font-bold text-[#102A43]">{{ $configuration['mailer'] }}</dd>
            </div>
            <div class="rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-3">
                <dt class="text-xs font-bold uppercase text-[#718096]">Địa chỉ gửi (From)</dt>
                <dd class="mt-1 break-all text-sm font-bold text-[#102A43]">{{ $fromAddress ?: '(chưa cấu hình)' }} — {{ $fromName }}</dd>
            </div>
        </dl>

        <div class="mt-5 rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-4 text-xs leading-relaxed text-[#52637A]">
            <p class="font-bold text-[#102A43]">Cách cấu hình tài khoản gửi mail (Gmail)</p>
            <ol class="mt-2 list-decimal space-y-1 pl-5">
                <li>Bật Xác thực 2 bước cho tài khoản Gmail.</li>
                <li>Tạo "Mật khẩu ứng dụng" (App Password) cho tài khoản đó.</li>
                <li>Chạy lệnh sau (mật khẩu không bị ghi vào log phiên làm việc):</li>
            </ol>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-[#102A43] p-3 text-[#EAF4FB]">php artisan mail:configure --email=ten-tai-khoan@gmail.com --password="MAT-KHAU-UNG-DUNG" --name="Mind Guide"</pre>
            <p class="mt-2">Sau đó kiểm tra kết nối bằng nút bên dưới hoặc lệnh <code>php artisan mail:diagnose --to=email@example.com</code>.</p>
        </div>
    </section>

    <section class="rounded-xl border border-[#D7E8F3] bg-white p-6">
        <h2 class="text-lg font-black text-[#102A43]">Gửi email thử</h2>
        <p class="mt-1 text-xs text-[#52637A]">Gửi một email kiểm tra tới địa chỉ bạn muốn nhận — dùng để chắc chắn khách sẽ nhận được thư.</p>

        <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="email" class="mb-1.5 block text-xs font-bold text-[#102A43]">Địa chỉ nhận thử <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $fromAddress) }}" required
                       class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <button class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white">Gửi email thử</button>
        </form>
    </section>

</div>
@endsection
