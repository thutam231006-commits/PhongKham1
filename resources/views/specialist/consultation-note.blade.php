@extends('layouts.specialist')

@section('title', 'Ghi nhận hồ sơ tư vấn')

@php
    $record = $appointment->consultationRecord;
    $totalChars = fn (?string $value): int => mb_strlen((string) $value);
@endphp

@section('content')
    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('specialist.appointments') }}"
           class="inline-flex items-center gap-1.5 text-sm font-bold text-[#2D82C4] hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
            Quay lại danh sách ca
        </a>

        <form method="POST"
              action="{{ route('specialist.consultation-note.store', $appointment->id) }}"
              class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white"
              x-data="{ summaryCount: {{ $totalChars(old('summary', $record?->summary)) }}, planCount: {{ $totalChars(old('follow_up_plan', $record?->follow_up_plan)) }} }">
            @csrf

            {{-- ── Đầu thẻ: định danh hồ sơ ── --}}
            <div class="flex flex-col gap-3 border-b border-[#E8F1F6] px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#EAF4FB] text-[#2D82C4]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-xl font-black text-[#102A43] sm:text-2xl">Hồ sơ tư vấn chuyên môn</h1>
                        <p class="mt-0.5 text-xs text-[#718096]">Chỉ dành cho chuyên viên được phân công</p>
                    </div>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2V9a2 2 0 00-2-2h-1V6a4 4 0 10-8 0v1H6a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Được mã hóa
                </span>
            </div>

            {{-- ── Thông tin ca ── --}}
            <div class="border-b border-[#E8F1F6] bg-[#F7FBFE] px-6 py-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Thông tin ca tư vấn</p>
                <p class="mt-1 text-base font-black text-[#102A43]">{{ $appointment->customer->name }}</p>
                <p class="mt-0.5 text-sm text-[#52637A]">
                    {{ $appointment->service->name }}
                    · {{ $appointment->start_at->format('H:i') }}–{{ $appointment->end_at->format('H:i') }}
                    · {{ $appointment->start_at->format('d/m/Y') }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-status-chip type="appointment" :status="$appointment->status" />
                    <span class="text-[11px] text-[#8CA3B8]">{{ $appointment->appointment_code }}</span>
                    @if($record)
                        <span class="rounded-full border border-[#D7E8F3] bg-white px-2 py-0.5 text-[11px] font-bold text-[#52637A]">
                            Phiên bản {{ $record->version }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="space-y-6 px-6 py-6">

                @if($errors->any())
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($record)
                    <input type="hidden" name="version" value="{{ $record->version }}">
                @endif

                {{-- 1. Tóm tắt buổi tư vấn --}}
                <div>
                    <div class="flex items-end justify-between gap-3">
                        <label for="summary" class="text-sm font-black text-[#102A43]">
                            1. Tóm tắt buổi tư vấn *
                        </label>
                        <span class="text-[11px] tabular-nums text-[#8CA3B8]" x-text="summaryCount + '/5000'"></span>
                    </div>
                    <textarea id="summary" name="summary" rows="7" maxlength="5000" required
                              x-on:input="summaryCount = $event.target.value.length"
                              placeholder="Tóm tắt nội dung chính của buổi tư vấn, diễn biến và nhận định chuyên môn…"
                              class="mt-2 w-full rounded-xl border-[#D7E8F3] text-sm leading-relaxed placeholder:text-[#B8D2E4] focus:border-[#2D82C4] focus:ring-[#2D82C4]">{{ old('summary', $record?->summary) }}</textarea>
                    <p class="mt-1.5 text-[11px] text-[#8CA3B8]">
                        Không nhập thông tin ngoài phạm vi chuyên môn hoặc nội dung không cần thiết.
                    </p>
                </div>

                {{-- 2. Kế hoạch theo dõi --}}
                <div>
                    <div class="flex items-end justify-between gap-3">
                        <label for="follow_up_plan" class="text-sm font-black text-[#102A43]">
                            2. Kế hoạch theo dõi
                        </label>
                        <span class="text-[11px] tabular-nums text-[#8CA3B8]" x-text="planCount + '/2000'"></span>
                    </div>
                    <textarea id="follow_up_plan" name="follow_up_plan" rows="5" maxlength="2000"
                              x-on:input="planCount = $event.target.value.length"
                              placeholder="Các bước tiếp theo, lịch hẹn lại hoặc bài tập theo dõi…"
                              class="mt-2 w-full rounded-xl border-[#D7E8F3] text-sm leading-relaxed placeholder:text-[#B8D2E4] focus:border-[#2D82C4] focus:ring-[#2D82C4]">{{ old('follow_up_plan', $record?->follow_up_plan) }}</textarea>
                </div>

                <label class="flex items-center gap-2.5 text-sm font-semibold text-[#334155]">
                    <input type="hidden" name="follow_up_needed" value="0">
                    <input type="checkbox" name="follow_up_needed" value="1"
                           @checked(old('follow_up_needed', $record?->follow_up_needed))
                           class="rounded border-[#D7E8F3] text-[#2D82C4] focus:ring-[#2D82C4]">
                    Cần theo dõi tiếp
                </label>

                {{-- 3. Bản tóm lược gửi khách hàng --}}
                <fieldset class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                    <legend class="px-2 text-sm font-black text-emerald-900">3. Bản tóm lược gửi khách hàng</legend>
                    <p class="text-[11px] leading-relaxed text-emerald-950/70">
                        Chỉ nội dung trong khối này hiển thị cho khách tại <strong>Lịch sử tư vấn &amp; Lời dặn</strong>.
                        Phần tóm tắt chuyên môn phía trên luôn được mã hóa và chỉ bạn đọc được.
                    </p>

                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="text-xs font-bold text-[#334155]">Nhận định gửi khách</span>
                            <textarea name="customer_summary" rows="4" maxlength="2000"
                                      placeholder="Tóm lược dễ hiểu, không đưa thông tin nhạy cảm…"
                                      class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm placeholder:text-[#B8D2E4] focus:border-emerald-500 focus:ring-emerald-500">{{ old('customer_summary', $record?->customer_summary) }}</textarea>
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-[#334155]">Khuyến nghị / bài tập gửi khách</span>
                            <textarea name="customer_recommendation" rows="4" maxlength="2000"
                                      placeholder="Hướng rèn luyện thực tế khách có thể áp dụng…"
                                      class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm placeholder:text-[#B8D2E4] focus:border-emerald-500 focus:ring-emerald-500">{{ old('customer_recommendation', $record?->customer_recommendation) }}</textarea>
                        </label>
                    </div>
                </fieldset>

                {{-- Thông tin bảo mật --}}
                <div class="rounded-xl border border-[#BFDBFE] bg-[#EAF4FB] p-4">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <div>
                            <h2 class="text-sm font-black text-[#1B5E91]">Thông tin bảo mật</h2>
                            <p class="mt-1 text-xs leading-relaxed text-[#23689C]">
                                Hồ sơ chỉ được lưu và đọc bởi người có quyền. Nội dung không xuất hiện trong
                                danh sách lịch hẹn, thông báo hay tài khoản Lễ tân. Mọi lần xem và sửa đều được ghi audit.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Hành động ── --}}
            <div class="flex flex-col gap-2 border-t border-[#E8F1F6] bg-[#F7FBFE] px-6 py-4 sm:flex-row sm:justify-end">
                <a href="{{ route('specialist.appointments') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-[#B8D2E4] bg-white px-5 py-2.5 text-sm font-bold text-[#52637A] transition hover:border-[#2D82C4] hover:text-[#2D82C4]">
                    Hủy thay đổi
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lưu hồ sơ
                </button>
            </div>
        </form>
    </div>
@endsection
