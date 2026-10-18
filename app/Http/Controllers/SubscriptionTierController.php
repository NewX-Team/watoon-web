<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Enums\UserTier;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionTierController extends Controller
{
    /**
     * Display the subscription pricing plans page.
     */
    public function pricing(Request $request): Response
    {
        $user = $request->user();

        // If user is currently without active subscription, check if they have a pending order recently settled on Midtrans
        if ($user && ! $user->hasActiveSubscription()) {
            $latestPending = $user->subscriptions()
                ->where('status', SubscriptionStatus::Pending)
                ->where('created_at', '>=', now()->subHours(24))
                ->latest()
                ->first();

            if ($latestPending) {
                $midtransService = app(MidtransService::class);
                $midtransStatus = $midtransService->getTransactionStatus($latestPending->order_id);
                if ($midtransStatus && isset($midtransStatus['transaction_status'])) {
                    $status = $midtransStatus['transaction_status'];
                    $fraud = $midtransStatus['fraud_status'] ?? 'accept';
                    if ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept')) {
                        $midtransService->processNotification($midtransStatus, skipSignature: true);
                        $user = $user->fresh();
                    }
                }
            }
        }

        return Inertia::render('subscription/Pricing', [
            'currentTier' => $user?->effectiveTier()->value ?? 'free',
            'rawTier' => $user?->tier?->value ?? 'free',
            'hasActiveSubscription' => $user?->hasActiveSubscription() ?? false,
            'subscriptionExpiresAt' => $user?->subscription_expires_at?->toIso8601String(),
            'subscriptionExpiresFormatted' => $user?->subscription_expires_at?->format('d M Y'),
            'canSubscribePro' => $user ? $user->canSubscribeTo(UserTier::Pro) : ['allowed' => true, 'reason' => null],
            'canSubscribeVip' => $user ? $user->canSubscribeTo(UserTier::Vip) : ['allowed' => true, 'reason' => null],
            'midtransClientKey' => config('services.midtrans.client_key'),
            'midtransSnapJsUrl' => config('services.midtrans.snap_js_url'),
            'midtransIsProduction' => (bool) config('services.midtrans.is_production'),
        ]);
    }

    /**
     * Update the authenticated user's tier.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tier' => ['required', Rule::enum(UserTier::class)],
        ]);

        $tier = UserTier::from($validated['tier']);
        $user = $request->user();

        $check = $user?->canSubscribeTo($tier);
        if ($check && ! $check['allowed']) {
            return back()->withErrors(['tier' => $check['reason']]);
        }

        $user?->update([
            'tier' => $tier,
            'subscription_expires_at' => $tier === UserTier::Free ? null : ($user->subscription_expires_at ?? now()->addDays(30)),
        ]);

        return back()->with('status', "Kasta langganan Anda berhasil diubah ke {$tier->label()}");
    }
}
