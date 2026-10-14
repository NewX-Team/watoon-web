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
