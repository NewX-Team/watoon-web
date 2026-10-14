export type User = {
    id: number;
    name: string;
    username?: string | null;
    email: string;
    bio?: string | null;
    phone?: string | null;
    role: 'user' | 'kreator' | 'admin';
    tier?: 'free' | 'pro' | 'vip';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
