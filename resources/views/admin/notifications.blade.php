@extends('layouts.admin')

@section('title', 'Gửi thông báo thủ công')

@php
    $audiences = [
        'all' => ['label' => 'Tất cả tài khoản đang hoạt động', 'desc' => 'Mọi vai trò'],
        'customer' => ['label' => 'Khách hàng', 'desc' => 'Tài khoản có vai trò khách hàng'],
        'specialist' => ['label' => 'Chuyên viên', 'desc' => 'Tài khoản chuyên viên'],
        'staff' => ['label' => 'Lễ tân', 'desc' => 'Tài khoản vận hành'],
        'admin' => ['label' => 'Quản trị viên', 'desc' => 'Tài khoản quản trị'],
    ];
    $countByRole = $users->groupBy('role')->map->count();
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ thông báo</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Gửi thông báo thủ công</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Gửi thông báo hệ thống tới nhóm tài khoản. Nội dung <strong class="font-bold text-[#334E68]">không được chứa</strong>
            thông tin tư vấn, ghi chú riêng tư hoặc dữ liệu thanh toán của khách.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ═══════════════ FORM GỬI ═══════════════ --}}
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] lg:col-span-2">
            <h2 class="text-lg font-black text-[#102A43]">Soạn thông báo</h2>

            <form method="POST" action="{{ route('admin.notifications.store') }}" class="mt-4 space-y-4">
                @csrf

                <label class="block text-xs font-bold text-[#52637A]">
                    Đối tượng nhận <span class="text-red-500" aria-hidden="true">*</span>
                    <select name="audience" required class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                        @foreach($audiences as $value => $audience)
                            <option value="{{ $value }}" @selected(old('audience', 'all') === $value)>
                                {{ $audience['label'] }}
                                ({{ $value === 'all' ? $users->count() : ($countByRole[$value] ?? 0) }} tài khoản)
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-xs font-bold text-[#52637A]">
                    Tiêu đề <span class="text-red-500" aria-hidden="true">*</span>
                    <input name="title" value="{{ old('title') }}" required maxlength="150"
                           placeholder="VD: Lịch nghỉ lễ Quốc khánh 2/9"
                           class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                </label>

                <label class="block text-xs font-bold text-[#52637A]">
                    Nội dung <span class="text-red-500" aria-hidden="true">*</span>
                    <textarea name="message" required maxlength="1000" rows="6"
                              placeholder="Nội dung ngắn gọn, rõ ràng, không chứa thông tin riêng tư…"
                              class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">{{ old('message') }}</textarea>
                    <span class="mt-1 block text-[10px] font-semibold text-[#8A9BB0]">Tối đa 1.000 ký tự.</span>
                </label>

                <div class="rounded-lg border border-amber-200 bg-amber-50/60 px-3.5 py-3">
                    <p class="text-[11px] font-extrabold uppercase tracking-wide text-[#B45309]">Nguyên tắc nội dung</p>
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-[11px] leading-relaxed text-[#8A6A3F]">
                        <li>Không nêu chi tiết buổi tư vấn hay chẩn đoán của khách.</li>
                        <li>Không đính kèm số tài khoản, mã giao dịch hoặc dữ liệu thanh toán.</li>
                        <li>Không hứa hẹn kết quả điều trị.</li>
                    </ul>
                </div>

                <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Gửi thông báo
                </button>
            </form>
        </section>

        {{-- ═══════════════ THỐNG KÊ ĐỐI TƯỢNG ═══════════════ --}}
        <aside class="space-y-4">
            <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
                <h2 class="text-sm font-black text-[#102A43]">Người nhận khả dụng</h2>
                <p class="mt-1 text-[11px] text-[#8A9BB0]">Chỉ tài khoản đang hoạt động nhận được thông báo.</p>

                <ul class="mt-3 space-y-2">
                    @foreach($audiences as $value => $audience)
                        @php
                            $count = $value === 'all' ? $users->count() : ($countByRole[$value] ?? 0);
                        @endphp
                        <li class="flex items-center justify-between gap-2 rounded-lg bg-[#FBFDFF] px-3 py-2">
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-bold text-[#334E68]">{{ $audience['label'] }}</span>
                                <span class="block text-[10px] text-[#8A9BB0]">{{ $audience['desc'] }}</span>
                            </span>
                            <span class="shrink-0 rounded-full bg-[#EAF4FB] px-2 py-0.5 text-[11px] font-black tabular-nums text-[#23689C]">
                                {{ $count }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
                <h2 class="text-sm font-black text-[#102A43]">Ghi nhận kiểm toán</h2>
                <p class="mt-1.5 text-[11px] leading-relaxed text-[#52637A]">
                    Mỗi lần gửi được ghi vào nhật ký với đối tượng và số người nhận.
                    Nội dung thông báo không chứa dữ liệu y tế.
                </p>
            </section>
        </aside>
    </div>
</div>
@endsection
