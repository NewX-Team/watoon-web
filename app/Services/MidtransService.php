<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransService
{
    /**
     * Create Snap Transaction token for the subscription order.
     *
     * @return array{token: string, redirect_url: string, is_mock?: bool}
     */
    public function createSnapTransaction(Subscription $subscription, User $user): array
    {
        $serverKey = config('services.midtrans.server_key');

        // Development/Test mock fallback if server key is not configured
        if (empty($serverKey)) {
            $mockToken = 'MOCK-SNAP-'.Str::upper(Str::random(24));
            $mockUrl = route('pricing', ['simulated_order' => $subscription->order_id]);

            $subscription->update([
                'snap_token' => $mockToken,
                'snap_redirect_url' => $mockUrl,
            ]);

            return [
                'token' => $mockToken,
                'redirect_url' => $mockUrl,
                'is_mock' => true,
            ];
        }

        $snapUrl = config('services.midtrans.snap_url');

        $payload = [
            'transaction_details' => [
                'order_id' => $subscription->order_id,
                'gross_amount' => $subscription->gross_amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id' => strtoupper($subscription->tier->value.'-'.$subscription->billing_cycle->value),
                    'price' => $subscription->gross_amount,
                    'quantity' => 1,
                    'name' => "WATOON {$subscription->tier->label()} ({$subscription->billing_cycle->label()})",
                ],
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($snapUrl, $payload);

        if (! $response->successful()) {
            Log::error('Midtrans Snap Transaction Failed', [
                'order_id' => $subscription->order_id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException($response->json('error_messages.0') ?? 'Gagal membuat transaksi pembayaran Midtrans.');
        }

        $token = $response->json('token');
        $redirectUrl = $response->json('redirect_url');

        $subscription->update([
            'snap_token' => $token,
            'snap_redirect_url' => $redirectUrl,
            'payload' => $response->json(),
        ]);

        return [
            'token' => $token,
            'redirect_url' => $redirectUrl,
            'is_mock' => false,
        ];
    }

    /**
     * Verify Midtrans notification signature key.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('services.midtrans.server_key');

        if (empty($serverKey)) {
            return true;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Process notification payload received from Midtrans webhook.
     */
    public function processNotification(array $payload): Subscription
    {
        $orderId = $payload['order_id'] ?? null;

        if (! $orderId) {
            throw new RuntimeException('Order ID tidak ditemukan pada payload Midtrans.');
        }

        $subscription = Subscription::where('order_id', $orderId)->firstOrFail();

        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? $subscription->gross_amount);
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if (! empty(config('services.midtrans.server_key')) && ! $this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Webhook Invalid Signature', [
                'order_id' => $orderId,
                'payload' => $payload,
            ]);

            throw new RuntimeException('Signature key Midtrans tidak valid.');
        }

        $transactionStatus = $payload['transaction_status'] ?? 'pending';
        $fraudStatus = $payload['fraud_status'] ?? 'accept';
        $paymentType = $payload['payment_type'] ?? null;
        $settlementTime = isset($payload['settlement_time'])
            ? Carbon::parse($payload['settlement_time'])
            : now();

        $isSettled = $transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

        if ($isSettled) {
            $user = $subscription->user;
            $durationDays = $subscription->billing_cycle->durationDays();

            // Calculate expiration
            $baseDate = ($user->hasActiveSubscription() && $user->tier === $subscription->tier && $user->subscription_expires_at)
                ? $user->subscription_expires_at->copy()
                : now();

            $newExpiresAt = $baseDate->addDays($durationDays);

            // Update user tier and expiration
            $user->update([
                'tier' => $subscription->tier,
                'subscription_expires_at' => $newExpiresAt,
            ]);

            // Update subscription record
            $subscription->update([
                'status' => SubscriptionStatus::Settlement,
                'payment_type' => $paymentType,
                'starts_at' => now(),
                'expires_at' => $newExpiresAt,
                'settlement_time' => $settlementTime,
                'payload' => $payload,
            ]);

            Log::info("Subscription {$subscription->order_id} activated for user {$user->id} ({$subscription->tier->value}) until {$newExpiresAt}");
        } elseif ($transactionStatus === 'pending') {
            $subscription->update([
                'status' => SubscriptionStatus::Pending,
                'payment_type' => $paymentType,
                'payload' => $payload,
            ]);
        } else {
            // deny, cancel, expire, failure
            $status = match ($transactionStatus) {
                'deny' => SubscriptionStatus::Deny,
                'cancel' => SubscriptionStatus::Cancel,
                'expire' => SubscriptionStatus::Expire,
                default => SubscriptionStatus::Failure,
            };

            $subscription->update([
                'status' => $status,
                'payment_type' => $paymentType,
                'payload' => $payload,
            ]);
        }

        return $subscription->fresh();
    }
}
