<?php

use App\Actions\Fortify\CreateNewUser;
use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Enums\UserTier;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('newly registered user defaults to free tier and null subscription expiry', function () {
    $action = new CreateNewUser;
    $user = $action->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    expect($user->tier)->toBe(UserTier::Free)
        ->and($user->subscription_expires_at)->toBeNull()
        ->and($user->hasActiveSubscription())->toBeFalse()
        ->and($user->effectiveTier())->toBe(UserTier::Free);
});

test('guest cannot checkout subscription', function () {
    $response = $this->postJson(route('subscription.checkout'), [
        'tier' => 'pro',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertUnauthorized();
});

test('free user can checkout pro subscription and get snap token', function () {
    $user = User::factory()->free()->create();

    $response = $this->actingAs($user)->postJson(route('subscription.checkout'), [
        'tier' => 'pro',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'order_id',
            'snap_token',
            'redirect_url',
            'tier',
            'billing_cycle',
            'gross_amount',
        ]);

    $orderId = $response->json('order_id');
    $subscription = Subscription::where('order_id', $orderId)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->tier)->toBe(UserTier::Pro)
        ->and($subscription->gross_amount)->toBe(80000)
        ->and($subscription->status)->toBe(SubscriptionStatus::Pending);
});

test('free user can checkout vip yearly subscription', function () {
    $user = User::factory()->free()->create();

    $response = $this->actingAs($user)->postJson(route('subscription.checkout'), [
        'tier' => 'vip',
        'billing_cycle' => 'yearly',
    ]);

    $response->assertOk()
        ->assertJson([
            'tier' => 'vip',
            'billing_cycle' => 'yearly',
            'gross_amount' => 1440000,
        ]);
});

test('pro user can upgrade to vip at normal price', function () {
    $user = User::factory()->pro()->create([
        'subscription_expires_at' => now()->addDays(15),
    ]);

    expect($user->hasActiveSubscription())->toBeTrue();

    $response = $this->actingAs($user)->postJson(route('subscription.checkout'), [
        'tier' => 'vip',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertOk()
        ->assertJson([
            'tier' => 'vip',
            'billing_cycle' => 'monthly',
            'gross_amount' => 150000,
        ]);
});

test('active vip user cannot downgrade to pro', function () {
    $user = User::factory()->vip()->create([
        'subscription_expires_at' => now()->addDays(20),
    ]);

    expect($user->hasActiveSubscription())->toBeTrue();

    $response = $this->actingAs($user)->postJson(route('subscription.checkout'), [
        'tier' => 'pro',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('active vip user cannot downgrade to free', function () {
    $user = User::factory()->vip()->create([
        'subscription_expires_at' => now()->addDays(20),
    ]);

    $check = $user->canSubscribeTo(UserTier::Free);
    expect($check['allowed'])->toBeFalse()
        ->and($check['reason'])->toContain('VIP');
});

test('active vip user can extend vip subscription', function () {
    $user = User::factory()->vip()->create([
        'subscription_expires_at' => now()->addDays(20),
    ]);

    $check = $user->canSubscribeTo(UserTier::Vip);
    expect($check['allowed'])->toBeTrue();

    $response = $this->actingAs($user)->postJson(route('subscription.checkout'), [
        'tier' => 'vip',
        'billing_cycle' => 'monthly',
    ]);

    $response->assertOk()
        ->assertJson([
            'tier' => 'vip',
            'gross_amount' => 150000,
        ]);
});

test('midtrans webhook settlement activates user subscription and updates tier and expiry', function () {
    $user = User::factory()->free()->create();

    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-SUB-TEST-1001',
        'tier' => UserTier::Pro,
        'billing_cycle' => BillingCycle::Monthly,
        'gross_amount' => 80000,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payload = [
        'order_id' => $subscription->order_id,
        'status_code' => '200',
        'gross_amount' => '80000',
        'transaction_status' => 'settlement',
        'payment_type' => 'qris',
        'settlement_time' => now()->toDateTimeString(),
        'signature_key' => '',
    ];

    $response = $this->postJson(route('midtrans.webhook'), $payload);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'order_id' => $subscription->order_id,
            'subscription_status' => 'settlement',
        ]);

    $user->refresh();
    $subscription->refresh();

    expect($user->tier)->toBe(UserTier::Pro)
        ->and($user->hasActiveSubscription())->toBeTrue()
        ->and($user->subscription_expires_at)->not->toBeNull()
        ->and($user->subscription_expires_at->isFuture())->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::Settlement)
        ->and($subscription->payment_type)->toBe('qris');
});

test('midtrans webhook capture with accept status activates subscription', function () {
    $user = User::factory()->free()->create();

    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-SUB-TEST-1002',
        'tier' => UserTier::Vip,
        'billing_cycle' => BillingCycle::Yearly,
        'gross_amount' => 1440000,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payload = [
        'order_id' => $subscription->order_id,
        'status_code' => '200',
        'gross_amount' => '1440000',
        'transaction_status' => 'capture',
        'fraud_status' => 'accept',
        'payment_type' => 'credit_card',
        'settlement_time' => now()->toDateTimeString(),
        'signature_key' => '',
    ];

    $response = $this->postJson(route('midtrans.webhook'), $payload);

    $response->assertOk();

    $user->refresh();
    expect($user->tier)->toBe(UserTier::Vip)
        ->and($user->hasActiveSubscription())->toBeTrue()
        // 365 days in future
        ->and(now()->diffInDays($user->subscription_expires_at))->toBeGreaterThanOrEqual(364);
});

test('subscription extending adds duration to current expiration', function () {
    $currentExpiry = now()->addDays(10);
    $user = User::factory()->pro()->create([
        'subscription_expires_at' => $currentExpiry,
    ]);

    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-SUB-TEST-EXTEND',
        'tier' => UserTier::Pro,
        'billing_cycle' => BillingCycle::Monthly,
        'gross_amount' => 80000,
        'status' => SubscriptionStatus::Pending,
    ]);

    $payload = [
        'order_id' => $subscription->order_id,
        'status_code' => '200',
        'gross_amount' => '80000',
        'transaction_status' => 'settlement',
        'payment_type' => 'bank_transfer',
        'settlement_time' => now()->toDateTimeString(),
        'signature_key' => '',
    ];

    $this->postJson(route('midtrans.webhook'), $payload);

    $user->refresh();
    // Expiration should be extended from 10 days to approx 40 days
    expect(now()->diffInDays($user->subscription_expires_at))->toBeGreaterThanOrEqual(39);
});

test('expired subscription user effective tier reverts to free', function () {
    $user = User::factory()->vip()->create([
        'subscription_expires_at' => now()->subDay(),
    ]);

    expect($user->hasActiveSubscription())->toBeFalse()
        ->and($user->effectiveTier())->toBe(UserTier::Free)
        ->and($user->canAccessTier(UserTier::Vip))->toBeFalse()
        ->and($user->canAccessTier(UserTier::Pro))->toBeFalse()
        ->and($user->canAccessTier(UserTier::Free))->toBeTrue();
});

test('midtrans webhook validates signature when server key is configured', function () {
    Config::set('services.midtrans.server_key', 'test-server-key-12345');

    $user = User::factory()->free()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-SUB-SIG-TEST',
        'tier' => UserTier::Pro,
        'billing_cycle' => BillingCycle::Monthly,
        'gross_amount' => 80000,
        'status' => SubscriptionStatus::Pending,
    ]);

    // Invalid signature
    $response = $this->postJson(route('midtrans.webhook'), [
        'order_id' => $subscription->order_id,
        'status_code' => '200',
        'gross_amount' => '80000',
        'transaction_status' => 'settlement',
        'signature_key' => 'invalid-signature-hash',
    ]);

    $response->assertStatus(400);

    // Valid signature
    $validSignature = hash('sha512', $subscription->order_id.'200'.'80000'.'test-server-key-12345');
    $validResponse = $this->postJson(route('midtrans.webhook'), [
        'order_id' => $subscription->order_id,
        'status_code' => '200',
        'gross_amount' => '80000',
        'transaction_status' => 'settlement',
        'signature_key' => $validSignature,
    ]);

    $validResponse->assertOk();
    $user->refresh();
    expect($user->tier)->toBe(UserTier::Pro);

    Config::set('services.midtrans.server_key', null);
});

test('simulation endpoint allows developer to activate subscription in local mock mode', function () {
    $user = User::factory()->free()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-SIMULATE-ORDER',
        'tier' => UserTier::Vip,
        'billing_cycle' => BillingCycle::Monthly,
        'gross_amount' => 150000,
        'status' => SubscriptionStatus::Pending,
    ]);

    $response = $this->actingAs($user)->postJson(route('subscription.orders.simulate', ['orderId' => $subscription->order_id]));

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'order_id' => 'WTN-SIMULATE-ORDER',
            'tier' => 'vip',
            'user_tier' => 'vip',
        ]);

    $user->refresh();
    expect($user->tier)->toBe(UserTier::Vip)
        ->and($user->hasActiveSubscription())->toBeTrue();
});

test('confirm endpoint activates pending subscription and upgrades user tier', function () {
    $user = User::factory()->free()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-CONFIRM-ORDER-101',
        'tier' => UserTier::Pro,
        'billing_cycle' => BillingCycle::Monthly,
        'gross_amount' => 80000,
        'status' => SubscriptionStatus::Pending,
    ]);

    $response = $this->actingAs($user)->postJson(route('subscription.orders.confirm', ['orderId' => $subscription->order_id]), [
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'payment_type' => 'credit_card',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'order_id' => 'WTN-CONFIRM-ORDER-101',
            'tier' => 'pro',
            'user_tier' => 'pro',
            'has_active_subscription' => true,
        ]);

    $user->refresh();
    $subscription->refresh();

    expect($user->tier)->toBe(UserTier::Pro)
        ->and($user->hasActiveSubscription())->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::Settlement);
});

test('status endpoint returns user tier and active subscription state', function () {
    $user = User::factory()->pro()->create([
        'subscription_expires_at' => now()->addDays(30),
    ]);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'order_id' => 'WTN-STATUS-ORDER-102',
        'tier' => UserTier::Pro,
        'billing_cycle' => BillingCycle::Monthly,
        'gross_amount' => 80000,
        'status' => SubscriptionStatus::Settlement,
    ]);

    $response = $this->actingAs($user)->getJson(route('subscription.orders.status', ['orderId' => $subscription->order_id]));

    $response->assertOk()
        ->assertJson([
            'order_id' => 'WTN-STATUS-ORDER-102',
            'status' => 'settlement',
            'is_paid' => true,
            'tier' => 'pro',
            'user_tier' => 'pro',
            'has_active_subscription' => true,
        ]);
});
