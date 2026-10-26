@extends('layouts.admin')

@section('title', 'Gói dịch vụ')

@php
    $statusLabels = ['ACTIVE' => 'Đang bán', 'INACTIVE' => 'Tạm ngừng'];
    $tabs = [
        ['label' => 'Gói dịch vụ', 'route' => 'admin.services'],
        ['label' => 'Danh mục', 'route' => 'admin.categories'],
        ['label' => 'Chuyên viên', 'route' => 'admin.specialists'],
    ];

    $activeCount = $services->where('status', 'ACTIVE')->count();

    $formatPrice = fn ($value): string => $value === null
        ? '—'
        : number_format((float) $value, 0, ',', '.').' đ';
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ dịch vụ &amp; chuyên viên</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Gói dịch vụ</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
                Giá và thời lượng dưới đây chỉ áp dụng cho <strong class="font-bold text-[#334E68]">lịch đặt mới</strong>.
                Lịch đã xác nhận giữ nguyên giá tại thời điểm đặt.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-xs font-bold text-[#52637A]">
                {{ $activeCount }} / {{ $services->total() }} gói đang bán
            </span>
        </div>
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
                Tìm theo tên hoặc mô tả
                <input name="q" value="{{ request('q') }}" maxlength="150" placeholder="Nhập từ khoá…"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Danh mục
                <select name="category_id" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả danh mục</option>
                    @foreach($allCategories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                            {{ $category->name }}{{ $category->is_active ? '' : ' (ngừng hoạt động)' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái bán
                <select name="status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap items-center gap-2 sm:col-span-2 xl:col-span-4">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lọc gói dịch vụ
                </button>
                @if(request()->query())
                    <a href="{{ route('admin.services') }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                        Xoá bộ lọc
                    </a>
                @endif
                <span class="ml-auto text-xs font-semibold text-[#8A9BB0]">{{ $services->total() }} gói</span>
            </div>
        </form>
    </section>

    {{-- ═══════════════ DANH SÁCH GÓI ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="overflow-x-auto">
            <table class="min-w-[1120px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Gói dịch vụ</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Danh mục</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thời lượng</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Giá niêm yết</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Lịch đã đặt</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($services as $service)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="px-5 py-4">
                                <p class="font-bold text-[#102A43]">{{ $service->name }}</p>
                                @if($service->description)
                                    <p class="mt-1 max-w-[26rem] text-xs leading-relaxed text-[#718096]">
                                        {{ \Illuminate\Support\Str::limit($service->description, 130) }}
                                    </p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-[#334E68]">
                                {{ $service->category?->name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-bold tabular-nums text-[#102A43]">{{ $service->duration_minutes }} phút</p>
                                <p class="text-[11px] text-[#8A9BB0]">Đệm {{ $service->buffer_minutes }} phút</p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm font-black tabular-nums text-[#2D82C4]">
                                {{ $formatPrice($service->price) }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm tabular-nums text-[#334E68]">
                                {{ number_format((int) ($usage[$service->id] ?? 0)) }} lượt
                            </td>

                            <td class="px-5 py-4">
                                @php
                                    $isSelling = $service->status === 'ACTIVE';
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold"
                                      style="color: {{ $isSelling ? '#15803D' : '#64748B' }}; background-color: {{ $isSelling ? '#ECFDF3' : '#F1F5F9' }}; border-color: {{ $isSelling ? '#BBF7D0' : '#E2E8F0' }}">
                                    <span aria-hidden="true">{{ $isSelling ? '●' : '⊘' }}</span>
                                    {{ $statusLabels[$service->status] ?? $service->status }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <details class="group">
                                        <summary class="cursor-pointer list-none rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                                            Sửa gói
                                        </summary>
                                        <div class="absolute z-20 mt-2 w-[min(38rem,90vw)] rounded-xl border border-[#D7E8F3] bg-white p-4 shadow-2xl">
                                            <form method="POST" action="{{ route('admin.services.update', $service) }}" class="grid gap-3 sm:grid-cols-2">
                                                @csrf
                                                @method('PUT')

                                                <label class="text-xs font-bold text-[#52637A] sm:col-span-2">
                                                    Tên gói
                                                    <input name="name" value="{{ $service->name }}" required maxlength="150"
                                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                </label>

                                                <label class="text-xs font-bold text-[#52637A]">
                                                    Danh mục
                                                    <select name="category_id" class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                        @foreach($allCategories as $category)
                                                            <option value="{{ $category->id }}" @selected($service->category_id === $category->id)>{{ $category->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                <label class="text-xs font-bold text-[#52637A]">
                                                    Trạng thái bán
                                                    <select name="status" class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                        @foreach($statusLabels as $value => $label)
                                                            <option value="{{ $value }}" @selected($service->status === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>

                                                <label class="text-xs font-bold text-[#52637A]">
                                                    Giá mới (VNĐ)
                                                    <input name="price" type="number" min="0" step="1000" value="{{ (int) $service->price }}"
                                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                    <span class="mt-1 block text-[10px] font-semibold text-[#8A9BB0]">
                                                        Giá hiện tại: {{ $formatPrice($service->price) }} · áp dụng cho lịch đặt mới
                                                    </span>
                                                </label>

                                                <label class="text-xs font-bold text-[#52637A]">
                                                    Thời lượng (phút)
                                                    <input name="duration_minutes" type="number" min="15" max="180" value="{{ $service->duration_minutes }}"
                                                           class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                                </label>

                                                <label class="text-xs font-bold text-[#52637A] sm:col-span-2">
                                                    Mô tả
                                                    <textarea name="description" rows="2" maxlength="2000"
                                                              class="mt-1 w-full rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">{{ $service->description }}</textarea>
                                                </label>

                                                <input type="hidden" name="display_order" value="{{ $service->display_order }}">

                                                <div class="flex items-center gap-2 sm:col-span-2">
                                                    <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">
                                                        Lưu thay đổi
                                                    </button>
                                                    <span class="text-[10px] font-semibold text-[#8A9BB0]">Thao tác được ghi vào nhật ký kiểm toán.</span>
                                                </div>
                                            </form>
                                        </div>
                                    </details>

                                    @if($service->status === 'ACTIVE')
                                        <form method="POST" action="{{ route('admin.services.update', $service) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="category_id" value="{{ $service->category_id }}">
                                            <input type="hidden" name="name" value="{{ $service->name }}">
                                            <input type="hidden" name="description" value="{{ $service->description }}">
                                            <input type="hidden" name="duration_minutes" value="{{ $service->duration_minutes }}">
                                            <input type="hidden" name="price" value="{{ (int) $service->price }}">
                                            <input type="hidden" name="display_order" value="{{ $service->display_order }}">
                                            <input type="hidden" name="status" value="INACTIVE">
                                            <button type="submit"
                                                    class="rounded-lg border border-amber-300 px-3 py-2 text-xs font-bold text-amber-800 transition hover:bg-amber-50"
                                                    title="Gói sẽ không còn hiển thị cho khách đặt mới; lịch đã đặt vẫn giữ nguyên.">
                                                Ngừng bán
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.services.update', $service) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="category_id" value="{{ $service->category_id }}">
                                            <input type="hidden" name="name" value="{{ $service->name }}">
                                            <input type="hidden" name="description" value="{{ $service->description }}">
                                            <input type="hidden" name="duration_minutes" value="{{ $service->duration_minutes }}">
                                            <input type="hidden" name="price" value="{{ (int) $service->price }}">
                                            <input type="hidden" name="display_order" value="{{ $service->display_order }}">
                                            <input type="hidden" name="status" value="ACTIVE">
                                            <button type="submit" class="rounded-lg border border-emerald-300 px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50">
                                                Mở bán lại
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-bold text-[#102A43]">Chưa có gói dịch vụ phù hợp.</p>
                                <p class="mt-1 text-xs text-[#718096]">Thêm gói mới ở biểu mẫu bên dưới hoặc xoá bộ lọc.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $services->links() }}</div>
        @endif
    </section>

    {{-- ═══════════════ TẠO GÓI MỚI ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-[#102A43]">Thêm gói dịch vụ</h2>
                <p class="mt-1 text-xs text-[#718096]">
                    Hệ thống kiểm tra giá không âm, thời lượng lớn hơn 0 và danh mục còn hoạt động trước khi lưu.
                </p>
            </div>
            <span class="rounded-full bg-[#EAF4FB] px-3 py-1 text-[10px] font-extrabold uppercase tracking-wide text-[#23689C]">
                Bước 1 · Thông tin &amp; giá
            </span>
        </div>

        <form method="POST" action="{{ route('admin.services.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @csrf

            <label class="text-xs font-bold text-[#52637A]">
                Tên gói <span class="text-red-500" aria-hidden="true">*</span>
                <input name="name" value="{{ old('name') }}" required maxlength="150"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Danh mục <span class="text-red-500" aria-hidden="true">*</span>
                <select name="category_id" required class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái xuất bản <span class="text-red-500" aria-hidden="true">*</span>
                <select name="status" required class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="ACTIVE" @selected(old('status') === 'ACTIVE')>Đang bán</option>
                    <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>Tạm ngừng (bản nháp)</option>
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Giá niêm yết (VNĐ) <span class="text-red-500" aria-hidden="true">*</span>
                <input name="price" type="number" min="0" step="1000" value="{{ old('price') }}" required
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Thời lượng một ca (phút) <span class="text-red-500" aria-hidden="true">*</span>
                <input name="duration_minutes" type="number" min="15" max="180" value="{{ old('duration_minutes', 60) }}" required
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Thứ tự hiển thị
                <input name="display_order" type="number" min="0" value="{{ old('display_order', 0) }}"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A] sm:col-span-2 xl:col-span-3">
                Mô tả / đối tượng phù hợp
                <textarea name="description" rows="3" maxlength="2000"
                          class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">{{ old('description') }}</textarea>
            </label>

            <div class="flex flex-wrap items-center gap-3 border-t border-[#EEF4F8] pt-4 sm:col-span-2 xl:col-span-3">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lưu gói dịch vụ
                </button>
                <span class="text-[11px] text-[#8A9BB0]">
                    Nội dung hiển thị cho khách là phần mô tả ở trên — không kèm ghi chú nội bộ.
                </span>
            </div>
        </form>
    </section>
</div>
@endsection
