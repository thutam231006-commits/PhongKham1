<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepayWebhookController extends Controller
{
    public function sepay(Request $request, PaymentService $payments): JsonResponse
    {
        $secret = (string) config('services.sepay.webhook_secret');
        if ($secret === '' || ! hash_equals($secret, (string) ($request->header('Authorization') ?: $request->header('Apikey')))) {
            return response()->json(['success' => false, 'data' => null, 'error' => ['code' => 'UNAUTHORIZED_WEBHOOK', 'message' => 'Webhook không hợp lệ.']], 401);
        }

        $data = $request->validate(['id' => ['required'], 'transferAmount' => ['required', 'numeric', 'min:0'], 'content' => ['nullable', 'string', 'max:500'], 'transferType' => ['nullable', 'string', 'max:10']]);
        $transaction = $payments->reconcile(['external_id' => (string) $data['id'], 'amount' => (float) $data['transferAmount'], 'content' => $data['content'] ?? '', 'transfer_type' => strtolower((string) ($data['transferType'] ?? 'in')), 'payload' => $request->all()]);

        return response()->json(['success' => true, 'data' => ['transaction_id' => $transaction->id, 'status' => $transaction->status], 'error' => null]);
    }
}
