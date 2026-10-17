<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Handle incoming webhook notification from Midtrans.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Webhook Received', [
            'order_id' => $payload['order_id'] ?? null,
            'status' => $payload['transaction_status'] ?? null,
        ]);

        try {
            $subscription = $this->midtransService->processNotification($payload);

            return response()->json([
                'status' => 'success',
                'order_id' => $subscription->order_id,
                'subscription_status' => $subscription->status->value,
            ]);
        } catch (Throwable $e) {
            Log::error('Midtrans Webhook Handling Error: '.$e->getMessage(), [
                'payload' => $payload,
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
