@extends('layouts.staff')

@section('title', 'Tổng quan & Điều phối')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8" x-data="staffSearch()">

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Quầy lễ tân</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43]">Tổng quan & Điều phối</h1>
            <p class="mt-1 text-sm text-[#52637A]">Màn hình làm việc hằng ngày — tìm khách, check-in, thu tiền và xử lý lịch hẹn.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('staff.appointments.book') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#23689C] transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Đặt lịch hộ
            </a>
            <a href="{{ route('staff.appointments') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-[#2D82C4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] hover:bg-[#EAF4FB] transition">
                Điều phối lịch
            </a>
        </div>
    </div>

    {{-- ── KHU VỰC TÌM KIẾM KHÁCH TẠI QUẦY ── --}}
    <section class="rounded-2xl border border-[#B8D2E4] bg-white p-5 shadow-sm" aria-labelledby="search-heading">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="search-heading" class="text-lg font-black text-[#102A43]">Tìm khách tại quầy</h2>
                <p class="mt-0.5 text-sm text-[#52637A]">Nhập họ tên, số điện thoại hoặc email. Không cần mã QR.</p>
            </div>
            <a href="{{ route('staff.checkin') }}" class="text-sm font-bold text-[#2D82C4] hover:underline whitespace-nowrap">Mở màn hình check-in →</a>
        </div>

        <div class="relative mt-4">
            <div class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                <svg class="h-4.5 w-4.5 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
            </div>
            <input x-model="q" @input.debounce.300ms="search"
                   type="search"
                   placeholder="Nhập ít nhất 2 ký tự để tìm khách hôm nay…"
                   class="w-full rounded-xl border border-[#B8D2E4] bg-[#F7FBFE] py-3 pl-10 pr-4 text-sm text-[#102A43] placeholder:text-[#8A9BB0] focus:border-[#2D82C4] focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20">
            <span x-show="loading" class="absolute right-4 top-3 text-xs text-[#718096]">Đang tìm…</span>
        </div>

        {{-- Search results --}}
        <div x-show="q.length >= 2" class="mt-4 space-y-2">
            <template x-for="item in results" :key="item.id">
                <article class="flex flex-col gap-4 rounded-xl border border-[#D7E8F3] bg-[#F7FBFE] p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="font-black text-[#102A43]" x-text="item.customer.name"></p>
                        <p class="mt-0.5 text-xs text-[#52637A]" x-text="item.customer.email + ' · ' + (item.customer.phone || 'Chưa có số điện thoại')"></p>
                        <p class="mt-2 text-sm font-semibold text-[#2D82C4]" x-text="item.service"></p>
                        <p class="mt-0.5 text-xs text-[#52637A]" x-text="item.specialist + ' · ' + item.time"></p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold ring-1 ring-[#D7E8F3]"
                              :class="item.payment_status === 'UNPAID' ? 'text-amber-700' : 'text-emerald-700'"
                              x-text="item.payment_status === 'UNPAID' ? 'Chưa thanh toán' : 'Đã thanh toán'"></span>
                        <template x-if="item.checked_in">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Đã check-in</span>
                        </template>
                        <template x-if="!item.checked_in">
                            <form :action="'{{ url('/staff/checkin') }}/' + item.id" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button class="rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white hover:bg-[#23689C] transition">
                                    Xác thực &amp; Check-in
                                </button>
                            </form>
                        </template>
                    </div>
                </article>
            </template>
            <p x-show="!loading && results.length === 0"
               class="rounded-xl bg-[#F7FBFE] px-4 py-6 text-center text-sm text-[#718096]">
                Không tìm thấy lịch hôm nay. Có thể
                <a href="{{ route('staff.appointments.book') }}" class="font-bold text-[#2D82C4] hover:underline">đặt lịch hộ khách</a>.
            </p>
        </div>
    </section>

    {{-- ── 4 THẺ THỐNG KÊ HÀNH ĐỘNG ── --}}
    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Hàng đợi vận hành">
        <a href="{{ route('staff.appointments.pending') }}"
           class="group rounded-xl border-2 border-amber-300 bg-amber-50 p-5 hover:shadow-md transition">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Chờ xác nhận</p>
            <p class="mt-2 text-4xl font-black text-amber-800">{{ $pending }}</p>
            <p class="mt-1 text-xs text-amber-700">Kiểm tra và xác nhận →</p>
        </a>
        <a href="{{ route('staff.checkin', ['date' => $date]) }}"
           class="group rounded-xl border border-[#D7E8F3] bg-white p-5 hover:shadow-md transition">
            <p class="text-xs font-bold uppercase tracking-wide text-[#52637A]">Khách chờ check-in</p>
            <p class="mt-2 text-4xl font-black text-[#2D82C4]">{{ $pendingCheckins }}</p>
            <p class="mt-1 text-xs text-[#718096]">Lịch trực tiếp hôm nay →</p>
        </a>
        <a href="{{ route('staff.appointments.attention') }}"
           class="group rounded-xl border border-orange-200 bg-orange-50 p-5 hover:shadow-md transition">
            <p class="text-xs font-bold uppercase tracking-wide text-orange-700">Cần xử lý</p>
            <p class="mt-2 text-4xl font-black text-orange-700">{{ $needsAttention }}</p>
            <p class="mt-1 text-xs text-orange-600">Lịch bị ảnh hưởng →</p>
        </a>
        <a href="{{ route('staff.payments') }}"
           class="group rounded-xl border border-emerald-200 bg-emerald-50 p-5 hover:shadow-md transition">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Chờ thu tiền mặt</p>
            <p class="mt-2 text-4xl font-black text-emerald-800">{{ $cashToCollect }}</p>
            <p class="mt-1 text-xs text-emerald-700">Sau khi hoàn tất buổi →</p>
        </a>
    </section>

    {{-- ── LỊCH HÔM NAY + HÀNG ĐỢI ── --}}
    <div class="grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">

        {{-- Lịch làm việc hôm nay --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="today-heading">
            <div class="flex items-center justify-between border-b border-[#E8F1F6] px-5 py-4">
                <div>
                    <h2 id="today-heading" class="text-lg font-black text-[#102A43]">Lịch làm việc hôm nay</h2>
                    <p class="mt-0.5 text-xs text-[#718096]">
                        {{ $today->count() }} lịch · Ngày {{ \Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('staff.appointments', ['date' => $date]) }}" class="text-xs font-bold text-[#2D82C4] hover:underline">Xem toàn bộ →</a>
            </div>
            @forelse($today as $appointment)
            <div class="flex items-center justify-between gap-4 border-b border-[#EEF4F8] px-5 py-3.5 last:border-0 hover:bg-[#F7FBFE] transition">
                <div class="flex min-w-0 items-start gap-4">
                    <time class="w-12 shrink-0 text-sm font-black text-[#2D82C4]">{{ $appointment->start_at->format('H:i') }}</time>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                        <p class="mt-0.5 truncate text-xs text-[#52637A]">{{ $appointment->service->name }} · {{ $appointment->specialistProfile->user->name }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="hidden text-xs text-[#718096] sm:inline">
                        {{ $appointment->consultation_type === 'ONLINE' ? 'Online' : 'Tại phòng' }}
                    </span>
                    @if($appointment->checked_in_at)
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Đã đến</span>
                    @else
                        <span class="rounded-full bg-[#EAF4FB] px-2.5 py-1 text-xs font-bold text-[#2D82C4]">
                            {{ \App\Support\AppointmentStatus::label($appointment->status) }}
                        </span>
                    @endif
                </div>
            </div>
            @empty
            <p class="px-5 py-12 text-center text-sm text-[#718096]">Hôm nay chưa có lịch hẹn.</p>
            @endforelse
        </section>

        {{-- Hàng đợi cần xử lý --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="queue-heading">
            <div class="border-b border-[#E8F1F6] px-5 py-4">
                <h2 id="queue-heading" class="text-lg font-black text-[#102A43]">Hàng đợi cần xử lý</h2>
                <p class="mt-0.5 text-xs text-[#718096]">Ưu tiên các việc ảnh hưởng trực tiếp đến khách.</p>
            </div>
            <div class="divide-y divide-[#EEF4F8]">
                <a href="{{ route('staff.appointments.pending') }}" class="flex items-center justify-between px-5 py-4 hover:bg-[#F7FBFE] transition">
                    <span>
                        <strong class="block text-sm text-[#102A43]">Duyệt lịch mới</strong>
                        <small class="text-xs text-[#718096]">Xác nhận hoặc chuyển chuyên viên</small>
                    </span>
                    <b class="text-xl font-black text-amber-600">{{ $pending }}</b>
                </a>
                <a href="{{ route('staff.appointments.attention') }}" class="flex items-center justify-between px-5 py-4 hover:bg-[#F7FBFE] transition">
                    <span>
                        <strong class="block text-sm text-[#102A43]">Lịch bị ảnh hưởng</strong>
                        <small class="text-xs text-[#718096]">Điều phối lại chuyên viên/khung giờ</small>
                    </span>
                    <b class="text-xl font-black text-orange-600">{{ $needsAttention }}</b>
                </a>
                <a href="{{ route('staff.appointments', ['status' => 'RESCHEDULE_REQUESTED']) }}" class="flex items-center justify-between px-5 py-4 hover:bg-[#F7FBFE] transition">
                    <span>
                        <strong class="block text-sm text-[#102A43]">Yêu cầu đổi lịch</strong>
                        <small class="text-xs text-[#718096]">Duyệt hoặc từ chối</small>
                    </span>
                    <b class="text-xl font-black text-[#2D82C4]">{{ $rescheduleRequests }}</b>
                </a>
                <a href="{{ route('staff.payments') }}" class="flex items-center justify-between px-5 py-4 hover:bg-[#F7FBFE] transition">
                    <span>
                        <strong class="block text-sm text-[#102A43]">Thu tiền mặt</strong>
                        <small class="text-xs text-[#718096]">Kiểm tra lịch đã hoàn tất</small>
                    </span>
                    <b class="text-xl font-black text-emerald-700">{{ $cashToCollect }}</b>
                </a>
            </div>
        </section>
    </div>

    {{-- ── LỊCH CẦN XỬ LÝ + THU TIỀN TẠI QUẦY ── --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-sm" aria-labelledby="attention-heading">
            <div class="flex items-center justify-between border-b border-orange-100 bg-orange-50/60 px-5 py-4">
                <div>
                    <h2 id="attention-heading" class="text-lg font-black text-[#102A43]">Lịch cần xử lý</h2>
                    <p class="mt-0.5 text-xs text-[#718096]">Lịch bị gắn cờ do chuyên viên nghỉ hoặc thay đổi lịch.</p>
                </div>
                <a href="{{ route('staff.appointments.attention') }}" class="text-xs font-bold text-orange-700 hover:underline">Mở danh sách →</a>
            </div>
            @forelse($attentionAppointments as $appointment)
            <a href="{{ route('staff.appointments.detail', $appointment) }}" class="block border-b border-orange-50 px-5 py-3.5 hover:bg-orange-50/50 transition last:border-0">
                <p class="text-sm font-bold text-[#102A43]">{{ $appointment->customer->name }} · {{ $appointment->service->name }}</p>
                <p class="mt-0.5 text-xs text-[#718096]">{{ $appointment->start_at->format('d/m/Y H:i') }} · {{ $appointment->specialistProfile->user->name }}</p>
            </a>
            @empty
            <p class="px-5 py-8 text-center text-sm text-[#718096]">Không có lịch bị ảnh hưởng.</p>
            @endforelse
        </section>

        <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm" aria-labelledby="cash-heading">
            <div class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/60 px-5 py-4">
                <div>
                    <h2 id="cash-heading" class="text-lg font-black text-[#102A43]">Thu tiền tại quầy</h2>
                    <p class="mt-0.5 text-xs text-[#718096]">Chỉ xác nhận sau khi đã nhận tiền thực tế.</p>
                </div>
                <a href="{{ route('staff.payments') }}" class="text-xs font-bold text-emerald-700 hover:underline">Mở thanh toán →</a>
            </div>
            @forelse($cashAppointments as $appointment)
            <div class="flex items-center justify-between gap-3 border-b border-emerald-50 px-5 py-3.5 last:border-0">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                    <p class="mt-0.5 truncate text-xs text-[#718096]">{{ $appointment->service->name }} · {{ number_format((int) $appointment->price, 0, ',', '.') }} VNĐ</p>
                </div>
                <a href="{{ route('staff.payments') }}" class="shrink-0 rounded-lg border border-emerald-300 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50 transition">Xử lý</a>
            </div>
            @empty
            <p class="px-5 py-8 text-center text-sm text-[#718096]">Không có khoản tiền mặt chờ thu.</p>
            @endforelse
        </section>
    </div>

    {{-- ── THỐNG KÊ BỔ SUNG ── --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Tải lịch bác sĩ --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="load-heading">
            <div class="border-b border-[#E8F1F6] px-5 py-4">
                    <h2 id="load-heading" class="text-lg font-black text-[#102A43]">Tải lịch bác sĩ</h2>
                <p class="mt-0.5 text-xs text-[#718096]">Số khách và slot còn trống hôm nay.</p>
            </div>
            <div class="divide-y divide-[#EEF4F8]">
                @forelse($specialistLoad as $load)
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm font-bold text-[#102A43]">{{ $load->name }}</span>
                    <span class="text-xs text-[#52637A]">{{ $load->booked }}/{{ $load->capacity }} · còn {{ $load->open }}</span>
                </div>
                @empty
                <p class="px-5 py-6 text-center text-sm text-[#718096]">Chưa có dữ liệu tải lịch.</p>
                @endforelse
            </div>
        </section>

        {{-- Lưu lượng theo giờ --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="traffic-heading">
            <div class="border-b border-[#E8F1F6] px-5 py-4">
                <h2 id="traffic-heading" class="text-lg font-black text-[#102A43]">Lưu lượng theo giờ</h2>
                <p class="mt-0.5 text-xs text-[#718096]">Giờ cao điểm: {{ $peakHour['hour'] ?? 'Chưa xác định' }}{{ isset($peakHour['hour']) ? ':00' : '' }}</p>
            </div>
            <div class="space-y-2.5 px-5 py-4">
                @forelse($hourlyTraffic as $traffic)
                <div class="flex items-center gap-3 text-xs">
                    <span class="w-12 shrink-0 font-semibold text-[#52637A]">{{ $traffic['hour'] }}:00</span>
                    <div class="flex-1 overflow-hidden rounded-full bg-[#EAF4FB]">
                        @php $maxTraffic = collect($hourlyTraffic)->max('total') ?: 1; @endphp
                        <div class="h-2 rounded-full bg-[#2D82C4]" style="width: {{ round($traffic['total'] / $maxTraffic * 100) }}%"></div>
                    </div>
                    <span class="w-8 text-right font-bold text-[#2D82C4]">{{ $traffic['total'] }}</span>
                </div>
                @empty
                <p class="text-center text-sm text-[#718096]">Chưa có dữ liệu.</p>
                @endforelse
            </div>
        </section>

        {{-- Thông báo mới --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="notif-heading">
            <div class="flex items-center justify-between border-b border-[#E8F1F6] px-5 py-4">
                <div>
                    <h2 id="notif-heading" class="text-lg font-black text-[#102A43]">Thông báo mới</h2>
                    <p class="mt-0.5 text-xs text-[#718096]">{{ $unreadNotifications }} chưa đọc · {{ $newCustomers }} Khách mới 7 ngày</p>
                </div>
                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-[#2D82C4] hover:underline">Xem tất cả</a>
            </div>
            @forelse($notifications->take(3) as $notification)
            <a href="{{ route('notifications.read', $notification->id) }}" class="block border-b border-[#EEF4F8] px-5 py-3.5 hover:bg-[#F7FBFE] transition last:border-0">
                <p class="text-xs font-bold text-[#102A43]">{{ $notification->data['title'] ?? 'Thông báo' }}</p>
                <p class="mt-0.5 truncate text-xs text-[#718096]">{{ $notification->data['message'] ?? '' }}</p>
            </a>
            @empty
            <p class="px-5 py-8 text-center text-sm text-[#718096]">Chưa có thông báo.</p>
            @endforelse
        </section>
    </div>
</div>

@push('scripts')
<script>
function staffSearch() {
    return {
        q: '',
        loading: false,
        results: [],
        timer: null,
        search() {
            clearTimeout(this.timer);
            if (this.q.trim().length < 2) { this.results = []; return; }
            this.loading = true;
            this.timer = setTimeout(async () => {
                try {
                    const res = await fetch(
                        '{{ route('staff.dashboard.customer-search') }}?q=' + encodeURIComponent(this.q.trim()),
                        { headers: { Accept: 'application/json' } }
                    );
                    const payload = await res.json();
                    this.results = payload.data || [];
                } catch (e) {
                    this.results = [];
                } finally {
                    this.loading = false;
                }
            }, 300);
        }
    };
}
</script>
@endpush
@endsection
