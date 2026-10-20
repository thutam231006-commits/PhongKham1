@extends('layouts.app')

@section('title', 'Lịch làm việc chuyên viên')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div><h1 class="text-3xl font-black text-[#2D82C4]">Lịch làm việc hằng tuần</h1><p class="mt-2 text-sm text-[#4A5568]">Đăng ký các ca tư vấn sáng, chiều hoặc tối. Các ca trong cùng ngày không được chồng nhau.</p></div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

    <section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="schedule-form-heading">
        <h2 id="schedule-form-heading" class="text-lg font-bold text-[#0F172A]">Thêm ca tư vấn</h2>
        <form method="POST" action="{{ route('specialist.schedule.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <label class="text-sm font-semibold text-[#334155]">Ngày<select name="day_of_week" class="mt-1 w-full rounded-lg border-gray-300" required>@foreach([1=>'Thứ Hai',2=>'Thứ Ba',3=>'Thứ Tư',4=>'Thứ Năm',5=>'Thứ Sáu',6=>'Thứ Bảy',7=>'Chủ Nhật'] as $day=>$label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold text-[#334155]">Bắt đầu<input type="time" name="start_time" value="08:00" class="mt-1 w-full rounded-lg border-gray-300" required></label>
            <label class="text-sm font-semibold text-[#334155]">Kết thúc<input type="time" name="end_time" value="12:00" class="mt-1 w-full rounded-lg border-gray-300" required></label>
            <label class="text-sm font-semibold text-[#334155]">Nghỉ giữa ca<input type="text" name="break_start" placeholder="10:00" class="mt-1 w-full rounded-lg border-gray-300"></label>
            <label class="text-sm font-semibold text-[#334155]">Kết thúc nghỉ<input type="text" name="break_end" placeholder="10:15" class="mt-1 w-full rounded-lg border-gray-300"></label>
            <label class="text-sm font-semibold text-[#334155]">Hiệu lực từ<input type="date" name="effective_from" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border-gray-300"></label>
            <div class="flex items-end"><button class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C]">Thêm ca</button></div>
        </form>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-bold text-[#0F172A]">Ngày nghỉ và chặn giờ</h2>
        <form method="POST" action="{{ route('specialist.leaves.store') }}" class="mt-4 grid gap-3 sm:grid-cols-3">@csrf<select name="type" class="rounded-lg border-gray-300"><option value="DAY_OFF">Nghỉ cả ngày</option><option value="TIME_BLOCK">Chặn một khoảng giờ</option></select><input type="date" name="start_date" required class="rounded-lg border-gray-300"><input type="date" name="end_date" required class="rounded-lg border-gray-300"><input type="time" name="start_time" class="rounded-lg border-gray-300"><input type="time" name="end_time" class="rounded-lg border-gray-300"><input name="reason" required maxlength="255" placeholder="Lý do" class="rounded-lg border-gray-300"><label class="flex items-center gap-2"><input type="checkbox" name="is_emergency" value="1"> Nghỉ đột xuất</label><button class="rounded-lg bg-[#2D82C4] px-4 py-2 font-bold text-white">Lưu ngày nghỉ</button></form>
        <div class="mt-5 space-y-2">@forelse($exceptions as $exception)<div class="flex justify-between rounded border p-3 text-sm"><span><strong>{{ $exception->type }}</strong> · {{ $exception->start_date->format('d/m/Y') }} - {{ $exception->end_date->format('d/m/Y') }}</span><span>{{ $exception->reason }}</span></div>@empty<p class="text-sm text-gray-500">Chưa có ngày nghỉ hoặc giờ bị chặn.</p>@endforelse</div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white" aria-labelledby="schedule-list-heading">
        <h2 id="schedule-list-heading" class="border-b border-gray-100 px-5 py-4 text-lg font-bold text-[#0F172A]">Các ca đang đăng ký</h2>
        @forelse($schedules as $schedule)
            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-bold text-[#2D82C4]">{{ [1=>'Thứ Hai',2=>'Thứ Ba',3=>'Thứ Tư',4=>'Thứ Năm',5=>'Thứ Sáu',6=>'Thứ Bảy',7=>'Chủ Nhật'][$schedule->day_of_week] }}</p><p class="text-sm text-[#4A5568]">{{ substr($schedule->start_time,0,5) }} - {{ substr($schedule->end_time,0,5) }}@if($schedule->break_start) · Nghỉ {{ substr($schedule->break_start,0,5) }} - {{ substr($schedule->break_end,0,5) }}@endif</p></div>
                <form method="POST" action="{{ route('specialist.schedule.update', $schedule) }}" class="flex items-center gap-2">@csrf @method('PUT')<input type="hidden" name="day_of_week" value="{{ $schedule->day_of_week }}"><input type="hidden" name="start_time" value="{{ substr($schedule->start_time,0,5) }}"><input type="hidden" name="end_time" value="{{ substr($schedule->end_time,0,5) }}"><input type="hidden" name="is_active" value="{{ $schedule->is_active ? 0 : 1 }}"><button class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-[#334155]">{{ $schedule->is_active ? 'Tạm tắt' : 'Bật lại' }}</button></form>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-[#4A5568]">Chưa có ca làm việc. Hãy thêm ca đầu tiên ở biểu mẫu phía trên.</p>
        @endforelse
    </section>
</div>
@endsection
