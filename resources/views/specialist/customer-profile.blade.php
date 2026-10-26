@extends('layouts.specialist')

@section('title', 'Hồ sơ tư vấn của khách')

@section('content')
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ══════════════════════════════════════════════════════════
             TIÊU ĐỀ KHÁCH HÀNG
             ══════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col gap-4 border-b border-[#D7E8F3] pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-start gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#EAF4FB] text-xl font-black uppercase text-[#2D82C4]">
                    {{ mb_substr($customer->name, 0, 1) }}
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Hồ sơ tư vấn</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">{{ $customer->name }}</h1>
                    <p class="mt-1 text-sm text-[#52637A]">
                        Mã khách: {{ $customer->customerProfile?->customer_code ?? 'Chưa cấp' }}
                        · {{ $appointments->count() }} buổi do bạn phụ trách
                    </p>
                </div>
            </div>
            <a href="{{ route('specialist.appointments') }}"
               class="inline-flex shrink-0 items-center gap-2 self-start rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:border-[#2D82C4] hover:bg-[#EAF4FB]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                Quay lại ca tư vấn
            </a>
        </div>

        <div class="rounded-xl border border-[#BFDBFE] bg-[#EAF4FB] px-4 py-3 text-xs leading-relaxed text-[#23689C]">
            Nội dung hồ sơ tư vấn được mã hóa và chỉ chuyên viên phụ trách xem được.
            Mọi lần xem hồ sơ khách đều được ghi nhận trong nhật ký hệ thống.
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ══════════════════════════════════════════════════════
                 CỘT CHÍNH — KẾT QUẢ CÁC BUỔI TƯ VẤN
                 ══════════════════════════════════════════════════════ --}}
            <section class="space-y-4 lg:col-span-2" aria-labelledby="consultation-results-heading">
                <div>
                    <h2 id="consultation-results-heading" class="text-lg font-black text-[#102A43]">Kết quả các buổi tư vấn</h2>
                    <p class="mt-1 text-xs text-[#718096]">
                        Nội dung chuyên môn của những buổi bạn phụ trách. Không hiển thị cho khách hàng, Lễ tân hay Quản trị.
                    </p>
                </div>

                @forelse($consultations as $record)
                    <article class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                        <div class="flex flex-col gap-2 border-b border-[#E8F1F6] bg-[#F7FBFE] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-mono text-[11px] font-bold text-[#2D82C4]">{{ $record->appointment?->appointment_code }}</p>
                                <h3 class="mt-0.5 text-sm font-black text-[#102A43]">{{ $record->appointment?->service?->name }}</h3>
                                <p class="text-[11px] text-[#8CA3B8]">
                                    {{ $record->appointment?->start_at?->format('d/m/Y H:i') }} – {{ $record->appointment?->end_at?->format('H:i') }}
                                    · Phiên bản {{ $record->version }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                @if($record->follow_up_needed)
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800">Cần theo dõi tiếp</span>
                                @endif
                                <a href="{{ route('specialist.consultation-note', $record->appointment_id) }}"
                                   class="rounded-lg border border-[#2D82C4] bg-white px-3 py-1.5 text-[11px] font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                                    Mở hồ sơ
                                </a>
                            </div>
                        </div>

                        <dl class="grid gap-5 px-5 py-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-[#6FA6CF]">Tóm tắt buổi tư vấn</dt>
                                <dd class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-[#334155]">{{ $record->summary }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-[#6FA6CF]">Kế hoạch theo dõi</dt>
                                <dd class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-[#334155]">{{ $record->follow_up_plan ?: 'Chưa có kế hoạch theo dõi.' }}</dd>
                            </div>
                        </dl>

                        @if($record->hasCustomerVisibleContent())
                            <div class="mx-5 mb-5 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-800">Bản tóm lược đã gửi khách hàng</p>
                                <dl class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-[11px] font-semibold text-emerald-900">Nhận định gửi khách</dt>
                                        <dd class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#334155]">{{ $record->customer_summary ?: 'Chưa gửi.' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-semibold text-emerald-900">Khuyến nghị gửi khách</dt>
                                        <dd class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#334155]">{{ $record->customer_recommendation ?: 'Chưa gửi.' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#D7E8F3] bg-white px-5 py-14 text-center">
                        <svg class="mx-auto h-10 w-10 text-[#B8D2E4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-3 text-sm font-bold text-[#102A43]">Chưa có hồ sơ tư vấn nào</p>
                        <p class="mt-1 text-xs text-[#718096]">
                            Sau khi bạn ghi hồ sơ cho một buổi tư vấn, kết quả sẽ hiển thị tại đây.
                        </p>
                    </div>
                @endforelse
            </section>

            {{-- ══════════════════════════════════════════════════════
                 CỘT PHỤ — LỊCH SỬ HỒ SƠ
                 ══════════════════════════════════════════════════════ --}}
            <aside class="space-y-4" aria-labelledby="appointment-history-heading">
                <div class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                    <div class="border-b border-[#E8F1F6] px-5 py-4">
                        <h2 id="appointment-history-heading" class="text-sm font-black uppercase tracking-wide text-[#52637A]">Lịch sử hồ sơ</h2>
                    </div>

                    <ul class="divide-y divide-[#EEF4F8]">
                        @forelse($appointments as $appointment)
                            <li class="px-5 py-3">
                                <a href="{{ route('specialist.appointments.detail', $appointment->id) }}"
                                   class="block rounded-lg transition hover:bg-[#F7FBFE]">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold tabular-nums text-[#8CA3B8]">
                                                {{ $appointment->start_at->format('d/m/Y') }}
                                                · {{ $appointment->start_at->format('H:i') }}
                                            </p>
                                            <p class="mt-0.5 truncate text-sm font-bold text-[#102A43]">{{ $appointment->service->name }}</p>
                                        </div>
                                        @if($appointment->consultationRecord)
                                            <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Đã ghi</span>
                                        @elseif($appointment->status === 'COMPLETED')
                                            <span class="shrink-0 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800">Chưa ghi</span>
                                        @else
                                            <span class="shrink-0 rounded-full border border-[#D7E8F3] bg-[#F7FBFE] px-2 py-0.5 text-[10px] font-bold text-[#52637A]">Sắp tới</span>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-xs text-[#718096]">
                                Khách hàng chưa có lịch hẹn nào với bạn.
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-[#52637A]">Ghi chú nghiệp vụ</h2>
                    <ul class="mt-2.5 space-y-2 text-xs leading-relaxed text-[#52637A]">
                        <li class="flex gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#6FA6CF]" aria-hidden="true"></span>
                            Chỉ ghi nhận hồ sơ cho ca của bạn.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#6FA6CF]" aria-hidden="true"></span>
                            Hồ sơ đã lưu được giữ theo phiên bản, không xóa hẳn.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#6FA6CF]" aria-hidden="true"></span>
                            Bản tóm lược gửi khách nằm trong khối riêng khi ghi hồ sơ.
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
@endsection
