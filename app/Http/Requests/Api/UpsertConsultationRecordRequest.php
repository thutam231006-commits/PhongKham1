<?php

namespace App\Http\Requests\Api;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpsertConsultationRecordRequest extends FormRequest
{
    /**
     * Chỉ chuyên viên phụ trách lịch hẹn mới được ghi hồ sơ tư vấn (BR-048).
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $appointment = $this->appointment();

        if ($user === null || $appointment === null) {
            return false;
        }

        $appointment->loadMissing('specialistProfile');

        return Gate::allows('updateConsultationRecord', $appointment);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:5000'],
            'follow_up_plan' => ['nullable', 'string', 'max:2000'],
            'follow_up_needed' => ['required', 'boolean'],
            'customer_summary' => ['nullable', 'string', 'max:2000'],
            'customer_recommendation' => ['nullable', 'string', 'max:2000'],
            'version' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'summary.required' => 'Vui lòng nhập tóm tắt buổi tư vấn.',
            'summary.max' => 'Tóm tắt không được vượt quá 5.000 ký tự.',
            'follow_up_plan.max' => 'Kế hoạch theo dõi không được vượt quá 2.000 ký tự.',
            'customer_summary.max' => 'Tóm lược gửi khách không được vượt quá 2.000 ký tự.',
            'customer_recommendation.max' => 'Khuyến nghị gửi khách không được vượt quá 2.000 ký tự.',
            'follow_up_needed.required' => 'Vui lòng chọn trạng thái cần theo dõi tiếp.',
        ];
    }

    /**
     * Chuẩn hóa văn bản trước khi validate: bỏ khoảng trắng thừa ở hai đầu,
     * văn bản rỗng sau khi trim thì coi như chưa nhập.
     */
    protected function prepareForValidation(): void
    {
        $trimmed = [];

        foreach (['summary', 'follow_up_plan', 'customer_summary', 'customer_recommendation'] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);
                $trimmed[$field] = $value === '' ? null : $value;
            }
        }

        if ($trimmed !== []) {
            $this->merge($trimmed);
        }
    }

    /**
     * Lịch hẹn chỉ được ghi hồ sơ khi đang diễn ra hoặc đã hoàn thành (BR-048).
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $appointment = $this->appointment();

                if ($appointment instanceof Appointment
                    && ! in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true)) {
                    $validator->errors()->add(
                        'appointment_id',
                        'Chỉ có thể ghi hồ sơ khi buổi tư vấn đang diễn ra hoặc đã hoàn thành.',
                    );
                }
            },
        ];
    }

    /**
     * Lịch hẹn của route hiện tại.
     */
    public function appointment(): ?Appointment
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment ? $appointment : null;
    }

    /**
     * Dữ liệu chuẩn hóa để ghi xuống record.
     *
     * @return array{summary: string, follow_up_plan: string|null, follow_up_needed: bool, customer_summary: string|null, customer_recommendation: string|null, version: int|null}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'summary' => (string) $validated['summary'],
            'follow_up_plan' => $validated['follow_up_plan'] ?? null,
            'follow_up_needed' => (bool) $validated['follow_up_needed'],
            'customer_summary' => $validated['customer_summary'] ?? null,
            'customer_recommendation' => $validated['customer_recommendation'] ?? null,
            'version' => isset($validated['version']) ? (int) $validated['version'] : null,
        ];
    }
}
