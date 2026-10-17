<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Enums\UserTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $order_id
 * @property UserTier $tier
 * @property BillingCycle $billing_cycle
 * @property int $gross_amount
 * @property SubscriptionStatus $status
 * @property string|null $snap_token
 * @property string|null $snap_redirect_url
 * @property string|null $payment_type
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $settlement_time
 * @property array|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'tier',
        'billing_cycle',
        'gross_amount',
        'status',
        'snap_token',
        'snap_redirect_url',
        'payment_type',
        'starts_at',
        'expires_at',
        'settlement_time',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => UserTier::class,
            'billing_cycle' => BillingCycle::class,
            'status' => SubscriptionStatus::class,
            'gross_amount' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'settlement_time' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }
}
