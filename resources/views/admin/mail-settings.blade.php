@extends('layouts.admin')

@section('title', 'Cấu hình gửi email')

{{--
    Cấu hình & chẩn đoán gửi email (quản trị viên)
    =================================================
    - Hiển thị trạng thái cấu hình SMTP hiện tại.
    - Gửi email thử tới một địa chỉ để xác nhận kết nối thật.
--}}

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Cấu hình hệ thống</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Cấu hình gửi email</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[#52637A]">
                Kiểm tra hệ thống có gửi được email cho khách trước khi triển khai chính thức.
            </p>
        </div>

        <a href="{{ route('admin.settings') }}"
           class="inline-flex shrink-0 items-center gap-2 self-start rounded-lg border border-[#2D82C4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Về tham số vận hành
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    {{-- ═══════════════ TRẠNG THÁI ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <div class="flex items-center gap-2">
            <h2 class="text-lg font-black text-[#102A43]">Trạng thái hiện tại</h2>
            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                  style="color: {{ $configuration['ok'] ? '#15803D' : '#B45309' }}; background-color: {{ $configuration['ok'] ? '#ECFDF3' : '#FFF7E6' }}; border-color: {{ $configuration['ok'] ? '#BBF7D0' : '#FDE68A' }}">
                <span aria-hidden="true">{{ $configuration['ok'] ? '●' : '⚠' }}</span>
                {{ $configuration['ok'] ? 'Sẵn sàng gửi' : 'Cần kiểm tra' }}
            </span>
        </div>

        <div class="mt-4 rounded-lg p-4 text-sm font-semibold {{ $configuration['ok'] ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900' }}">
            {{ $configuration['message'] }}
        </div>

        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach($configuration['details'] as $label => $value)
                <div class="rounded-xl border border-[#E8F1F6] bg-[#F7FBFE] p-3.5">
                    <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">{{ $label }}</dt>
                    <dd class="mt-1 break-all text-sm font-bold text-[#102A43]">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="rounded-xl border border-[#E8F1F6] bg-[#F7FBFE] p-3.5">
                <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Mailer</dt>
                <dd class="mt-1 text-sm font-bold text-[#102A43]">{{ $configuration['mailer'] }}</dd>
            </div>
            <div class="rounded-xl border border-[#E8F1F6] bg-[#F7FBFE] p-3.5">
                <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Địa chỉ gửi (From)</dt>
                <dd class="mt-1 break-all text-sm font-bold text-[#102A43]">{{ $fromAddress ?: '(chưa cấu hình)' }} — {{ $fromName }}</dd>
            </div>
        </dl>

        <div class="mt-5 rounded-xl border border-[#E8F1F6] bg-[#F7FBFE] p-4 text-xs leading-relaxed text-[#52637A]">
            <p class="font-extrabold text-[#102A43]">Cách cấu hình tài khoản gửi mail (Gmail)</p>
            <ol class="mt-2 list-decimal space-y-1 pl-5">
                <li>Bật Xác thực 2 bước cho tài khoản Gmail.</li>
                <li>Tạo "Mật khẩu ứng dụng" (App Password) cho tài khoản đó.</li>
                <li>Chạy lệnh sau (mật khẩu không bị ghi vào log phiên làm việc):</li>
            </ol>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-[#102A43] p-3 text-[#EAF4FB]">php artisan mail:configure --email=ten-tai-khoan@gmail.com --password="MAT-KHAU-UNG-DUNG" --name="Mind Guide"</pre>
            <p class="mt-2">Sau đó kiểm tra kết nối bằng nút bên dưới hoặc lệnh <code class="rounded bg-white px-1 py-0.5 font-mono text-[11px]">php artisan mail:diagnose --to=email@example.com</code>.</p>
        </div>
    </section>

    {{-- ═══════════════ GỬI THỬ ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Gửi email thử</h2>
        <p class="mt-1 text-xs text-[#718096]">
            Gửi một email kiểm tra tới địa chỉ bạn muốn nhận — dùng để chắc chắn khách sẽ nhận được thư.
        </p>

        <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <label class="flex-1 text-xs font-bold text-[#52637A]">
                Địa chỉ nhận thử <span class="text-red-500" aria-hidden="true">*</span>
                <input type="email" name="email" value="{{ old('email', $fromAddress) }}" required maxlength="255"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>
            <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                Gửi email thử
            </button>
        </form>
    </section>
</div>
@endsection
