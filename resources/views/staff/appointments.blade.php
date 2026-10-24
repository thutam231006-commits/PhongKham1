@extends('layouts.staff')

@section('title', 'Quản lý lịch hẹn')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Lịch làm việc & phân bổ khách</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43]">Điều phối lịch tư vấn</h1>
            <p class="mt-1 text-sm text-[#52637A]">Duyệt lịch mới, xử lý đổi lịch và đặt lịch hộ khách hàng.</p>
        </div>
        <a href="{{ route('staff.appointments.book') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#23689C] transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Đặt lịch hộ khách
        </a>
    </div>

    @if(session('success'))
        <div role="alert" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    {{-- TAB NAVIGATION --}}
    <div class="flex gap-1 rounded-xl border border-[#D7E8F3] bg-white p-1.5 shadow-sm">
        @php
            $tabs = [
                'pending'   => ['label' => 'Lịch mới chờ duyệt', 'count' => $appointments->where('status','PENDING')->count(), 'color' => 'amber'],
                'all'       => ['label' => 'Toàn bộ lịch hẹn',    'count' => $appointments->count(),                           'color' => 'blue'],
                'reschedule'=> ['label' => 'Yêu cầu đổi lịch',   'count' => $rescheduleRequests->count(),                    'color' => 'blue'],
            ];
            $activeTab = request('tab', 'pending');
        @endphp
        @foreach($tabs as $key => $tab)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
           class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition
                  {{ $activeTab === $key
                     ? 'bg-[#2D82C4] text-white shadow'
                     : 'text-[#52637A] hover:bg-[#F0F7FC] hover:text-[#2D82C4]' }}">
            {{ $tab['label'] }}
            @if($tab['count'] > 0)
            <span class="rounded-full px-2 py-0.5 text-xs font-black
                         {{ $activeTab === $key ? 'bg-white/20 text-white' : 'bg-[#EAF4FB] text-[#2D82C4]' }}">
                {{ $tab['count'] }}
            </span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- BỘ LỌC --}}
    <form method="GET" class="grid gap-3 rounded-xl border border-[#D7E8F3] bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <label class="text-xs font-bold text-[#52637A]">
            Chế độ xem
            <select name="view" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                <option value="day"   @selected($view === 'day')>Ngày</option>
                <option value="week"  @selected($view === 'week')>Tuần</option>
                <option value="month" @selected($view === 'month')>Tháng</option>
            </select>
        </label>
        <label class="text-xs font-bold text-[#52637A]">
            Ngày cần xem
            <input name="date" type="date" value="{{ $selectedDate->toDateString() }}" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
        </label>
        <label class="text-xs font-bold text-[#52637A]">
            Chuyên viên
            <select name="specialist_id" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                <option value="">Tất cả chuyên viên</option>
                @foreach($specialists as $specialist)
                <option value="{{ $specialist->id }}" @selected((string)($data['specialist_id']??'') === (string)$specialist->id)>{{ $specialist->user->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-bold text-[#52637A]">
            Trạng thái
            <select name="status" class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                <option value="">Tất cả trạng thái</option>
                @foreach(['PENDING'=>'Chờ xác nhận','CONFIRMED'=>'Đã xác nhận','RESCHEDULE_REQUESTED'=>'Chờ đổi lịch','COMPLETED'=>'Hoàn thành','CANCELLED_BY_CUSTOMER'=>'Khách đã hủy'] as $k=>$lbl)
                <option value="{{ $k }}" @selected(($data['status']??'') === $k)>{{ $lbl }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end">
            <button class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition">
                Cập nhật
            </button>
        </div>
    </form>

    {{-- ── TAB: LỊCH MỚI CHỜ DUYỆT ── --}}
    @if($activeTab === 'pending')
    <section class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm" aria-labelledby="pending-heading">
        <div class="flex items-center justify-between border-b border-amber-100 bg-amber-50/70 px-5 py-4">
            <div>
                <h2 id="pending-heading" class="text-lg font-black text-amber-800">Lịch chờ xác nhận</h2>
                <p class="mt-0.5 text-xs text-amber-700">Kiểm tra chuyên viên và khung giờ trước khi xác nhận.</p>
            </div>
            <span class="rounded-full bg-amber-500 px-3 py-1 text-xs font-black text-white">
                {{ $appointments->where('status','PENDING')->count() }}
            </span>
        </div>
        @forelse($appointments->where('status','PENDING') as $appointment)
        <details class="group border-b border-amber-100 last:border-0">
            <summary class="flex cursor-pointer list-none flex-col gap-2 px-5 py-4 hover:bg-amber-50/40 md:flex-row md:items-center md:justify-between">
                <span>
                    <strong class="text-sm text-[#102A43]">{{ $appointment->customer->name }}</strong>
                    <span class="ml-2 text-xs text-[#718096]">{{ $appointment->service->name }} · {{ $appointment->start_at->format('d/m/Y H:i') }}</span>
                </span>
                <span class="text-xs font-bold text-amber-700">{{ $appointment->specialistProfile->user->name }} · {{ $appointment->consultation_type === 'ONLINE' ? 'Online' : 'Offline' }}</span>
            </summary>
            <div class="grid gap-4 border-t border-amber-100 bg-amber-50/30 px-5 py-4 text-sm md:grid-cols-4">
                <div>
                    <p class="text-xs text-[#718096]">Khách hàng</p>
                    <p class="font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                    <p class="text-xs text-[#52637A]">{{ $appointment->customer->email }}</p>
                    <p class="text-xs text-[#52637A]">{{ $appointment->customer->phone }}</p>
                </div>
                <div>
                    <p class="text-xs text-[#718096]">Dịch vụ</p>
                    <p class="font-bold text-[#102A43]">{{ $appointment->service->name }}</p>
                    <p class="text-xs text-[#52637A]">{{ $appointment->service->duration_minutes }} phút</p>
                </div>
                <div>
                    <p class="text-xs text-[#718096]">Chuyên viên & giờ</p>
                    <p class="font-bold text-[#102A43]">{{ $appointment->specialistProfile->user->name }}</p>
                    <p class="text-xs text-[#52637A]">{{ $appointment->start_at->format('d/m/Y H:i') }} – {{ $appointment->end_at->format('H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs text-[#718096]">Hình thức</p>
                    <p class="font-bold text-[#102A43]">{{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : 'Trực tiếp' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 md:col-span-4">
                    <a href="{{ route('staff.appointments.detail', $appointment) }}"
                       class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] hover:bg-white transition">Xem chi tiết</a>
                    <form method="POST" action="{{ route('staff.appointments.confirm', $appointment) }}">
                        @csrf
                        <button class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white hover:bg-[#23689C] transition">Xác nhận lịch</button>
                    </form>
                    <form method="POST" action="{{ route('staff.appointments.reassign', $appointment) }}" class="flex gap-2">
                        @csrf
                        <select name="specialist_id" required class="rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                            <option value="">Chuyển chuyên viên…</option>
                            @foreach($specialists as $sp)
                            <option value="{{ $sp->id }}" @disabled($sp->id === $appointment->specialist_profile_id)>{{ $sp->user->name }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-lg border border-[#2D82C4] px-3 py-2 text-xs font-bold text-[#2D82C4] hover:bg-[#EAF4FB] transition">Chuyển</button>
                    </form>
                </div>
            </div>
        </details>
        @empty
        <p class="px-5 py-10 text-center text-sm text-[#718096]">Không có lịch đang chờ xác nhận trong khoảng xem này.</p>
        @endforelse
    </section>
    @endif

    {{-- ── TAB: TẤT CẢ LỊCH HẸN (lịch dạng lưới) ── --}}
    @if($activeTab === 'all')
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="calendar-heading">
        <div class="border-b border-[#E8F1F6] px-5 py-4">
            <h2 id="calendar-heading" class="text-lg font-black text-[#102A43]">
                Lịch {{ $view === 'day' ? 'ngày' : ($view === 'month' ? 'tháng' : 'tuần') }}
            </h2>
            <p class="mt-0.5 text-xs text-[#718096]">{{ $rangeStart->format('d/m/Y') }} – {{ $rangeEnd->format('d/m/Y') }}</p>
        </div>

        {{-- Chú thích trạng thái --}}
        <div class="flex flex-wrap gap-3 border-b border-[#EEF4F8] px-5 py-3 text-xs font-semibold text-[#52637A]">
            <span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-400"></i>Chờ xác nhận</span>
            <span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-[#2D82C4]"></i>Đã xác nhận</span>
            <span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-400"></i>Đã đến</span>
            <span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-gray-400"></i>Nghỉ/khóa lịch</span>
            <span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-red-400"></i>Đã hủy</span>
        </div>

        <div class="grid gap-px bg-[#D7E8F3] {{ $view === 'day' ? 'grid-cols-1' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-7' }}">
            @foreach($calendarDays as $day)
            @php $dayAppointments = $appointments->filter(fn($a) => $a->start_at->isSameDay($day)); @endphp
            <div class="min-h-36 bg-white p-3 {{ $day->isToday() ? 'ring-2 ring-inset ring-[#2D82C4]' : '' }}">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-black {{ $day->isToday() ? 'text-[#2D82C4]' : 'text-[#52637A]' }}">
                        {{ $day->isoFormat('ddd') }} {{ $day->format('d/m') }}
                    </p>
                    <span class="text-[11px] text-[#8A9BB0]">{{ $dayAppointments->count() }}</span>
                </div>
                <div class="mt-2 space-y-1.5">
                    @forelse($dayAppointments as $appt)
                    @php
                        $cls = match($appt->status) {
                            'PENDING'                                  => 'border-amber-300 bg-amber-50 text-amber-900',
                            'CONFIRMED','CHECKED_IN','IN_SESSION'      => 'border-[#8BB9D8] bg-[#EAF4FB] text-[#1D5E8A]',
                            'COMPLETED'                                => 'border-emerald-300 bg-emerald-50 text-emerald-900',
                            'CANCELLED_BY_CUSTOMER','CANCELLED_BY_CLINIC' => 'border-red-200 bg-red-50 text-red-800',
                            default                                    => 'border-gray-200 bg-gray-50 text-gray-700',
                        };
                    @endphp
                    <div class="rounded-lg border p-1.5 text-xs {{ $cls }}">
                        <div class="flex justify-between gap-1">
                            <strong>{{ $appt->start_at->format('H:i') }}</strong>
                            <span>{{ \App\Support\AppointmentStatus::label($appt->status) }}</span>
                        </div>
                        <p class="mt-0.5 truncate font-bold">{{ $appt->customer->name }}</p>
                        <p class="truncate">{{ $appt->specialistProfile->user->name }}</p>
                    </div>
                    @empty
                    <span class="block rounded-lg border border-dashed border-emerald-200 bg-emerald-50/50 px-2 py-2.5 text-center text-[11px] font-semibold text-emerald-700">Trống</span>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── TAB: YÊU CẦU ĐỔI LỊCH ── --}}
    @if($activeTab === 'reschedule')
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="reschedule-heading">
        <div class="border-b border-[#E8F1F6] px-5 py-4">
            <h2 id="reschedule-heading" class="text-lg font-black text-[#102A43]">Yêu cầu đổi lịch</h2>
            <p class="mt-0.5 text-xs text-[#718096]">Kiểm tra khung giờ mới trước khi duyệt — không tạo lịch trùng lặp.</p>
        </div>
        @forelse($rescheduleRequests as $item)
        <div class="flex flex-col gap-3 border-b border-[#EEF4F8] px-5 py-4 last:border-0 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-bold text-[#102A43]">{{ $item->appointment->customer->name }} · {{ $item->appointment->service->name }}</p>
                <p class="mt-0.5 text-xs text-[#52637A]">
                    Lịch hiện tại: <strong>{{ $item->appointment->start_at->format('d/m/Y H:i') }}</strong>
                    → Mong muốn: <strong>{{ $item->requested_start_at->format('d/m/Y H:i') }}</strong>
                    · {{ $item->appointment->specialistProfile->user->name }}
                </p>
            </div>
            <form method="POST" action="{{ route('staff.reschedule-requests.resolve', $item) }}" class="flex shrink-0 gap-2">
                @csrf
                <select name="decision" class="rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                    <option value="approve">Duyệt</option>
                    <option value="reject">Từ chối</option>
                </select>
                <input name="reason" placeholder="Lý do từ chối (nếu có)" class="w-36 rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                <button class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white hover:bg-[#23689C] transition">Xử lý</button>
            </form>
        </div>
        @empty
        <p class="px-5 py-10 text-center text-sm text-[#718096]">Không có yêu cầu đổi lịch chờ duyệt.</p>
        @endforelse
    </section>
    @endif
</div>
@endsection
