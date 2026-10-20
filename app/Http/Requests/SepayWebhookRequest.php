<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SepayWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $signature = (string) $this->header('X-SePay-Signature');
        $timestamp = (int) $this->header('X-SePay-Timestamp', 0);
        $secret = (string) config('services.sepay.webhook_secret');

        if ($signature !== '' || $timestamp !== 0) {
            if ($secret === '' || abs(time() - $timestamp) > 300) {
                return false;
            }

            $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$this->getContent(), $secret);

            return hash_equals($expected, $signature);
        }

        $apiKey = (string) config('services.sepay.webhook_api_key');
        $authorization = (string) $this->header('Authorization');
        $provided = str_starts_with($authorization, 'Apikey ')
            ? substr($authorization, 7)
            : (string) $this->header('Apikey');

        return $apiKey !== '' && hash_equals($apiKey, $provided);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required'],
            'gateway' => ['nullable', 'string', 'max:50'],
            'transactionDate' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'accountNumber' => ['required', 'string', 'max:50'],
            'subAccount' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
            'content' => ['nullable', 'string', 'max:500'],
            'transferType' => ['required', 'string', 'in:in,out'],
            'description' => ['nullable', 'string', 'max:1000'],
            'transferAmount' => ['required', 'numeric', 'min:0'],
            'accumulated' => ['nullable', 'numeric'],
            'referenceCode' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'UNAUTHORIZED_WEBHOOK',
                'message' => 'Webhook không hợp lệ.',
            ],
        ], 401));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'INVALID_WEBHOOK_PAYLOAD',
                'message' => $validator->errors()->first(),
            ],
        ], 422));
    }
}
