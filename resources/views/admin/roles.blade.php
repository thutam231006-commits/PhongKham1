@extends('layouts.admin')

@section('title', 'Vai trò & phân quyền')

@php
    $roleMeta = [
        'admin' => ['label' => 'Quản trị viên', 'desc' => 'Toàn quyền hệ thống, cấu hình và kiểm toán', 'tone' => '#7C3AED', 'soft' => '#F5F3FF'],
        'staff' => ['label' => 'Lễ tân', 'desc' => 'Vận hành lịch hẹn, thanh toán tại quầy', 'tone' => '#2D82C4', 'soft' => '#EAF4FB'],
        'specialist' => ['label' => 'Chuyên viên', 'desc' => 'Ca làm việc, lịch hẹn và hồ sơ tư vấn', 'tone' => '#0D9488', 'soft' => '#ECFDF5'],
        'customer' => ['label' => 'Khách hàng', 'desc' => 'Đặt lịch, thanh toán và đánh giá', 'tone' => '#64748B', 'soft' => '#F1F5F9'],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ tài khoản &amp; phân quyền</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Vai trò &amp; phân quyền</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            4 vai trò hệ thống là cố định (không xoá, không đổi mã). Ma trận dưới đây bổ sung quyền chi tiết cho từng vai trò.
            Thay đổi quyền được ghi vào nhật ký kiểm toán.
        </p>
    </div>

    {{-- ═══════════════ TÓM TẮT VAI TRÒ ═══════════════ --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($roles as $role)
            @php
                $code = strtolower((string) $role->code);
                $meta = $roleMeta[$code] ?? ['label' => $role->name, 'desc' => '', 'tone' => '#64748B', 'soft' => '#F1F5F9'];
            @endphp
            <div class="relative overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
                <span class="absolute inset-x-0 top-0 h-1" style="background-color: {{ $meta['tone'] }}" aria-hidden="true"></span>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-[#78889B]">Vai trò hệ thống</p>
                <p class="mt-1.5 text-lg font-black text-[#102A43]">{{ $meta['label'] }}</p>
                <p class="mt-1 text-xs leading-relaxed text-[#718096]">{{ $meta['desc'] }}</p>
                <p class="mt-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold"
                   style="color: {{ $meta['tone'] }}; background-color: {{ $meta['soft'] }}">
                    {{ $role->permissions->count() }} / {{ $permissions->count() }} quyền
                </p>
            </div>
        @endforeach
    </div>

    {{-- ═══════════════ MA TRẬN QUYỀN ═══════════════ --}}
    @foreach($roles as $role)
        @php
            $code = strtolower((string) $role->code);
            $meta = $roleMeta[$code] ?? ['label' => $role->name, 'desc' => '', 'tone' => '#64748B', 'soft' => '#F1F5F9'];
            $grouped = $permissions->groupBy(fn ($permission) => (string) ($permission->group ?: 'khác'));
        @endphp

        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E8F1F6] px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl text-sm font-black"
                          style="color: {{ $meta['tone'] }}; background-color: {{ $meta['soft'] }}" aria-hidden="true">
                        {{ mb_substr($meta['label'], 0, 1) }}
                    </span>
                    <div>
                        <h2 class="text-base font-black text-[#102A43]">{{ $meta['label'] }}</h2>
                        <p class="text-[11px] font-semibold text-[#8A9BB0]">Mã vai trò: <span class="font-mono">{{ $role->code }}</span></p>
                    </div>
                </div>
                <span class="rounded-full bg-[#EAF4FB] px-3 py-1 text-[10px] font-extrabold uppercase tracking-wide text-[#23689C]">
                    {{ $role->is_system ? 'Vai trò hệ thống' : 'Vai trò tuỳ chỉnh' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="p-5">
                @csrf
                @method('PUT')

                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach($grouped as $group => $groupPermissions)
                        <fieldset class="rounded-xl border border-[#EEF4F8] bg-[#FBFDFF] p-3.5">
                            <legend class="px-1 text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">
                                {{ \Illuminate\Support\Str::headline($group) }}
                            </legend>
                            <div class="mt-2 space-y-1.5">
                                @foreach($groupPermissions as $permission)
                                    <label class="flex items-start gap-2 rounded-lg px-1.5 py-1 text-xs font-semibold text-[#334E68] transition hover:bg-white">
                                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}"
                                               @checked($role->permissions->contains('id', $permission->id))
                                               class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#B8D2E4] text-[#2D82C4] focus:ring-[#2D82C4]">
                                        <span class="min-w-0">
                                            <span class="block">{{ $permission->name }}</span>
                                            <span class="block font-mono text-[10px] font-normal text-[#8A9BB0]">{{ $permission->code }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-[#EEF4F8] pt-4">
                    <button type="submit" class="rounded-lg px-5 py-2.5 text-sm font-bold text-white transition"
                            style="background-color: {{ $meta['tone'] }}">
                        Lưu quyền cho {{ $meta['label'] }}
                    </button>
                    <span class="text-[11px] text-[#8A9BB0]">Chỉ quyền bổ sung — quyền lõi luôn được bảo vệ.</span>
                </div>
            </form>
        </section>
    @endforeach
</div>
@endsection
