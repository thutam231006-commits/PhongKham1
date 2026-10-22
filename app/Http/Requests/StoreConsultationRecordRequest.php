<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return strtolower((string) $this->user()?->role) === 'specialist';
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
     * Chuẩn hóa văn bản: bỏ khoảng trắng thừa ở hai đầu, rỗng thì thành null.
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
