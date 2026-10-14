<?php

namespace App\Http\Controllers;

use App\Enums\UserTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionTierController extends Controller
{
    /**
     * Update the authenticated user's tier.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tier' => ['required', Rule::enum(UserTier::class)],
        ]);

        $tier = UserTier::from($validated['tier']);

        $request->user()?->update([
            'tier' => $tier,
        ]);

        return back()->with('status', "Kasta langganan Anda berhasil diubah ke {$tier->label()}");
    }
}
