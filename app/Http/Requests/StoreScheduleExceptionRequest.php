<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleExceptionRequest extends FormRequest
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
            'type' => ['required', 'in:DAY_OFF,TIME_BLOCK'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'required_if:type,TIME_BLOCK', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_if:type,TIME_BLOCK', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
            'is_emergency' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_time.required_if' => 'Vui lòng chọn giờ bắt đầu của khoảng bị chặn.',
            'end_time.required_if' => 'Vui lòng chọn giờ kết thúc của khoảng bị chặn.',
        ];
    }
}
