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
            'version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
