<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return strtoupper((string) $this->user()?->role) === 'CUSTOMER';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9](\s*-\s*([01][0-9]|2[0-3]):[0-5][0-9])?$/'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['CASH', 'BANK_QR'])],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ];
    }
}
