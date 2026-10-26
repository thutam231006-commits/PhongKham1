@extends('layouts.admin')

@section('title', 'Hồ sơ chuyên viên')

@php
    $tabs = [
        ['label' => 'Gói dịch vụ', 'route' => 'admin.services'],
        ['label' => 'Danh mục', 'route' => 'admin.categories'],
        ['label' => 'Chuyên viên', 'route' => 'admin.specialists'],
    ];

    $statusLabels = ['ACTIVE' => 'Đang hoạt động', 'INACTIVE' => 'Tạm ngừng'];
    $dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    $formatPrice = fn ($value): string => $value === null ? '—' : number_format((float) $value, 0, ',', '.').' đ';
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ dịch vụ &amp; chuyên viên</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Hồ sơ chuyên viên</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Thông tin <strong class="font-bold text-[#334E68]">công khai</strong> hiển thị cho khách; thông tin
            <strong class="font-bold text-[#334E68]">nội bộ</strong> chỉ dành cho quản trị. Ngừng hoạt động không tự hủy
            lịch đã xác nhận — các lịch ảnh hưởng được liệt kê để xử lý.
        </p>
    </div>

    {{-- ═══════════════ TABS ═══════════════ --}}
    <nav class="flex flex-wrap gap-1 rounded-xl border border-[#D7E8F3] bg-white p-1.5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]" aria-label="Nhóm quản lý dịch vụ">
        @foreach($tabs as $tab)
            @php $isActive = request()->routeIs($tab['route']); @endphp
            <a href="{{ route($tab['route']) }}"
               class="rounded-lg px-4 py-2 text-sm font-bold transition {{ $isActive ? 'bg-[#2D82C4] text-white shadow-sm' : 'text-[#52637A] hover:bg-[#F3F9FD]' }}"
               @if($isActive) aria-current="page" @endif>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- ═══════════════ BỘ LỌC ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-xs font-bold text-[#52637A] xl:col-span-2">
                Tìm theo tên, email, chức danh hoặc chuyên môn
                <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nhập từ khoá…"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái công tác
                <select name="status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lọc
                </button>
                @if(request()->query())
                    <a href="{{ route('admin.specialists') }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                        Xoá lọc
                    </a>
                @endif
            </div>
        </form>
    </section>

    {{-- ═══════════════ DANH SÁCH HỒ SƠ ═══════════════ --}}
    <div class="space-y-4">
        @forelse($specialists as $specialist)
            @php
                $isActive = $specialist->status === 'ACTIVE';
                $hasWorkingSchedule = $specialist->workingSchedules->where('is_active', true)->isNotEmpty();
                $scheduleDays = $specialist->workingSchedules
                    ->where('is_active', true)
                    ->sortBy('day_of_week')
                    ->groupBy('day_of_week');
                $canActivate = $specialist->services->isNotEmpty() && $hasWorkingSchedule;
            @endphp

            <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
                {{-- ── Đầu hồ sơ ── --}}
                <div class="flex flex-wrap items-start gap-4 border-b border-[#E8F1F6] bg-[#FBFDFF] px-5 py-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#EAF4FB] text-base font-black uppercase text-[#23689C]">
                        @if($specialist->avatar_url)
                            <img src="{{ $specialist->avatar_url }}" alt="Ảnh đại diện {{ $specialist->user?->name }}"
                                 class="h-full w-full object-cover" loading="lazy">
                        @else
                            {{ mb_substr($specialist->user?->name ?? '?', 0, 1) }}
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-black text-[#102A43]">{{ $specialist->user?->name ?? 'Chưa liên kết tài khoản' }}</h2>
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                  style="color: {{ $isActive ? '#15803D' : '#64748B' }}; background-color: {{ $isActive ? '#ECFDF3' : '#F1F5F9' }}; border-color: {{ $isActive ? '#BBF7D0' : '#E2E8F0' }}">
                                <span aria-hidden="true">{{ $isActive ? '●' : '⊘' }}</span>
                                {{ $statusLabels[$specialist->status] ?? $specialist->status }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm font-semibold text-[#334E68]">{{ $specialist->title }}</p>
                        <p class="mt-0.5 text-xs text-[#8A9BB0]">{{ $specialist->user?->email }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-center">
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Đánh giá</p>
                            <p class="text-sm font-black tabular-nums text-[#102A43]">{{ number_format((float) $specialist->average_rating, 1) }} / 5</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Đã hoàn thành</p>
                            <p class="text-sm font-black tabular-nums text-[#102A43]">{{ number_format($specialist->completed_appointments) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Lịch sắp tới</p>
                            <p class="text-sm font-black tabular-nums {{ $specialist->future_appointments_count > 0 ? 'text-[#2D82C4]' : 'text-[#102A43]' }}">
                                {{ number_format($specialist->future_appointments_count) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 p-5 lg:grid-cols-3">
                    {{-- ── Thông tin công khai ── --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-[#EAF4FB] px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-[#23689C]">Công khai</span>
                            <p class="text-xs text-[#8A9BB0]">Khách hàng nhìn thấy nội dung này</p>
                        </div>

                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Chuyên môn</dt>
                                <dd class="mt-0.5 text-sm text-[#334E68]">{{ $specialist->expertise ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Kinh nghiệm</dt>
                                <dd class="mt-0.5 text-sm text-[#334E68]">
                                    {{ $specialist->years_of_experience !== null ? $specialist->years_of_experience.' năm' : '—' }}
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Giới thiệu</dt>
                                <dd class="mt-0.5 text-sm leading-relaxed text-[#52637A]">{{ $specialist->bio ?: '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Gói dịch vụ phụ trách</dt>
                                <dd class="mt-1.5 flex flex-wrap gap-1.5">
                                    @forelse($specialist->services as $service)
                                        <span class="rounded-md bg-[#F3F9FD] px-2 py-1 text-[11px] font-bold text-[#23689C] ring-1 ring-inset ring-[#D7E8F3]">
                                            {{ $service->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs font-semibold text-amber-700">Chưa gán dịch vụ — không thể kích hoạt.</span>
                                    @endforelse
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- ── Thông tin nội bộ ── --}}
                    <div class="rounded-xl border border-[#E8F1F6] bg-[#FBFDFF] p-4">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-[#FEF3C7] px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-[#B45309]">Nội bộ</span>
                        </div>

                        <dl class="mt-3 space-y-2.5 text-xs">
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-[#8A9BB0]">Mã hồ sơ</dt>
                                <dd class="font-mono font-bold text-[#334E68]">#{{ $specialist->id }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-[#8A9BB0]">Tài khoản</dt>
                                <dd class="truncate font-bold text-[#334E68]">{{ $specialist->user?->email ?? '—' }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-[#8A9BB0]">Phí tư vấn</dt>
                                <dd class="font-bold tabular-nums text-[#334E68]">{{ $formatPrice($specialist->consultation_fee) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-2">
                                <dt class="font-semibold text-[#8A9BB0]">Lịch đã thanh toán (sắp tới)</dt>
                                <dd class="font-bold tabular-nums {{ $specialist->paid_future_count > 0 ? 'text-orange-700' : 'text-[#334E68]' }}">
                                    {{ $specialist->paid_future_count }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3 border-t border-[#E8F1F6] pt-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Ca làm việc đang bật</p>
                            @if($scheduleDays->isEmpty())
                                <p class="mt-1 text-xs font-semibold text-amber-700">Chưa có ca làm việc — không thể kích hoạt.</p>
                            @else
                                <ul class="mt-1.5 space-y-1">
                                    @foreach($scheduleDays as $day => $shifts)
                                        <li class="flex items-baseline gap-1.5 text-[11px] text-[#52637A]">
                                            <span class="w-6 shrink-0 font-bold text-[#334E68]">{{ $dayNames[$day] ?? $day }}</span>
                                            <span class="tabular-nums">
                                                {{ $shifts->map(fn ($shift) => substr((string) $shift->start_time, 0, 5).'–'.substr((string) $shift->end_time, 0, 5))->implode(', ') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Hành động ── --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-[#E8F1F6] bg-[#FBFDFF] px-5 py-3">
                    <details class="group">
                        <summary class="cursor-pointer list-none rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">
                            Cập nhật hồ sơ
                        </summary>
                        <div class="absolute z-20 mt-2 w-[min(46rem,92vw)] rounded-xl border border-[#D7E8F3] bg-white p-4 shadow-2xl">
                            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.specialists.update', $specialist) }}"
                                  class="grid gap-3 sm:grid-cols-2">
                                @csrf
                                @method('PUT')

                                <label class="text-xs font-bold text-[#52637A]">
                                    Họ tên hiển thị <span class="text-red-500" aria-hidden="true">*</span>
                                    <input name="name" value="{{ $specialist->user?->name }}" required maxlength="100"
                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                </label>

                                <label class="text-xs font-bold text-[#52637A]">
                                    Chức danh <span class="text-red-500" aria-hidden="true">*</span>
                                    <input name="title" value="{{ $specialist->title }}" required maxlength="100"
                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                </label>

                                <label class="text-xs font-bold text-[#52637A]">
                                    Chuyên môn
                                    <input name="expertise" value="{{ $specialist->expertise }}" maxlength="255"
                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                </label>

                                <label class="text-xs font-bold text-[#52637A]">
                                    Số năm kinh nghiệm
                                    <input name="years_of_experience" type="number" min="0" max="70" value="{{ $specialist->years_of_experience }}"
                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                </label>

                                <label class="text-xs font-bold text-[#52637A] sm:col-span-2">
                                    Giới thiệu (công khai)
                                    <textarea name="bio" rows="3" maxlength="3000"
                                              class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">{{ $specialist->bio }}</textarea>
                                </label>

                                <label class="text-xs font-bold text-[#52637A] sm:col-span-2">
                                    Bằng cấp / chứng chỉ
                                    <textarea name="credentials" rows="2" maxlength="2000"
                                              class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">{{ $specialist->credentials }}</textarea>
                                </label>

                                <label class="text-xs font-bold text-[#52637A] sm:col-span-2">
                                    Ảnh đại diện mới (JPG/PNG, tối đa 2 MB)
                                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                    <span class="mt-1 block text-[10px] font-semibold text-[#8A9BB0]">
                                        Ảnh hiện tại {{ $specialist->avatar_url ? 'đã có' : 'chưa có' }} · ảnh được xem trước trên trang khách hàng.
                                    </span>
                                </label>

                                <fieldset class="sm:col-span-2">
                                    <legend class="text-xs font-bold text-[#52637A]">Gói dịch vụ phụ trách <span class="text-red-500" aria-hidden="true">*</span></legend>
                                    <div class="mt-1.5 grid max-h-44 gap-1.5 overflow-y-auto rounded-lg border border-[#D7E8F3] p-3 sm:grid-cols-2">
                                        @foreach($services as $service)
                                            <label class="flex items-start gap-2 text-xs font-semibold text-[#334E68]">
                                                <input type="checkbox" name="service_ids[]" value="{{ $service->id }}"
                                                       @checked($specialist->services->contains('id', $service->id))
                                                       class="mt-0.5 h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                                                {{ $service->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                                    <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">
                                        Lưu hồ sơ
                                    </button>
                                    <span class="text-[10px] font-semibold text-[#8A9BB0]">Thay đổi được ghi vào nhật ký kiểm toán.</span>
                                </div>
                            </form>
                        </div>
                    </details>

                    @if($isActive)
                        <x-modal-confirm
                            id="stop-specialist-{{ $specialist->id }}"
                            title="Tạm ngừng hoạt động {{ $specialist->user?->name }}"
                            description="Chuyên viên sẽ ngừng nhận lịch mới. Lịch đã xác nhận không bị hủy tự động. {{ $specialist->future_appointments_count > 0 ? 'Hiện có '.$specialist->future_appointments_count.' lịch sắp tới cần xử lý, trong đó '.$specialist->paid_future_count.' lịch đã thanh toán.' : 'Không có lịch sắp tới bị ảnh hưởng.' }}"
                            confirm-label="Tạm ngừng hoạt động"
                            :require-reason="true"
                            reason-label="Lý do ngừng hoạt động"
                            :action="route('admin.specialists.toggle', $specialist)">
                            <x-slot:trigger>
                                <button type="button" class="rounded-lg border border-amber-300 px-4 py-2 text-xs font-bold text-amber-800 transition hover:bg-amber-50">
                                    Tạm ngừng hoạt động
                                </button>
                            </x-slot:trigger>
                        </x-modal-confirm>
                    @else
                        <form method="POST" action="{{ route('admin.specialists.toggle', $specialist) }}">
                            @csrf
                            <button type="submit"
                                    @disabled(! $canActivate)
                                    title="{{ $canActivate ? 'Kích hoạt lại chuyên viên' : 'Cần có ít nhất một dịch vụ và một ca làm việc trước khi kích hoạt' }}"
                                    class="rounded-lg px-4 py-2 text-xs font-bold transition
                                           {{ $canActivate
                                               ? 'border border-emerald-300 text-emerald-800 hover:bg-emerald-50'
                                               : 'cursor-not-allowed border border-[#E2E8F0] bg-[#F1F5F9] text-[#94A3B8]' }}">
                                Kích hoạt lại
                            </button>
                        </form>
                    @endif

                    <span class="ml-auto text-[11px] font-semibold text-[#8A9BB0]">
                        Tổng {{ number_format($specialist->appointments_count) }} lịch hẹn
                    </span>
                </div>
            </section>
        @empty
            <section class="rounded-2xl border border-dashed border-[#D7E8F3] bg-white p-10 text-center">
                <p class="text-sm font-bold text-[#102A43]">Chưa có hồ sơ chuyên viên phù hợp.</p>
                <p class="mt-1 text-xs text-[#718096]">Tạo hồ sơ mới ở biểu mẫu bên dưới.</p>
            </section>
        @endforelse

        @if($specialists->hasPages())
            <div class="rounded-2xl border border-[#D7E8F3] bg-white px-5 py-4">{{ $specialists->links() }}</div>
        @endif
    </div>

    {{-- ═══════════════ TẠO HỒ SƠ MỚI ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Tạo hồ sơ chuyên viên</h2>
        <p class="mt-1 text-xs text-[#718096]">
            Tài khoản chuyên viên được tạo ở trạng thái <strong class="font-bold text-[#334E68]">tạm ngừng</strong> —
            kích hoạt sau khi đã gán dịch vụ và ca làm việc.
        </p>

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.specialists.store') }}"
              class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @csrf

            <label class="text-xs font-bold text-[#52637A]">
                Họ tên <span class="text-red-500" aria-hidden="true">*</span>
                <input name="name" value="{{ old('name') }}" required maxlength="100"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Email đăng nhập <span class="text-red-500" aria-hidden="true">*</span>
                <input name="email" type="email" value="{{ old('email') }}" required maxlength="255"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Chức danh <span class="text-red-500" aria-hidden="true">*</span>
                <input name="title" value="{{ old('title') }}" required maxlength="100"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Chuyên môn
                <input name="expertise" value="{{ old('expertise') }}" maxlength="255"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Số năm kinh nghiệm
                <input name="years_of_experience" type="number" min="0" max="70" value="{{ old('years_of_experience') }}"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Ảnh đại diện (JPG/PNG, tối đa 2 MB)
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A] sm:col-span-2 xl:col-span-3">
                Giới thiệu (công khai)
                <textarea name="bio" rows="3" maxlength="3000"
                          class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">{{ old('bio') }}</textarea>
            </label>

            <label class="text-xs font-bold text-[#52637A] sm:col-span-2 xl:col-span-3">
                Bằng cấp / chứng chỉ
                <textarea name="credentials" rows="2" maxlength="2000"
                          class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">{{ old('credentials') }}</textarea>
            </label>

            <fieldset class="sm:col-span-2 xl:col-span-3">
                <legend class="text-xs font-bold text-[#52637A]">Gói dịch vụ phụ trách <span class="text-red-500" aria-hidden="true">*</span></legend>
                <div class="mt-1.5 grid gap-1.5 rounded-lg border border-[#D7E8F3] p-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($services as $service)
                        <label class="flex items-start gap-2 text-xs font-semibold text-[#334E68]">
                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}"
                                   @checked(in_array($service->id, (array) old('service_ids', []), true))
                                   class="mt-0.5 h-4 w-4 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                            {{ $service->name }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center gap-3 border-t border-[#EEF4F8] pt-4 sm:col-span-2 xl:col-span-3">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Tạo hồ sơ
                </button>
                <span class="text-[11px] text-[#8A9BB0]">Mật khẩu tạm thời được cấp tự động và ghi vào nhật ký kiểm toán.</span>
            </div>
        </form>
    </section>
</div>
@endsection
