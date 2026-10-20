<?php

namespace App\Http\Controllers;

use App\Http\Requests\SepayWebhookRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class SepayWebhookController extends Controller
{
    public function sepay(SepayWebhookRequest $request, PaymentService $payments): JsonResponse
    {
        $data = $request->validated();
        $payments->reconcile([
            'external_id' => (string) $data['id'],
            'amount' => (float) $data['transferAmount'],
            'account_number' => $data['accountNumber'],
            'content' => $data['content'] ?? '',
            'transfer_type' => strtolower($data['transferType']),
            'payload' => $request->all(),
        ]);

        return response()->json(['success' => true]);
    }
}
