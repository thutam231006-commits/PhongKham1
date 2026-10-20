{{--
    x-modal-confirm — Pop-up xác nhận hành động không hoàn tác
    ------------------------------------------------------------
    Căn cứ: ThietKeGiaoDien.md §1.6 (NFR-USA-002)
    Quy tắc bắt buộc (§1.6):
      • Hộp xác nhận nêu rõ ĐỐI TƯỢNG và HẬU QUẢ.
      • Hai nút: nút nguy hiểm (tên hành động) + "Quay lại".
      • Khi cần lý do: ô lý do bắt buộc, tối đa 500 ký tự.
    NFR-SEC-004: không render HTML từ input (dùng e()).
    Alpine.js bắt buộc để điều khiển open/close.

    Props:
      @type  string   $id            — ID duy nhất (dùng cho aria-labelledby, x-data)
      @type  string   $title         — Tiêu đề hộp thoại (ví dụ: "Xác nhận hủy lịch")
      @type  string   $description   — Mô tả hậu quả rõ ràng (ví dụ: "Bạn sẽ được hoàn 250.000 đ.")
      @type  string   $confirmLabel  — Nhãn nút xác nhận nguy hiểm (ví dụ: "Hủy lịch hẹn")
      @type  string   $cancelLabel   — Nhãn nút "Quay lại" (mặc định "Quay lại")
      @type  string   $confirmClass  — CSS bổ sung cho nút xác nhận (mặc định: đỏ)
      @type  bool     $requireReason — Có bắt buộc nhập lý do không (§1.6)
      @type  string   $reasonLabel   — Nhãn ô lý do (mặc định "Lý do")
      @type  int      $reasonMaxLen  — Độ dài tối đa lý do (mặc định 500)
      @type  string   $action        — form action URL (khi dùng form-based confirm)
      @type  string   $method        — HTTP method (mặc định "POST")
      @type  bool     $useForm       — true = submit form, false = emit event (Alpine)

    Named slots:
      $trigger   — Nội dung kích hoạt mở modal (nút, link...)
      $details   — Nội dung chi tiết bổ sung (ví dụ: bảng hoàn tiền, countdown)
      $slot      — Slot mặc định (content bổ sung trong modal)

    Cách dùng (form POST):
      <x-modal-confirm
          id="cancel-appt-{{ $appointment->id }}"
          title="Xác nhận hủy lịch hẹn"
          description="Hủy lịch 15/10 09:00 với chuyên viên Nguyễn Văn A. Bạn sẽ được hoàn 250.000 đ."
          confirmLabel="Hủy lịch hẹn"
          :requireReason="true"
          reasonLabel="Lý do hủy (tùy chọn)"
          :action="route('customer.appointments.cancel', $appointment)"
          :useForm="true">
          <x-slot:trigger>
              <button type="button" ...>Hủy lịch</button>
          </x-slot:trigger>
      </x-modal-confirm>

    Cách dùng (emit Alpine event):
      <x-modal-confirm
          id="delete-user"
          title="Xóa người dùng"
          description="Hành động này không thể hoàn tác."
          confirmLabel="Xóa">
          <x-slot:trigger>
              <button @click="$dispatch('open-modal-delete-user')">Xóa</button>
          </x-slot:trigger>
      </x-modal-confirm>
      {{-- Lắng nghe: @modal-confirm-delete-user --}}
--}}

@props([
    'id'           => 'modal-confirm',
    'title'        => 'Xác nhận thao tác',
    'description'  => '',
    'confirmLabel' => 'Xác nhận',
    'cancelLabel'  => 'Quay lại',
    'confirmClass' => '',
    'requireReason'=> false,
    'reasonLabel'  => 'Lý do',
    'reasonMaxLen' => 500,
    'action'       => '#',
    'method'       => 'POST',
    'useForm'      => true,
])

@php
    $modalVar    = 'modal_' . str_replace(['-', '.'], '_', $id);
    $labelId     = $id . '-title';
    $descId      = $id . '-desc';
    $reasonId    = $id . '-reason';

    // Lớp nút xác nhận — mặc định đỏ theo §1.6 "nút nguy hiểm"
    $dangerClass = $confirmClass ?: 'bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white';

    // Phương thức HTTP giả (Laravel _method spoofing)
    $fakeMethod = strtoupper($method) !== 'POST' ? strtoupper($method) : null;
@endphp

{{-- ────────────────────────────────────────────
     Container Alpine.js — quản lý trạng thái modal
     ──────────────────────────────────────────── --}}
<div
    x-data="confirmModal({{ $requireReason ? 'true' : 'false' }}, {{ $useForm ? 'true' : 'false' }}, 'modal-confirm-{{ $id }}')"
    @open-modal-{{ $id }}.window="openModal()"
    @close-modal-{{ $id }}.window="closeModal()"
    {{ $attributes->only('class') }}>

    {{-- ── TRIGGER SLOT (nút / link mở modal) ── --}}
    @if (isset($trigger))
    <div @click="openModal()">
        {{ $trigger }}
    </div>
    @endif

    {{-- ════════════════════════════════════════
         OVERLAY + MODAL DIALOG
         ════════════════════════════════════════ --}}
    <div
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $labelId }}"
        aria-describedby="{{ $descId }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
             @click="closeModal()"
             aria-hidden="true"></div>

        {{-- Panel --}}
        <div
            class="relative z-10 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-200"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            @keydown.escape.window="closeModal()">

            {{-- ── Header modal ── --}}
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    {{-- Icon cảnh báo --}}
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100"
                         aria-hidden="true">
                        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 id="{{ $labelId }}"
                            class="text-base font-semibold text-[#1A202C]">
                            {{ e($title) }}
                        </h2>
                    </div>
                </div>
                {{-- Nút đóng (X) --}}
                <button type="button"
                        @click="closeModal()"
                        class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600
                               focus:outline-none focus:ring-2 focus:ring-[#2D82C4] transition"
                        aria-label="Đóng hộp thoại">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ── Nội dung modal ── --}}
            @if ($useForm)
            {{-- Form-based: submit khi xác nhận --}}
            <form
                x-ref="modalForm"
                action="{{ $action }}"
                method="POST"
                novalidate>
                @csrf
                @if ($fakeMethod)
                    @method($fakeMethod)
                @endif
            @endif

                <div class="px-6 py-5 space-y-4">

                    {{-- Mô tả hậu quả (§1.6: nêu rõ đối tượng và hậu quả) --}}
                    @if ($description)
                    <p id="{{ $descId }}" class="text-sm text-[#4A5568] leading-relaxed">
                        {!! nl2br(e($description)) !!}
                    </p>
                    @endif

                    {{-- Slot chi tiết bổ sung (ví dụ bảng hoàn tiền, countdown) --}}
                    @if (isset($details))
                    <div class="rounded-lg bg-[#F5FAFE] border border-gray-200 p-4 text-sm">
                        {{ $details }}
                    </div>
                    @endif

                    {{-- Slot nội dung mặc định --}}
                    {{ $slot }}

                    {{-- Ô lý do (bắt buộc khi $requireReason = true) --}}
                    @if ($requireReason)
                    <div>
                        <label for="{{ $reasonId }}"
                               class="block text-sm font-medium text-[#1A202C]">
                            {{ e($reasonLabel) }}
                            <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(bắt buộc)</span>
                        </label>
                        <textarea
                            id="{{ $reasonId }}"
                            name="reason"
                            x-ref="reasonInput"
                            x-model="reason"
                            rows="3"
                            maxlength="{{ $reasonMaxLen }}"
                            required
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                   text-[#1A202C] placeholder-[#4A5568]
                                   focus:border-[#2D82C4] focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/30
                                   resize-none transition"
                            placeholder="Nhập lý do…"
                            :aria-describedby="'{{ $reasonId }}-count'"></textarea>
                        {{-- Bộ đếm ký tự (§1.5) --}}
                        <p id="{{ $reasonId }}-count"
                           class="mt-1 text-right text-xs text-[#4A5568]"
                           aria-live="polite">
                            <span x-text="reason.length"></span>/{{ $reasonMaxLen }}
                        </p>
                    </div>
                    @else
                    {{-- Lý do tùy chọn --}}
                    <div>
                        <label for="{{ $reasonId }}"
                               class="block text-sm font-medium text-[#1A202C]">
                            {{ e($reasonLabel) }}
                            <span class="text-xs font-normal text-[#4A5568] ml-1">(tùy chọn)</span>
                        </label>
                        <textarea
                            id="{{ $reasonId }}"
                            name="reason"
                            x-ref="reasonInput"
                            x-model="reason"
                            rows="2"
                            maxlength="{{ $reasonMaxLen }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                                   text-[#1A202C] placeholder-[#4A5568]
                                   focus:border-[#2D82C4] focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/30
                                   resize-none transition"
                            placeholder="Nhập lý do (tùy chọn)…"
                            :aria-describedby="'{{ $reasonId }}-count-opt'"></textarea>
                        <p id="{{ $reasonId }}-count-opt"
                           class="mt-1 text-right text-xs text-[#4A5568]"
                           aria-live="polite">
                            <span x-text="reason.length"></span>/{{ $reasonMaxLen }}
                        </p>
                    </div>
                    @endif

                </div>

                {{-- ── Footer: hai nút hành động (§1.6) ── --}}
                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-6 py-4 sm:flex-row sm:justify-end">

                    {{-- Nút "Quay lại" — luôn ở bên trái / dưới cùng trên mobile --}}
                    <button type="button"
                            x-ref="cancelBtn"
                            @click="close()"
                            class="w-full rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium
                                   text-[#1A202C] hover:bg-gray-50
                                   focus:outline-none focus:ring-2 focus:ring-[#2D82C4] focus:ring-offset-1 transition
                                   sm:w-auto">
                        {{ e($cancelLabel) }}
                    </button>

                    {{-- Nút nguy hiểm — tên hành động (§1.6) --}}
                    <button type="button"
                            @click="confirmHandler()"
                            :disabled="submitting"
                            :class="{ 'opacity-60 cursor-not-allowed': submitting }"
                            class="w-full rounded-lg px-5 py-2.5 text-sm font-semibold
                                   focus:outline-none focus:ring-2 focus:ring-offset-1 transition
                                   {{ $dangerClass }} sm:w-auto"
                            aria-live="polite">
                        {{-- Spinner khi đang gửi (§1.4 — nút bị vô hiệu khi đang gửi) --}}
                        <span x-show="submitting" class="mr-1.5 inline-block" aria-hidden="true">
                            <svg class="inline h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </span>
                        <span x-text="submitting ? 'Đang xử lý…' : '{{ addslashes(e($confirmLabel)) }}'"></span>
                    </button>
                </div>

            @if ($useForm)
            </form>
            @endif

        </div>{{-- /panel --}}
    </div>{{-- /overlay --}}

</div>{{-- /x-data --}}
