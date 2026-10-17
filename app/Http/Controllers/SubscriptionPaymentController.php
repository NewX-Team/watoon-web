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
     * Check status of a subscription order.
     */
    public function status(Request $request, string $orderId): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'order_id' => $subscription->order_id,
            'status' => $subscription->status->value,
            'is_paid' => $subscription->isPaid(),
            'tier' => $subscription->tier->value,
            'expires_at' => $subscription->expires_at?->toIso8601String(),
            'user_tier' => $user->fresh()->effectiveTier()->value,
        ]);
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

        $updated = $this->midtransService->processNotification($payload);

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
