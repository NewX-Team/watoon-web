export type SubscriptionStats = {
    has_subscription: boolean;
    tier: 'free' | 'pro' | 'vip';
    tier_label: string;
    remaining_days: number;
    remaining_hours: number;
    remaining_seconds: number;
    total_days: number;
    percentage: number;
    is_expiring_soon: boolean;
    formatted_remaining: string;
    expires_at: string | null;
    expires_formatted: string | null;
};

export type User = {
    id: number;
    name: string;
    username?: string | null;
    email: string;
    bio?: string | null;
    phone?: string | null;
    role: 'user' | 'kreator' | 'admin';
    tier?: 'free' | 'pro' | 'vip';
    subscription_expires_at?: string | null;
    subscription_expires_formatted?: string | null;
    subscription_stats?: SubscriptionStats;
    has_active_subscription?: boolean;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
