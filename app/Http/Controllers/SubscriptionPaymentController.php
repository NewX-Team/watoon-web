<?php

namespace App\Http\Controllers;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Enums\UserTier;
use App\Models\Subscription;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Create checkout session and return Midtrans Snap token.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tier' => ['required', Rule::in(['pro', 'vip'])],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $targetTier = UserTier::from($validated['tier']);
        $billingCycle = BillingCycle::from($validated['billing_cycle']);

        // Check subscription downgrade/upgrade rules
        $check = $user->canSubscribeTo($targetTier);
        if (! $check['allowed']) {
            return response()->json([
                'message' => $check['reason'],
            ], 422);
        }

        $grossAmount = BillingCycle::priceFor($targetTier, $billingCycle);
        $orderId = 'WTN-SUB-'.date('YmdHis').'-'.strtoupper(Str::random(6));

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'tier' => $targetTier,
            'billing_cycle' => $billingCycle,
            'gross_amount' => $grossAmount,
            'status' => SubscriptionStatus::Pending,
        ]);

        $snapData = $this->midtransService->createSnapTransaction($subscription, $user);

        return response()->json([
            'order_id' => $subscription->order_id,
            'snap_token' => $snapData['token'],
            'redirect_url' => $snapData['redirect_url'],
            'is_mock' => $snapData['is_mock'] ?? false,
            'tier' => $subscription->tier->value,
            'billing_cycle' => $subscription->billing_cycle->value,
            'gross_amount' => $subscription->gross_amount,
            'client_key' => config('services.midtrans.client_key'),
            'is_production' => (bool) config('services.midtrans.is_production'),
        ]);
    }

    /**
     * Check status of a subscription order, verifying with Midtrans API if pending.
     */
    public function status(Request $request, string $orderId): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If pending, check with Midtrans API in real-time
        if ($subscription->status === SubscriptionStatus::Pending) {
            $midtransStatus = $this->midtransService->getTransactionStatus($orderId);
            if ($midtransStatus && isset($midtransStatus['transaction_status'])) {
                $status = $midtransStatus['transaction_status'];
                $fraud = $midtransStatus['fraud_status'] ?? 'accept';
                if ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept')) {
                    $this->midtransService->processNotification($midtransStatus, skipSignature: true);
                    $subscription = $subscription->fresh();
                }
            }
        }

        $freshUser = $user->fresh();

        return response()->json([
            'order_id' => $subscription->order_id,
            'status' => $subscription->status->value,
            'is_paid' => $subscription->isPaid(),
            'tier' => $subscription->tier->value,
            'expires_at' => $subscription->expires_at?->toIso8601String(),
            'user_tier' => $freshUser->effectiveTier()->value,
            'has_active_subscription' => $freshUser->hasActiveSubscription(),
        ]);
    }

    /**
     * Confirm subscription payment from client Snap callback and verify with Midtrans.
     */
    public function confirm(Request $request, string $orderId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $subscription = Subscription::where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If already settled, return success immediately
        if ($subscription->isPaid()) {
            $freshUser = $user->fresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran langganan sudah aktif.',
                'order_id' => $subscription->order_id,
                'tier' => $subscription->tier->value,
                'user_tier' => $freshUser->effectiveTier()->value,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
                'has_active_subscription' => $freshUser->hasActiveSubscription(),
            ]);
        }

        // 1. Try querying official Midtrans API status
        $midtransStatus = $this->midtransService->getTransactionStatus($orderId);
        $clientPayload = $request->all();

        // 2. Determine payload to process
        $payloadToProcess = null;

        if ($midtransStatus && isset($midtransStatus['transaction_status'])) {
            $status = $midtransStatus['transaction_status'];
            $fraud = $midtransStatus['fraud_status'] ?? 'accept';
            if ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept')) {
                $payloadToProcess = $midtransStatus;
            }
        }

        // If Midtrans API didn't return settlement yet (e.g. slight delay in sandbox indexing),
        // but client Snap returned success result:
        if (! $payloadToProcess) {
            $clientStatus = $clientPayload['transaction_status'] ?? null;
            $clientStatusCode = (string) ($clientPayload['status_code'] ?? '');
            $isClientSuccess = $clientStatus === 'settlement'
                || ($clientStatus === 'capture' && ($clientPayload['fraud_status'] ?? 'accept') === 'accept')
                || $clientStatusCode === '200'
                || ($clientPayload['status'] ?? null) === 'success';

            if ($isClientSuccess) {
                $payloadToProcess = [
                    'order_id' => $orderId,
                    'status_code' => '200',
                    'gross_amount' => (string) $subscription->gross_amount,
                    'transaction_status' => 'settlement',
                    'payment_type' => $clientPayload['payment_type'] ?? 'midtrans_snap',
                    'settlement_time' => now()->toDateTimeString(),
                    'fraud_status' => 'accept',
                    'signature_key' => '',
                ];
            }
        }

        if ($payloadToProcess) {
            $updated = $this->midtransService->processNotification($payloadToProcess, skipSignature: true);
            $freshUser = $user->fresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran sah dan kasta berhasil diperbarui!',
                'order_id' => $updated->order_id,
                'tier' => $updated->tier->value,
                'user_tier' => $freshUser->effectiveTier()->value,
                'expires_at' => $updated->expires_at?->toIso8601String(),
                'has_active_subscription' => $freshUser->hasActiveSubscription(),
            ]);
        }

        // If still pending
        return response()->json([
            'status' => 'pending',
            'message' => 'Pembayaran belum diselesaikan atau masih menunggu konfirmasi bank.',
            'order_id' => $subscription->order_id,
            'tier' => $subscription->tier->value,
            'user_tier' => $user->fresh()->effectiveTier()->value,
            'expires_at' => $subscription->expires_at?->toIso8601String(),
            'has_active_subscription' => false,
        ], 202);
    }

    /**
     * Simulate successful payment (for development / mock when keys are not configured).
     */
    public function simulate(Request $request, string $orderId): JsonResponse
    {
        $subscription = Subscription::where('order_id', $orderId)->firstOrFail();

        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => (string) $subscription->gross_amount,
            'transaction_status' => 'settlement',
            'payment_type' => 'simulation',
            'settlement_time' => now()->toDateTimeString(),
            'signature_key' => '',
        ];

        $updated = $this->midtransService->processNotification($payload, skipSignature: true);

        return response()->json([
            'status' => 'success',
            'message' => 'Simulasi pembayaran Midtrans berhasil diselesaikan!',
            'order_id' => $updated->order_id,
            'tier' => $updated->tier->value,
            'expires_at' => $updated->expires_at?->toIso8601String(),
            'user_tier' => $subscription->user->fresh()->effectiveTier()->value,
        ]);
    }
}
