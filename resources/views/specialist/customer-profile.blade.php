@extends('layouts.app')

@section('title', 'Hồ sơ khách hàng')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 border-b border-[#D7E8F3] pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-[#6FA6CF]">Hồ sơ khách hàng</p>
            <h1 class="mt-1 text-3xl font-black text-[#102A43]">{{ $customer->name }}</h1>
            <p class="mt-2 text-sm text-[#52637A]">Mã khách: {{ $customer->customerProfile?->customer_code ?? 'Chưa cấp' }}</p>
        </div>
        <a href="{{ route('specialist.appointments') }}" class="rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4]">Quay lại lịch hẹn</a>
    </div>

    <div class="rounded-lg border border-[#6FA6CF]/30 bg-[#EAF4FB] px-4 py-3 text-sm text-[#2D82C4]">
        Nội dung hồ sơ tư vấn được mã hóa và chỉ chuyên viên phụ trách xem được. Mọi lần xem hồ sơ khách đều được ghi nhận.
    </div>

    {{-- Lịch hẹn của khách với chuyên viên này --}}
    <section class="overflow-hidden rounded-xl border border-[#D7E8F3] bg-white">
        <div class="border-b border-[#E8F1F6] px-5 py-4">
            <h2 class="text-lg font-black text-[#102A43]">Lịch hẹn đã phụ trách</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#F7FBFE] text-xs uppercase text-[#52637A]">
                    <tr>
                        <th class="px-5 py-3">Mã lịch</th>
                        <th class="px-5 py-3">Dịch vụ</th>
                        <th class="px-5 py-3">Thời gian</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3">Hồ sơ tư vấn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($appointments as $appointment)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('specialist.appointments.detail', $appointment->id) }}" class="font-bold text-[#2D82C4] hover:underline">{{ $appointment->appointment_code }}</a>
                            </td>
                            <td class="px-5 py-4">{{ $appointment->service->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-semibold">{{ $appointment->start_at->format('d/m/Y H:i') }} - {{ $appointment->end_at->format('H:i') }}</td>
                            <td class="px-5 py-4"><x-status-chip type="appointment" :status="$appointment->status" /></td>
                            <td class="px-5 py-4">
                                @if (in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true))
                                    <a href="{{ route('specialist.consultation-note', $appointment->id) }}" class="font-bold text-[#2D82C4] hover:underline">
                                        {{ $appointment->consultationRecord ? 'Xem / sửa' : 'Ghi hồ sơ' }}
                                    </a>
                                @else
                                    <span class="text-xs text-[#718096]">Chưa đến buổi tư vấn</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-[#718096]">Khách hàng chưa có lịch hẹn nào với bạn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Kết quả các buổi tư vấn tâm lý đã ghi nhận --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-black text-[#102A43]">Kết quả các buổi tư vấn</h2>
            <p class="mt-1 text-xs text-[#718096]">Nội dung chuyên môn của những buổi bạn phụ trách. Nội dung này không hiển thị cho khách hàng, Staff hay Admin.</p>
        </div>

        @forelse($consultations as $record)
            <article class="rounded-xl border border-[#D7E8F3] bg-white p-6">
                <div class="flex flex-col gap-3 border-b border-[#EEF4F8] pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-mono text-xs font-black text-[#2D82C4]">{{ $record->appointment?->appointment_code }}</p>
                        <h3 class="mt-1 font-bold text-[#102A43]">{{ $record->appointment?->service?->name }}</h3>
                        <p class="text-xs text-[#52637A]">
                            {{ $record->appointment?->start_at?->format('d/m/Y H:i') }} - {{ $record->appointment?->end_at?->format('H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($record->follow_up_needed)
                            <span class="rounded-full bg-[#FEF3C7] px-3 py-1 text-xs font-bold text-[#B45309]">Cần theo dõi tiếp</span>
                        @endif
                        <a href="{{ route('specialist.consultation-note', $record->appointment_id) }}" class="rounded-lg border border-[#2D82C4] px-3 py-1.5 text-xs font-bold text-[#2D82C4] hover:bg-[#EAF4FB]">Mở hồ sơ</a>
                    </div>
                </div>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-[#6FA6CF]">Tóm tắt buổi tư vấn</dt>
                        <dd class="mt-1.5 text-sm leading-relaxed text-[#334155]">{{ $record->summary }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-[#6FA6CF]">Kế hoạch theo dõi</dt>
                        <dd class="mt-1.5 text-sm leading-relaxed text-[#334155]">{{ $record->follow_up_plan ?: 'Chưa có kế hoạch theo dõi.' }}</dd>
                    </div>
                </dl>

                @if ($record->hasCustomerVisibleContent())
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Bản tóm lược đã gửi khách hàng</p>
                        <dl class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold text-emerald-900">Nhận định gửi khách</dt>
                                <dd class="mt-1 text-sm leading-relaxed text-[#334155]">{{ $record->customer_summary ?: 'Chưa gửi.' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-emerald-900">Khuyến nghị gửi khách</dt>
                                <dd class="mt-1 text-sm leading-relaxed text-[#334155]">{{ $record->customer_recommendation ?: 'Chưa gửi.' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-[#D7E8F3] bg-white p-12 text-center">
                <p class="text-sm font-semibold text-[#102A43]">Chưa có hồ sơ tư vấn nào.</p>
                <p class="mt-1 text-xs text-[#718096]">Sau khi bạn ghi hồ sơ cho một buổi tư vấn, kết quả sẽ hiển thị tại đây.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
