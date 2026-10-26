@extends('layouts.specialist')

@section('title', 'Chi tiết ca tư vấn')

@section('content')
    @php
        $allowedStartAt = $appointment->start_at->copy()->subMinutes(15);
        $canStart = $appointment->status === 'CONFIRMED' && now()->gte($allowedStartAt);
        $canNoShow = $appointment->status === 'CONFIRMED' && now()->gte($appointment->start_at->copy()->addMinutes(15));
        $canWriteRecord = in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true);
    @endphp

    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        <a href="{{ route('specialist.appointments') }}"
           class="inline-flex items-center gap-1.5 text-sm font-bold text-[#2D82C4] hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
            Quay lại danh sách ca
        </a>

        {{-- Thông tin ca --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
            <div class="flex flex-col gap-3 border-b border-[#E8F1F6] px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="font-mono text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">{{ $appointment->appointment_code }}</p>
                    <h1 class="mt-1 text-2xl font-black text-[#102A43]">{{ $appointment->customer->name }}</h1>
                    <p class="mt-1 text-sm text-[#52637A]">{{ $appointment->service->name }}</p>
                </div>
                <x-status-chip type="appointment" :status="$appointment->status" size="md" />
            </div>

            <dl class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Thời gian</dt>
                    <dd class="mt-1 font-bold tabular-nums text-[#102A43]">
                        {{ $appointment->start_at->format('d/m/Y H:i') }} – {{ $appointment->end_at->format('H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Hình thức</dt>
                    <dd class="mt-1 font-semibold text-[#102A43]">
                        {{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : 'Trực tiếp tại phòng khám' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Phòng / phòng họp</dt>
                    <dd class="mt-1 font-semibold text-[#102A43]">
                        @if($appointment->consultation_type === 'ONLINE')
                            @if($appointment->meeting_url)
                                <a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener"
                                   class="font-bold text-[#2D82C4] hover:underline">Vào phòng họp trực tuyến →</a>
                            @else
                                <span class="text-[#8CA3B8]">Sẽ có sau khi lịch được xác nhận</span>
                            @endif
                        @else
                            {{ $appointment->consultationRoom?->name ?? 'Chưa xếp phòng' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Check-in</dt>
                    <dd class="mt-1 font-semibold text-[#102A43]">
                        {{ $appointment->checked_in_at ? $appointment->checked_in_at->format('H:i d/m/Y') : 'Khách chưa check-in' }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Ghi chú của khách</dt>
                    <dd class="mt-1 text-sm leading-relaxed text-[#334155]">
                        {{ $appointment->customer_note ?: 'Khách không để lại ghi chú.' }}
                    </dd>
                </div>
            </dl>

            {{-- Thao tác --}}
            <div class="flex flex-wrap items-center gap-2 border-t border-[#E8F1F6] bg-[#F7FBFE] px-6 py-4">
                @if($appointment->status === 'CONFIRMED')
                    <form method="POST" action="{{ route('specialist.appointments.start', $appointment->id) }}">
                        @csrf
                        <button type="submit" @disabled(! $canStart)
                                title="{{ $canStart ? 'Bắt đầu ca tư vấn' : 'Chỉ có thể bắt đầu từ 15 phút trước giờ hẹn' }}"
                                class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition enabled:hover:bg-[#23689C] disabled:cursor-not-allowed disabled:bg-gray-300">
                            Bắt đầu ca tư vấn
                        </button>
                    </form>
                    <form method="POST" action="{{ route('specialist.appointments.no-show', $appointment->id) }}"
                          onsubmit="return confirm('Ghi nhận khách vắng mặt cho ca này?');">
                        @csrf
                        <button type="submit" @disabled(! $canNoShow)
                                title="{{ $canNoShow ? 'Ghi nhận vắng mặt' : 'Chỉ ghi nhận sau 15 phút kể từ giờ hẹn' }}"
                                class="rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-bold text-red-700 transition enabled:hover:bg-red-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400">
                            Ghi nhận vắng mặt
                        </button>
                    </form>
                @elseif($appointment->status === 'IN_PROGRESS')
                    <form method="POST" action="{{ route('specialist.appointments.complete', $appointment->id) }}"
                          onsubmit="return confirm('Hoàn thành ca và chuyển sang ghi hồ sơ?');">
                        @csrf
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                            Hoàn thành ca
                        </button>
                    </form>
                @endif

                @if($canWriteRecord)
                    <a href="{{ route('specialist.consultation-note', $appointment->id) }}"
                       class="rounded-lg border border-[#2D82C4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                        {{ $appointment->consultationRecord ? 'Xem / sửa hồ sơ tư vấn' : 'Ghi hồ sơ tư vấn' }}
                    </a>
                @endif

                <a href="{{ route('specialist.customers.profile', $appointment->customer_id) }}"
                   class="rounded-lg border border-[#D7E8F3] bg-white px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:border-[#B8D2E4] hover:text-[#2D82C4]">
                    Hồ sơ khách hàng
                </a>
            </div>

            {{-- Gửi thông tin buổi tư vấn cho khách hàng — chỉ khách nhận --}}
            <details class="group border-t border-[#E8F1F6] bg-white px-6 py-4">
                <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-bold text-[#2D82C4]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Gửi thông tin buổi tư vấn cho khách
                    <svg class="h-4 w-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>

                <form method="POST" action="{{ route('specialist.appointments.send-information', $appointment->id) }}"
                      class="mt-3 space-y-3">
                    @csrf
                    <p class="text-xs leading-relaxed text-[#52637A]">
                        Email kèm thông báo trong ứng dụng sẽ chỉ gửi tới
                        <strong class="text-[#102A43]">{{ $appointment->customer->email ?: 'khách chưa có email' }}</strong>.
                        Nội dung gồm mã lịch, thời gian, phòng/hình thức, chuyên viên và lưu ý chuẩn bị.
                    </p>
                    <label class="block">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Ghi chú thêm (không bắt buộc)</span>
                        <textarea name="note" rows="2" maxlength="500"
                                  class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]"
                                  placeholder="Ví dụ: Vui lòng mang theo kết quả xét nghiệm lần trước."></textarea>
                    </label>
                    <button type="submit"
                            @disabled(blank($appointment->customer->email))
                            class="rounded-lg bg-[#1B5E91] px-4 py-2.5 text-sm font-bold text-white transition enabled:hover:bg-[#134A72] disabled:cursor-not-allowed disabled:bg-gray-300">
                        Gửi cho khách hàng
                    </button>
                    @if(blank($appointment->customer->email))
                        <p class="text-xs font-semibold text-red-600">
                            Khách chưa có email. Hãy cập nhật email trong hồ sơ khách hàng trước khi gửi.
                        </p>
                    @endif
                </form>
            </details>

            @if($appointment->status === 'CONFIRMED' && ! $canStart)
                <p class="border-t border-[#E8F1F6] bg-white px-6 py-3 text-xs text-[#718096]">
                    Nút bắt đầu mở từ {{ $allowedStartAt->format('d/m/Y H:i') }}. Nếu đã qua giờ hẹn, bạn vẫn có thể bắt đầu muộn
                    trong ngày; nếu khách chưa check-in, hãy phối hợp với Lễ tân.
                </p>
            @endif
        </section>

        {{-- Lịch sử trạng thái --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
            <div class="border-b border-[#E8F1F6] px-6 py-4">
                <h2 class="text-lg font-black text-[#102A43]">Lịch sử trạng thái</h2>
            </div>
            <ul class="divide-y divide-[#EEF4F8]">
                @forelse($appointment->statusHistory->sortByDesc('created_at') as $history)
                    <li class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                        <span class="font-bold text-[#2D82C4]">{{ \App\Support\AppointmentStatus::label((string) $history->to_status) }}</span>
                        <span class="text-xs text-[#8CA3B8]">{{ optional($history->created_at)->format('d/m/Y H:i') }}</span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-[#718096]">Chưa có lịch sử trạng thái.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
