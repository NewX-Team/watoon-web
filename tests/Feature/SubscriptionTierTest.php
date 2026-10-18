<?php

use App\Enums\UserTier;
use App\Models\User;

test('guests cannot update subscription tier', function () {
    $response = $this->post(route('subscription.tier.update'), [
        'tier' => 'pro',
    ]);

    $response->assertRedirect(route('login'));
});

test('authenticated user defaults to free tier', function () {
    $user = User::factory()->create();

    expect($user->tier)->toBe(UserTier::Free);
    expect($user->canAccessTier(UserTier::Free))->toBeTrue();
    expect($user->canAccessTier(UserTier::Pro))->toBeFalse();
    expect($user->canAccessTier(UserTier::Vip))->toBeFalse();
});

test('pro user can access free and pro but not vip', function () {
    $user = User::factory()->pro()->create();

    expect($user->tier)->toBe(UserTier::Pro);
    expect($user->canAccessTier(UserTier::Free))->toBeTrue();
    expect($user->canAccessTier(UserTier::Pro))->toBeTrue();
    expect($user->canAccessTier(UserTier::Vip))->toBeFalse();
});

test('vip user can access all tiers', function () {
    $user = User::factory()->vip()->create();

    expect($user->tier)->toBe(UserTier::Vip);
    expect($user->canAccessTier(UserTier::Free))->toBeTrue();
    expect($user->canAccessTier(UserTier::Pro))->toBeTrue();
    expect($user->canAccessTier(UserTier::Vip))->toBeTrue();
});

test('authenticated user can upgrade subscription tier', function () {
    $user = User::factory()->free()->create();

    $response = $this->actingAs($user)
        ->from(route('messages.index'))
        ->post(route('subscription.tier.update'), [
            'tier' => 'pro',
        ]);

    $response->assertRedirect(route('messages.index'));
    $response->assertSessionHas('status');

    $user->refresh();
    expect($user->tier)->toBe(UserTier::Pro);
});

test('subscription tier update validates tier field', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('subscription.tier.update'), [
            'tier' => 'invalid_tier',
        ]);

    $response->assertSessionHasErrors('tier');
});

test('pricing page can be rendered for guests and users', function () {
    $guestResponse = $this->get(route('pricing'));
    $guestResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('subscription/Pricing')
            ->where('currentTier', 'free')
        );

    $user = User::factory()->vip()->create();
    $userResponse = $this->actingAs($user)->get(route('pricing'));
    $userResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('subscription/Pricing')
            ->where('currentTier', 'vip')
        );
});

test('pricing aliases redirect to pricing route', function () {
    $this->get('/subscription/pricing')->assertRedirect(route('pricing'));
    $this->get('/plans')->assertRedirect(route('pricing'));
});

test('subscription stats returns free defaults for free tier user', function () {
    $user = User::factory()->free()->create();
    $stats = $user->subscriptionStats();

    expect($stats['has_subscription'])->toBeFalse();
    expect($stats['tier'])->toBe('free');
    expect($stats['percentage'])->toBe(0);
    expect($stats['remaining_days'])->toBe(0);
    expect($stats['is_expiring_soon'])->toBeFalse();
});

test('subscription stats calculates percentage and remaining days correctly for active paid user', function () {
    $user = User::factory()->vip()->create([
        'subscription_expires_at' => now()->addDays(30),
    ]);

    $stats = $user->subscriptionStats();

    expect($stats['has_subscription'])->toBeTrue();
    expect($stats['tier'])->toBe('vip');
    expect($stats['percentage'])->toBeGreaterThanOrEqual(95);
    expect($stats['remaining_days'])->toBe(30);
    expect($stats['is_expiring_soon'])->toBeFalse();
});

test('subscription stats flags expiring soon when remaining days is 3 days or less', function () {
    $user = User::factory()->pro()->create([
        'subscription_expires_at' => now()->addDays(2)->addHours(4),
    ]);

    $stats = $user->subscriptionStats();

    expect($stats['has_subscription'])->toBeTrue();
    expect($stats['tier'])->toBe('pro');
    expect($stats['remaining_days'])->toBe(3);
    expect($stats['is_expiring_soon'])->toBeTrue();
});

test('profile edit shares subscription stats in auth user inertia prop', function () {
    $user = User::factory()->vip()->create([
        'subscription_expires_at' => now()->addDays(2),
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/UserSettings')
            ->where('auth.user.tier', 'vip')
            ->where('auth.user.subscription_stats.has_subscription', true)
            ->where('auth.user.subscription_stats.is_expiring_soon', true)
            ->where('auth.user.subscription_stats.tier', 'vip')
        );
});
