<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Award,
    BookOpen,
    Check,
    CheckCircle2,
    ChevronDown,
    Clock,
    CreditCard,
    Crown,
    Eye,
    Film,
    Filter,
    HelpCircle,
    Laptop,
    Lock,
    LogOut,
    MessageSquare,
    Minus,
    Play,
    RefreshCw,
    Search,
    Send,
    Settings,
    ShieldCheck,
    Sliders,
    Smartphone,
    Sparkles,
    Star,
    Tv,
    Unlock,
    Users,
    Volume2,
    VolumeX,
    X,
    Zap,
} from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { dashboard, logout, pricing } from '@/routes';
import messages from '@/routes/messages';
import { edit } from '@/routes/profile';
import { checkout } from '@/routes/subscription';
import orders from '@/routes/subscription/orders';
import tierRoute from '@/routes/subscription/tier';
import type { User } from '@/types/auth';

declare global {
    interface Window {
        snap?: {
            pay: (
                token: string,
                options?: {
                    onSuccess?: (result: unknown) => void;
                    onPending?: (result: unknown) => void;
                    onError?: (result: unknown) => void;
                    onClose?: () => void;
                },
            ) => void;
        };
    }
}

type KastaTier = 'FREE' | 'PRO' | 'VIP';

const props = withDefaults(
    defineProps<{
        currentTier?: string;
        rawTier?: string;
        hasActiveSubscription?: boolean;
        subscriptionExpiresAt?: string | null;
        subscriptionExpiresFormatted?: string | null;
        canSubscribePro?: { allowed: boolean; reason: string | null };
        canSubscribeVip?: { allowed: boolean; reason: string | null };
        midtransClientKey?: string;
        midtransSnapJsUrl?: string;
        midtransIsProduction?: boolean;
    }>(),
    {
        currentTier: 'free',
        rawTier: 'free',
        hasActiveSubscription: false,
        subscriptionExpiresAt: null,
        subscriptionExpiresFormatted: null,
        canSubscribePro: () => ({ allowed: true, reason: null }),
        canSubscribeVip: () => ({ allowed: true, reason: null }),
        midtransClientKey: '',
        midtransSnapJsUrl: 'https://app.sandbox.midtrans.com/snap/snap.js',
        midtransIsProduction: false,
    },
);

const page = usePage<{ auth: { user: User } }>();
const currentUser = computed(() => page.props.auth?.user);

const userInitials = computed(() => {
    const name = currentUser.value?.name || 'User';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

// 3-Kasta System
const normalizeTier = (val?: string | null): KastaTier => {
    if (!val) return 'FREE';
    const up = val.toUpperCase();
    if (up === 'VIP') return 'VIP';
    if (up === 'PRO') return 'PRO';
    return 'FREE';
};

const getInitialTier = (): KastaTier => {
    if (props.currentTier) {
        const t = normalizeTier(props.currentTier);
        if (typeof window !== 'undefined') {
            localStorage.setItem('watoon_user_tier', t);
        }
        return t;
    }
    if (currentUser.value?.tier) {
        const t = normalizeTier(currentUser.value.tier);
        if (typeof window !== 'undefined') {
            localStorage.setItem('watoon_user_tier', t);
        }
        return t;
    }
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('watoon_user_tier');
        if (stored) return normalizeTier(stored);
    }
    return 'FREE';
};

const currentUserTier = ref<KastaTier>(getInitialTier());
const isUpdatingTier = ref(false);
const pendingTier = ref<KastaTier | null>(null);
const userMenuOpen = ref(false);
const navSearchQuery = ref('');
const mediaType = ref<'film' | 'novel'>('film');
const isScrolled = ref(false);

watch(
    [() => currentUser.value?.tier, () => props.currentTier],
    ([newAuthTier, newPropTier]) => {
        const tierToUse = newPropTier || newAuthTier;
        if (tierToUse) {
            const normalized = normalizeTier(tierToUse);
            currentUserTier.value = normalized;
            if (typeof window !== 'undefined') {
                localStorage.setItem('watoon_user_tier', normalized);
            }
        }
    },
    { immediate: true },
);

// Billing Cycle Toggle (Monthly vs Yearly)
const billingCycle = ref<'monthly' | 'yearly'>('monthly');

// Sound Effects Synthesizer (Web Audio API)
const soundEnabled = ref(true);
let audioCtx: AudioContext | null = null;

const initAudio = () => {
    if (!audioCtx && typeof window !== 'undefined') {
        const AudioContextClass = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        if (AudioContextClass) {
            audioCtx = new AudioContextClass();
        }
    }
    if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
};

const playSoftClick = () => {
    if (!soundEnabled.value) return;
    try {
        initAudio();
        if (!audioCtx) return;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(600, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(150, audioCtx.currentTime + 0.04);
        gain.gain.setValueAtTime(0.04, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.04);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.04);
    } catch {
        // Audio error ignored safely
    }
};

const playCelebrationChime = () => {
    if (!soundEnabled.value) return;
    try {
        initAudio();
        if (!audioCtx) return;
        const notes = [523.25, 659.25, 783.99, 1046.5]; // C5, E5, G5, C6
        notes.forEach((freq, idx) => {
            if (!audioCtx) return;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.value = freq;
            const startTime = audioCtx.currentTime + idx * 0.09;
            gain.gain.setValueAtTime(0, startTime);
            gain.gain.linearRampToValueAtTime(0.06, startTime + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.0001, startTime + 0.5);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(startTime);
            osc.stop(startTime + 0.5);
        });
    } catch {
        // Audio error ignored safely
    }
};

const playCinemaFrequency = (tier: '720' | '1080' | '4k') => {
    if (!soundEnabled.value) return;
    try {
        initAudio();
        if (!audioCtx) return;

        if (tier === '720') {
            // Standard telephone/FM bandwidth sound
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(440, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.03, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.4);
        } else if (tier === '1080') {
            // Rich dual tone stereo
            [329.63, 493.88].forEach((freq) => {
                if (!audioCtx) return;
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.04, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.6);
            });
        } else {
            // 4K Cinema Sub-bass & Dolby Atmos shimmer
            const sub = audioCtx.createOscillator();
            const subGain = audioCtx.createGain();
            sub.type = 'sine';
            sub.frequency.setValueAtTime(80, audioCtx.currentTime);
            sub.frequency.exponentialRampToValueAtTime(45, audioCtx.currentTime + 0.8);
            subGain.gain.setValueAtTime(0.09, audioCtx.currentTime);
            subGain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.9);
            sub.connect(subGain);
            subGain.connect(audioCtx.destination);
            sub.start();
            sub.stop(audioCtx.currentTime + 0.9);

            [523.25, 783.99, 1174.66].forEach((f, i) => {
                if (!audioCtx) return;
                const shimmer = audioCtx.createOscillator();
                const sGain = audioCtx.createGain();
                shimmer.type = 'triangle';
                shimmer.frequency.value = f;
                const st = audioCtx.currentTime + 0.08 + i * 0.06;
                sGain.gain.setValueAtTime(0.03, st);
                sGain.gain.exponentialRampToValueAtTime(0.0001, st + 0.7);
                shimmer.connect(sGain);
                sGain.connect(audioCtx.destination);
                shimmer.start(st);
                shimmer.stop(st + 0.7);
            });
        }
    } catch {
        // Audio error ignored safely
    }
};

// Confetti & Celebration State
interface ConfettiParticle {
    id: number;
    x: number;
    y: number;
    color: string;
    size: number;
    rotation: number;
    delay: number;
}
const confettiParticles = ref<ConfettiParticle[]>([]);
const isCelebrating = ref(false);
const showCelebrationModal = ref(false);
const celebratedTier = ref<KastaTier>('PRO');

const launchConfetti = (theme: KastaTier) => {
    isCelebrating.value = true;
    celebratedTier.value = theme;
    showCelebrationModal.value = true;
    playCelebrationChime();

    const colors =
        theme === 'VIP'
            ? ['#D4B67A', '#F3DEB0', '#FFFFFF', '#F59E0B', '#FDE68A']
            : ['#1B44E8', '#7A9BFF', '#FFFFFF', '#60A5FA', '#93C5FD'];

    const count = 52;
    const particles: ConfettiParticle[] = [];
    for (let i = 0; i < count; i++) {
        particles.push({
            id: i,
            x: Math.random() * 100,
            y: Math.random() * 30 - 20,
            color: colors[Math.floor(Math.random() * colors.length)],
            size: Math.random() * 8 + 4,
            rotation: Math.random() * 360,
            delay: Math.random() * 0.4,
        });
    }
    confettiParticles.value = particles;

    setTimeout(() => {
        isCelebrating.value = false;
        confettiParticles.value = [];
    }, 4500);
};

// Toast Feedback State
const toastMessage = ref('');
const showToast = ref(false);
let toastTimer: ReturnType<typeof setTimeout> | null = null;

const triggerToast = (msg: string) => {
    toastMessage.value = msg;
    showToast.value = true;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        showToast.value = false;
    }, 4500);
};

// Midtrans Payment & Subscription Management
const isProcessingPayment = ref(false);
const pendingPaymentTier = ref<KastaTier | null>(null);
const showSimulationModal = ref(false);
const isSimulatingSettlement = ref(false);

interface MockOrderData {
    order_id: string;
    snap_token: string;
    redirect_url?: string;
    is_mock: boolean;
    amount: number;
    tier: string;
    billing_cycle: string;
}

const currentMockOrder = ref<MockOrderData | null>(null);

const getCsrfToken = () => {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) return meta;
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : '';
};

// Choose Plan Handler with Midtrans Checkout & Tier Access Control
const choosePlan = async (targetTier: KastaTier) => {
    playSoftClick();

    if (!currentUser.value) {
        router.visit('/login');
        return;
    }

    if (targetTier === 'FREE') {
        if (currentUserTier.value === 'FREE') {
            triggerToast('Akun Anda saat ini sudah berada di Kasta Free.');
            return;
        }
        if (props.hasActiveSubscription) {
            triggerToast(
                `Paket ${props.rawTier.toUpperCase()} Anda masih aktif hingga ${props.subscriptionExpiresFormatted || 'selesai'}. Tidak dapat beralih ke Free selama masa langganan aktif.`,
            );
            return;
        }

        // If inactive or free revert
        router.post(
            tierRoute.update.url(),
            { tier: 'free' },
            {
                preserveScroll: true,
                onSuccess: () => {
                    currentUserTier.value = 'FREE';
                    triggerToast('Kasta akun Anda sekarang Free.');
                },
            },
        );
        return;
    }

    // Downgrade rules enforcement
    if (targetTier === 'PRO' && !props.canSubscribePro.allowed) {
        triggerToast(props.canSubscribePro.reason || 'Paket VIP tidak dapat di-downgrade ke Pro selama masa aktif.');
        return;
    }

    if (targetTier === 'VIP' && !props.canSubscribeVip.allowed) {
        triggerToast(props.canSubscribeVip.reason || 'Paket VIP tidak dapat dipilih saat ini.');
        return;
    }

    // Initiate Midtrans Checkout
    isProcessingPayment.value = true;
    pendingPaymentTier.value = targetTier;

    try {
        const response = await fetch(checkout.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                tier: targetTier.toLowerCase(),
                billing_cycle: billingCycle.value,
            }),
        });

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            triggerToast(err.message || 'Terjadi kesalahan saat memproses checkout.');
            isProcessingPayment.value = false;
            pendingPaymentTier.value = null;
            return;
        }

        const data: MockOrderData = await response.json();

        // Check if real Midtrans Snap JS is loaded on window
        if (!data.is_mock && typeof window !== 'undefined' && window.snap) {
            window.snap.pay(data.snap_token, {
                onSuccess: async (result: any) => {
                    isProcessingPayment.value = true;
                    pendingPaymentTier.value = targetTier;

                    try {
                        // 1. Immediately confirm with the backend and verify with Midtrans
                        const confirmResponse = await fetch(orders.confirm.url({ orderId: data.order_id }), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-XSRF-TOKEN': getCsrfToken(),
                            },
                            body: JSON.stringify(result || {}),
                        });

                        const confirmData = await confirmResponse.json().catch(() => ({}));
                        const confirmedTier = (confirmData.user_tier || targetTier).toUpperCase() as KastaTier;

                        // 2. Instantly update UI state before & after reload
                        currentUserTier.value = confirmedTier;
                        highlightedTier.value = confirmedTier;
                        if (typeof window !== 'undefined') {
                            localStorage.setItem('watoon_user_tier', confirmedTier);
                        }

                        // 3. Reload Inertia page so all shared props (auth.user, currentTier) update
                        router.reload({
                            preserveScroll: true,
                            onSuccess: () => {
                                currentUserTier.value = confirmedTier;
                                highlightedTier.value = confirmedTier;
                                if (typeof window !== 'undefined') {
                                    localStorage.setItem('watoon_user_tier', confirmedTier);
                                }
                                isProcessingPayment.value = false;
                                pendingPaymentTier.value = null;
                                launchConfetti(confirmedTier);
                                triggerToast(`Pembayaran sah & berhasil! Kasta akun Anda sekarang ${confirmedTier}.`);
                                nextTick(() => {
                                    scrollToSection('section-plans');
                                });
                            },
                            onError: () => {
                                isProcessingPayment.value = false;
                                pendingPaymentTier.value = null;
                            },
                        });
                    } catch {
                        isProcessingPayment.value = false;
                        pendingPaymentTier.value = null;
                        triggerToast('Pembayaran berhasil diproses. Memuat ulang kasta...');
                        router.reload({ preserveScroll: true });
                    }
                },
                onPending: async (result: any) => {
                    try {
                        await fetch(orders.confirm.url({ orderId: data.order_id }), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-XSRF-TOKEN': getCsrfToken(),
                            },
                            body: JSON.stringify(result || {}),
                        });
                    } catch {}
                    isProcessingPayment.value = false;
                    pendingPaymentTier.value = null;
                    triggerToast('Menunggu pembayaran diselesaikan. Kasta akan aktif otomatis setelah lunas.');
                },
                onError: () => {
                    isProcessingPayment.value = false;
                    pendingPaymentTier.value = null;
                    triggerToast('Pembayaran gagal atau dibatalkan.');
                },
                onClose: async () => {
                    isProcessingPayment.value = false;
                    pendingPaymentTier.value = null;
                    // Check if payment was completed right before close
                    try {
                        const checkRes = await fetch(orders.status.url({ orderId: data.order_id }), {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const checkData = await checkRes.json();
                        if (checkData.is_paid) {
                            const newTier = (checkData.user_tier || targetTier).toUpperCase() as KastaTier;
                            currentUserTier.value = newTier;
                            highlightedTier.value = newTier;
                            if (typeof window !== 'undefined') {
                                localStorage.setItem('watoon_user_tier', newTier);
                            }
                            router.reload({
                                preserveScroll: true,
                                onSuccess: () => {
                                    launchConfetti(newTier);
                                    triggerToast(`Pembayaran sah & berhasil! Kasta akun Anda sekarang ${newTier}.`);
                                },
                            });
                        }
                    } catch {}
                },
            });
        } else {
            // Midtrans Sandbox Simulation Mode (Fallback when API key not yet entered in .env)
            currentMockOrder.value = data;
            showSimulationModal.value = true;
            isProcessingPayment.value = false;
            pendingPaymentTier.value = null;
        }
    } catch {
        triggerToast('Gagal terhubung ke gateway pembayaran Midtrans.');
        isProcessingPayment.value = false;
        pendingPaymentTier.value = null;
    }
};

const completeSimulation = async () => {
    if (!currentMockOrder.value) return;
    isSimulatingSettlement.value = true;

    try {
        const orderId = currentMockOrder.value.order_id;
        const targetTier = currentMockOrder.value.tier.toUpperCase() as KastaTier;
        const response = await fetch(orders.simulate.url({ orderId }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });

        if (!response.ok) {
            triggerToast('Gagal memproses simulasi pembayaran.');
            isSimulatingSettlement.value = false;
            return;
        }

        const simData = await response.json().catch(() => ({}));
        showSimulationModal.value = false;
        currentMockOrder.value = null;
        isSimulatingSettlement.value = false;

        const confirmedTier = (simData.user_tier || targetTier).toUpperCase() as KastaTier;
        currentUserTier.value = confirmedTier;
        highlightedTier.value = confirmedTier;
        if (typeof window !== 'undefined') {
            localStorage.setItem('watoon_user_tier', confirmedTier);
        }

        router.reload({
            preserveScroll: true,
            onSuccess: () => {
                currentUserTier.value = confirmedTier;
                highlightedTier.value = confirmedTier;
                if (typeof window !== 'undefined') {
                    localStorage.setItem('watoon_user_tier', confirmedTier);
                }
                launchConfetti(confirmedTier);
                triggerToast(`Pembayaran simulasi sah & berhasil! Kasta akun Anda sekarang ${confirmedTier}.`);
                nextTick(() => {
                    scrollToSection('section-plans');
                });
            },
        });
    } catch {
        triggerToast('Gagal memproses simulasi pembayaran.');
        isSimulatingSettlement.value = false;
    }
};

const switchTier = (targetTier: KastaTier) => {
    choosePlan(targetTier);
};

// =========================================================================
// 1. INTERACTIVE FEATURE: PLAN RECOMMENDATION FINDER / QUIZ
// =========================================================================
interface QuizState {
    device: 'phone' | 'laptop' | 'tv';
    creatorInterest: 'watch_only' | 'indie_chat' | 'master_circle';
    novelAccess: 'intro_only' | 'full_episodes' | 'early_screenplay';
}

const quiz = ref<QuizState>({
    device: 'laptop',
    creatorInterest: 'indie_chat',
    novelAccess: 'full_episodes',
});

const calculatedRecommendation = computed(() => {
    let score = 0;
    // Device points
    if (quiz.value.device === 'tv') score += 3;
    else if (quiz.value.device === 'laptop') score += 2;
    else score += 1;

    // Creator points
    if (quiz.value.creatorInterest === 'master_circle') score += 3;
    else if (quiz.value.creatorInterest === 'indie_chat') score += 2;
    else score += 1;

    // Content points
    if (quiz.value.novelAccess === 'early_screenplay') score += 3;
    else if (quiz.value.novelAccess === 'full_episodes') score += 2;
    else score += 1;

    if (score >= 7) {
        return {
            tier: 'VIP' as KastaTier,
            match: 98,
            title: 'Kasta VIP (Sinematik Tertinggi)',
            badgeColor: 'text-[#D4B67A] bg-[#D4B67A]/15 border-[#D4B67A]/40',
            glowColor: 'shadow-[0_0_30px_rgba(212,182,122,0.25)]',
            reasons: [
                'Resolusi 4K HDR & audio Dolby Atmos ideal untuk Smart TV & home theater Anda.',
                'Akses saluran privat langsung ke sutradara film festival elit & kurator WATOON.',
                'Tiket screening penayangan perdana dan naskah skenario asli sebelum rilis umum.',
            ],
        };
    } else if (score >= 4) {
        return {
            tier: 'PRO' as KastaTier,
            match: 95,
            title: 'Kasta PRO (Paling Seimbang & Favorit)',
            badgeColor: 'text-[#7A9BFF] bg-[#1B44E8]/15 border-[#1B44E8]/40',
            glowColor: 'shadow-[0_0_30px_rgba(27,68,232,0.25)]',
            reasons: [
                'Kualitas 1080p Crystal HD tanpa kompresi artefak, sangat jernih di laptop & monitor.',
                'Akses penuh obrolan langsung dengan kreator kasta PRO & FREE.',
                '100% bebas jeda iklan dan akses seluruh bab novel sinematik yang diterbitkan.',
            ],
        };
    } else {
        return {
            tier: 'FREE' as KastaTier,
            match: 88,
            title: 'Kasta FREE (Cicipi Gratis)',
            badgeColor: 'text-[#A0A0A5] bg-white/10 border-white/20',
            glowColor: 'shadow-[0_0_20px_rgba(255,255,255,0.1)]',
            reasons: [
                'Cicipi katalog film indie awal tanpa perlu kartu kredit atau komitmen biaya.',
                'Obrolan privat dengan kreator sesama kasta FREE yang baru berkembang.',
                'Bebas upgrade ke PRO atau VIP kapan pun Anda membutuhkan kualitas lebih tinggi.',
            ],
        };
    }
});

const scrollToSection = (id: string) => {
    playSoftClick();
    if (typeof document !== 'undefined') {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
};

const applyRecommendedTier = (tier: KastaTier) => {
    highlightedTier.value = tier;
    scrollToSection('section-plans');
    triggerToast(`Kasta ${tier} disorot berdasarkan kuis kebutuhan Anda.`);
};

// =========================================================================
// 2. INTERACTIVE FEATURE: CINEMA QUALITY & AUDIO VISUALIZER
// =========================================================================
const selectedQuality = ref<'720' | '1080' | '4k'>('1080');
const showFilmGrain = ref(false);
const isVisualizerPlaying = ref(true);

const setQuality = (q: '720' | '1080' | '4k') => {
    selectedQuality.value = q;
    playCinemaFrequency(q);
};

// =========================================================================
// 3. INTERACTIVE FEATURE: CREATOR CHAT SIMULATOR & LIVE DIALOGUE PREVIEW
// =========================================================================
const simulatorTier = ref<KastaTier>(currentUserTier.value);

interface Creator {
    id: string;
    name: string;
    handle: string;
    initials: string;
    film: string;
    genre: string;
    tier: KastaTier;
    avatar: string;
    starterQuestions: { q: string; a: string }[];
}

const simulatorCreators: Creator[] = [
    {
        id: 'creator-5',
        name: 'Dimas Aditya',
        handle: '@dimasaditya',
        initials: 'DA',
        film: 'Senja di Kota Tua',
        genre: 'Indie Drama',
        tier: 'FREE',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=160&q=80',
        starterQuestions: [
            {
                q: 'Adegan di stasiun kereta maknanya apa?',
                a: 'Stasiun itu metafora perpisahan yang tertunda. Kami mengambil gambar saat golden hour jam 17:40 dengan lensa vintage 35mm agar rasa nostalgic-nya sampai ke penonton.',
            },
            {
                q: 'Tips membuat film indie dengan budget minim?',
                a: 'Kekuatan utama ada di kedalaman naskah. Cahaya natural jendela sore seringkali jauh lebih sinematik dibanding set lampu studio mahal!',
            },
        ],
    },
    {
        id: 'creator-8',
        name: 'Kirana Dewi',
        handle: '@kiranadewi',
        initials: 'KD',
        film: 'Arus Tenang',
        genre: 'Poetic Essay',
        tier: 'FREE',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=160&q=80',
        starterQuestions: [
            {
                q: 'Mengapa memilih format essay film naratif?',
                a: 'Format essay memberi kebebasan merenungkan tempo waktu dan riak air tanpa harus terjebak plot formula konvensional.',
            },
        ],
    },
    {
        id: 'creator-2',
        name: 'Alya Kusuma',
        handle: '@alyakusuma',
        initials: 'AK',
        film: 'Rumah di Ujung Jalan',
        genre: 'Short Docu',
        tier: 'PRO',
        avatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=160&q=80',
        starterQuestions: [
            {
                q: 'Bagaimana proses riset dokumenter ini?',
                a: 'Saya tinggal 3 minggu bersama keluarga subjek sebelum kamera pertama dinyalakan. Kejujuran emosi hanya lahir dari kepercayaan mutual.',
            },
            {
                q: 'Peralatan kamera apa yang digunakan?',
                a: 'Kami memakai rig kamera kompak dengan lensa anamorphic 1.33x dan field recorder 32-bit float agar bisikan angin malam terdengar kristal.',
            },
        ],
    },
    {
        id: 'creator-7',
        name: 'Rangga Pratama',
        handle: '@ranggapratama',
        initials: 'RP',
        film: 'Gema Pelabuhan',
        genre: 'Atmospheric Noir',
        tier: 'PRO',
        avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=160&q=80',
        starterQuestions: [
            {
                q: 'Sound design film ini sangat padat, bagaimana prosesnya?',
                a: 'Kami merekam foley riil di Tanjung Priok jam 3 pagi, menangkap suara derit sling baja dan deburan ombak berbusa.',
            },
        ],
    },
    {
        id: 'creator-1',
        name: 'Rendra Wijaya',
        handle: '@rendrawijaya',
        initials: 'RW',
        film: 'Cakrawala Senja',
        genre: 'Cinema 4K Master',
        tier: 'VIP',
        avatar: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=160&q=80',
        starterQuestions: [
            {
                q: 'Pendekatan color grading apa yang dipakai di master 4K?',
                a: 'Kami emulasikan film stock Kodak 5219 dengan highlight rolloff lembut. Format 4K HDR WATOON menjaga gradasi warna jingga dan ungu senja tanpa color banding.',
            },
            {
                q: 'Bisa ceritakan rekaman live orkestranya?',
                a: 'Direkam dengan 40 pemain orkestra di Praha dalam format 7.1.4 Dolby Atmos. Separasi cello di belakang telinga kiri adalah kunci emosinya.',
            },
        ],
    },
    {
        id: 'creator-6',
        name: 'Nadia Rahma',
        handle: '@nadiarahma',
        initials: 'NR',
        film: 'Bayang Khatulistiwa',
        genre: 'Festival Feature',
        tier: 'VIP',
        avatar: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=160&q=80',
        starterQuestions: [
            {
                q: 'Bagaimana mengatur pencahayaan malam di tengah hutan?',
                a: 'Kami hanya menggunakan pantulan balon difusi helium dan obor asli. Sensor kamera 4K menangkap bayangan hitam tanpa noise digital yang kasar.',
            },
        ],
    },
];

const tierRank = (t: string): number => {
    const up = t.toUpperCase();
    if (up === 'VIP') return 3;
    if (up === 'PRO') return 2;
    return 1;
};

const canSimulateChat = (creatorTier: KastaTier): boolean => {
    return tierRank(simulatorTier.value) >= tierRank(creatorTier);
};

// Live Mini-Dialogue Tester Modal State
const activeTestCreator = ref<Creator | null>(null);
const dialogueMessages = ref<{ sender: 'user' | 'creator'; text: string; time: string }[]>([]);
const isCreatorTyping = ref(false);

const openCreatorDialogue = (c: Creator) => {
    playSoftClick();
    activeTestCreator.value = c;
    dialogueMessages.value = [
        {
            sender: 'creator',
            text: `Halo! Terima kasih sudah menonton karya saya "${c.film}". Senang bisa terhubung langsung di ruang diskusi WATOON.`,
            time: 'Baru saja',
        },
    ];
    isCreatorTyping.value = false;
};

const sendTestQuestion = (q: string, a: string) => {
    playSoftClick();
    dialogueMessages.value.push({
        sender: 'user',
        text: q,
        time: 'Baru saja',
    });

    isCreatorTyping.value = true;
    setTimeout(() => {
        isCreatorTyping.value = false;
        dialogueMessages.value.push({
            sender: 'creator',
            text: a,
            time: 'Baru saja',
        });
        playSoftClick();
    }, 1100);
};

// =========================================================================
// 4. INTERACTIVE FEATURE: TABLE CATEGORY FILTER & REAL-TIME SEARCH
// =========================================================================
interface FeatureRow {
    category: 'chat' | 'video' | 'exclusive';
    name: string;
    free: string | boolean;
    pro: string | boolean;
    vip: string | boolean;
    isDifference: boolean;
    hint: string;
}

const comparisonFeatures: FeatureRow[] = [
    {
        category: 'video',
        name: 'Akses Katalog Koleksi Film',
        free: 'Terbatas (Pilihan Awal)',
        pro: 'Katalog Kurasi Luas',
        vip: 'Seluruh Perpustakaan 4K',
        isDifference: true,
        hint: 'Film independen & festival film pilihan kurator',
    },
    {
        category: 'video',
        name: 'Kualitas Streaming Sinematik',
        free: 'Standar (720p)',
        pro: 'HD Tajam (1080p)',
        vip: 'Ultra 4K HDR Lossless',
        isDifference: true,
        hint: 'Bitrate tinggi tanpa artefak kompresi',
    },
    {
        category: 'video',
        name: 'Bebas Iklan & Distraksi',
        free: false,
        pro: true,
        vip: true,
        isDifference: true,
        hint: 'Pengalaman menonton murni tanpa interupsi',
    },
    {
        category: 'chat',
        name: 'Chat dengan Kreator Kasta FREE',
        free: true,
        pro: true,
        vip: true,
        isDifference: false,
        hint: 'Direct message dengan sineas berkembang',
    },
    {
        category: 'chat',
        name: 'Chat dengan Kreator Kasta PRO',
        free: false,
        pro: true,
        vip: true,
        isDifference: true,
        hint: 'Diskusi privat langsung dengan sutradara terverifikasi',
    },
    {
        category: 'chat',
        name: 'Chat dengan Kreator Kasta VIP Elit',
        free: false,
        pro: false,
        vip: true,
        isDifference: true,
        hint: 'Saluran intim dengan sutradara film festival papan atas',
    },
    {
        category: 'chat',
        name: 'Chat dengan Kurator Admin WATOON',
        free: false,
        pro: false,
        vip: true,
        isDifference: true,
        hint: 'Diskusi rekomendasi dan kurasi film bersama tim WATOON',
    },
    {
        category: 'exclusive',
        name: 'Fitur Novel Sinematik & Naskah',
        free: 'Bab Pembuka Saja',
        pro: 'Akses Penuh Bab Seri',
        vip: 'Akses Perdana Bab Awal',
        isDifference: true,
        hint: 'Buku cerita visual dan skenario orisinal',
    },
    {
        category: 'exclusive',
        name: 'SLA Dukungan Prioritas Layanan',
        free: false,
        pro: false,
        vip: true,
        isDifference: true,
        hint: 'Respon cepat langsung dari tim teknis & kurasi',
    },
    {
        category: 'exclusive',
        name: 'Akses Awal Penayangan Perdana (Screening)',
        free: false,
        pro: false,
        vip: true,
        isDifference: true,
        hint: 'Tonton pemutaran perdana sebelum dibuka untuk umum',
    },
];

const activeFeatureCategory = ref<'all' | 'chat' | 'video' | 'exclusive'>('all');
const showOnlyDifferences = ref(false);
const tableSearchQuery = ref('');

const filteredFeatures = computed(() => {
    return comparisonFeatures.filter((item) => {
        if (showOnlyDifferences.value && !item.isDifference) {
            return false;
        }
        if (activeFeatureCategory.value !== 'all' && item.category !== activeFeatureCategory.value) {
            return false;
        }
        if (tableSearchQuery.value.trim()) {
            const q = tableSearchQuery.value.toLowerCase();
            return (
                item.name.toLowerCase().includes(q) ||
                item.hint.toLowerCase().includes(q) ||
                String(item.free).toLowerCase().includes(q) ||
                String(item.pro).toLowerCase().includes(q) ||
                String(item.vip).toLowerCase().includes(q)
            );
        }
        return true;
    });
});

// FAQ Accordion State
const openFaqIndex = ref<number | null>(0);
const toggleFaq = (index: number) => {
    playSoftClick();
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const faqs = [
    {
        question: 'Apa perbedaan paling mendasar antara Pro dan VIP?',
        answer: 'Kasta Pro memberi Anda streaming 1080p jernih untuk seluruh katalog film kurasi dan akses obrolan privat dengan kreator Pro & Free. Kasta VIP membuka master 4K HDR tanpa batas, akses rilis penayangan perdana, naskah sastra eksklusif, serta obrolan privat langsung dengan sutradara elit VIP dan tim kurasi WATOON.',
    },
    {
        question: 'Apakah saya bisa ganti kasta atau upgrade kapan saja?',
        answer: 'Ya, Anda bebas berganti kasta kapan pun. Ketika upgrade ke Pro atau VIP, hak akses chat kreator dan kualitas film baru Anda langsung aktif seketika tanpa jeda.',
    },
    {
        question: 'Metode pembayaran apa saja yang diterima?',
        answer: 'WATOON mendukung seluruh sistem pembayaran populer di Indonesia termasuk QRIS (GoPay, OVO, ShopeePay, DANA), Virtual Account (BCA, Mandiri, BNI, BRI), kartu kredit/debit, serta transfer bank yang terenkripsi aman oleh Midtrans.',
    },
    {
        question: 'Apakah saya bisa membatalkan langganan kapan saja?',
        answer: 'Tentu. Anda tidak terikat kontrak jangka panjang. Anda dapat membatalkan perpanjangan otomatis kapan saja langsung dari menu Pengaturan Akun dengan sekali klik.',
    },
    {
        question: 'Bagaimana akses novel sinematik di setiap kasta?',
        answer: 'Kasta Free dapat menikmati bab-bab pembuka dari semua novel. Member Pro mendapatkan akses penuh seluruh bab yang diterbitkan, dan member VIP mendapatkan hak istimewa membaca bab terbaru lebih awal sebelum dirilis ke publik.',
    },
];

// Highlight plan from query param (e.g. ?highlight=vip or ?highlight=pro)
const highlightedTier = ref<string | null>(null);

// Hover column index in feature comparison table
const hoveredTableCol = ref<'free' | 'pro' | 'vip' | null>(null);

// Dynamic Mouse Tracking on Plan Cards (Spotlight Effect)
const cardsContainerRef = ref<HTMLElement | null>(null);

const handleCardsMouseMove = (e: MouseEvent) => {
    if (!cardsContainerRef.value) return;
    const rect = cardsContainerRef.value.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    cardsContainerRef.value.style.setProperty('--spotlight-x', `${x}px`);
    cardsContainerRef.value.style.setProperty('--spotlight-y', `${y}px`);
};

// Scroll Listener
const handleScroll = () => {
    if (typeof window !== 'undefined') {
        isScrolled.value = window.scrollY > 25;
    }
};

let revealObserver: IntersectionObserver | null = null;

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        const params = new URLSearchParams(window.location.search);
        const hl = params.get('highlight');
        if (hl) {
            highlightedTier.value = hl.toUpperCase();
        }

        // Dynamically load Midtrans Snap JS if client key is configured
        if (props.midtransClientKey) {
            const existing = document.querySelector('script[src*="snap.js"]');
            if (!existing) {
                const script = document.createElement('script');
                script.src = props.midtransSnapJsUrl;
                script.setAttribute('data-client-key', props.midtransClientKey);
                script.async = true;
                document.head.appendChild(script);
            }
        }

        // Initialize scroll reveal animations
        revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '0px 0px -40px 0px',
            },
        );

        nextTick(() => {
            document.querySelectorAll('.scroll-reveal').forEach((el) => {
                revealObserver?.observe(el);
            });
        });
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('scroll', handleScroll);
    }
    if (revealObserver) {
        revealObserver.disconnect();
        revealObserver = null;
    }
});
</script>

<template>
    <Head title="Pilihan Paket Langganan & Kasta Penonton" />

    <div class="relative min-h-screen bg-[#0E0F11] text-[#F2EFEA] selection:bg-[#1B44E8] selection:text-white pb-24 font-sans overflow-x-hidden">
        <!-- ========================================================================= -->
        <!-- AMBIENT CINEMATIC LIGHTING & DRIFTING PARTICLES                           -->
        <!-- ========================================================================= -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <!-- Top Hero Flare -->
            <div
                class="absolute -top-[180px] left-1/2 -translate-x-1/2 w-[920px] h-[550px] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#1B44E8]/20 via-[#1B44E8]/05 to-transparent blur-3xl opacity-80"
            ></div>
            <!-- VIP Gold Ambient Glow -->
            <div
                class="absolute top-[480px] -right-[120px] w-[580px] h-[580px] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#D4B67A]/12 via-transparent to-transparent blur-3xl opacity-70"
            ></div>
            <!-- Interactive Simulator Violet Ambient Aura -->
            <div
                class="absolute top-[1300px] -left-[140px] w-[620px] h-[620px] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#7A9BFF]/10 via-transparent to-transparent blur-3xl opacity-60"
            ></div>
            <!-- Cinematic Bokeh Embers (Animated drifting dots) -->
            <div class="absolute inset-0 opacity-40">
                <span class="ember-dot left-[15%] top-[25%] [animation-delay:0s]"></span>
                <span class="ember-dot left-[35%] top-[45%] [animation-delay:2s]"></span>
                <span class="ember-dot left-[75%] top-[15%] [animation-delay:4s]"></span>
                <span class="ember-dot left-[85%] top-[65%] [animation-delay:1.5s]"></span>
                <span class="ember-dot left-[50%] top-[80%] [animation-delay:3.5s]"></span>
            </div>
            <!-- Subtle Grid Texture -->
            <div
                class="absolute inset-0 bg-[radial-gradient(#F2EFEA_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.025]"
            ></div>
        </div>

        <!-- ========================================================================= -->
        <!-- 1. TOP NAVBAR (Floating Detached Header @ Top 20px)                       -->
        <!-- ========================================================================= -->
        <header class="fixed top-4 sm:top-5 left-0 right-0 z-50 flex justify-center px-4 transition-all duration-300">
            <nav
                class="w-full max-w-[1200px] rounded-2xl border px-5 flex items-center justify-between transition-all duration-300"
                :class="[
                    isScrolled
                        ? 'h-14 sm:h-15 bg-[#0E0F11]/92 backdrop-blur-xl border-[rgba(242,239,234,0.14)] shadow-[0_12px_36px_rgba(0,0,0,0.65)]'
                        : 'h-16 bg-[rgba(14,15,17,0.75)] backdrop-blur-md border-[rgba(242,239,234,0.08)] shadow-[0_4px_24px_rgba(0,0,0,0.45)]'
                ]"
            >
                <!-- Brand & Left Navigation Links -->
                <div class="flex items-center space-x-7">
                    <Link :href="dashboard.url()" class="flex items-center space-x-2.5 group">
                        <div
                            class="w-7 h-7 bg-[#1B44E8] rounded-[6px] flex items-center justify-center text-white font-bold text-sm tracking-tighter group-hover:scale-105 transition-transform duration-200 shadow-md shadow-[#1B44E8]/30"
                        >
                            W
                        </div>
                        <span class="font-semibold text-lg tracking-tight text-[#F2EFEA] group-hover:text-white transition-colors">
                            WATOON
                        </span>
                    </Link>

                    <!-- Navigation Links: ONLY Home, Favorite, Subscribe (Strictly no extra nav features) -->
                    <div class="hidden lg:flex items-center space-x-6 text-[13px] text-[#8C8C90] font-medium">
                        <Link
                            :href="dashboard.url()"
                            class="hover:text-[#F2EFEA] transition-colors hover:translate-y-[-1px]"
                        >
                            Home
                        </Link>
                        <Link
                            :href="dashboard.url({ query: { tab: 'favorite' } })"
                            class="hover:text-[#F2EFEA] transition-colors hover:translate-y-[-1px]"
                        >
                            Favorite
                        </Link>
                        <Link
                            :href="dashboard.url({ query: { tab: 'subscribe' } })"
                            class="hover:text-[#F2EFEA] transition-colors hover:translate-y-[-1px]"
                        >
                            Subscribe
                        </Link>
                    </div>

                    <!-- Inline Global Search Input -->
                    <div class="hidden md:flex items-center relative w-[210px]">
                        <Search class="absolute left-2.5 text-[#8C8C90] h-4 w-4 pointer-events-none" />
                        <input
                            v-model="navSearchQuery"
                            type="text"
                            placeholder="Search films…"
                            class="w-full h-9 pl-8 pr-9 bg-[#17181B] text-[13px] text-[#F2EFEA] placeholder-[#5e5e61] border border-[rgba(242,239,234,0.08)] rounded-[6px] focus:outline-none focus:border-[#F2EFEA] transition-colors"
                        />
                        <kbd
                            class="absolute right-2 px-1.5 py-0.5 text-[10px] text-[#8C8C90] bg-[#23252A] border border-[rgba(242,239,234,0.08)] rounded font-sans pointer-events-none"
                        >
                            ⌘K
                        </kbd>
                    </div>
                </div>

                <!-- Right Navigation Cluster -->
                <div class="flex items-center space-x-3.5">
                    <!-- Audio Effects Toggle -->
                    <button
                        type="button"
                        @click="soundEnabled = !soundEnabled; playSoftClick()"
                        class="hidden sm:flex h-8 w-8 items-center justify-center rounded-lg border border-[rgba(242,239,234,0.1)] bg-[#17181B] text-[#8C8C90] hover:text-[#F2EFEA] hover:border-white/20 transition-colors cursor-pointer"
                        :title="soundEnabled ? 'Efek Suara Aktif (Klik untuk Mute)' : 'Efek Suara Bisu (Klik untuk Aktifkan)'"
                    >
                        <Volume2 v-if="soundEnabled" class="w-4 h-4 text-[#7A9BFF]" />
                        <VolumeX v-else class="w-4 h-4 text-[#8C8C90]" />
                    </button>

                    <!-- Segmented Film / Novel Toggle -->
                    <div
                        class="hidden sm:flex items-center bg-[#17181B] border border-[rgba(242,239,234,0.08)] p-0.5 rounded-[6px] h-8 w-[142px]"
                    >
                        <button
                            type="button"
                            @click="mediaType = 'film'; playSoftClick()"
                            class="flex-1 h-full rounded-[4px] text-[12px] font-medium flex items-center justify-center transition-colors cursor-pointer"
                            :class="
                                mediaType === 'film'
                                    ? 'bg-[#2A2B30] text-[#F2EFEA] shadow-sm'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            Film
                        </button>
                        <button
                            type="button"
                            @click="mediaType = 'novel'; playSoftClick()"
                            class="flex-1 h-full rounded-[4px] text-[12px] font-medium flex items-center justify-center space-x-1 transition-colors cursor-pointer"
                            :class="
                                mediaType === 'novel'
                                    ? 'bg-[#2A2B30] text-[#F2EFEA] shadow-sm'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            <span>Novel</span>
                            <span class="w-1 h-1 rounded-full bg-[#1B44E8]"></span>
                        </button>
                    </div>

                    <!-- Kasta Tier Badge (Active Plan Pill next to avatar) -->
                    <button
                        type="button"
                        @click="scrollToSection('section-plans')"
                        class="flex h-[28px] cursor-pointer items-center justify-center gap-1.5 rounded-[8px] px-3 text-[11px] font-bold tracking-[0.14em] uppercase transition-all select-none hover:scale-105 active:scale-95"
                        :class="[
                            currentUserTier === 'VIP'
                                ? 'border border-[#D4B67A]/60 bg-[#D4B67A]/20 text-[#D4B67A] shadow-[0_0_16px_rgba(212,182,122,0.3)] hover:bg-[#D4B67A]/30'
                                : currentUserTier === 'PRO'
                                ? 'border border-[#1B44E8]/60 bg-[#1B44E8]/20 text-[#7A9BFF] shadow-[0_0_16px_rgba(27,68,232,0.3)] hover:bg-[#1B44E8]/30'
                                : 'border border-[rgba(242,239,234,0.18)] bg-[rgba(242,239,234,0.07)] text-[#A0A0A5] hover:text-[#F2EFEA] hover:border-white/30'
                        ]"
                        :title="`Kasta Anda Saat Ini: ${currentUserTier} (Klik untuk lihat paket Anda)`"
                    >
                        <Crown v-if="currentUserTier === 'VIP'" class="w-3.5 h-3.5 text-[#D4B67A]" />
                        <Sparkles v-else-if="currentUserTier === 'PRO'" class="w-3.5 h-3.5 text-[#7A9BFF]" />
                        <span v-else class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>{{ currentUserTier }}</span>
                    </button>

                    <!-- User Avatar & Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="userMenuOpen = !userMenuOpen"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-[rgba(242,239,234,0.15)] bg-[#17181B] text-[13px] font-medium text-[#F2EFEA] transition-colors select-none hover:border-[#F2EFEA]/40"
                            :title="currentUser?.name || 'Account'"
                        >
                            <img
                                v-if="currentUser?.avatar"
                                :src="currentUser.avatar"
                                :alt="currentUser?.name"
                                class="h-full w-full rounded-full object-cover"
                            />
                            <span v-else>{{ userInitials }}</span>
                        </button>

                        <!-- User Dropdown Menu -->
                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 top-11 z-50 w-60 rounded-xl border border-[rgba(242,239,234,0.1)] bg-[#17181B] p-2 shadow-2xl backdrop-blur-xl animate-dropdown"
                            @click="userMenuOpen = false"
                        >
                            <div class="border-b border-[rgba(242,239,234,0.08)] px-3 py-2.5">
                                <p class="truncate text-xs font-medium text-[#F2EFEA]">
                                    {{ currentUser?.name || 'Viewer' }}
                                </p>
                                <p class="truncate text-[11px] text-[#8C8C90]">
                                    {{ currentUser?.email }}
                                </p>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="text-[10px] font-mono text-[#8C8C90]">Kasta:</span>
                                    <span
                                        class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold"
                                        :class="
                                            currentUserTier === 'VIP'
                                                ? 'bg-[#D4B67A]/20 text-[#D4B67A]'
                                                : currentUserTier === 'PRO'
                                                ? 'bg-[#1B44E8]/20 text-[#7A9BFF]'
                                                : 'bg-white/10 text-[#8C8C90]'
                                        "
                                    >
                                        {{ currentUserTier }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-1 space-y-0.5">
                                <Link
                                    :href="edit.url()"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#8C8C90] hover:bg-white/[0.06] hover:text-[#F2EFEA] transition-colors"
                                >
                                    <Settings class="h-3.5 w-3.5" />
                                    <span>Account Settings</span>
                                </Link>

                                <Link
                                    :href="logout.url()"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-rose-400 hover:bg-rose-500/10 transition-colors text-left"
                                >
                                    <LogOut class="h-3.5 w-3.5" />
                                    <span>Log out</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- ========================================================================= -->
        <!-- 2. PAGE HERO & MINIMAL BILLING TOGGLE                                      -->
        <!-- ========================================================================= -->
        <main class="relative z-10 pt-32 sm:pt-40 max-w-[1200px] mx-auto px-5 sm:px-6">
            <!-- Header Block -->
            <div class="text-center max-w-xl mx-auto scroll-reveal">
                <div class="inline-flex items-center gap-2 uppercase text-[11px] tracking-[0.18em] font-semibold text-[#7A9BFF] mb-3 px-3 py-1 rounded-full bg-[#1B44E8]/12 border border-[#1B44E8]/30 backdrop-blur-sm shadow-[0_0_15px_rgba(27,68,232,0.25)]">
                    <Sparkles class="w-3.5 h-3.5 text-[#7A9BFF]" />
                    <span>PILIHAN KASTA PENONTON WATOON</span>
                </div>
                <h1 class="text-3xl sm:text-[44px] leading-[44px] sm:leading-[52px] font-semibold text-[#F2EFEA] tracking-tight mb-4">
                    Choose your plan.
                </h1>
                <p class="text-[15px] leading-relaxed text-[#8C8C90]">
                    Mulai gratis. Tingkatkan kasta akun Anda untuk akses katalog film lebih luas, kualitas sinema 4K, dan obrolan privat dengan sineas favorit.
                </p>

                <!-- Modern Segmented Pill Billing Switcher -->
                <div class="mt-10 flex flex-col items-center justify-center gap-3">
                    <div class="relative inline-flex items-center p-1 rounded-full bg-[#17181B] border border-[rgba(242,239,234,0.12)] shadow-xl backdrop-blur-md">
                        <!-- Sliding Indicator Pill -->
                        <div
                            class="absolute top-1 bottom-1 rounded-full bg-gradient-to-r from-[#1B44E8] to-[#1537C2] shadow-[0_2px_14px_rgba(27,68,232,0.45)] transition-all duration-300 ease-out"
                            :class="billingCycle === 'monthly' ? 'left-1 w-[105px]' : 'left-[112px] w-[150px]'"
                        ></div>

                        <button
                            type="button"
                            @click="billingCycle = 'monthly'; playSoftClick()"
                            class="relative z-10 w-[105px] py-2 text-xs font-semibold text-center transition-colors cursor-pointer select-none"
                            :class="billingCycle === 'monthly' ? 'text-white' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            Monthly
                        </button>

                        <button
                            type="button"
                            @click="billingCycle = 'yearly'; playSoftClick()"
                            class="relative z-10 w-[150px] py-2 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors cursor-pointer select-none"
                            :class="billingCycle === 'yearly' ? 'text-white' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            <span>Yearly</span>
                            <span
                                class="uppercase text-[9px] font-bold tracking-wider px-1.5 py-0.5 rounded-full transition-all"
                                :class="billingCycle === 'yearly' ? 'bg-white/20 text-white shadow-sm' : 'bg-[#1B44E8]/20 text-[#7A9BFF] border border-[#1B44E8]/40'"
                            >
                                SAVE 20%
                            </span>
                        </button>
                    </div>

                    <!-- Live Savings Callout -->
                    <div
                        v-if="billingCycle === 'yearly'"
                        class="text-xs font-medium text-emerald-400 flex items-center gap-1.5 animate-fadeIn"
                    >
                        <Zap class="w-3.5 h-3.5" />
                        <span>Hemat hingga Rp 360.000 / tahun dengan komitmen tahunan</span>
                    </div>
                </div>

                <!-- Active Subscription Status Banner -->
                <div v-if="currentUser" class="mt-8 max-w-[840px] mx-auto animate-fadeIn text-left">
                    <!-- Active Paid Subscription (PRO or VIP) -->
                    <div
                        v-if="currentUserTier !== 'FREE' || props.hasActiveSubscription"
                        class="relative overflow-hidden rounded-2xl border p-5 backdrop-blur-xl transition-all duration-300"
                        :class="
                            currentUserTier === 'VIP'
                                ? 'border-[#D4B67A]/40 bg-gradient-to-r from-[#2a2417]/90 via-[#17181B]/95 to-[#17181B]/95 shadow-[0_8px_32px_rgba(212,182,122,0.15)]'
                                : 'border-[#1B44E8]/40 bg-gradient-to-r from-[#141b33]/90 via-[#17181B]/95 to-[#17181B]/95 shadow-[0_8px_32px_rgba(27,68,232,0.15)]'
                        "
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div
                                    class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-inner"
                                    :class="
                                        currentUserTier === 'VIP'
                                            ? 'bg-[#D4B67A]/20 text-[#D4B67A] border border-[#D4B67A]/40'
                                            : 'bg-[#1B44E8]/20 text-[#7A9BFF] border border-[#1B44E8]/40'
                                    "
                                >
                                    <Crown v-if="currentUserTier === 'VIP'" class="w-6 h-6" />
                                    <Sparkles v-else class="w-6 h-6" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-white">
                                            Status Langganan: Kasta {{ currentUserTier }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#8C8C90] mt-0.5 flex items-center gap-1.5">
                                        <Clock class="w-3.5 h-3.5 text-[#7A9BFF]" />
                                        <span>Masa berlaku hingga: <strong class="text-[#F2EFEA]">{{ props.subscriptionExpiresFormatted || 'Aktif' }}</strong></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Helpful Tier Badge & Restriction Notice -->
                            <div class="text-left sm:text-right shrink-0">
                                <span
                                    v-if="currentUserTier === 'VIP'"
                                    class="text-[11px] font-medium text-[#D4B67A] bg-[#D4B67A]/10 border border-[#D4B67A]/20 px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5"
                                >
                                    <ShieldCheck class="w-3.5 h-3.5" />
                                    Kasta Tertinggi (Tidak dapat downgrade)
                                </span>
                                <span
                                    v-else-if="currentUserTier === 'PRO'"
                                    class="text-[11px] font-medium text-[#7A9BFF] bg-[#1B44E8]/10 border border-[#1B44E8]/20 px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5"
                                >
                                    <Sparkles class="w-3.5 h-3.5" />
                                    Bisa upgrade ke VIP (Harga Normal)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Free Account Notice -->
                    <div
                        v-else
                        class="rounded-xl border border-white/10 bg-[#17181B]/70 p-4 backdrop-blur-md flex items-center justify-between gap-4 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-[#8C8C90] font-mono text-xs font-bold">
                                FREE
                            </div>
                            <div>
                                <p class="font-medium text-[#F2EFEA]">Akun Anda Saat Ini: Kasta Free (Dasar)</p>
                                <p class="text-[11px] text-[#8C8C90]">Tingkatkan kasta ke Pro atau VIP untuk obrolan privat dengan kreator idola dan kualitas tayangan tanpa batas.</p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex text-[11px] text-emerald-400 font-medium px-2 py-1 rounded bg-emerald-500/10 border border-emerald-500/20">
                            Gratis Selamanya
                        </span>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 3. PLAN COLUMNS (Interactive Spotlight & Dynamic Card Lift)               -->
            <!-- ========================================================================= -->
            <section
                id="section-plans"
                ref="cardsContainerRef"
                @mousemove="handleCardsMouseMove"
                class="mt-14 max-w-[1120px] mx-auto scroll-reveal delay-100 relative group/grid"
            >
                <div
                    class="grid grid-cols-1 lg:grid-cols-3 border border-[rgba(242,239,234,0.08)] rounded-[16px] overflow-hidden divide-y lg:divide-y-0 lg:divide-x divide-[rgba(242,239,234,0.08)] bg-[#0E0F11] shadow-[0_20px_60px_rgba(0,0,0,0.55)] relative"
                >
                    <!-- ================= PLAN 1: FREE ================= -->
                    <div
                        class="p-8 sm:p-9 flex flex-col justify-between relative bg-[#0E0F11] transition-all duration-300 hover:bg-white/[0.015] group/card"
                        :class="[
                            currentUserTier === 'FREE' ? 'ring-2 ring-emerald-500/50 shadow-[0_0_35px_rgba(52,211,153,0.14)] bg-gradient-to-b from-white/[0.04] to-[#0E0F11]' : '',
                            highlightedTier === 'FREE' ? 'ring-2 ring-emerald-400 animate-pulse' : ''
                        ]"
                    >
                        <div>
                            <!-- Active Status Overline if User is FREE -->
                            <div v-if="currentUserTier === 'FREE'" class="mb-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-emerald-500/15 border border-emerald-500/40 text-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.25)] animate-fadeIn">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Kasta Anda Saat Ini (Aktif)</span>
                            </div>

                            <!-- Tier Header -->
                            <div class="flex items-center justify-between">
                                <span class="text-[19px] font-semibold text-[#F2EFEA]">Free</span>
                                <span
                                    class="px-2 py-0.5 text-[10px] tracking-wider font-semibold uppercase text-[#A8A8AC] bg-[rgba(168,168,172,0.10)] border border-[rgba(168,168,172,0.2)] rounded-[4px]"
                                >
                                    FREE
                                </span>
                            </div>

                            <!-- Price Display -->
                            <div class="mt-6 flex items-baseline">
                                <span class="text-[14px] text-[#8C8C90] font-normal mr-1.5">Rp</span>
                                <span class="text-[40px] font-semibold tracking-tight text-[#F2EFEA] leading-none">
                                    0
                                </span>
                                <span class="text-[13px] text-[#8C8C90] ml-2">/month</span>
                            </div>
                            <div class="text-[12px] text-[#8C8C90] mt-1">Forever free · Selamanya gratis</div>

                            <p class="text-[13px] leading-relaxed text-[#8C8C90] mt-6 min-h-[40px]">
                                Cicipi pengalaman WATOON. Katalog film pilihan dasar, obrolan dibatasi hanya untuk sesama kreator Free.
                            </p>

                            <!-- Action Button -->
                            <div class="mt-7">
                                <button
                                    type="button"
                                    @click="choosePlan('FREE')"
                                    :disabled="currentUserTier === 'FREE' || props.hasActiveSubscription || isProcessingPayment"
                                    class="w-full h-[44px] rounded-[10px] border border-[rgba(242,239,234,0.20)] text-[#F2EFEA] text-[13px] font-medium transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99]"
                                    :class="
                                        currentUserTier === 'FREE'
                                            ? 'bg-white/10 text-white font-semibold shadow-inner'
                                            : 'hover:bg-white/10 hover:border-white/30'
                                    "
                                >
                                    <span v-if="currentUserTier === 'FREE'" class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]"></span>
                                    <Lock v-else-if="props.hasActiveSubscription" class="w-3.5 h-3.5 text-[#8C8C90]" />
                                    <span>
                                        {{
                                            currentUserTier === 'FREE'
                                                ? 'Kasta Anda Saat Ini'
                                                : props.hasActiveSubscription
                                                ? 'Terkunci (Paket Aktif)'
                                                : 'Pilih Free'
                                        }}
                                    </span>
                                </button>
                                <p v-if="props.hasActiveSubscription" class="text-[11px] text-[#8C8C90] text-center mt-2">
                                    Tidak dapat beralih ke Free selama masa langganan aktif
                                </p>
                            </div>

                            <!-- Divider -->
                            <div class="my-7 border-t border-[rgba(242,239,234,0.08)]"></div>

                            <!-- Feature Checklist -->
                            <div class="space-y-3.5 text-[13px]">
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#F2EFEA] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Akses pilihan film indie dasar</span>
                                </div>
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#F2EFEA] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Detail sinopsis & ulasan sineas</span>
                                </div>
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-emerald-400 shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Chat sesama kreator kasta FREE</span>
                                </div>
                                <!-- Excluded (Quiet minus glyph in #4A4A4E) -->
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Chat dengan kreator PRO</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Chat dengan kreator VIP elit</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Chat langsung dengan kurator admin</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Resolusi 4K & Screening perdana</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= PLAN 2: PRO (RECOMMENDED) ================= -->
                    <div
                        class="p-8 sm:p-9 flex flex-col justify-between relative transition-all duration-300 hover:shadow-[0_12px_45px_rgba(27,68,232,0.25)] group/card"
                        :class="[
                            currentUserTier === 'PRO'
                                ? 'border-t-2 border-t-[#7A9BFF] ring-2 ring-[#7A9BFF] shadow-[0_0_45px_rgba(27,68,232,0.35)] bg-gradient-to-b from-[rgba(27,68,232,0.18)] via-[rgba(27,68,232,0.05)] to-[#0E0F11]'
                                : 'bg-gradient-to-b from-[rgba(27,68,232,0.08)] via-[rgba(27,68,232,0.02)] to-transparent border-t-2 border-t-[#1B44E8]',
                            highlightedTier === 'PRO' ? 'ring-2 ring-[#7A9BFF] animate-pulse' : ''
                        ]"
                    >
                        <div>
                            <!-- Recommended Overline with Breathing Indicator -->
                            <div class="flex items-center justify-between mb-2">
                                <div v-if="currentUserTier === 'PRO'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-[#1B44E8]/30 border border-[#1B44E8]/60 text-[#7A9BFF] shadow-[0_0_15px_rgba(27,68,232,0.4)] animate-fadeIn">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#7A9BFF] opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#1B44E8]"></span>
                                    </span>
                                    <span>Kasta Anda Saat Ini (Aktif)</span>
                                </div>
                                <div v-else class="flex items-center gap-1.5">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#7A9BFF] opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#1B44E8]"></span>
                                    </span>
                                    <span class="text-[10px] uppercase tracking-widest text-[#7A9BFF] font-semibold">
                                        RECOMMENDED · PILIHAN FAVORIT
                                    </span>
                                </div>
                            </div>

                            <!-- Tier Header -->
                            <div class="flex items-center justify-between">
                                <span class="text-[19px] font-semibold text-[#F2EFEA]">Pro</span>
                                <span
                                    class="px-2 py-0.5 text-[10px] tracking-wider font-semibold uppercase text-[#7A9BFF] bg-[rgba(27,68,232,0.18)] border border-[rgba(27,68,232,0.4)] rounded-[4px]"
                                >
                                    PRO
                                </span>
                            </div>

                            <!-- Price Display -->
                            <div class="mt-6 flex items-baseline">
                                <span class="text-[14px] text-[#8C8C90] font-normal mr-1.5">Rp</span>
                                <span class="text-[40px] font-semibold tracking-tight text-[#F2EFEA] leading-none transition-all duration-300">
                                    {{ billingCycle === 'yearly' ? '64.000' : '80.000' }}
                                </span>
                                <span class="text-[13px] text-[#8C8C90] ml-2">/month</span>
                            </div>
                            <div class="text-[12px] text-[#8C8C90] mt-1 transition-all duration-200">
                                {{ billingCycle === 'yearly' ? 'Ditagih tahunan (Rp 768.000/thn) · Batal kapan saja' : 'Ditagih bulanan · Batal kapan saja' }}
                            </div>

                            <p class="text-[13px] leading-relaxed text-[#8C8C90] mt-6 min-h-[40px]">
                                Pilihan ideal bagi pecinta film. Akses streaming HD 1080p, bebas iklan, dan obrolan dengan sineas PRO & Free.
                            </p>

                            <!-- Solid Cobalt Button -->
                            <div class="mt-7">
                                <button
                                    type="button"
                                    @click="choosePlan('PRO')"
                                    :disabled="!props.canSubscribePro.allowed || isProcessingPayment"
                                    class="w-full h-[44px] rounded-[10px] text-[13px] font-semibold transition-all duration-200 cursor-pointer shadow-[0_4px_16px_rgba(27,68,232,0.35)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group-hover/card:shadow-[0_6px_24px_rgba(27,68,232,0.5)] hover:scale-[1.01] active:scale-[0.99]"
                                    :class="[
                                        !props.canSubscribePro.allowed
                                            ? 'bg-white/5 border border-white/10 text-[#8C8C90]'
                                            : currentUserTier === 'PRO'
                                            ? 'bg-[#1B44E8]/20 border border-[#1B44E8]/50 text-[#7A9BFF]'
                                            : 'bg-[#1B44E8] hover:bg-[#1537bc] text-white'
                                    ]"
                                >
                                    <span v-if="isProcessingPayment && pendingPaymentTier === 'PRO'" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                    <Lock v-else-if="!props.canSubscribePro.allowed" class="w-3.5 h-3.5 text-[#8C8C90]" />
                                    <RefreshCw v-else-if="currentUserTier === 'PRO'" class="w-3.5 h-3.5 text-[#7A9BFF]" />
                                    <Sparkles v-else class="w-3.5 h-3.5" />
                                    <span>
                                        {{
                                            !props.canSubscribePro.allowed
                                                ? 'Terkunci (Paket VIP Aktif)'
                                                : currentUserTier === 'PRO'
                                                ? 'Perpanjang Paket Pro'
                                                : 'Pilih Pro'
                                        }}
                                    </span>
                                </button>
                                <p v-if="!props.canSubscribePro.allowed" class="text-[11px] text-amber-400/80 text-center mt-2">
                                    {{ props.canSubscribePro.reason }}
                                </p>
                                <p v-else-if="currentUserTier === 'PRO' && props.subscriptionExpiresFormatted" class="text-[11px] text-[#7A9BFF] text-center mt-2">
                                    Perpanjang untuk menambah {{ billingCycle === 'yearly' ? '365' : '30' }} hari masa aktif
                                </p>
                            </div>

                            <!-- Divider -->
                            <div class="my-7 border-t border-[rgba(242,239,234,0.08)]"></div>

                            <!-- Feature Checklist -->
                            <div class="space-y-3.5 text-[13px]">
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#F2EFEA] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Akses katalog film kurasi luas</span>
                                </div>
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#F2EFEA] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Kualitas HD 1080p sinematik</span>
                                </div>
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#F2EFEA] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Bebas gangguan iklan komersial</span>
                                </div>
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#7A9BFF] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA] font-medium">Chat dengan kreator PRO & FREE</span>
                                </div>
                                <div class="flex items-start space-x-2.5">
                                    <Check class="h-4 w-4 text-[#F2EFEA] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Akses baca serial novel lengkap</span>
                                </div>
                                <!-- Excluded -->
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Chat dengan kreator VIP elit</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Chat langsung dengan admin</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#4A4A4E]">
                                    <Minus class="h-4 w-4 text-[#4A4A4E] shrink-0 mt-0.5" />
                                    <span class="line-through">Resolusi 4K & Screening perdana</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= PLAN 3: VIP ================= -->
                    <div
                        class="p-8 sm:p-9 flex flex-col justify-between relative transition-all duration-300 hover:shadow-[0_12px_45px_rgba(212,182,122,0.25)] group/card"
                        :class="[
                            currentUserTier === 'VIP'
                                ? 'border-t-2 border-t-[#D4B67A] ring-2 ring-[#D4B67A] shadow-[0_0_45px_rgba(212,182,122,0.35)] bg-gradient-to-b from-[#D4B67A]/20 via-[#D4B67A]/05 to-[#0E0F11]'
                                : 'bg-gradient-to-b from-[#D4B67A]/08 via-[#D4B67A]/02 to-transparent',
                            highlightedTier === 'VIP' ? 'ring-2 ring-[#D4B67A] animate-pulse' : ''
                        ]"
                    >
                        <div>
                            <!-- VIP Overline Badge -->
                            <div class="flex items-center justify-between mb-2">
                                <div v-if="currentUserTier === 'VIP'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-[#D4B67A]/30 border border-[#D4B67A]/60 text-[#D4B67A] shadow-[0_0_15px_rgba(212,182,122,0.4)] animate-fadeIn">
                                    <Crown class="w-3.5 h-3.5 text-[#D4B67A] animate-pulse" />
                                    <span>Kasta Anda Saat Ini (Aktif)</span>
                                </div>
                                <div v-else class="flex items-center gap-1.5 text-[10px] uppercase tracking-widest text-[#D4B67A] font-semibold">
                                    <Crown class="w-3.5 h-3.5 text-[#D4B67A]" />
                                    <span>KASTA TERTINGGI TANPA BATAS</span>
                                </div>
                            </div>

                            <!-- Tier Header with Crown Icon -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <Crown class="h-4 w-4 text-[#D4B67A]" />
                                    <span class="text-[19px] font-semibold text-[#F2EFEA]">VIP</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 text-[10px] tracking-wider font-semibold uppercase text-[#D4B67A] bg-[rgba(212,182,122,0.14)] border border-[rgba(212,182,122,0.35)] rounded-[4px]"
                                >
                                    VIP
                                </span>
                            </div>

                            <!-- Price Display -->
                            <div class="mt-6 flex items-baseline">
                                <span class="text-[14px] text-[#8C8C90] font-normal mr-1.5">Rp</span>
                                <span class="text-[40px] font-semibold tracking-tight text-[#F2EFEA] leading-none transition-all duration-300">
                                    {{ billingCycle === 'yearly' ? '120.000' : '150.000' }}
                                </span>
                                <span class="text-[13px] text-[#8C8C90] ml-2">/month</span>
                            </div>
                            <div class="text-[12px] text-[#8C8C90] mt-1 transition-all duration-200">
                                {{ billingCycle === 'yearly' ? 'Ditagih tahunan (Rp 1.440.000/thn) · Batal kapan saja' : 'Ditagih bulanan · Batal kapan saja' }}
                            </div>

                            <p class="text-[13px] leading-relaxed text-[#8C8C90] mt-6 min-h-[40px]">
                                Kasta tertinggi tanpa batas. Akses seluruh master film 4K HDR, obrolan dengan seluruh sutradara elit, dan jalur langsung ke tim kurator WATOON.
                            </p>

                            <!-- Ghost Button with Brass Hairline -->
                            <div class="mt-7">
                                <button
                                    type="button"
                                    @click="choosePlan('VIP')"
                                    :disabled="!props.canSubscribeVip.allowed || isProcessingPayment"
                                    class="w-full h-[44px] rounded-[10px] border border-[rgba(212,182,122,0.45)] text-[#D4B67A] text-[13px] font-semibold transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 group-hover/card:border-[#D4B67A] group-hover/card:shadow-[0_4px_16px_rgba(212,182,122,0.25)] hover:scale-[1.01] active:scale-[0.99]"
                                    :class="
                                        currentUserTier === 'VIP'
                                            ? 'bg-[#D4B67A]/20 text-[#D4B67A] font-semibold shadow-inner'
                                            : 'hover:bg-[rgba(212,182,122,0.12)]'
                                    "
                                >
                                    <span v-if="isProcessingPayment && pendingPaymentTier === 'VIP'" class="w-3.5 h-3.5 border-2 border-[#D4B67A]/30 border-t-[#D4B67A] rounded-full animate-spin"></span>
                                    <RefreshCw v-else-if="currentUserTier === 'VIP'" class="w-3.5 h-3.5 text-[#D4B67A]" />
                                    <Crown v-else class="w-3.5 h-3.5" />
                                    <span>
                                        {{
                                            currentUserTier === 'VIP'
                                                ? 'Perpanjang Paket VIP'
                                                : currentUserTier === 'PRO'
                                                ? 'Upgrade ke VIP (Harga Normal)'
                                                : 'Pilih VIP'
                                        }}
                                    </span>
                                </button>
                                <p v-if="currentUserTier === 'PRO'" class="text-[11px] text-[#D4B67A] text-center mt-2">
                                    Upgrade ke VIP berlaku dengan harga normal (Rp {{ billingCycle === 'yearly' ? '1.440.000' : '150.000' }})
                                </p>
                                <p v-else-if="currentUserTier === 'VIP' && props.subscriptionExpiresFormatted" class="text-[11px] text-[#D4B67A] text-center mt-2">
                                    Perpanjang untuk menambah {{ billingCycle === 'yearly' ? '365' : '30' }} hari masa aktif
                                </p>
                            </div>

                            <!-- Divider -->
                            <div class="my-7 border-t border-[rgba(242,239,234,0.08)]"></div>

                            <!-- Feature Checklist (all checks in brass #D4B67A) -->
                            <div class="space-y-3.5 text-[13px]">
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Akses seluruh koleksi film tanpa batas</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Kualitas Ultra 4K HDR sinematik master</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Pengalaman murni tanpa iklan sama sekali</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA] font-medium">Chat dengan SEMUA kreator (VIP, PRO, FREE)</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Chat langsung dengan kurator admin WATOON</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Akses perdana bab novel & naskah skenario</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">SLA dukungan prioritas & konsultasi film</span>
                                </div>
                                <div class="flex items-start space-x-2.5 text-[#D4B67A]">
                                    <Check class="h-4 w-4 text-[#D4B67A] shrink-0 mt-0.5" />
                                    <span class="text-[#F2EFEA]">Tiket screening film sebelum rilis umum</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 4. TRUST & PAYMENT GATEWAYS BAR                                           -->
            <!-- ========================================================================= -->
            <div
                class="mt-14 max-w-[1120px] mx-auto p-5 rounded-2xl border border-[rgba(242,239,234,0.08)] bg-[#121316]/70 backdrop-blur-md flex flex-col md:flex-row items-center justify-between gap-4 text-[12px] text-[#8C8C90] scroll-reveal delay-200"
            >
                <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                    <div class="flex items-center space-x-2 text-[#F2EFEA]">
                        <ShieldCheck class="h-4 w-4 text-[#7A9BFF]" />
                        <span class="font-medium">Pembayaran Resmi Terenkripsi</span>
                    </div>
                    <div class="h-3 w-[1px] bg-[rgba(242,239,234,0.15)] hidden sm:block"></div>
                    <div class="flex items-center space-x-2">
                        <Check class="h-4 w-4 text-emerald-400" />
                        <span>Batal Kapan Saja</span>
                    </div>
                    <div class="h-3 w-[1px] bg-[rgba(242,239,234,0.15)] hidden sm:block"></div>
                    <div class="flex items-center space-x-2">
                        <Lock class="h-4 w-4 text-[#D4B67A]" />
                        <span>Tanpa Biaya Tersembunyi</span>
                    </div>
                </div>

                <!-- Payment Method Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">QRIS</span>
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">GoPay</span>
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">OVO</span>
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">ShopeePay</span>
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">BCA Virtual Account</span>
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">Mandiri</span>
                    <span class="px-2 py-0.5 rounded bg-[#1C1E23] text-[10px] font-mono text-[#A0A0A5] border border-white/5 hover:border-[#7A9BFF]/40 transition-colors">Visa/Mastercard</span>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 5. INTERACTIVE FEATURE: PLAN FINDER / QUIZ ("Temukan Kasta Idealmu")       -->
            <!-- ========================================================================= -->
            <section
                id="section-quiz"
                class="mt-24 p-6 sm:p-9 rounded-2xl border border-[rgba(242,239,234,0.10)] bg-gradient-to-br from-[#121316] via-[#16181C] to-[#121316] shadow-2xl relative overflow-hidden scroll-reveal"
            >
                <div class="absolute -top-32 -left-32 w-80 h-80 bg-[#1B44E8]/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-[rgba(242,239,234,0.08)]">
                    <div>
                        <div class="inline-flex items-center gap-1.5 uppercase text-[10px] tracking-widest font-semibold text-[#7A9BFF] mb-2 px-2.5 py-0.5 rounded-full bg-[#1B44E8]/15 border border-[#1B44E8]/25">
                            <Sliders class="w-3.5 h-3.5" />
                            <span>KUIS KEBUTUHAN INTERAKTIF</span>
                        </div>
                        <h2 class="text-2xl font-semibold text-[#F2EFEA] tracking-tight">
                            Temukan Kasta Ideal untuk Anda
                        </h2>
                        <p class="text-[13px] text-[#8C8C90] mt-1 max-w-xl">
                            Pilih preferensi menonton dan interaksi Anda di bawah. Algoritma kami akan merekomendasikan kasta yang paling efisien dan memuaskan.
                        </p>
                    </div>

                    <div class="text-xs text-[#8C8C90] flex items-center gap-1.5">
                        <Activity class="w-4 h-4 text-emerald-400 animate-pulse" />
                        <span>Kalkulasi Real-time Aktif</span>
                    </div>
                </div>

                <!-- 3 Question Cards Grid -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Question 1: Device -->
                    <div class="p-5 rounded-xl border border-[rgba(242,239,234,0.08)] bg-[#0E0F11]/80 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-mono text-[#7A9BFF] font-semibold">LANGKAH 1</span>
                            <h3 class="text-sm font-semibold text-[#F2EFEA] mt-1 mb-3">
                                Perangkat Nonton Utama
                            </h3>
                            <div class="space-y-2">
                                <button
                                    type="button"
                                    @click="quiz.device = 'phone'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.device === 'phone' ? 'bg-[#1B44E8]/20 border-[#1B44E8] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Smartphone class="w-4 h-4 shrink-0" />
                                    <span>Ponsel / Tablet (720p)</span>
                                </button>
                                <button
                                    type="button"
                                    @click="quiz.device = 'laptop'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.device === 'laptop' ? 'bg-[#1B44E8]/20 border-[#1B44E8] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Laptop class="w-4 h-4 shrink-0" />
                                    <span>Laptop / PC FHD (1080p)</span>
                                </button>
                                <button
                                    type="button"
                                    @click="quiz.device = 'tv'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.device === 'tv' ? 'bg-[#D4B67A]/20 border-[#D4B67A] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Tv class="w-4 h-4 shrink-0" />
                                    <span>Smart TV 4K HDR Home Theater</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Question 2: Creator Access -->
                    <div class="p-5 rounded-xl border border-[rgba(242,239,234,0.08)] bg-[#0E0F11]/80 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-mono text-[#7A9BFF] font-semibold">LANGKAH 2</span>
                            <h3 class="text-sm font-semibold text-[#F2EFEA] mt-1 mb-3">
                                Interaksi dengan Sineas
                            </h3>
                            <div class="space-y-2">
                                <button
                                    type="button"
                                    @click="quiz.creatorInterest = 'watch_only'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.creatorInterest === 'watch_only' ? 'bg-[#1B44E8]/20 border-[#1B44E8] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Eye class="w-4 h-4 shrink-0" />
                                    <span>Cukup Menonton Saja</span>
                                </button>
                                <button
                                    type="button"
                                    @click="quiz.creatorInterest = 'indie_chat'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.creatorInterest === 'indie_chat' ? 'bg-[#1B44E8]/20 border-[#1B44E8] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <MessageSquare class="w-4 h-4 shrink-0" />
                                    <span>Diskusi Sineas PRO & Indie</span>
                                </button>
                                <button
                                    type="button"
                                    @click="quiz.creatorInterest = 'master_circle'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.creatorInterest === 'master_circle' ? 'bg-[#D4B67A]/20 border-[#D4B67A] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Crown class="w-4 h-4 shrink-0" />
                                    <span>Saluran VIP Sutradara Festival</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Question 3: Exclusive Novel & Script -->
                    <div class="p-5 rounded-xl border border-[rgba(242,239,234,0.08)] bg-[#0E0F11]/80 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-mono text-[#7A9BFF] font-semibold">LANGKAH 3</span>
                            <h3 class="text-sm font-semibold text-[#F2EFEA] mt-1 mb-3">
                                Novel Sinematik & Naskah
                            </h3>
                            <div class="space-y-2">
                                <button
                                    type="button"
                                    @click="quiz.novelAccess = 'intro_only'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.novelAccess === 'intro_only' ? 'bg-[#1B44E8]/20 border-[#1B44E8] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <BookOpen class="w-4 h-4 shrink-0" />
                                    <span>Bab Pembuka Saja Cukup</span>
                                </button>
                                <button
                                    type="button"
                                    @click="quiz.novelAccess = 'full_episodes'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.novelAccess === 'full_episodes' ? 'bg-[#1B44E8]/20 border-[#1B44E8] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Film class="w-4 h-4 shrink-0" />
                                    <span>Akses Seluruh Episode Novel</span>
                                </button>
                                <button
                                    type="button"
                                    @click="quiz.novelAccess = 'early_screenplay'; playSoftClick()"
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all flex items-center gap-2.5 cursor-pointer"
                                    :class="quiz.novelAccess === 'early_screenplay' ? 'bg-[#D4B67A]/20 border-[#D4B67A] text-white shadow-sm' : 'border-white/5 text-[#8C8C90] hover:bg-white/[0.03]'"
                                >
                                    <Award class="w-4 h-4 shrink-0" />
                                    <span>Naskah Asli & Screening Awal</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommendation Output Card -->
                <div
                    class="mt-8 p-6 rounded-xl border transition-all duration-300 bg-gradient-to-r from-[#17181B] via-[#141518] to-[#17181B] flex flex-col md:flex-row items-center justify-between gap-6"
                    :class="calculatedRecommendation.glowColor"
                >
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold border tracking-wider uppercase inline-flex items-center gap-1.5"
                                :class="calculatedRecommendation.badgeColor"
                            >
                                <Sparkles class="w-3.5 h-3.5" />
                                <span>REKOMENDASI: {{ calculatedRecommendation.tier }}</span>
                            </span>
                            <span class="text-xs text-emerald-400 font-semibold font-mono">
                                {{ calculatedRecommendation.match }}% Kecocokan
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#F2EFEA] mt-2">
                            {{ calculatedRecommendation.title }}
                        </h4>
                        <ul class="mt-2 space-y-1.5 text-xs text-[#8C8C90]">
                            <li v-for="(reason, rIdx) in calculatedRecommendation.reasons" :key="rIdx" class="flex items-start gap-2">
                                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" />
                                <span>{{ reason }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="shrink-0 flex items-center gap-3 w-full md:w-auto">
                        <button
                            type="button"
                            @click="applyRecommendedTier(calculatedRecommendation.tier)"
                            class="w-full md:w-auto px-6 py-3 rounded-xl font-semibold text-xs transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer hover:scale-[1.02]"
                            :class="
                                calculatedRecommendation.tier === 'VIP'
                                    ? 'bg-[#D4B67A] text-[#0E0F11] hover:bg-[#c4a468] shadow-[#D4B67A]/20'
                                    : calculatedRecommendation.tier === 'PRO'
                                    ? 'bg-[#1B44E8] text-white hover:bg-[#1537C2] shadow-[#1B44E8]/20'
                                    : 'bg-white/15 text-white hover:bg-white/20'
                            "
                        >
                            <span>Lihat & Sorot Paket {{ calculatedRecommendation.tier }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 6. INTERACTIVE CINEMA QUALITY & AUDIO VISUALIZER                          -->
            <!-- ========================================================================= -->
            <section
                id="section-quality"
                class="mt-28 p-6 sm:p-9 rounded-2xl border border-[rgba(242,239,234,0.10)] bg-[#121316] shadow-2xl relative overflow-hidden scroll-reveal"
            >
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-[rgba(242,239,234,0.08)]">
                    <div>
                        <div class="inline-flex items-center gap-1.5 uppercase text-[10px] tracking-widest font-semibold text-[#7A9BFF] mb-2 px-2.5 py-0.5 rounded-full bg-[#1B44E8]/15 border border-[#1B44E8]/25">
                            <Tv class="w-3.5 h-3.5" />
                            <span>PRATINJAU KUALITAS SINEMA INTERAKTIF</span>
                        </div>
                        <h2 class="text-2xl font-semibold text-[#F2EFEA] tracking-tight">
                            Bandingkan Kualitas Visual & Audio Langsung
                        </h2>
                        <p class="text-[13px] text-[#8C8C90] mt-1 max-w-xl">
                            Klik tab kasta di bawah untuk merasakan perbedaan visual, bitrate warna 10-bit, audio Dolby Atmos, dan kebersihan tanpa jeda iklan.
                        </p>
                    </div>

                    <!-- Quality Resolution Selector Buttons -->
                    <div class="inline-flex items-center p-1 rounded-xl bg-[#0E0F11] border border-[rgba(242,239,234,0.12)] shrink-0">
                        <button
                            type="button"
                            @click="setQuality('720')"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="selectedQuality === '720' ? 'bg-white/15 text-white shadow-sm' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            Free · 720p SDR
                        </button>
                        <button
                            type="button"
                            @click="setQuality('1080')"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="selectedQuality === '1080' ? 'bg-[#1B44E8] text-white shadow-md shadow-[#1B44E8]/30' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            Pro · 1080p Crystal
                        </button>
                        <button
                            type="button"
                            @click="setQuality('4k')"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="selectedQuality === '4k' ? 'bg-[#D4B67A] text-[#0E0F11] shadow-md shadow-[#D4B67A]/30' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            VIP · Ultra 4K HDR
                        </button>
                    </div>
                </div>

                <!-- Cinematic Frame Showcase Container -->
                <div class="mt-8 relative rounded-xl overflow-hidden border border-[rgba(242,239,234,0.1)] bg-black aspect-[16/9] sm:aspect-[21/9] max-h-[440px] flex items-center justify-center group">
                    <!-- Film Frame Background Image with dynamic CSS filter -->
                    <img
                        src="https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=1600&q=80"
                        alt="Cinematic Preview Frame"
                        class="w-full h-full object-cover transition-all duration-700 ease-out select-none"
                        :class="[
                            selectedQuality === '720'
                                ? 'blur-[0.6px] brightness-90 contrast-95 grayscale-[15%]'
                                : selectedQuality === '1080'
                                ? 'contrast-105 saturate-105 brightness-100'
                                : 'contrast-115 saturate-110 brightness-105'
                        ]"
                    />

                    <!-- VIP Film Grain Layer (Toggleable) -->
                    <div
                        v-if="selectedQuality === '4k' && showFilmGrain"
                        class="absolute inset-0 pointer-events-none bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:3px_3px] opacity-15 mix-blend-overlay animate-pulse"
                    ></div>

                    <!-- Watermark / Ad Overlay on 720p Free -->
                    <div
                        v-if="selectedQuality === '720'"
                        class="absolute top-4 left-4 px-3 py-1 rounded bg-black/80 border border-white/20 backdrop-blur-sm text-[11px] text-[#A0A0A5] flex items-center gap-1.5 animate-fadeIn"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span>Streaming Didukung Iklan Sponsor Terjadwal</span>
                    </div>

                    <!-- Clean 4K Master Badge on VIP -->
                    <div
                        v-if="selectedQuality === '4k'"
                        class="absolute top-4 left-4 px-3 py-1 rounded-full bg-[#D4B67A]/25 border border-[#D4B67A]/50 backdrop-blur-md text-[11px] font-bold text-[#D4B67A] flex items-center gap-1.5 shadow-lg shadow-[#D4B67A]/20 animate-fadeIn"
                    >
                        <Crown class="w-3.5 h-3.5" />
                        <span>CINEMA 4K HDR10+ MASTER · 10-BIT LOSSLESS</span>
                    </div>

                    <!-- Bottom Specs Telemetry HUD -->
                    <div class="absolute bottom-0 inset-x-0 p-4 sm:p-6 bg-gradient-to-t from-black/95 via-black/70 to-transparent flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-white tracking-wide">
                                    {{ selectedQuality === '720' ? 'Standard Definition (720p)' : selectedQuality === '1080' ? 'High Definition (1080p FHD)' : 'Ultra High Definition (3840×2160 UHD)' }}
                                </span>
                                <span
                                    class="px-2 py-0.2 rounded text-[10px] font-mono font-bold uppercase"
                                    :class="
                                        selectedQuality === '4k'
                                            ? 'bg-[#D4B67A] text-[#0E0F11]'
                                            : selectedQuality === '1080'
                                            ? 'bg-[#1B44E8] text-white'
                                            : 'bg-white/20 text-[#A0A0A5]'
                                    "
                                >
                                    {{ selectedQuality === '4k' ? 'VIP' : selectedQuality === '1080' ? 'PRO' : 'FREE' }}
                                </span>
                            </div>
                            <p class="text-xs text-[#8C8C90]">
                                {{ selectedQuality === '720' ? 'Bitrate 3.5 Mbps · Stereo 2.0 (128 kbps) · Standar layar ponsel' : selectedQuality === '1080' ? 'Bitrate 15 Mbps · Dolby Surround 5.1 (384 kbps) · Gambar jernih tanpa artefak kompresi' : 'Bitrate 45 Mbps CineMaster · Dolby Atmos 7.1.4 (Lossless) · Akurasi warna sinematik DCI-P3' }}
                            </p>
                        </div>

                        <!-- Equalizer Waveform Animation & Sound Preview Button -->
                        <div class="flex items-center gap-3">
                            <!-- Visualizer Waveform Bars -->
                            <div class="flex items-end gap-1 h-6 px-2 py-1 rounded bg-black/60 border border-white/10">
                                <span class="w-1 bg-[#7A9BFF] rounded-full animate-soundWave [animation-delay:0.1s]" :style="{ height: selectedQuality === '4k' ? '20px' : selectedQuality === '1080' ? '14px' : '7px' }"></span>
                                <span class="w-1 bg-[#7A9BFF] rounded-full animate-soundWave [animation-delay:0.3s]" :style="{ height: selectedQuality === '4k' ? '24px' : selectedQuality === '1080' ? '18px' : '10px' }"></span>
                                <span class="w-1 bg-[#7A9BFF] rounded-full animate-soundWave [animation-delay:0.2s]" :style="{ height: selectedQuality === '4k' ? '18px' : selectedQuality === '1080' ? '12px' : '6px' }"></span>
                                <span class="w-1 bg-[#7A9BFF] rounded-full animate-soundWave [animation-delay:0.4s]" :style="{ height: selectedQuality === '4k' ? '22px' : selectedQuality === '1080' ? '16px' : '8px' }"></span>
                                <span class="w-1 bg-[#7A9BFF] rounded-full animate-soundWave [animation-delay:0.25s]" :style="{ height: selectedQuality === '4k' ? '16px' : selectedQuality === '1080' ? '10px' : '5px' }"></span>
                            </div>

                            <button
                                type="button"
                                @click="playCinemaFrequency(selectedQuality)"
                                class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-semibold text-white flex items-center gap-1.5 transition-all cursor-pointer"
                                title="Putar simulasi frekuensi audio"
                            >
                                <Volume2 class="w-3.5 h-3.5 text-[#7A9BFF]" />
                                <span>Dengarkan Akustik</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 7. INTERACTIVE LIVE CREATOR CHAT SIMULATOR & DIALOGUE TESTER             -->
            <!-- ========================================================================= -->
            <section
                id="section-simulator"
                class="mt-28 p-6 sm:p-9 rounded-2xl border border-[rgba(242,239,234,0.10)] bg-gradient-to-br from-[#121316] via-[#17181B] to-[#121316] shadow-2xl relative overflow-hidden scroll-reveal"
            >
                <!-- Background Ambient Accent -->
                <div class="absolute -top-24 -right-24 w-72 h-72 bg-[#1B44E8]/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-[rgba(242,239,234,0.08)]">
                    <div>
                        <div class="inline-flex items-center gap-1.5 uppercase text-[10px] tracking-widest font-semibold text-[#7A9BFF] mb-2 px-2.5 py-0.5 rounded-full bg-[#1B44E8]/15 border border-[#1B44E8]/25">
                            <Users class="w-3.5 h-3.5" />
                            <span>SIMULATOR INTERAKTIF AKSES KREATOR</span>
                        </div>
                        <h2 class="text-2xl font-semibold text-[#F2EFEA] tracking-tight">
                            Uji Akses Obrolan Berdasarkan Kasta
                        </h2>
                        <p class="text-[13px] text-[#8C8C90] mt-1 max-w-xl">
                            Klik kasta di samping untuk melihat langsung sineas mana saja yang pintunya terbuka. Klik salah satu sineas untuk mencoba simulasi obrolan langsung!
                        </p>
                    </div>

                    <!-- Simulator Kasta Switcher Pill -->
                    <div class="inline-flex items-center p-1 rounded-xl bg-[#0E0F11] border border-[rgba(242,239,234,0.12)] shrink-0 shadow-lg">
                        <button
                            type="button"
                            @click="simulatorTier = 'FREE'; playSoftClick()"
                            class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none"
                            :class="simulatorTier === 'FREE' ? 'bg-white/15 text-white shadow-sm' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            Simulasi FREE
                        </button>
                        <button
                            type="button"
                            @click="simulatorTier = 'PRO'; playSoftClick()"
                            class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none"
                            :class="simulatorTier === 'PRO' ? 'bg-[#1B44E8] text-white shadow-md shadow-[#1B44E8]/30' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            Simulasi PRO
                        </button>
                        <button
                            type="button"
                            @click="simulatorTier = 'VIP'; playSoftClick()"
                            class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none"
                            :class="simulatorTier === 'VIP' ? 'bg-[#D4B67A] text-[#0E0F11] shadow-md shadow-[#D4B67A]/30' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                        >
                            Simulasi VIP
                        </button>
                    </div>
                </div>

                <!-- Live Creator Access Grid -->
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="c in simulatorCreators"
                        :key="c.id"
                        class="p-4 rounded-xl border transition-all duration-300 relative flex flex-col justify-between group cursor-pointer"
                        :class="[
                            canSimulateChat(c.tier)
                                ? 'bg-[#17181B] border-[rgba(242,239,234,0.12)] hover:border-[#1B44E8]/60 shadow-md hover:-translate-y-0.5'
                                : 'bg-[#0E0F11]/60 border-[rgba(242,239,234,0.05)] opacity-60 grayscale-[40%]'
                        ]"
                        @click="canSimulateChat(c.tier) ? openCreatorDialogue(c) : switchTier(c.tier)"
                    >
                        <!-- Creator Info -->
                        <div>
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <img
                                            :src="c.avatar"
                                            :alt="c.name"
                                            class="w-11 h-11 rounded-full object-cover border border-white/10"
                                        />
                                        <!-- Tier Pill on Avatar -->
                                        <span
                                            class="absolute -bottom-1 -right-1 px-1 py-0.2 rounded text-[8px] font-mono font-bold border"
                                            :class="[
                                                c.tier === 'VIP'
                                                    ? 'bg-[#D4B67A] text-[#0E0F11] border-[#D4B67A]'
                                                    : c.tier === 'PRO'
                                                    ? 'bg-[#1B44E8] text-white border-[#1B44E8]'
                                                    : 'bg-[#23252A] text-[#8C8C90] border-white/10'
                                            ]"
                                        >
                                            {{ c.tier }}
                                        </span>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-semibold text-[#F2EFEA] group-hover:text-white transition-colors">
                                            {{ c.name }}
                                        </h4>
                                        <p class="text-[11px] text-[#8C8C90]">
                                            {{ c.handle }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                    :class="[
                                        canSimulateChat(c.tier)
                                            ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                                            : 'bg-red-500/10 text-red-400 border border-red-500/20'
                                    ]"
                                >
                                    <Unlock v-if="canSimulateChat(c.tier)" class="w-3 h-3" />
                                    <Lock v-else class="w-3 h-3" />
                                    <span>{{ canSimulateChat(c.tier) ? 'Terbuka' : 'Terkunci' }}</span>
                                </span>
                            </div>

                            <!-- Film Attribution -->
                            <div class="px-2.5 py-1.5 rounded-lg bg-black/30 border border-white/5 text-[11px] flex items-center justify-between">
                                <span class="text-[#8C8C90] truncate">Karya: {{ c.film }}</span>
                                <span class="text-[10px] text-[#7A9BFF] shrink-0 font-mono">{{ c.genre }}</span>
                            </div>
                        </div>

                        <!-- Action State -->
                        <div class="mt-4 pt-3 border-t border-[rgba(242,239,234,0.06)] flex items-center justify-between text-xs">
                            <span v-if="canSimulateChat(c.tier)" class="text-[11px] text-emerald-400 font-medium flex items-center gap-1">
                                <MessageSquare class="w-3.5 h-3.5" />
                                Klik untuk Tes Chat
                            </span>
                            <span v-else class="text-[11px] text-[#8C8C90] flex items-center gap-1">
                                Butuh kasta <strong>{{ c.tier }}</strong>
                            </span>

                            <button
                                v-if="canSimulateChat(c.tier)"
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-[#7A9BFF] group-hover:text-white transition-colors"
                            >
                                <span>Coba Chat</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </button>

                            <button
                                v-else
                                type="button"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#D4B67A] hover:underline cursor-pointer"
                            >
                                <span>Upgrade {{ c.tier }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 8. COMPARISON TABLE WITH SEARCH & DIFFERENCE FILTER                       -->
            <!-- ========================================================================= -->
            <section id="section-table" class="mt-28 max-w-[1120px] mx-auto scroll-reveal delay-100">
                <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <div class="uppercase text-[11px] tracking-widest font-semibold text-[#1B44E8] mb-2">
                            COMPARE PLANS
                        </div>
                        <h2 class="text-2xl font-semibold text-[#F2EFEA] tracking-tight">
                            Perbandingan Fitur Lengkap
                        </h2>
                    </div>

                    <!-- Category Pills, Search & Difference Toggle -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Search Table Input -->
                        <div class="relative w-[180px]">
                            <Search class="absolute left-2.5 top-2.5 text-[#8C8C90] h-3.5 w-3.5 pointer-events-none" />
                            <input
                                v-model="tableSearchQuery"
                                type="text"
                                placeholder="Cari fitur…"
                                class="w-full h-8 pl-8 pr-2.5 bg-[#141518] text-xs text-[#F2EFEA] placeholder-[#5e5e61] border border-[rgba(242,239,234,0.1)] rounded-lg focus:outline-none focus:border-[#7A9BFF] transition-colors"
                            />
                        </div>

                        <!-- Category Filter Buttons -->
                        <div class="inline-flex p-0.5 rounded-lg bg-[#141518] border border-[rgba(242,239,234,0.08)] text-xs">
                            <button
                                type="button"
                                @click="activeFeatureCategory = 'all'; playSoftClick()"
                                class="px-2.5 py-1 rounded-[6px] transition-colors cursor-pointer"
                                :class="activeFeatureCategory === 'all' ? 'bg-[#23252A] text-white font-medium' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                            >
                                Semua ({{ comparisonFeatures.length }})
                            </button>
                            <button
                                type="button"
                                @click="activeFeatureCategory = 'chat'; playSoftClick()"
                                class="px-2.5 py-1 rounded-[6px] transition-colors cursor-pointer"
                                :class="activeFeatureCategory === 'chat' ? 'bg-[#23252A] text-white font-medium' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                            >
                                Chat Kreator
                            </button>
                            <button
                                type="button"
                                @click="activeFeatureCategory = 'video'; playSoftClick()"
                                class="px-2.5 py-1 rounded-[6px] transition-colors cursor-pointer"
                                :class="activeFeatureCategory === 'video' ? 'bg-[#23252A] text-white font-medium' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                            >
                                Kualitas Sinema
                            </button>
                            <button
                                type="button"
                                @click="activeFeatureCategory = 'exclusive'; playSoftClick()"
                                class="px-2.5 py-1 rounded-[6px] transition-colors cursor-pointer"
                                :class="activeFeatureCategory === 'exclusive' ? 'bg-[#23252A] text-white font-medium' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                            >
                                Akses Eksklusif
                            </button>
                        </div>

                        <!-- Only Differences Toggle -->
                        <button
                            type="button"
                            @click="showOnlyDifferences = !showOnlyDifferences; playSoftClick()"
                            class="px-3 py-1.5 rounded-lg border text-xs font-medium flex items-center gap-1.5 transition-colors cursor-pointer"
                            :class="showOnlyDifferences ? 'bg-[#1B44E8]/20 border-[#1B44E8]/50 text-[#7A9BFF]' : 'border-[rgba(242,239,234,0.1)] text-[#8C8C90] hover:text-white'"
                        >
                            <Filter class="w-3.5 h-3.5" />
                            <span>Hanya Perbedaan</span>
                        </button>
                    </div>
                </div>

                <!-- Table Container with strict Hairline divisions -->
                <div class="border border-[rgba(242,239,234,0.08)] rounded-[14px] overflow-x-auto bg-[#0E0F11] shadow-2xl">
                    <table class="w-full text-left border-collapse min-w-[720px]">
                        <thead>
                            <tr class="border-b border-[rgba(242,239,234,0.08)] bg-[#141518]/90 backdrop-blur text-[13px] text-[#8C8C90]">
                                <th class="py-4.5 px-6 w-[40%] font-medium">Fitur & Fasilitas</th>
                                <th
                                    @mouseenter="hoveredTableCol = 'free'"
                                    @mouseleave="hoveredTableCol = null"
                                    class="py-4.5 px-6 w-[20%] font-medium text-center transition-colors cursor-pointer"
                                    :class="hoveredTableCol === 'free' || currentUserTier === 'FREE' ? 'text-white bg-white/[0.04]' : ''"
                                >
                                    Free
                                    <span v-if="currentUserTier === 'FREE'" class="block text-[10px] text-emerald-400 font-normal">Aktif</span>
                                </th>
                                <th
                                    @mouseenter="hoveredTableCol = 'pro'"
                                    @mouseleave="hoveredTableCol = null"
                                    class="py-4.5 px-6 w-[20%] font-medium text-center text-[#7A9BFF] transition-colors cursor-pointer"
                                    :class="hoveredTableCol === 'pro' || currentUserTier === 'PRO' ? 'bg-[#1B44E8]/[0.09]' : ''"
                                >
                                    Pro
                                    <span v-if="currentUserTier === 'PRO'" class="block text-[10px] text-[#7A9BFF] font-normal">Aktif</span>
                                </th>
                                <th
                                    @mouseenter="hoveredTableCol = 'vip'"
                                    @mouseleave="hoveredTableCol = null"
                                    class="py-4.5 px-6 w-[20%] font-medium text-center text-[#D4B67A] transition-colors cursor-pointer"
                                    :class="hoveredTableCol === 'vip' || currentUserTier === 'VIP' ? 'bg-[#D4B67A]/[0.09]' : ''"
                                >
                                    VIP
                                    <span v-if="currentUserTier === 'VIP'" class="block text-[10px] text-[#D4B67A] font-normal">Aktif</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(242,239,234,0.06)] text-[13px]">
                            <tr
                                v-for="(row, idx) in filteredFeatures"
                                :key="idx"
                                class="h-[56px] hover:bg-white/[0.02] transition-colors"
                            >
                                <td class="px-6">
                                    <p class="text-[#F2EFEA] font-medium">{{ row.name }}</p>
                                    <p class="text-[11px] text-[#8C8C90]">{{ row.hint }}</p>
                                </td>

                                <!-- Free Column -->
                                <td
                                    class="px-6 text-center text-[#8C8C90]"
                                    :class="hoveredTableCol === 'free' || currentUserTier === 'FREE' ? 'bg-white/[0.02]' : ''"
                                >
                                    <template v-if="typeof row.free === 'boolean'">
                                        <Check v-if="row.free" class="h-4 w-4 mx-auto text-emerald-400" />
                                        <Minus v-else class="h-4 w-4 mx-auto text-[#4A4A4E]" />
                                    </template>
                                    <span v-else>{{ row.free }}</span>
                                </td>

                                <!-- Pro Column -->
                                <td
                                    class="px-6 text-center text-[#F2EFEA]"
                                    :class="hoveredTableCol === 'pro' || currentUserTier === 'PRO' ? 'bg-[#1B44E8]/[0.05]' : ''"
                                >
                                    <template v-if="typeof row.pro === 'boolean'">
                                        <Check v-if="row.pro" class="h-4 w-4 mx-auto text-[#7A9BFF]" />
                                        <Minus v-else class="h-4 w-4 mx-auto text-[#4A4A4E]" />
                                    </template>
                                    <span v-else class="font-medium text-[#7A9BFF]">{{ row.pro }}</span>
                                </td>

                                <!-- VIP Column -->
                                <td
                                    class="px-6 text-center text-[#D4B67A]"
                                    :class="hoveredTableCol === 'vip' || currentUserTier === 'VIP' ? 'bg-[#D4B67A]/[0.05]' : ''"
                                >
                                    <template v-if="typeof row.vip === 'boolean'">
                                        <Check v-if="row.vip" class="h-4 w-4 mx-auto text-[#D4B67A]" />
                                        <Minus v-else class="h-4 w-4 mx-auto text-[#4A4A4E]" />
                                    </template>
                                    <span v-else class="font-semibold">{{ row.vip }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 9. SINEAS / CREATOR COMMUNITY TESTIMONIAL TICKER                          -->
            <!-- ========================================================================= -->
            <section class="mt-28 max-w-[1120px] mx-auto scroll-reveal">
                <div class="p-8 rounded-2xl border border-[rgba(242,239,234,0.08)] bg-gradient-to-r from-[#17181B] via-[#121316] to-[#17181B] shadow-xl">
                    <div class="flex flex-col lg:flex-row items-center gap-8 justify-between">
                        <div class="flex items-center gap-4 max-w-xl">
                            <img
                                src="https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=180&q=80"
                                alt="Rendra Wijaya"
                                class="w-16 h-16 rounded-2xl object-cover border-2 border-[#D4B67A]/50 shrink-0 shadow-lg"
                            />
                            <div>
                                <p class="text-xs uppercase tracking-widest text-[#D4B67A] font-semibold mb-1">
                                    SOROTAN SINEAS VIP
                                </p>
                                <blockquote class="text-[14px] text-[#F2EFEA] italic leading-relaxed">
                                    "Obrolan langsung dengan penonton VIP memberi ruang intim tanpa algoritma, di mana saya bisa membagikan proses shot-list dan inspirasi di balik Cakrawala Senja."
                                </blockquote>
                                <p class="text-[12px] text-[#8C8C90] mt-1.5 font-medium">
                                    — Rendra Wijaya, Sutradara Film Terkurasi WATOON
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-3">
                            <Link
                                :href="messages.index.url({ query: { creator: 'creator-1' } })"
                                class="px-5 py-2.5 rounded-xl bg-[#D4B67A] hover:bg-[#c4a468] text-[#0E0F11] font-semibold text-xs transition-colors flex items-center gap-2 shadow-lg shadow-[#D4B67A]/15 cursor-pointer"
                            >
                                <MessageSquare class="w-4 h-4" />
                                <span>Buka Obrolan Kreator</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 10. FAQ ACCORDION (Fluid animated height expansion)                       -->
            <!-- ========================================================================= -->
            <section id="section-faq" class="mt-28 max-w-[760px] mx-auto scroll-reveal delay-100">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-1.5 uppercase text-[11px] tracking-widest font-semibold text-[#1B44E8] mb-2 px-2.5 py-0.5 rounded-full bg-[#1B44E8]/10 border border-[#1B44E8]/20">
                        <HelpCircle class="w-3.5 h-3.5" />
                        <span>FAQ</span>
                    </div>
                    <h2 class="text-2xl sm:text-[28px] font-semibold text-[#F2EFEA] tracking-tight">
                        Pertanyaan yang Sering Diajukan
                    </h2>
                </div>

                <!-- Accordion List with Hairline Dividers -->
                <div class="divide-y divide-[rgba(242,239,234,0.08)] border-y border-[rgba(242,239,234,0.08)]">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="py-5 -mx-4 px-4 rounded-xl transition-all duration-200"
                        :class="openFaqIndex === idx ? 'bg-white/[0.02]' : 'hover:bg-white/[0.01]'"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(idx)"
                            class="w-full flex items-center justify-between text-left cursor-pointer group"
                        >
                            <span
                                class="text-[15px] font-medium transition-colors"
                                :class="openFaqIndex === idx ? 'text-white font-semibold' : 'text-[#F2EFEA] group-hover:text-white'"
                            >
                                {{ faq.question }}
                            </span>
                            <ChevronDown
                                class="h-4 w-4 text-[#8C8C90] transition-transform duration-300 ease-out shrink-0 ml-4"
                                :class="openFaqIndex === idx ? 'rotate-180 text-[#7A9BFF]' : 'group-hover:text-white'"
                            />
                        </button>

                        <!-- Smooth CSS Grid Height Transition -->
                        <div
                            class="grid transition-all duration-300 ease-in-out"
                            :class="openFaqIndex === idx ? 'grid-rows-[1fr] opacity-100 mt-3' : 'grid-rows-[0fr] opacity-0 mt-0'"
                        >
                            <div class="overflow-hidden">
                                <p class="text-[13px] leading-relaxed text-[#8C8C90]">
                                    {{ faq.answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================================= -->
            <!-- 11. FINAL CTA                                                             -->
            <!-- ========================================================================= -->
            <section class="mt-28 py-16 text-center border-t border-[rgba(242,239,234,0.08)] scroll-reveal">
                <div class="max-w-md mx-auto">
                    <div class="w-12 h-12 rounded-2xl bg-[#1B44E8]/15 border border-[#1B44E8]/30 flex items-center justify-center text-[#7A9BFF] mx-auto mb-4 shadow-lg shadow-[#1B44E8]/20">
                        <Zap class="w-6 h-6" />
                    </div>
                    <h3 class="text-[28px] font-semibold text-[#F2EFEA] tracking-tight mb-2">
                        Siap memulai petualangan sinema Anda?
                    </h3>
                    <p class="text-[14px] text-[#8C8C90] mb-8 leading-relaxed">
                        Mulai dari kasta Free secara cuma-cuma. Tingkatkan kasta kapan saja Anda ingin membuka obrolan langsung dengan kreator idola.
                    </p>
                    <div class="flex items-center justify-center space-x-5">
                        <button
                            type="button"
                            @click="switchTier('FREE')"
                            class="h-[44px] px-6 rounded-[10px] border border-[rgba(242,239,234,0.20)] text-[#F2EFEA] text-[13px] font-medium hover:bg-white/10 hover:border-white/30 transition-all duration-200 cursor-pointer shadow-sm hover:scale-[1.02]"
                        >
                            Mulai Gratis
                        </button>
                        <Link
                            :href="dashboard.url({ query: { tab: 'subscribe' } })"
                            class="h-[44px] px-5 rounded-[10px] bg-white/[0.05] hover:bg-white/[0.08] text-[13px] font-medium text-[#7A9BFF] hover:text-[#1B44E8] flex items-center space-x-1.5 transition-all duration-200 hover:scale-[1.02]"
                        >
                            <span>Jelajahi Kreator WATOON</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>


        <!-- ========================================================================= -->
        <!-- 12. FOOTER                                                                -->
        <!-- ========================================================================= -->
        <footer class="mt-28 border-t border-[rgba(242,239,234,0.08)] bg-[#0A0B0D] py-12 relative z-10">
            <div class="max-w-[1200px] mx-auto px-5 sm:px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                <!-- Brand & Copyright -->
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4 text-center sm:text-left">
                    <div class="flex items-center space-x-2">
                        <div class="w-5 h-5 bg-[#1B44E8] rounded-[4px] flex items-center justify-center text-white text-[10px] font-bold">
                            W
                        </div>
                        <span class="font-medium text-[15px] text-[#F2EFEA]">WATOON</span>
                    </div>
                    <span class="text-[12px] text-[#8C8C90]">
                        © 2025 WATOON Inc. All rights reserved. Crafted for critical readers and film enthusiasts.
                    </span>
                </div>

                <!-- Links Cluster -->
                <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[12px] text-[#8C8C90]">
                    <Link :href="dashboard.url()" class="hover:text-[#F2EFEA] transition-colors">Home</Link>
                    <Link :href="dashboard.url({ query: { tab: 'favorite' } })" class="hover:text-[#F2EFEA] transition-colors">Favorite</Link>
                    <Link :href="dashboard.url({ query: { tab: 'subscribe' } })" class="hover:text-[#F2EFEA] transition-colors">Subscribe</Link>
                    <span class="text-[#F2EFEA] underline">Pricing Plans</span>
                    <a href="#" class="hover:text-[#F2EFEA] transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-[#F2EFEA] transition-colors">Terms of Service</a>
                </div>
            </div>
        </footer>

        <!-- ========================================================================= -->
        <!-- LIVE INTERACTIVE CREATOR MINI-DIALOGUE POPUP                              -->
        <!-- ========================================================================= -->
        <div
            v-if="activeTestCreator"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4 animate-fadeIn"
            @click.self="activeTestCreator = null"
        >
            <div
                class="relative w-full max-w-lg rounded-2xl border border-[rgba(242,239,234,0.15)] bg-[#17181B] p-6 shadow-2xl backdrop-blur-2xl animate-scaleUp overflow-hidden flex flex-col max-h-[85vh]"
            >
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b border-[rgba(242,239,234,0.08)]">
                    <div class="flex items-center gap-3">
                        <img
                            :src="activeTestCreator.avatar"
                            :alt="activeTestCreator.name"
                            class="w-10 h-10 rounded-full object-cover border border-white/20"
                        />
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-bold text-white">{{ activeTestCreator.name }}</h3>
                                <span
                                    class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold"
                                    :class="
                                        activeTestCreator.tier === 'VIP'
                                            ? 'bg-[#D4B67A]/20 text-[#D4B67A]'
                                            : activeTestCreator.tier === 'PRO'
                                            ? 'bg-[#1B44E8]/20 text-[#7A9BFF]'
                                            : 'bg-white/10 text-[#8C8C90]'
                                    "
                                >
                                    {{ activeTestCreator.tier }}
                                </span>
                            </div>
                            <p class="text-[11px] text-[#8C8C90]">Sineas Karya: {{ activeTestCreator.film }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="activeTestCreator = null"
                        class="p-1 rounded-lg text-[#8C8C90] hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Dialogue History -->
                <div class="flex-1 overflow-y-auto py-4 space-y-3 pr-1 text-xs">
                    <div
                        v-for="(msg, mIdx) in dialogueMessages"
                        :key="mIdx"
                        class="flex flex-col"
                        :class="msg.sender === 'user' ? 'items-end' : 'items-start'"
                    >
                        <div
                            class="max-w-[85%] rounded-2xl px-4 py-2.5 leading-relaxed"
                            :class="
                                msg.sender === 'user'
                                    ? 'bg-[#1B44E8] text-white rounded-br-none'
                                    : 'bg-[#23252A] text-[#F2EFEA] border border-white/5 rounded-bl-none'
                            "
                        >
                            {{ msg.text }}
                        </div>
                        <span class="text-[10px] text-[#5e5e61] mt-1 px-1">{{ msg.time }}</span>
                    </div>

                    <!-- Typing indicator bubble -->
                    <div v-if="isCreatorTyping" class="flex items-center gap-1.5 p-3 rounded-2xl bg-[#23252A] w-fit rounded-bl-none text-xs text-[#8C8C90]">
                        <span class="w-2 h-2 rounded-full bg-[#7A9BFF] animate-bounce [animation-delay:0s]"></span>
                        <span class="w-2 h-2 rounded-full bg-[#7A9BFF] animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-2 h-2 rounded-full bg-[#7A9BFF] animate-bounce [animation-delay:0.4s]"></span>
                        <span class="ml-1 text-[11px]">{{ activeTestCreator.name }} sedang mengetik...</span>
                    </div>
                </div>

                <!-- Conversation Starters Prompt Buttons -->
                <div class="pt-3 border-t border-[rgba(242,239,234,0.08)]">
                    <p class="text-[11px] text-[#8C8C90] mb-2 font-medium">Kirim Pertanyaan Uji Coba Cepat:</p>
                    <div class="space-y-1.5">
                        <button
                            v-for="(qa, qaIdx) in activeTestCreator.starterQuestions"
                            :key="qaIdx"
                            type="button"
                            @click="sendTestQuestion(qa.q, qa.a)"
                            :disabled="isCreatorTyping"
                            class="w-full text-left p-2 rounded-lg bg-white/[0.04] hover:bg-white/[0.08] border border-white/5 text-xs text-[#F2EFEA] flex items-center justify-between gap-2 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <span class="truncate">💬 "{{ qa.q }}"</span>
                            <Send class="w-3.5 h-3.5 text-[#7A9BFF] shrink-0" />
                        </button>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <Link
                            :href="messages.index.url({ query: { creator: activeTestCreator.id } })"
                            class="px-4 py-2 rounded-xl bg-[#1B44E8] hover:bg-[#1537C2] text-xs font-semibold text-white flex items-center gap-1.5 transition-colors"
                        >
                            <MessageSquare class="w-3.5 h-3.5" />
                            <span>Buka Obrolan Lengkap di Pesan</span>
                        </Link>

                        <button
                            type="button"
                            @click="activeTestCreator = null"
                            class="px-3 py-2 text-xs text-[#8C8C90] hover:text-white cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- CELEBRATION UPGRADE MODAL                                                 -->
        <!-- ========================================================================= -->
        <div
            v-if="showCelebrationModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4 animate-fadeIn"
            @click.self="showCelebrationModal = false"
        >
            <div
                class="relative w-full max-w-md rounded-2xl border p-6 text-center shadow-2xl backdrop-blur-2xl animate-scaleUp overflow-hidden"
                :class="[
                    celebratedTier === 'VIP'
                        ? 'border-[#D4B67A]/50 bg-gradient-to-b from-[#242017] via-[#17181B] to-[#121316]'
                        : 'border-[#1B44E8]/50 bg-gradient-to-b from-[#141b33] via-[#17181B] to-[#121316]'
                ]"
            >
                <!-- Floating Confetti Burst Elements -->
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <span
                        v-for="p in confettiParticles"
                        :key="p.id"
                        class="confetti-dot absolute rounded-sm"
                        :style="{
                            left: `${p.x}%`,
                            top: `${p.y}%`,
                            backgroundColor: p.color,
                            width: `${p.size}px`,
                            height: `${p.size * 0.8}px`,
                            transform: `rotate(${p.rotation}deg)`,
                            animationDelay: `${p.delay}s`,
                        }"
                    ></span>
                </div>

                <div class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 shadow-xl"
                        :class="[
                            celebratedTier === 'VIP'
                                ? 'bg-[#D4B67A]/20 border border-[#D4B67A]/50 text-[#D4B67A]'
                                : 'bg-[#1B44E8]/20 border border-[#1B44E8]/50 text-[#7A9BFF]'
                        ]"
                    >
                        <Crown v-if="celebratedTier === 'VIP'" class="w-7 h-7" />
                        <Sparkles v-else class="w-7 h-7" />
                    </div>

                    <h3 class="text-xl font-bold text-white tracking-tight">
                        Selamat! Kasta {{ celebratedTier }} Berhasil Diaktifkan!
                    </h3>

                    <p class="text-xs text-[#8C8C90] mt-2 leading-relaxed max-w-sm">
                        Lencana akun Anda di dekat avatar profil kini telah resmi berubah menjadi <strong class="text-white">{{ celebratedTier }}</strong>. Semua fitur, hak tayang resolusi tinggi, dan akses obrolan kreator telah aktif secara instan.
                    </p>

                    <!-- Active Badge Preview -->
                    <div class="mt-4 px-4 py-2 rounded-xl bg-white/[0.04] border border-white/10 flex items-center justify-between gap-3 w-full text-xs">
                        <span class="text-[#8C8C90]">Lencana Baru Anda:</span>
                        <span
                            class="px-2.5 py-1 rounded-[6px] text-xs font-bold font-mono tracking-wider flex items-center gap-1.5"
                            :class="
                                celebratedTier === 'VIP'
                                    ? 'bg-[#D4B67A]/25 border border-[#D4B67A]/50 text-[#D4B67A]'
                                    : 'bg-[#1B44E8]/25 border border-[#1B44E8]/50 text-[#7A9BFF]'
                            "
                        >
                            <Crown v-if="celebratedTier === 'VIP'" class="w-3.5 h-3.5" />
                            <Sparkles v-else class="w-3.5 h-3.5" />
                            <span>{{ celebratedTier }}</span>
                        </span>
                    </div>

                    <div class="mt-6 flex flex-col gap-2.5 w-full">
                        <Link
                            :href="dashboard.url()"
                            class="w-full py-2.5 rounded-xl font-semibold text-xs flex items-center justify-center gap-2 shadow-lg transition-transform active:scale-95"
                            :class="[
                                celebratedTier === 'VIP'
                                    ? 'bg-[#D4B67A] text-[#0E0F11] hover:bg-[#c4a468]'
                                    : 'bg-[#1B44E8] text-white hover:bg-[#1537C2]'
                            ]"
                        >
                            <span>Kembali ke Dashboard Utama</span>
                            <ArrowRight class="w-4 h-4" />
                        </Link>

                        <div class="flex items-center gap-2 w-full">
                            <Link
                                :href="messages.index.url()"
                                class="flex-1 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-medium text-white flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <MessageSquare class="w-3.5 h-3.5" />
                                <span>Chat Kreator</span>
                            </Link>

                            <button
                                type="button"
                                @click="showCelebrationModal = false; scrollToSection('section-plans')"
                                class="flex-1 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-medium text-[#8C8C90] hover:text-white transition-colors cursor-pointer"
                            >
                                Lihat Paket Saya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MIDTRANS SANDBOX SIMULATION MODAL (DEV & TESTING FALLBACK)                -->
        <!-- ========================================================================= -->
        <div
            v-if="showSimulationModal && currentMockOrder"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-md p-4 animate-fadeIn"
            @click.self="showSimulationModal = false"
        >
            <div
                class="relative w-full max-w-md rounded-2xl border border-[rgba(242,239,234,0.15)] bg-[#17181B] p-6 shadow-2xl backdrop-blur-2xl animate-scaleUp text-left"
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#1B44E8]/20 border border-[#1B44E8]/40 flex items-center justify-center text-[#7A9BFF]">
                            <CreditCard class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white tracking-tight">Midtrans Payment Gateway</h3>
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.2 rounded">
                                <Sparkles class="w-2.5 h-2.5" />
                                SIMULATOR SANDBOX
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showSimulationModal = false"
                        class="p-1 rounded-lg text-[#8C8C90] hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Order Summary Details -->
                <div class="py-4 space-y-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/5 space-y-2">
                        <div class="flex justify-between items-center text-[#8C8C90]">
                            <span>Nomor Order:</span>
                            <span class="font-mono text-white text-[11px]">{{ currentMockOrder.order_id }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[#8C8C90]">
                            <span>Paket Langganan:</span>
                            <span class="font-semibold text-white">
                                Kasta {{ currentMockOrder.tier.toUpperCase() }} ({{ currentMockOrder.billing_cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }})
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-[#8C8C90]">
                            <span>Durasi Ditambahkan:</span>
                            <span class="text-white">{{ currentMockOrder.billing_cycle === 'yearly' ? '365 Hari' : '30 Hari' }}</span>
                        </div>
                        <div class="pt-2 border-t border-white/5 flex justify-between items-baseline">
                            <span class="text-[#8C8C90] font-medium">Total Pembayaran:</span>
                            <span class="text-base font-bold text-[#F2EFEA]">
                                Rp {{ Number(currentMockOrder.amount).toLocaleString('id-ID') }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-[#1B44E8]/20 bg-[#1B44E8]/10 p-3 text-[11px] text-[#A0B5FF] leading-relaxed flex items-start gap-2">
                        <ShieldCheck class="w-4 h-4 text-[#7A9BFF] shrink-0 mt-0.5" />
                        <div>
                            <strong>Simulasi Pembayaran Terintegrasi:</strong>
                            Mode simulasi ini aktif karena <code class="text-white bg-black/40 px-1 py-0.5 rounded font-mono">MIDTRANS_SERVER_KEY</code> belum diisi di file <code class="text-white bg-black/40 px-1 py-0.5 rounded font-mono">.env</code>. Saat Anda mengisi key resmi, popup Snap Midtrans akan terbuka secara otomatis. Anda dapat menekan tombol di bawah untuk menyimulasikan pembayaran berhasil saat ini.
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-3 border-t border-white/10 flex flex-col sm:flex-row items-center gap-2.5">
                    <button
                        type="button"
                        @click="completeSimulation"
                        :disabled="isSimulatingSettlement"
                        class="w-full sm:flex-1 py-2.5 px-4 rounded-xl bg-[#1B44E8] hover:bg-[#1537C2] text-white text-xs font-semibold flex items-center justify-center gap-2 transition-all cursor-pointer shadow-lg shadow-[#1B44E8]/30 disabled:opacity-50"
                    >
                        <span v-if="isSimulatingSettlement" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <CheckCircle2 v-else class="w-4 h-4 text-emerald-400" />
                        <span>{{ isSimulatingSettlement ? 'Memproses...' : 'Simulasikan Pembayaran Sukses' }}</span>
                    </button>
                    <button
                        type="button"
                        @click="showSimulationModal = false"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-medium text-white transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Feedback Popup -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0 scale-95"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="showToast"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl border border-[#1B44E8]/50 bg-[#17181B] px-4 py-3 shadow-[0_12px_36px_rgba(27,68,232,0.25)] backdrop-blur-xl"
            >
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#1B44E8]/20 text-[#7A9BFF]">
                    <Sparkles class="h-4 w-4" />
                </div>
                <p class="text-xs font-medium text-[#F2EFEA]">
                    {{ toastMessage }}
                </p>
                <button
                    type="button"
                    @click="showToast = false"
                    class="ml-2 text-[#8C8C90] hover:text-white cursor-pointer"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Scroll-triggered reveal animations */
.scroll-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}

.scroll-reveal.is-revealed {
    opacity: 1;
    transform: translateY(0);
}

.delay-100 {
    transition-delay: 120ms;
}

.delay-200 {
    transition-delay: 240ms;
}

.delay-300 {
    transition-delay: 360ms;
}

/* Ambient Ember Particles */
@keyframes floatEmber {
    0% {
        transform: translateY(0) translateX(0);
        opacity: 0.2;
    }
    50% {
        transform: translateY(-80px) translateX(30px);
        opacity: 0.6;
    }
    100% {
        transform: translateY(-160px) translateX(0);
        opacity: 0;
    }
}

.ember-dot {
    position: absolute;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: #7a9bff;
    box-shadow: 0 0 10px #1b44e8;
    animation: floatEmber 6s infinite ease-in-out;
}

/* Audio wave equalizer animation */
@keyframes soundWavePulse {
    0%, 100% {
        transform: scaleY(0.4);
    }
    50% {
        transform: scaleY(1);
    }
}

.animate-soundWave {
    transform-origin: bottom;
    animation: soundWavePulse 0.9s ease-in-out infinite alternate;
}

/* Dropdown entrance animation */
@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-dropdown {
    animation: dropdownFade 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fadeIn {
    animation: fadeIn 0.25s ease-out forwards;
}

@keyframes scaleUp {
    from {
        opacity: 0;
        transform: scale(0.92);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scaleUp {
    animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Confetti Falling Keyframes */
@keyframes confettiFall {
    0% {
        opacity: 1;
        transform: translateY(0) rotate(0deg);
    }
    100% {
        opacity: 0;
        transform: translateY(320px) rotate(720deg);
    }
}

.confetti-dot {
    animation: confettiFall 2.8s cubic-bezier(0.25, 1, 0.5, 1) forwards;
}
</style>
