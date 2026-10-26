@extends('layouts.admin')

@section('title', 'Danh mục dịch vụ')

@php
    $tabs = [
        ['label' => 'Gói dịch vụ', 'route' => 'admin.services'],
        ['label' => 'Danh mục', 'route' => 'admin.categories'],
        ['label' => 'Chuyên viên', 'route' => 'admin.specialists'],
    ];

    $activeCategories = $categories->where('is_active', true)->count();
    $totalServices = $categories->sum('services_count');
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ dịch vụ &amp; chuyên viên</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Danh mục dịch vụ</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Danh mục nhóm các gói dịch vụ cho bộ lọc công khai và bảng giá quản trị.
            Danh mục đã được sử dụng <strong class="font-bold text-[#334E68]">không xoá cứng</strong> — chỉ ngừng hoạt động để giữ liên kết dữ liệu.
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

    {{-- ═══════════════ KPI ═══════════════ --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-admin.stat-card label="Tổng danh mục" :value="$categories->count()" unit="danh mục"
                           hint="Bao gồm cả danh mục đang ngừng hoạt động" tone="blue"
                           icon="M4 6h16M4 12h16M4 18h7"/>
        <x-admin.stat-card label="Đang hoạt động" :value="$activeCategories" unit="danh mục"
                           hint="Hiển thị trong bộ lọc công khai" tone="emerald"
                           icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        <x-admin.stat-card label="Gói dịch vụ liên kết" :value="$totalServices" unit="gói"
                           hint="Tổng số gói thuộc các danh mục" tone="slate"
                           icon="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </div>

    {{-- ═══════════════ DANH SÁCH DANH MỤC ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="border-b border-[#E8F1F6] px-5 py-4">
            <h2 class="text-base font-black text-[#102A43]">Danh sách danh mục</h2>
            <p class="mt-1 text-xs text-[#718096]">Thứ tự nhỏ hiển thị trước. Ngừng hoạt động không ảnh hưởng dữ liệu lịch sử.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[940px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thứ tự</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Tên danh mục</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Mô tả</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Số gói</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($categories as $category)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF4FB] text-xs font-black tabular-nums text-[#23689C]">
                                    {{ $category->display_order }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-bold text-[#102A43]">{{ $category->name }}</td>

                            <td class="px-5 py-4">
                                <p class="max-w-[24rem] text-xs leading-relaxed text-[#718096]">
                                    {{ $category->description ?: '—' }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm tabular-nums text-[#334E68]">
                                {{ $category->services_count }} gói
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold"
                                      style="color: {{ $category->is_active ? '#15803D' : '#64748B' }}; background-color: {{ $category->is_active ? '#ECFDF3' : '#F1F5F9' }}; border-color: {{ $category->is_active ? '#BBF7D0' : '#E2E8F0' }}">
                                    <span aria-hidden="true">{{ $category->is_active ? '●' : '⊘' }}</span>
                                    {{ $category->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <details class="group">
                                    <summary class="cursor-pointer list-none rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                                        Cập nhật
                                    </summary>
                                    <div class="absolute z-20 mt-2 w-[min(30rem,90vw)] rounded-xl border border-[#D7E8F3] bg-white p-4 shadow-2xl">
                                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="grid gap-3">
                                            @csrf
                                            @method('PUT')

                                            <label class="text-xs font-bold text-[#52637A]">
                                                Tên danh mục
                                                <input name="name" value="{{ $category->name }}" required maxlength="100"
                                                       class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                            </label>

                                            <label class="text-xs font-bold text-[#52637A]">
                                                Mô tả
                                                <textarea name="description" rows="2" maxlength="500"
                                                          class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">{{ $category->description }}</textarea>
                                            </label>

                                            <div class="grid grid-cols-2 gap-3">
                                                <label class="text-xs font-bold text-[#52637A]">
                                                    Thứ tự
                                                    <input name="display_order" type="number" min="0" value="{{ $category->display_order }}"
                                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                </label>

                                                <label class="text-xs font-bold text-[#52637A]">
                                                    Trạng thái
                                                    <select name="is_active" class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                        <option value="1" @selected($category->is_active)>Đang hoạt động</option>
                                                        <option value="0" @selected(! $category->is_active)>Ngừng hoạt động</option>
                                                    </select>
                                                </label>
                                            </div>

                                            @if($category->services_count > 0)
                                                <p class="rounded-lg bg-amber-50 px-3 py-2 text-[11px] font-semibold text-amber-800">
                                                    Danh mục này đang gắn với {{ $category->services_count }} gói dịch vụ.
                                                    Ngừng hoạt động sẽ ẩn danh mục khỏi bộ lọc công khai nhưng giữ nguyên các gói và lịch sử.
                                                </p>
                                            @endif

                                            <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">
                                                Lưu thay đổi
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="text-sm font-bold text-[#102A43]">Chưa có danh mục nào.</p>
                                <p class="mt-1 text-xs text-[#718096]">Thêm danh mục đầu tiên ở biểu mẫu bên dưới.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ═══════════════ THÊM DANH MỤC ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <h2 class="text-lg font-black text-[#102A43]">Thêm danh mục</h2>
        <p class="mt-1 text-xs text-[#718096]">Hệ thống kiểm tra tên trùng và các trường bắt buộc trước khi tạo.</p>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @csrf

            <label class="text-xs font-bold text-[#52637A]">
                Tên danh mục <span class="text-red-500" aria-hidden="true">*</span>
                <input name="name" value="{{ old('name') }}" required maxlength="100"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A] xl:col-span-2">
                Mô tả
                <input name="description" value="{{ old('description') }}" maxlength="500"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Thứ tự hiển thị
                <input name="display_order" type="number" min="0" value="{{ old('display_order', 0) }}"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái
                <select name="is_active" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="1" @selected(old('is_active', '1') === '1')>Đang hoạt động</option>
                    <option value="0" @selected(old('is_active') === '0')>Ngừng hoạt động</option>
                </select>
            </label>

            <div class="flex items-center gap-3 border-t border-[#EEF4F8] pt-4 sm:col-span-2 xl:col-span-4">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Tạo danh mục
                </button>
                <span class="text-[11px] text-[#8A9BB0]">Thao tác tạo danh mục được ghi vào nhật ký kiểm toán.</span>
            </div>
        </form>
    </section>
</div>
@endsection
