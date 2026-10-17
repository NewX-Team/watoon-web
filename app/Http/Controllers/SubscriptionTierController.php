<?php

namespace App\Http\Controllers;

use App\Enums\UserTier;
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
