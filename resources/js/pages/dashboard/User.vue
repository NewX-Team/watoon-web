<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    BellOff,
    Bookmark,
    BookmarkCheck,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Clapperboard,
    Crown,
    Film as FilmIcon,
    Lock,
    LogOut,
    MessageSquare,
    Play,
    Plus,
    Search,
    Send,
    Settings,
    Sparkles,
    Star,
    Undo2,
    UserCheck,
    UserPlus,
    X,
} from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { dashboard, logout } from '@/routes';
import messages from '@/routes/messages';
import { edit } from '@/routes/profile';
import tierRoute from '@/routes/subscription/tier';
import type { User } from '@/types/auth';

type KastaTier = 'FREE' | 'PRO' | 'VIP';

const page = usePage<{ auth: { user: User } }>();
const currentUser = computed(() => page.props.auth?.user);

// 3-Kasta System
const normalizeTier = (val?: string | null): KastaTier => {
    if (!val) return 'FREE';
    const up = val.toUpperCase();
    if (up === 'VIP') return 'VIP';
    if (up === 'PRO') return 'PRO';
    return 'FREE';
};

const getInitialTier = (): KastaTier => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('watoon_user_tier');
        if (stored) return normalizeTier(stored);
    }
    return normalizeTier(currentUser.value?.tier);
};

const currentUserTier = ref<KastaTier>(getInitialTier());
const isUpgradingTier = ref(false);

watch(
    () => currentUser.value?.tier,
    (newTier) => {
        if (newTier) {
            currentUserTier.value = normalizeTier(newTier);
        }
    },
);

const switchUserTier = (newTier: KastaTier) => {
    isUpgradingTier.value = true;
    currentUserTier.value = newTier;
    if (typeof window !== 'undefined') {
        localStorage.setItem('watoon_user_tier', newTier);
    }

    router.post(
        tierRoute.update.url(),
        { tier: newTier.toLowerCase() },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isUpgradingTier.value = false;
                isProModalOpen.value = false;
            },
            onError: () => {
                isUpgradingTier.value = false;
                isProModalOpen.value = false;
            },
        },
    );
};

// Search & active states
const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);
const activeTab = ref<'home' | 'favorite' | 'subscribe'>('home');
const mediaType = ref<'film' | 'novel'>('film');
const isProModalOpen = ref(false);
const activeSpotlightIndex = ref(0);
const isCarouselPaused = ref(false);
const isScrolled = ref(false);
const userMenuOpen = ref(false);
const loadWatchlist = (): number[] => {
    if (typeof window === 'undefined') return [1, 4];
    try {
        const stored = localStorage.getItem('watoon_watchlist');
        return stored ? JSON.parse(stored) : [1, 4];
    } catch {
        return [1, 4];
    }
};

const myWatchlist = ref<number[]>(loadWatchlist()); // default saved items
const showNovelNotice = ref(false);

const AUTOPLAY_INTERVAL = 5000;
let carouselTimer: ReturnType<typeof setInterval> | null = null;
let observer: IntersectionObserver | null = null;

const nextSpotlight = () => {
    activeSpotlightIndex.value =
        (activeSpotlightIndex.value + 1) % spotlights.length;
};

const prevSpotlight = () => {
    activeSpotlightIndex.value =
        (activeSpotlightIndex.value - 1 + spotlights.length) %
        spotlights.length;
};

const goToSpotlight = (index: number) => {
    activeSpotlightIndex.value = index;
    restartCarouselTimer();
};

const startCarouselTimer = () => {
    stopCarouselTimer();
    carouselTimer = setInterval(() => {
        if (!isCarouselPaused.value) {
            nextSpotlight();
        }
    }, AUTOPLAY_INTERVAL);
};

const stopCarouselTimer = () => {
    if (carouselTimer) {
        clearInterval(carouselTimer);
        carouselTimer = null;
    }
};

const restartCarouselTimer = () => {
    stopCarouselTimer();
    startCarouselTimer();
};

const userInitials = computed(() => {
    const name = currentUser.value?.name || 'User';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

// Spotlights data
const spotlights = [
    {
        id: 101,
        title: 'The Quiet Archipelago',
        year: '2024',
        duration: '1h 48m',
        genre: 'Atmospheric Drama · 4K HDR',
        synopsis:
            'Di pesisir utara yang diterpa badai, seorang pengarsip audio merekam gema terakhir dari komunitas nelayan terpencil yang hampir lenyap.',
        image: 'https://lh3.googleusercontent.com/aida/AEtjO1Uf-TV0jxjqj9Jb_iu9txy1sg5YpoTM-WHj6bq7raYvchbiywBMky2dtzxr2d34I84FyoA7cp9w4lOryYb5nxAZBCbKTkhn6Wk_nccBVx_CPbsqzQUKuaR5H5gJIj-R5GVt6NqxwXrboY--n8jNHNiF-2-CGuxe87plA5f2Tq3FpQnwQqn5716T1sz3oxIsG-WRmSKVaJ4nslCjyui06H0O675pQV4GikPjjKfJLrVwvdn9fMIxhis_tx4',
    },
    {
        id: 102,
        title: 'Northbound',
        year: '2024',
        duration: '2h 10m',
        genre: 'Nordic Noir Mystery · 4K HDR',
        synopsis:
            'Penyelidikan mendalam di mercusuar terisolasi membuka jejak ekspedisi kutub utara yang diselimuti rahasia selama setengah abad.',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
    },
    {
        id: 103,
        title: 'The Archivist',
        year: '2023',
        duration: '1h 35m',
        genre: 'Psychological Thriller · 4K HDR',
        synopsis:
            'Di ruang arsip tua yang temaram, penemuan pita rekaman analog tak berlabel mengubah persepsi seorang kurator tentang masa lalu kotanya.',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
    },
    {
        id: 104,
        title: 'Cold Meridian',
        year: '2024',
        duration: '2h 04m',
        genre: 'Arctic Maritime Drama · 4K HDR',
        synopsis:
            'Pelayaran hening sebuah kapal kargo tua menembus labirin fjord beku saat musim dingin ekstrem menyelimuti samudra Atlantik Utara.',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn',
    },
    {
        id: 105,
        title: 'Night Train to Kyoto',
        year: '2024',
        duration: '1h 52m',
        genre: 'Poetic Nocturne · 4K HDR',
        synopsis:
            'Sebuah perjalanan malam melintasi hutan bambu berembun, mempertemukan dua musafir yang mencari kedamaian dalam keheningan gerbong kayu.',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d',
    },
];

const activeSpotlight = computed(() => spotlights[activeSpotlightIndex.value]);

// Continue watching data
const continueWatching = [
    {
        id: 1,
        title: 'Solitude by Design',
        left: '32m left',
        progress: 65,
        year: '2024',
        genre: 'Documentary',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPJZ3HaMzt88VuI8vveBpWUJoKNTWgqhdO9meVjJbOHb6zYz9cYXzl4K44CvvkxCc5gYDMM5m0aoRfIDvhXOUOSlaRTzanr1qwTqDM-am9HkiVvyKQfVQOU2cYu3uaf1c8X8h6uhzDTAifeg2SQfJft6K3IvDn1jRlha5bcJWMKF1hdRiSuqceW-PfM84956wFZS5RMBTVCRAidanh5P0lzHw1w4VAlRV8sOOO1pnWBjy-9ic8e-x3',
    },
    {
        id: 2,
        title: 'Dusk Over Manhattan',
        left: '14m left',
        progress: 85,
        year: '2023',
        genre: 'Noir',
        image: 'https://lh3.googleusercontent.com/aida/AEtjO1VxVGGw4aESkVj48zBpMA4gFCLbUCrpe13mskDfTdoWhN6WtSiVR532gzqKTnwl3EQYwqu33ldGR1Ao1C8jCWM2Q88eMDhzwrrfHwOswNAjOjsn-roS75cxKsvYhDqIMnfd_pj0Q40twJDMfqxB-BKYN92Db8gHtAwERoRXqEetpQcdQcMQlbib8RRQzzDfFt18T_TU6ygmbVjByR0qoIzdqEUcrf0b28G_4OCrBcNMk1GfoFC7ws1TWJc',
    },
    {
        id: 3,
        title: 'Chronicles of Silence',
        left: '45m left',
        progress: 40,
        year: '2024',
        genre: 'Essay',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuARSgovhqArkqjbDk5vJuDMfAAHsEYgOpr7djEoM_gUKIOaYhfKE0rgwGkPUUL63q7xMObe_LMNKWtOV38Wfh5uA_C_mSLX7nkmElqrznApFQnEa6yfqgpvJ_Yr4seLqEQta9mIRPAJ1e16XK3Iv3GiyQgfS3ubRREUa7H9ufsrsVdOBozCnNRsT0SyNZ28Y1YF18PCk1k6KMYgKG-jdN0yQ7a3ZUqTo1Jr68UtAJ5wcpJAiRwAcgkR',
    },
    {
        id: 4,
        title: 'Aura of the Harbor',
        left: '1h 10m left',
        progress: 20,
        year: '2024',
        genre: 'Drama',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA75ZYduJ92CypXxVJaGQU7YQyhYVMC0U-C5la6fAj9-dtD_111KcZKKOaOR6bA4HPtQBTPhKBKz9JqRKikiSToG6SU_b6jsUWEqtIi2ED5XRylAevofgxHusXYJkde0tAD2K07Eq23TWtXQjXSBcvZtsB36R88Kh2DG_YuYutxsGZHcGXHgNlajtpHNmxFR9rChJc8vxtkQP2q65BnQbI7symvAkfaAyepu6G5ug5PH59yFlNHd7Xo',
    },
    {
        id: 5,
        title: 'Shadows in Monochrome',
        left: '8m left',
        progress: 92,
        year: '2024',
        genre: 'Experimental',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGPWBPNprLKpGImSEcCHWCweWLSqZZMg8PFtEVrdG63Jskj_u4ERvSC3HaVIyBmX-tUqXobxp7VDjMVy59rMcdxNRc1QSkfkMW57UloiYmwvtt4ta_ZGH26u9csjQbRxvSbd4H1fh8T8AeaYhrSSez_8jbWgbIUfQJfgPM6WyeZArbaDXT1ef5un5BvR3Z-X_mL2Kt2HCciBAjeg3ELADhvtbQS8HGp-TIcP3nhdJoVWkRuwhOdMfX',
    },
    {
        id: 6,
        title: 'Night Train to Kyoto',
        left: '50m left',
        progress: 30,
        year: '2024',
        genre: 'Drama',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d',
    },
];

// Popular weekly
const popularWeekly = [
    {
        id: 11,
        rank: '01',
        title: 'Northbound',
        year: '2024',
        genre: 'Mystery',
        rating: '8.6',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
    },
    {
        id: 12,
        rank: '02',
        title: 'The Archivist',
        year: '2023',
        genre: 'Thriller',
        rating: '8.9',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
    },
    {
        id: 13,
        rank: '03',
        title: 'Cold Meridian',
        year: '2024',
        genre: 'Drama',
        rating: '8.4',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn',
    },
    {
        id: 14,
        rank: '04',
        title: 'Subterranea',
        year: '2024',
        genre: 'Sci-Fi',
        rating: '8.7',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCK02uLiaIUq9aP6T17yCtg7S-ZWcLHkM8q9HQGuvSFK8HLgGjsFJbkw7EhxlafDPzOlegiUHPa2nlYDyHNJjFWXTiIltPxSBVFqjv589cyHBxYrqJ3dyPovoq7u78pSEEYfseCf1_EqdIiwUDry5C1R-RpEdPjObBQrxj8bVfft55X5VtJHzTGFJtIuV_WRGcmXj2kDwbcYGHTIec57_vGjfd70jNa-vsMODg83VktfxT7hGYErXPM',
    },
    {
        id: 15,
        rank: '05',
        title: 'Acoustic Memory',
        year: '2023',
        genre: 'Essay',
        rating: '8.9',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCMHIUh3zc9II4xww7PeJKUI8NlqPzgER-Ker9xV_9ckFtAE2o5j5n9VQmXLckW-XaDZHhH7JfJvHIspP3xTNNFHud57lk-mCQZGumbVhXP2FmnLvPDzu13M5_tkWMDnqbhWQ2XFdbIGGnfqZQuPKPwmh1A_zN5VpkNpkjqK-pIqIBwrNDInkSMTChEafcl0BsrF9Ju5y8-00k4_acMZRsuTIQ4mFo0B8fS1a13rUYRE_lY9_BzdTgk',
    },
    {
        id: 16,
        rank: '06',
        title: 'The Outer Ridge',
        year: '2024',
        genre: 'Nature',
        rating: '8.5',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDe5cJcqxuNQiSBLHImbEUL9_c8ZUURA41XG-4fby-w7-cd0gaAeylqSyh4amHYiuR0A9-PvRRMJRVkTrw3C_f9phDVXEiZsMS1CgBNNN6uyhOlnE2K25h-qvByd_D_BSY6BLajf3qnnAwZ8s9vXFM-0PfyrJk2cEAwMzklVq_ZrMNnbfGINVUn7PnfI4CBDXjBEGxujeayey4u_5_z6udiULKHNVEs1pP849pHxoRtVM0CLvPq5sY0',
    },
];

// Baru rilis (from creators)
const newReleases = [
    {
        id: 21,
        title: 'The Kiln House',
        year: '2024',
        genre: 'Documentary',
        rating: '8.8',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCPKYPZVwearNOSrhohmQ2dzIIhmNEgrO5sN7m0QMm-oKQNfg9ffDjRssPFrHe6l7Ug-swsA9NMN67Op9NLDLsKZewVxBFXv1UU8zqnA_0LfkZFZPUatk6fPcjMDol2_mU_ms-WJM1TcnC5EmGLpAf2G2I1TRoFaEPAEx4z-fL732NeFt7efM7E9nBJbutFuJaGslLR49WVeaK2nFjkjNgZwa97Y8nHRMvu9vC1jGh6wnEAspK79Q8C',
    },
    {
        id: 22,
        title: 'Frequency 14',
        year: '2024',
        genre: 'Experimental',
        rating: '8.5',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBaX2ewnx-8Awym8fdkWJk1l-mlqx6wv2Y6ZPhXoTrrYuiEFO_6r-AsXOC4RI49ZJItG80-H7Zt4fmwbd5ROvdKgzAg1xEgWvbb0MrODBDh8dHHCmsuigdnYO-RXZBu4ablvDW09hXPz-ZdIhgQ8CUDtp5wYawH6hnO0vsdPOF0DRfq4dHIlrcIpkiKY9lK6uAP7Ilt4RHpJxbb-o15SmC31pfq6U7gYIiucOfxcQuUDpSy3NQvfbTZ',
    },
    {
        id: 23,
        title: 'Tide & Tarmac',
        year: '2024',
        genre: 'Narrative',
        rating: '8.7',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCXvkGNQtkq7yQBlB6LHwmWNahw_8iJbNZr_keJ8eapPi42f_ZFAgiTPCoIJxA9oND8EsxmFOgGKNKdXVoklni89gwHPlajiJngbegnd8j9sXq0kyRnuVHWkv_QjQeWVSmcjXcw9h296EqKo1Y39uPJ_TYFlHb5ZTvbYASr6IthYyYIBenwC0W1Rcx5DZ-qp485iugO0O5kz-yF_NFtvXesyVmjhEULLiRbemgjBxbw5HIiz_xe925H',
    },
    {
        id: 24,
        title: 'The Paper Master',
        year: '2024',
        genre: 'Short Film',
        rating: '9.0',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDx4NC7_GxE6n55258yBMEQj6rQc4B_9rgJ4dx32kUmrHezOAsQAyZ5pMPl-FIR2vJLtrVr5A7Kr36MmnvmKJ_-XWJTgv-Eo_F67x72X8FA3TV2SzA9ju_1UTvaBz4Q_VeKMawvvTdLdbL5RFo2XsxWGlSAaJdsH32vQqs3T9ACnloYjNGbsMCXTZh1lyoxV907oDyxM3hybDlDsl_bAG0MGPt9fAjAoU-jSF4NEehvDNd-9NmJLUfK',
    },
    {
        id: 25,
        title: 'Linear Forms',
        year: '2024',
        genre: 'Essay',
        rating: '8.6',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGPWBPNprLKpGImSEcCHWCweWLSqZZMg8PFtEVrdG63Jskj_u4ERvSC3HaVIyBmX-tUqXobxp7VDjMVy59rMcdxNRc1QSkfkMW57UloiYmwvtt4ta_ZGH26u9csjQbRxvSbd4H1fh8T8AeaYhrSSez_8jbWgbIUfQJfgPM6WyeZArbaDXT1ef5un5BvR3Z-X_mL2Kt2HCciBAjeg3ELADhvtbQS8HGp-TIcP3nhdJoVWkRuwhOdMfX',
    },
    {
        id: 26,
        title: 'Night Train to Kyoto',
        year: '2024',
        genre: 'Drama',
        rating: '8.9',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d',
    },
];

// Explore film collection (18 items)
const exploreFilmsInitial = [
    {
        id: 31,
        title: 'Echoes of the North',
        year: '2024',
        genre: 'Mystery',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
    },
    {
        id: 32,
        title: 'The Cartographer',
        year: '2023',
        genre: 'Thriller',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
    },
    {
        id: 33,
        title: 'Polar Passage',
        year: '2024',
        genre: 'Drama',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn',
    },
    {
        id: 34,
        title: 'Station Zero',
        year: '2024',
        genre: 'Sci-Fi',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCK02uLiaIUq9aP6T17yCtg7S-ZWcLHkM8q9HQGuvSFK8HLgGjsFJbkw7EhxlafDPzOlegiUHPa2nlYDyHNJjFWXTiIltPxSBVFqjv589cyHBxYrqJ3dyPovoq7u78pSEEYfseCf1_EqdIiwUDry5C1R-RpEdPjObBQrxj8bVfft55X5VtJHzTGFJtIuV_WRGcmXj2kDwbcYGHTIec57_vGjfd70jNa-vsMODg83VktfxT7hGYErXPM',
    },
    {
        id: 35,
        title: 'Magnetic Resonance',
        year: '2023',
        genre: 'Essay',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCMHIUh3zc9II4xww7PeJKUI8NlqPzgER-Ker9xV_9ckFtAE2o5j5n9VQmXLckW-XaDZHhH7JfJvHIspP3xTNNFHud57lk-mCQZGumbVhXP2FmnLvPDzu13M5_tkWMDnqbhWQ2XFdbIGGnfqZQuPKPwmh1A_zN5VpkNpkjqK-pIqIBwrNDInkSMTChEafcl0BsrF9Ju5y8-00k4_acMZRsuTIQ4mFo0B8fS1a13rUYRE_lY9_BzdTgk',
    },
    {
        id: 36,
        title: 'Basalt Plain',
        year: '2024',
        genre: 'Nature',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDe5cJcqxuNQiSBLHImbEUL9_c8ZUURA41XG-4fby-w7-cd0gaAeylqSyh4amHYiuR0A9-PvRRMJRVkTrw3C_f9phDVXEiZsMS1CgBNNN6uyhOlnE2K25h-qvByd_D_BSY6BLajf3qnnAwZ8s9vXFM-0PfyrJk2cEAwMzklVq_ZrMNnbfGINVUn7PnfI4CBDXjBEGxujeayey4u_5_z6udiULKHNVEs1pP849pHxoRtVM0CLvPq5sY0',
    },
    {
        id: 37,
        title: 'Fired Earth',
        year: '2023',
        genre: 'Craft',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCPKYPZVwearNOSrhohmQ2dzIIhmNEgrO5sN7m0QMm-oKQNfg9ffDjRssPFrHe6l7Ug-swsA9NMN67Op9NLDLsKZewVxBFXv1UU8zqnA_0LfkZFZPUatk6fPcjMDol2_mU_ms-WJM1TcnC5EmGLpAf2G2I1TRoFaEPAEx4z-fL732NeFt7efM7E9nBJbutFuJaGslLR49WVeaK2nFjkjNgZwa97Y8nHRMvu9vC1jGh6wnEAspK79Q8C',
    },
    {
        id: 38,
        title: 'Open Air Recorders',
        year: '2024',
        genre: 'Experimental',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBaX2ewnx-8Awym8fdkWJk1l-mlqx6wv2Y6ZPhXoTrrYuiEFO_6r-AsXOC4RI49ZJItG80-H7Zt4fmwbd5ROvdKgzAg1xEgWvbb0MrODBDh8dHHCmsuigdnYO-RXZBu4ablvDW09hXPz-ZdIhgQ8CUDtp5wYawH6hnO0vsdPOF0DRfq4dHIlrcIpkiKY9lK6uAP7Ilt4RHpJxbb-o15SmC31pfq6U7gYIiucOfxcQuUDpSy3NQvfbTZ',
    },
    {
        id: 39,
        title: 'Nocturne by Water',
        year: '2024',
        genre: 'Narrative',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCXvkGNQtkq7yQBlB6LHwmWNahw_8iJbNZr_keJ8eapPi42f_ZFAgiTPCoIJxA9oND8EsxmFOgGKNKdXVoklni89gwHPlajiJngbegnd8j9sXq0kyRnuVHWkv_QjQeWVSmcjXcw9h296EqKo1Y39uPJ_TYFlHb5ZTvbYASr6IthYyYIBenwC0W1Rcx5DZ-qp485iugO0O5kz-yF_NFtvXesyVmjhEULLiRbemgjBxbw5HIiz_xe925H',
    },
    {
        id: 40,
        title: 'Folio & Spine',
        year: '2023',
        genre: 'Short Film',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDx4NC7_GxE6n55258yBMEQj6rQc4B_9rgJ4dx32kUmrHezOAsQAyZ5pMPl-FIR2vJLtrVr5A7Kr36MmnvmKJ_-XWJTgv-Eo_F67x72X8FA3TV2SzA9ju_1UTvaBz4Q_VeKMawvvTdLdbL5RFo2XsxWGlSAaJdsH32vQqs3T9ACnloYjNGbsMCXTZh1lyoxV907oDyxM3hybDlDsl_bAG0MGPt9fAjAoU-jSF4NEehvDNd-9NmJLUfK',
    },
    {
        id: 41,
        title: 'Diagonal Geometries',
        year: '2024',
        genre: 'Essay',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGPWBPNprLKpGImSEcCHWCweWLSqZZMg8PFtEVrdG63Jskj_u4ERvSC3HaVIyBmX-tUqXobxp7VDjMVy59rMcdxNRc1QSkfkMW57UloiYmwvtt4ta_ZGH26u9csjQbRxvSbd4H1fh8T8AeaYhrSSez_8jbWgbIUfQJfgPM6WyeZArbaDXT1ef5un5BvR3Z-X_mL2Kt2HCciBAjeg3ELADhvtbQS8HGp-TIcP3nhdJoVWkRuwhOdMfX',
    },
    {
        id: 42,
        title: 'Through the Grove',
        year: '2024',
        genre: 'Drama',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d',
    },
    {
        id: 43,
        title: 'The Cloister',
        year: '2023',
        genre: 'Philosophy',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuARSgovhqArkqjbDk5vJuDMfAAHsEYgOpr7djEoM_gUKIOaYhfKE0rgwGkPUUL63q7xMObe_LMNKWtOV38Wfh5uA_C_mSLX7nkmElqrznApFQnEa6yfqgpvJ_Yr4seLqEQta9mIRPAJ1e16XK3Iv3GiyQgfS3ubRREUa7H9ufsrsVdOBozCnNRsT0SyNZ28Y1YF18PCk1k6KMYgKG-jdN0yQ7a3ZUqTo1Jr68UtAJ5wcpJAiRwAcgkR',
    },
    {
        id: 44,
        title: 'Terminal Sleep',
        year: '2024',
        genre: 'Atmosphere',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA75ZYduJ92CypXxVJaGQU7YQyhYVMC0U-C5la6fAj9-dtD_111KcZKKOaOR6bA4HPtQBTPhKBKz9JqRKikiSToG6SU_b6jsUWEqtIi2ED5XRylAevofgxHusXYJkde0tAD2K07Eq23TWtXQjXSBcvZtsB36R88Kh2DG_YuYutxsGZHcGXHgNlajtpHNmxFR9rChJc8vxtkQP2q65BnQbI7symvAkfaAyepu6G5ug5PH59yFlNHd7Xo',
    },
    {
        id: 45,
        title: 'Glass & Asphalt',
        year: '2023',
        genre: 'Noir',
        image: 'https://lh3.googleusercontent.com/aida/AEtjO1VxVGGw4aESkVj48zBpMA4gFCLbUCrpe13mskDfTdoWhN6WtSiVR532gzqKTnwl3EQYwqu33ldGR1Ao1C8jCWM2Q88eMDhzwrrfHwOswNAjOjsn-roS75cxKsvYhDqIMnfd_pj0Q40twJDMfqxB-BKYN92Db8gHtAwERoRXqEetpQcdQcMQlbib8RRQzzDfFt18T_TU6ygmbVjByR0qoIzdqEUcrf0b28G_4OCrBcNMk1GfoFC7ws1TWJc',
    },
    {
        id: 46,
        title: 'Fjord Horizon',
        year: '2024',
        genre: 'Nordic Drama',
        image: 'https://lh3.googleusercontent.com/aida/AEtjO1Uf-TV0jxjqj9Jb_iu9txy1sg5YpoTM-WHj6bq7raYvchbiywBMky2dtzxr2d34I84FyoA7cp9w4lOryYb5nxAZBCbKTkhn6Wk_nccBVx_CPbsqzQUKuaR5H5gJIj-R5GVt6NqxwXrboY--n8jNHNiF-2-CGuxe87plA5f2Tq3FpQnwQqn5716T1sz3oxIsG-WRmSKVaJ4nslCjyui06H0O675pQV4GikPjjKfJLrVwvdn9fMIxhis_tx4',
    },
    {
        id: 47,
        title: 'Quiet Hours',
        year: '2024',
        genre: 'Study',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPJZ3HaMzt88VuI8vveBpWUJoKNTWgqhdO9meVjJbOHb6zYz9cYXzl4K44CvvkxCc5gYDMM5m0aoRfIDvhXOUOSlaRTzanr1qwTqDM-am9HkiVvyKQfVQOU2cYu3uaf1c8X8h6uhzDTAifeg2SQfJft6K3IvDn1jRlha5bcJWMKF1hdRiSuqceW-PfM84956wFZS5RMBTVCRAidanh5P0lzHw1w4VAlRV8sOOO1pnWBjy-9ic8e-x3',
    },
    {
        id: 48,
        title: 'Signals in the Fog',
        year: '2023',
        genre: 'Mystery',
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
    },
];

const exploreFilms = ref([...exploreFilmsInitial]);
const hasLoadedMore = ref(false);

const setupScrollObserver = () => {
    if (
        typeof window === 'undefined' ||
        typeof IntersectionObserver === 'undefined'
    ) {
        return;
    }

    observer?.disconnect();
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                }
            });
        },
        {
            threshold: 0.02,
            rootMargin: '100px 0px 100px 0px',
        },
    );

    document.querySelectorAll('.scroll-reveal').forEach((el) => {
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight + 150) {
            el.classList.add('is-revealed');
        }
        observer?.observe(el);
    });
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

const loadMoreFilms = () => {
    hasLoadedMore.value = true;
    exploreFilms.value.push(
        ...exploreFilmsInitial.slice(0, 6).map((item, idx) => ({
            ...item,
            id: item.id + 100 + idx,
            title: `${item.title} (Part II)`,
        })),
    );
    nextTick(() => {
        setupScrollObserver();
    });
};

// Filtered lists based on search
const matchesSearch = (title: string, genre: string) => {
    if (!searchQuery.value.trim()) return true;
    const q = searchQuery.value.toLowerCase();
    return title.toLowerCase().includes(q) || genre.toLowerCase().includes(q);
};

const filteredContinueWatching = computed(() =>
    continueWatching.filter((item) => matchesSearch(item.title, item.genre)),
);

const filteredPopularWeekly = computed(() =>
    popularWeekly.filter((item) => matchesSearch(item.title, item.genre)),
);

const filteredNewReleases = computed(() =>
    newReleases.filter((item) => matchesSearch(item.title, item.genre)),
);

const filteredExploreFilms = computed(() =>
    exploreFilms.value.filter((item) => matchesSearch(item.title, item.genre)),
);

// Toggle watchlist with persistent storage
const toggleWatchlist = (id: number) => {
    if (myWatchlist.value.includes(id)) {
        myWatchlist.value = myWatchlist.value.filter((x) => x !== id);
    } else {
        myWatchlist.value.push(id);
    }
    if (typeof window !== 'undefined') {
        localStorage.setItem(
            'watoon_watchlist',
            JSON.stringify(myWatchlist.value),
        );
    }
    nextTick(() => {
        setupScrollObserver();
    });
};

const isBookmarked = (id: number) => myWatchlist.value.includes(id);

// --- FAVORITE LIBRARY DATA & METHODS (Stitch FlixRead Screen Specification) ---
interface FavoriteItem {
    id: string;
    type: 'film' | 'episode';
    title: string;
    year?: string;
    genre?: string;
    episodeCode?: string;
    parentFilmTitle?: string;
    parentFilmHref?: string;
    parentFilmId?: number;
    season?: string;
    savedAgo: string;
    savedTimestamp: number;
    image: string;
    href: string;
}

const initialFavoriteItems: FavoriteItem[] = [
    {
        id: 'fav-1',
        type: 'film',
        title: 'Cakrawala Senja',
        year: '2024',
        genre: 'Thriller',
        savedAgo: '2 days ago',
        savedTimestamp: Date.now() - 2 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCisIX_CUQtZJJPzqFoARcq5FwIYr4Pueh8AI4tv3AUv_Le-kp4EJ1MozQH4HSRRxR4NiqaOWdBcroweRgqQzkNTi34okE3kuN204RuB6ZO_b12MiTy3UqLge5XRTZgVmbUmf02h1tImz7Vxu7MxYwBtvbGCFXyBeZJoesIru17YdbthVRv8uLBtULAlmSgKKPQZwpEQX1Z8j3B7uAcCczhS82nxEqj59PggH5exhhww9I4E_LNZeou',
        href: '/films/1',
    },
    {
        id: 'fav-2',
        type: 'episode',
        title: 'Episode Four: Silent Echo',
        episodeCode: 'EP 04',
        parentFilmTitle: 'Cakrawala Senja',
        parentFilmHref: '/films/1',
        parentFilmId: 1,
        season: 'S1',
        savedAgo: '3 days ago',
        savedTimestamp: Date.now() - 3 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBFNfIQIqsqzD-ge4D0gE14HN_NGtsWwtic0kCT0sGRcv2O0EaAdV6g6ac7qsad5pajcKlTcHIvRvImJ8NjQRGjNTlDVOvDCe7VpWHNQNF-NVXLjGH-mkU8RVwPRe_yGby0edmELDyADLegikPO3091GAgcGQ5b2w6oYlQE3Hl1mCpRKAQ1rQdbfU6GVoAPbPanVlOhdtlawEkWVl0Q77YLENLezXLRdicgP5WK2crGvj1VMiEzfE7o',
        href: '/films/1/episodes/4',
    },
    {
        id: 'fav-3',
        type: 'film',
        title: 'Rumah di Ujung Jalan',
        year: '2023',
        genre: 'Mystery',
        savedAgo: '4 days ago',
        savedTimestamp: Date.now() - 4 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDT3LLgaEweSzHlUA9CmTAPqKL-j77eMMCQQtkbwHjEODS12-m-QRWYxVdPN4ixc9TnJMvfbttC7XK55D0ZvX4PX3bh7lKGBwAFBJQFBusW6zj0DX3Fa9gcpdLGJElW-lBpOtCnZBCtyjljyLMjdnB9yLsAWr9107GraWhysyriNJVXXbDjkQAxcrUNMsXJfEoM6nCV4_914t3OPiC5CkuKlTwy2dwTj1i9fHDoQfjlHOxuGevd2VsL',
        href: '/films/2',
    },
    {
        id: 'fav-4',
        type: 'film',
        title: 'Detik Terakhir',
        year: '2024',
        genre: 'Noir Drama',
        savedAgo: '5 days ago',
        savedTimestamp: Date.now() - 5 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB4HlNVWrw9hM3vVZ5zc64gJiMrUgygCiYdcMZgFpGEHX-hfMGTFIuE_CScA3qzo1ichkueFwV0LA8w7TMcmKyp1RbHwG2pCPNCZaU1LWhoJvH0mf32XeuldPUTvmQqVXbweU6oWBYg3Yl_3aNTxflNpchRsfzEsAtPsO5DR5ySCGml7gPBB7kTfaO8eOf8uaJEobOiOBBC7f_0m_2SAcy9s09ivJoaP8GHGcpDVTLzR6gZQ3RSR8hE',
        href: '/films/3',
    },
    {
        id: 'fav-5',
        type: 'episode',
        title: 'Episode Two: The Attic',
        episodeCode: 'EP 02',
        parentFilmTitle: 'Rumah di Ujung Jalan',
        parentFilmHref: '/films/2',
        parentFilmId: 2,
        season: 'S1',
        savedAgo: '5 days ago',
        savedTimestamp: Date.now() - 5.1 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDtSfMQfD0O1oGY_CdRgwgxtODRI711GjphVbJB1xBqLExmb66MXXtj-nGOVahpSff9h4fOjByqY1hUrbyaVwqSXoVWjTHlY6lwCVksnP902JMEXz-r3kU5yCLDn7U5zLU8cbwKL1nTDz3hInd_Sk0BZtzd7bQO1iMXHKtRWXdHkmOGhpIBn3OZZQxW_Dk0qUjhAkluhWm_APWQ4d9OsDy209YqkIAxyNSfk8wb5mKR6Pr4fi6fGh1T',
        href: '/films/2/episodes/2',
    },
    {
        id: 'fav-6',
        type: 'film',
        title: 'Bayang Khatulistiwa',
        year: '2023',
        genre: 'Documentary',
        savedAgo: '1 week ago',
        savedTimestamp: Date.now() - 7 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAL7Jf73izLaTSo-ZX7QwxtIwpxKkJNTMcru5qRS_XEN76mvULXYM8kYyldALfrkOozaVwQpHqLQfvDkcO24oLykkuQqSWmE3If1tFeXt_XZc5JeobR0g9gnTs2ZakL_bmg15fp19nxHtiZJQhpZ9kOT-b5IEOVHfYBdDsXE_L9S-9uY_C9i88PiUaQnI8mlFKazQMQSiJwqchs6FNN18VAQiKavUNfcE65-u32MX5gjILgqunW6lUl',
        href: '/films/4',
    },
    {
        id: 'fav-7',
        type: 'episode',
        title: 'Episode Eight: Shoreline',
        episodeCode: 'EP 08',
        parentFilmTitle: 'The Quiet Archipelago',
        parentFilmHref: '/films/101',
        parentFilmId: 101,
        season: 'S1',
        savedAgo: '1 week ago',
        savedTimestamp: Date.now() - 7.2 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDPm9Lq6VHLLEQH4Gfl_S7ETffzobbyh_UUEaWbhXeHGcH-nM1SkGzjLR8_YfbFYfYBsVdL-eFeieMOKzEADr7dppGh8T4OBaxA8WcGHJtcLtnU3ISht_hE-dvNCqJgZV0fBz7e6-f9Xbe-d38BZ1pvpG63iAvKogbRxlTUX1q86om8taSYJfgMqhiw-fuqRmvytR0Ziy90PBPTX0xuHlvShhqMyc6gRnmFgwDAVO9Fw-2l7cDfXNni',
        href: '/films/101/episodes/8',
    },
    {
        id: 'fav-8',
        type: 'film',
        title: 'Senyap di Lembah',
        year: '2022',
        genre: 'Independent',
        savedAgo: '2 weeks ago',
        savedTimestamp: Date.now() - 14 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuACd19ATzy9qhzDIdcYTK4Y4sp-XP_Bjcu5Tv3FME33_poXERqAlTDhD_AsiBigVDHjc1C-n8J7A7Dyvui9kCnBBAFKckU7DOmgYyj9Pjdn3Zc8N4h-pa7Q4kxqjzDmGiRQ1SsMAfMrfiVbW4JQDLz4Xu0BG89eXYE6MO7fr0VqNkt3_HWZPOMltDh1YKfBb62G6-RdPuROh6lgylvWnOX5h1OAncVvLGttu5X6b_Ys5gWHUOgHI0pY',
        href: '/films/5',
    },
    {
        id: 'fav-9',
        type: 'film',
        title: 'Arus Tenang',
        year: '2024',
        genre: 'Essay Film',
        savedAgo: '2 weeks ago',
        savedTimestamp: Date.now() - 14.2 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAdS283RkA3rnGREMtdALQtBGDs2LpL_1U4vLQfg_4MJZ-xiYwMPiSicGut8g5_9JvOIRNhLPCao_H5uppIQ3K8aAgJ2nGu3YwGcwcTQ-gnXXI4IqVQUUXhYCgKQYUHc1tQ5244-5UgjpmgbKd1tGoYC2841coKEr0XVf5g2MnGfqdi_Zv7ASH8aoUTGM6rwX0cQIoPQMMqO1r7a8pMlXlc874KTLUm41BJCL_51vgLBPsBAuxYjKW7',
        href: '/films/6',
    },
    {
        id: 'fav-10',
        type: 'episode',
        title: 'Episode Seven: Red Wire',
        episodeCode: 'EP 07',
        parentFilmTitle: 'Detik Terakhir',
        parentFilmHref: '/films/3',
        parentFilmId: 3,
        season: 'S1',
        savedAgo: '2 weeks ago',
        savedTimestamp: Date.now() - 14.5 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBtwo5JnKVrw56Ff_Wr644a6wPYg6QPX3IcbmnSCV3U61Sy4eF5kZ4DJmrWtzKqGUrrbUpZpi7bboQT0Yol8kHNwfWOudoUqGKxrtpwyGIEvOMffCvWXzSD6DFYIowhh57dH8B41R2CHB3QqkRQo0C-cYf6o07kIwWT-DsMUh6sIBzx7B3vS3iZ-2bl6l-3aPXdfXgHGw0IeoYbKAxqK1imQBS4X47m6TAoSMj_KH3IY0QIvngRvdiG',
        href: '/films/3/episodes/7',
    },
    {
        id: 'fav-11',
        type: 'film',
        title: 'Labirin Waktu',
        year: '2023',
        genre: 'Sci-Fi',
        savedAgo: '3 weeks ago',
        savedTimestamp: Date.now() - 21 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCrkeUjgWncPj71qNMQMcIZNpN625VtwXnkROP757VhD19xbWB4_bJ4tkAWirEgVhypNAesRGcZIFAKOBjRQj9zpny0vnu10B_0pD_KfLu7A02a6DD-hCjuq5JvIyB_7r_xVMTf7wJNY_H8SiUK0dYj8I1ltyxO4y3Jgier9LLWMzcTPk0C6b5vVBZma1nBPPq3vmqqUJOEvSYP7BcSQtdU73SsjIHcU5rPdgXewirQLMrpepcFmYyS',
        href: '/films/7',
    },
    {
        id: 'fav-12',
        type: 'film',
        title: 'Nada Musim Gugur',
        year: '2021',
        genre: 'Music Doc',
        savedAgo: '3 weeks ago',
        savedTimestamp: Date.now() - 21.3 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDpIoELfjHlTKY9-EyLgf-NFkE-8bJmBfKPmKfqx8ClkaHyKXGIqD7kCyZzTAxkpYR7RgoICDMjWpCW2uav6feqkYISrOO8KGMi_8dVaYHjEG2AgB8KrhoR6gGTP9uoWGsSVhpfXuQxSEuEQ5ztQxpXTe5oG46KviI8MN44oKyaQFZNghhuFeW6L_gIl5HSjFaK6kCUG9Pn1CSV1_6E_EgF8-kgfz37HIl6zrRI4drspFzd9uBD5DxR',
        href: '/films/8',
    },
    {
        id: 'fav-13',
        type: 'film',
        title: 'Hening Malam',
        year: '2024',
        genre: 'Psychological',
        savedAgo: '1 month ago',
        savedTimestamp: Date.now() - 30 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA1QJK-tky6_GZLJmFRo4WdF99vPaZfG0E2uP_6lojCBCsGD6KCdqyqStkb17sRyafkqRlU2-6535Zx54Dfv0YsFidNz4EvvZnhVW8mN8kvifwXNwNj798IVSuGT1C3jLJTnhWj_3exJZpZe-bhCrRoNsSLOVbnmvL1RhLYoQP3oogQWnRvK7fnfkbDk2n_lPh_jsyscb48jHfl_pC0h_sJCyQr93V-9xwJA_sOQuVwmI-7Zrhpr9br',
        href: '/films/9',
    },
    {
        id: 'fav-14',
        type: 'episode',
        title: 'Episode Three: Downstream',
        episodeCode: 'EP 03',
        parentFilmTitle: 'Arus Tenang',
        parentFilmHref: '/films/6',
        parentFilmId: 6,
        season: 'S1',
        savedAgo: '1 month ago',
        savedTimestamp: Date.now() - 30.5 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD9o5Re2uHEtQtJmPOhkM6xzP2iRzKOftAkEi9KLLOKfqrfEXDFfdsCsK6tkvww7zMvKsJuU0n__ydGjoSZ_cQoLvZhsK9ayOXyeqRpEcwYT-l6rq-QdPWmFf_KHenQY0n0OcOGVJDvqpQC-SlY0jVFd-z-Ezo_i3Tt94x-OFT96TC3z-wpgnOSn0iG3WPZ8ew3UAaNruWAbHs1-2S8D-w1HdT8KQJPJzUUJ8kWvOqDH-c_OdiqHBo7',
        href: '/films/6/episodes/3',
    },
    {
        id: 'fav-15',
        type: 'film',
        title: 'Pesisir Utara',
        year: '2023',
        genre: 'Maritime Drama',
        savedAgo: '1 month ago',
        savedTimestamp: Date.now() - 31 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBFSPU60eskL0QC1PaxnDBgEIoreqk4_TZt7oxOxeAf7uGAKqjqNrRkkgFabKSoIWsVL-5izsa6NGSkEKVJKfGqeQlAZ0t-eu8dRjMdOC4qCUT7QSdH17Z4kcQBISrHY18VKhm3iYM6be9rsZNW1mXx4HpQqWOH49aqjGFt6ZJPmBxKWEuCIURmHwzR6IiDonh6ZjA0qg7bjSC7ZMfwR-TN47LSc2y0dy0rWi0zr2_NDRq7jy0QjK0O',
        href: '/films/10',
    },
    {
        id: 'fav-16',
        type: 'film',
        title: 'Garis Batas',
        year: '2022',
        genre: 'Political Thriller',
        savedAgo: '2 months ago',
        savedTimestamp: Date.now() - 60 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDSHKXQ90kKNe1o4Zsb9MDCqGwGXx0ZtOu5NoP5JtWTUIj0mMcE2rjKbjUr0qamRn-iwYgI-1ZKCtmlpHMCiKclPxzEj3-gcDyFVqHaX35E9PIKAmYOUxJCAJxsqv2T4iD91kYd9NLNj-5B-T2refVr-ncqj3dGkrBz2vC13iB2TAEk2dQuIIVh3Cl9w0mOVWQTunDk5lCUfnC6-678oUDcHp02mvkmy9UksUrQTbpYFgT3lZ47GdB-',
        href: '/films/11',
    },
    {
        id: 'fav-17',
        type: 'episode',
        title: 'Episode Five: The Cavern',
        episodeCode: 'EP 05',
        parentFilmTitle: 'Senyap di Lembah',
        parentFilmHref: '/films/5',
        parentFilmId: 5,
        season: 'S1',
        savedAgo: '2 months ago',
        savedTimestamp: Date.now() - 60.3 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA7BPqdImlT0-ttgUp4__YCHDOOyydY2fsN5hD83Z4hDtTPjSVPX_IUo5RkE5UN9TG0p2Fy2geRXb1pgURo4OkfIuzkVRdXgkNQiwlpK939i-QJz85omZCCRHPYrqYwPRHPPPA52ypHiIf2_S8WoYkOl98KEje8d_J0ILQ_o7e1kEeOCcw-EIHxpz20AMTU1H3Yi2gkjKe8oynbADAZQ2uzNEmdkA9jxOshO1WHrl4OPtwqflXAZzLE',
        href: '/films/5/episodes/5',
    },
    {
        id: 'fav-18',
        type: 'film',
        title: 'Menanti Kabut',
        year: '2024',
        genre: 'Nordic Noir',
        savedAgo: '2 months ago',
        savedTimestamp: Date.now() - 61 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC5iljxoKTlud4D3RivUyFxbl0MlT9o3rud4XIMMifIKNd7pHSmElslqBBt7nPnAd-zI0HF2MrdFkxpVT-_R9pgNtUEsoYt0RiZLyTjPDglUotREkoABmlA668BdOvYEN24969TH1EzgBAxUCRSlWiG2goaPsNJXzaZGJIdh58YaP9JjwAu1xDsU_9Ls2zI99g0cKj84cxWqA4R9lioL9AfV6_d78SS3KgQ_cwF1OcuHG_7T4wf744N',
        href: '/films/12',
    },
];

const additionalFavoriteItems: FavoriteItem[] = [
    {
        id: 'fav-19',
        type: 'film',
        title: 'Solitude by Design',
        year: '2024',
        genre: 'Nordic Architecture',
        savedAgo: '2 months ago',
        savedTimestamp: Date.now() - 65 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAL7Jf73izLaTSo-ZX7QwxtIwpxKkJNTMcru5qRS_XEN76mvULXYM8kYyldALfrkOozaVwQpHqLQfvDkcO24oLykkuQqSWmE3If1tFeXt_XZc5JeobR0g9gnTs2ZakL_bmg15fp19nxHtiZJQhpZ9kOT-b5IEOVHfYBdDsXE_L9S-9uY_C9i88PiUaQnI8mlFKazQMQSiJwqchs6FNN18VAQiKavUNfcE65-u32MX5gjILgqunW6lUl',
        href: '/films/101',
    },
    {
        id: 'fav-20',
        type: 'episode',
        title: 'Episode One: The Departure',
        episodeCode: 'EP 01',
        parentFilmTitle: 'Solitude by Design',
        parentFilmHref: '/films/101',
        parentFilmId: 101,
        season: 'S1',
        savedAgo: '3 months ago',
        savedTimestamp: Date.now() - 75 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBFNfIQIqsqzD-ge4D0gE14HN_NGtsWwtic0kCT0sGRcv2O0EaAdV6g6ac7qsad5pajcKlTcHIvRvImJ8NjQRGjNTlDVOvDCe7VpWHNQNF-NVXLjGH-mkU8RVwPRe_yGby0edmELDyADLegikPO3091GAgcGQ5b2w6oYlQE3Hl1mCpRKAQ1rQdbfU6GVoAPbPanVlOhdtlawEkWVl0Q77YLENLezXLRdicgP5WK2crGvj1VMiEzfE7o',
        href: '/films/101/episodes/1',
    },
    {
        id: 'fav-21',
        type: 'film',
        title: 'Lembah Siluet',
        year: '2023',
        genre: 'Drama Puitis',
        savedAgo: '3 months ago',
        savedTimestamp: Date.now() - 80 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuACd19ATzy9qhzDIdcYTK4Y4sp-XP_Bjcu5Tv3FME33_poXERqAlTDhD_AsiBigVDHjc1C-n8J7A7Dyvui9kCnBBAFKckU7DOmgYyj9Pjdn3Zc8N4h-pa7Q4kxqjzDmGiRQ1SsMAfMrfiVbW4JQDLz4Xu0BG89eXYE6MO7fr0VqNkt3_HWZPOMltDh1YKfBb62G6-RdPuROh6lgylvWnOX5h1OAncVvLGttu5X6b_Ys5gWHUOgHI0pY',
        href: '/films/102',
    },
    {
        id: 'fav-22',
        type: 'episode',
        title: 'Episode Six: Shadows in Mist',
        episodeCode: 'EP 06',
        parentFilmTitle: 'Lembah Siluet',
        parentFilmHref: '/films/102',
        parentFilmId: 102,
        season: 'S1',
        savedAgo: '3 months ago',
        savedTimestamp: Date.now() - 85 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDtSfMQfD0O1oGY_CdRgwgxtODRI711GjphVbJB1xBqLExmb66MXXtj-nGOVahpSff9h4fOjByqY1hUrbyaVwqSXoVWjTHlY6lwCVksnP902JMEXz-r3kU5yCLDn7U5zLU8cbwKL1nTDz3hInd_Sk0BZtzd7bQO1iMXHKtRWXdHkmOGhpIBn3OZZQxW_Dk0qUjhAkluhWm_APWQ4d9OsDy209YqkIAxyNSfk8wb5mKR6Pr4fi6fGh1T',
        href: '/films/102/episodes/6',
    },
    {
        id: 'fav-23',
        type: 'film',
        title: 'Arsip Batavia',
        year: '2024',
        genre: 'Historical Doc',
        savedAgo: '4 months ago',
        savedTimestamp: Date.now() - 110 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAdS283RkA3rnGREMtdALQtBGDs2LpL_1U4vLQfg_4MJZ-xiYwMPiSicGut8g5_9JvOIRNhLPCao_H5uppIQ3K8aAgJ2nGu3YwGcwcTQ-gnXXI4IqVQUUXhYCgKQYUHc1tQ5244-5UgjpmgbKd1tGoYC2841coKEr0XVf5g2MnGfqdi_Zv7ASH8aoUTGM6rwX0cQIoPQMMqO1r7a8pMlXlc874KTLUm41BJCL_51vgLBPsBAuxYjKW7',
        href: '/films/103',
    },
    {
        id: 'fav-24',
        type: 'film',
        title: 'Mata Air Sunyi',
        year: '2023',
        genre: 'Art-House',
        savedAgo: '5 months ago',
        savedTimestamp: Date.now() - 140 * 86400000,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDpIoELfjHlTKY9-EyLgf-NFkE-8bJmBfKPmKfqx8ClkaHyKXGIqD7kCyZzTAxkpYR7RgoICDMjWpCW2uav6feqkYISrOO8KGMi_8dVaYHjEG2AgB8KrhoR6gGTP9uoWGsSVhpfXuQxSEuEQ5ztQxpXTe5oG46KviI8MN44oKyaQFZNghhuFeW6L_gIl5HSjFaK6kCUG9Pn1CSV1_6E_EgF8-kgfz37HIl6zrRI4drspFzd9uBD5DxR',
        href: '/films/104',
    },
];

const FAVORITES_STORAGE_KEY = 'watoon_user_favorites';
const loadFavoriteItems = (): FavoriteItem[] => {
    if (typeof window === 'undefined') return [...initialFavoriteItems];
    try {
        const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
                return parsed;
            }
        }
        return [...initialFavoriteItems];
    } catch {
        return [...initialFavoriteItems];
    }
};

const favoriteItems = ref<FavoriteItem[]>(loadFavoriteItems());
const favoriteFilter = ref<'all' | 'film' | 'episode'>('all');
const favoriteSort = ref<'recent' | 'title' | 'year'>('recent');
const isSortDropdownOpen = ref(false);
const sortDropdownRef = ref<HTMLElement | null>(null);
const hasLoadedMoreFavorites = ref(false);

const toastMessage = ref('');
const showToast = ref(false);
const lastRemovedItem = ref<{ item: FavoriteItem; index: number } | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | null = null;

const saveFavoritesToStorage = () => {
    if (typeof window !== 'undefined') {
        try {
            localStorage.setItem(
                FAVORITES_STORAGE_KEY,
                JSON.stringify(favoriteItems.value),
            );
        } catch {
            // ignore
        }
    }
};

const currentSortLabel = computed(() => {
    switch (favoriteSort.value) {
        case 'title':
            return 'Title (A - Z)';
        case 'year':
            return 'Release Year';
        case 'recent':
        default:
            return 'Recently added';
    }
});

const displayedFavoriteItems = computed(() => {
    let items = favoriteItems.value;

    if (favoriteFilter.value === 'film') {
        items = items.filter((item) => item.type === 'film');
    } else if (favoriteFilter.value === 'episode') {
        items = items.filter((item) => item.type === 'episode');
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter((item) => {
            return (
                item.title.toLowerCase().includes(q) ||
                (item.genre && item.genre.toLowerCase().includes(q)) ||
                (item.parentFilmTitle &&
                    item.parentFilmTitle.toLowerCase().includes(q))
            );
        });
    }

    const sorted = [...items];
    if (favoriteSort.value === 'title') {
        sorted.sort((a, b) => a.title.localeCompare(b.title));
    } else if (favoriteSort.value === 'year') {
        sorted.sort((a, b) => {
            const yearA = parseInt(a.year || '0') || 0;
            const yearB = parseInt(b.year || '0') || 0;
            return yearB - yearA;
        });
    } else {
        sorted.sort((a, b) => b.savedTimestamp - a.savedTimestamp);
    }

    return sorted;
});

const selectSort = (sortKey: 'recent' | 'title' | 'year') => {
    favoriteSort.value = sortKey;
    isSortDropdownOpen.value = false;
};

const triggerToast = (msg: string) => {
    toastMessage.value = msg;
    showToast.value = true;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        showToast.value = false;
    }, 4500);
};

const removeFavoriteItem = (item: FavoriteItem) => {
    const idx = favoriteItems.value.findIndex((x) => x.id === item.id);
    if (idx !== -1) {
        lastRemovedItem.value = { item, index: idx };
        favoriteItems.value.splice(idx, 1);
        saveFavoritesToStorage();
        triggerToast(`"${item.title}" dihapus dari favorit`);
    }
};

const undoRemoveFavorite = () => {
    if (lastRemovedItem.value) {
        const { item, index } = lastRemovedItem.value;
        favoriteItems.value.splice(
            Math.min(index, favoriteItems.value.length),
            0,
            item,
        );
        saveFavoritesToStorage();
        lastRemovedItem.value = null;
        showToast.value = false;
        nextTick(() => {
            setupScrollObserver();
        });
    }
};

const loadMoreFavorites = () => {
    hasLoadedMoreFavorites.value = true;
    favoriteItems.value.push(...additionalFavoriteItems);
    saveFavoritesToStorage();
    nextTick(() => {
        setupScrollObserver();
    });
};

const handleClickOutside = (e: MouseEvent) => {
    if (
        sortDropdownRef.value &&
        !sortDropdownRef.value.contains(e.target as Node)
    ) {
        isSortDropdownOpen.value = false;
    }
    if (
        subscribeSortDropdownRef.value &&
        !subscribeSortDropdownRef.value.contains(e.target as Node)
    ) {
        isSubscribeSortDropdownOpen.value = false;
    }
};

// --- SUBSCRIBE CREATORS & FEED DATA & METHODS (Stitch FlixRead Screen Specification) ---
interface Creator {
    id: string;
    name: string;
    handle: string;
    subscribersCount: string;
    subscribersNumeric: number;
    initials: string;
    bio: string;
    tier: 'VIP' | 'PRO' | 'FREE';
    isVerified: boolean;
    notificationsEnabled: boolean;
    isFollowing: boolean;
    latestContent?: {
        isNew: boolean;
        title: string;
        timeAgo: string;
        timestamp: number;
        image: string;
        filmId?: number;
    };
}

interface CreatorFeedItem {
    id: string;
    filmTitle: string;
    timeAgo: string;
    duration: string;
    image: string;
    filmId: number;
    creator: {
        name: string;
        initials: string;
        tier: 'VIP' | 'PRO' | 'FREE';
    };
}

const initialCreators: Creator[] = [
    {
        id: 'creator-1',
        name: 'Rendra Wijaya',
        handle: '@rendrawijaya',
        subscribersCount: '124K subscribers',
        subscribersNumeric: 124000,
        initials: 'RW',
        bio: 'Cinematic storyteller based in Bandung.',
        tier: 'VIP',
        isVerified: true,
        notificationsEnabled: true,
        isFollowing: true,
        latestContent: {
            isNew: true,
            title: 'Cakrawala Senja',
            timeAgo: '2h ago',
            timestamp: Date.now() - 2 * 3600000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCWDPykiUkJAUfIWkglxSzyVmu4UTX0ORjTAgOcLwcsNuKIOa9YIyZOsUjTPEOwZIcVLa9yskIJUxl_N0icDS4LjehAPh7MRsOO7EOoTIBbM2OzLSv_ezEMGQJlaj6IYWX-iQCO5RhYMaPKRVITqZ4FnItiLzVT9xfQLweC43Y_ilEafb9q0w78SJdjMdMnbzfkQLioeL4f4dFpP9C7PHTlE4SffgAh8AqZj0Jef4LAI6ZJWekagd4d',
            filmId: 1,
        },
    },
    {
        id: 'creator-2',
        name: 'Alya Kusuma',
        handle: '@alyakusuma',
        subscribersCount: '88K subscribers',
        subscribersNumeric: 88000,
        initials: 'AK',
        bio: 'Short films & documentaries.',
        tier: 'PRO',
        isVerified: false,
        notificationsEnabled: true,
        isFollowing: true,
        latestContent: {
            isNew: false,
            title: 'Rumah di Ujung Jalan',
            timeAgo: '1d ago',
            timestamp: Date.now() - 24 * 3600000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCfYfb4Ode0ngX8EhHz1uzySu6ddpETbPoMCKL9czYMYizZ2PZ8zk5i0VGeTZh3AjFWc6i77Sf43-9pM-PjwGIlCr_10yY4jRkXSBHOWQvXi1EKsjVwdImbDLUNfffUzzyZqO1OZXNIKJ3jjXIi872oLqstW8sVv-k0Znw0wmaglQ1d9E3P3GAHRx0F3GyFjXIS4L1Qts5biXBDJiMcjml3FJ1XmcPe29_hrjMe4La9hr-JvaNzSSl6',
            filmId: 2,
        },
    },
    {
        id: 'creator-3',
        name: 'Bagas Prasetyo',
        handle: '@bagasfilm',
        subscribersCount: '210K subscribers',
        subscribersNumeric: 210000,
        initials: 'BP',
        bio: 'Genre-bending thrillers.',
        tier: 'VIP',
        isVerified: true,
        notificationsEnabled: false,
        isFollowing: true,
        latestContent: {
            isNew: false,
            title: 'Detik Terakhir',
            timeAgo: '3d ago',
            timestamp: Date.now() - 3 * 86400000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAe5Kxu8OJpJl7kYc0BHqQaYgtET6HnsvhuEpII2ozyXMF5IsvOhdMciPWJxbBhAhOgDigMc-hxJGIWyE4nUJ2ydgdMPOY9atFlTGj9T2ouBHq3jBlt_pkwB4THe9It3H1Yc0iBvyNqJ8AqUlz7Ik-6chwiYbXbxJq42UkVwcFdMqlhmGPLyCwkdAuh-O5aRN3XlvdigGlyauLTHLanv6_QSkXGXUOh7u1dG8L1bTr8P3F3VBmz1HE2',
            filmId: 3,
        },
    },
    {
        id: 'creator-4',
        name: 'Sinta Maharani',
        handle: '@sintamaharani',
        subscribersCount: '45K subscribers',
        subscribersNumeric: 45000,
        initials: 'SM',
        bio: 'Women-led dramas.',
        tier: 'PRO',
        isVerified: false,
        notificationsEnabled: true,
        isFollowing: true,
        latestContent: {
            isNew: false,
            title: 'Pulang',
            timeAgo: '1w ago',
            timestamp: Date.now() - 7 * 86400000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDoRQ3WsOXyy3jrlHPeno9_vVuVKjA7sSbjpoW-p0o-thfQxIfSNB0xBelUXMLKkZnrinsLct4M3avXjAWCsR2KPwqGh4DYnbau5PhtSwvwTeKr4Ia-fwvxK9F-uRKWt8WKNHgM6qulQVV-8NOHMtWDf39RwOrzHtj-1VgWyPg8H76VuXbaFKYCE2H_VaEIoLzWbe2ncv18vCdCVBYPW1MKShgml8c92_wLvRi_LB4wP-zsReax1Igo',
            filmId: 4,
        },
    },
    {
        id: 'creator-5',
        name: 'Dimas Aditya',
        handle: '@dimasaditya',
        subscribersCount: '12K subscribers',
        subscribersNumeric: 12000,
        initials: 'DA',
        bio: 'Emerging filmmaker. New films weekly.',
        tier: 'FREE',
        isVerified: false,
        notificationsEnabled: true,
        isFollowing: true,
        latestContent: {
            isNew: false,
            title: 'Senja di Kota Tua',
            timeAgo: '2w ago',
            timestamp: Date.now() - 14 * 86400000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB93VtM5yCFoG-4vYpg4jEHEKonZOOTboK6dFUe6nlorM1eAX_mDkLgOh3MScIWfWurjHI9P7Y1vgbpBFR4uj4_Vw4UUMXg89iMTQD2WsGXg73dbhf3zwE9nWZV-fSik1y6Gia9fg-7WUjNTkoZV3lwu4j0xa2E7jlepI9xWGzUXuwDJvx_H_wn73v7QtvsohNVEOZJ0li0dibgOfFe_OaBpSvO0UERol6W233pQOFJrnWBlh7oYSfv',
            filmId: 5,
        },
    },
    {
        id: 'creator-6',
        name: 'Nadia Rahma',
        handle: '@nadiarahma',
        subscribersCount: '96K subscribers',
        subscribersNumeric: 96000,
        initials: 'NR',
        bio: 'Experimental & poetic cinema.',
        tier: 'VIP',
        isVerified: true,
        notificationsEnabled: false,
        isFollowing: true,
    },
];

const additionalCreators: Creator[] = [
    {
        id: 'creator-7',
        name: 'Farhan Alatas',
        handle: '@farhanalatas',
        subscribersCount: '72K subscribers',
        subscribersNumeric: 72000,
        initials: 'FA',
        bio: 'Archival footage and historical documentaries.',
        tier: 'PRO',
        isVerified: true,
        notificationsEnabled: true,
        isFollowing: true,
        latestContent: {
            isNew: false,
            title: 'Arsip Batavia',
            timeAgo: '3w ago',
            timestamp: Date.now() - 21 * 86400000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAdS283RkA3rnGREMtdALQtBGDs2LpL_1U4vLQfg_4MJZ-xiYwMPiSicGut8g5_9JvOIRNhLPCao_H5uppIQ3K8aAgJ2nGu3YwGcwcTQ-gnXXI4IqVQUUXhYCgKQYUHc1tQ5244-5UgjpmgbKd1tGoYC2841coKEr0XVf5g2MnGfqdi_Zv7ASH8aoUTGM6rwX0cQIoPQMMqO1r7a8pMlXlc874KTLUm41BJCL_51vgLBPsBAuxYjKW7',
            filmId: 103,
        },
    },
    {
        id: 'creator-8',
        name: 'Maya Safitri',
        handle: '@mayasafitri',
        subscribersCount: '150K subscribers',
        subscribersNumeric: 150000,
        initials: 'MS',
        bio: 'Nordic atmospheric cinema and environmental soundscapes.',
        tier: 'VIP',
        isVerified: true,
        notificationsEnabled: true,
        isFollowing: true,
        latestContent: {
            isNew: true,
            title: 'The Quiet Archipelago',
            timeAgo: '4d ago',
            timestamp: Date.now() - 4 * 86400000,
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAL7Jf73izLaTSo-ZX7QwxtIwpxKkJNTMcru5qRS_XEN76mvULXYM8kYyldALfrkOozaVwQpHqLQfvDkcO24oLykkuQqSWmE3If1tFeXt_XZc5JeobR0g9gnTs2ZakL_bmg15fp19nxHtiZJQhpZ9kOT-b5IEOVHfYBdDsXE_L9S-9uY_C9i88PiUaQnI8mlFKazQMQSiJwqchs6FNN18VAQiKavUNfcE65-u32MX5gjILgqunW6lUl',
            filmId: 101,
        },
    },
];

const initialFeedItems: CreatorFeedItem[] = [
    {
        id: 'feed-1',
        filmTitle: 'Cakrawala Senja',
        timeAgo: '2h ago',
        duration: '42m',
        filmId: 1,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuARlPHgRnz7kTZdwGI5aRkE1Cdvw6ucoParg5gsk1go7PYySqlP25QetiAkeCA47qtD9Mk4WvaklTmXrnkTRpu1LGVLLaRNVIIHzqK0Wxx_ZvXoiVdGfgMsDqy-PM_I4YIsilDXlogXSS8OY0xN-y-Xg3tQQTNtlrWMioxOgrvZxYtr74BR7sFa9vWxoHqRky9Xb-NeEGAXUZwKT60tkfX8A6KlqevHMo3KYzHYDzxlv3yPk59bEOmr',
        creator: {
            name: 'Rendra Wijaya',
            initials: 'RW',
            tier: 'VIP',
        },
    },
    {
        id: 'feed-2',
        filmTitle: 'Rumah di Ujung Jalan',
        timeAgo: '1d ago',
        duration: '28m',
        filmId: 2,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCfYfb4Ode0ngX8EhHz1uzySu6ddpETbPoMCKL9czYMYizZ2PZ8zk5i0VGeTZh3AjFWc6i77Sf43-9pM-PjwGIlCr_10yY4jRkXSBHOWQvXi1EKsjVwdImbDLUNfffUzzyZqO1OZXNIKJ3jjXIi872oLqstW8sVv-k0Znw0wmaglQ1d9E3P3GAHRx0F3GyFjXIS4L1Qts5biXBDJiMcjml3FJ1XmcPe29_hrjMe4La9hr-JvaNzSSl6',
        creator: {
            name: 'Alya Kusuma',
            initials: 'AK',
            tier: 'PRO',
        },
    },
    {
        id: 'feed-3',
        filmTitle: 'Detik Terakhir',
        timeAgo: '3d ago',
        duration: '1h 14m',
        filmId: 3,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA9HIDtDZpIKJXaBBxtdQBBqXYeLem_kLlATKV9qWMeWblFV5sEms8ZWr_0NrkfZ02byxMAK4lg32BegIB3M88uuTCpOkI2bhjLlmrf9-Xe8t1dtp_upqsZvFDwCkd3rbFhFfei0OsaoT_zt_0q8kEZzCNfS8Oobc3MWLjf4gyzvJs7YuqjqmhrKwlgZQ5Mc_DlwHWF7zBGCkWXqSED9A3_4y8qQVoaKuzCeve2KjI7-ZN0LGRZOhAX',
        creator: {
            name: 'Bagas Prasetyo',
            initials: 'BP',
            tier: 'VIP',
        },
    },
    {
        id: 'feed-4',
        filmTitle: 'Pulang',
        timeAgo: '1w ago',
        duration: '35m',
        filmId: 4,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCAZ9FPEwzGqe1r_w39atPCmBofYw5IhPL5ZMqddXz8PpWAOiBRYDQ3tW3iiBaZxfHhFrVS2KA08kQReNKT1jr_McetZyx7auEB9DCh0PPNIf2_vDHu2ClBk6nqbgOlRgWFzizPchncTAhzEJygQmbH6bacyxi_6Z0w2PerZRMZuLF5jOiZczoHlQqMfA5pZqVCL6EnpU1VV-B6EfziVAdXlAUB2BnMn0H0tYHRpinjqhDH_9fHYFur',
        creator: {
            name: 'Sinta Maharani',
            initials: 'SM',
            tier: 'PRO',
        },
    },
    {
        id: 'feed-5',
        filmTitle: 'Senja di Kota Tua',
        timeAgo: '2w ago',
        duration: '18m',
        filmId: 5,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBk0bQqv-KjKNo0732JNObRzWcXlFLCz2cb1aqskOpGFBZrkFQulBPsgYbN2_VvPMau-3TuSzC_R3oErp3XAg9NaGYOAxy35tberWoUNJns_8RzuA76T2lA_2bqcmog9eLLnPeV1rjlxOpvrV-3uZ4mW3E5fzIgCGhxY7B32CwU3ts9TZkue-G9QzhqvwO7B5Gcl_ixh9CDly4N3yQPEk_N7bX2g2F1BeAjLPCLrnPpFN_9yd0VOkMF',
        creator: {
            name: 'Dimas Aditya',
            initials: 'DA',
            tier: 'FREE',
        },
    },
    {
        id: 'feed-6',
        filmTitle: 'Bayang-Bayang Kota',
        timeAgo: '3w ago',
        duration: '54m',
        filmId: 6,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBU0g0UmmYi-JLhKQlnU8PaAg0BD39tSn3ViVaGHkenf5ieUbh8JNkPl_9L7hU-KH5A6MN4sEZkb-jVrteW6VxWtU5WuwXweYfbygKGqKCu38Zn6RowvxxCzsz_Zf2X0fD58fENRVegPQinx-eZcrUOfKS-Q_iDLXRQslffJ69PDID94lCSiKr_yMX-5COsY-77FsDW224gv2grK6RHlwO9Kvj5SOugb2kxZkNJX21DfvkknVOiBTJo',
        creator: {
            name: 'Rendra Wijaya',
            initials: 'RW',
            tier: 'VIP',
        },
    },
    {
        id: 'feed-7',
        filmTitle: 'Gema di Balik Layar',
        timeAgo: '1mo ago',
        duration: '1h 02m',
        filmId: 7,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAEj2Y-poBf63nRygmNzc0mcewsnZ4zKFbPR646vybUwTFFNcGgMV2BOZ5NunRM0izogPyh0zlvta1pnfBf1ogxPpJpaNGFzeynlh-nH6YmqdL32hmslBHSI4CRwP2NZaQj3gEvt_jdbRNYgFrmiikZD9cBiCvYy9jyIV9iWK5aG01P1DY5PIvfWI1a7eh9hsPLIuQ8zEeWnb12FaFQeoVz4gxHBikaa8laH3snLwx265L16TZCFROu',
        creator: {
            name: 'Alya Kusuma',
            initials: 'AK',
            tier: 'PRO',
        },
    },
    {
        id: 'feed-8',
        filmTitle: 'Lembah Kabut',
        timeAgo: '1mo ago',
        duration: '48m',
        filmId: 8,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDtUiNrLqaJFVOaD18FANEJHlYDquDPMdkAZwRHpwA4ODx8fm67Gq9GYEvFP5lfVjs2xLR81yxeM34u-2jrPG_Ptw5jsK0rrY2MzCTbDtDgBkGuqPTgQj55uICZderK2ehhhv76aD5kc4vQAdyngAgIBgDvzH9JYOFTFgz3lqCrcnafHoMhY1SaRmBUzPRj-af9grzaqbIthLluH0y1Fq2s4Xyn1Jy04IqQlVwsXoE4O5XNqYV5ZRYm',
        creator: {
            name: 'Bagas Prasetyo',
            initials: 'BP',
            tier: 'VIP',
        },
    },
];

const additionalFeedItems: CreatorFeedItem[] = [
    {
        id: 'feed-9',
        filmTitle: 'The Quiet Archipelago',
        timeAgo: '1mo ago',
        duration: '1h 48m',
        filmId: 101,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAL7Jf73izLaTSo-ZX7QwxtIwpxKkJNTMcru5qRS_XEN76mvULXYM8kYyldALfrkOozaVwQpHqLQfvDkcO24oLykkuQqSWmE3If1tFeXt_XZc5JeobR0g9gnTs2ZakL_bmg15fp19nxHtiZJQhpZ9kOT-b5IEOVHfYBdDsXE_L9S-9uY_C9i88PiUaQnI8mlFKazQMQSiJwqchs6FNN18VAQiKavUNfcE65-u32MX5gjILgqunW6lUl',
        creator: {
            name: 'Maya Safitri',
            initials: 'MS',
            tier: 'VIP',
        },
    },
    {
        id: 'feed-10',
        filmTitle: 'Northbound',
        timeAgo: '1mo ago',
        duration: '2h 10m',
        filmId: 102,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
        creator: {
            name: 'Bagas Prasetyo',
            initials: 'BP',
            tier: 'VIP',
        },
    },
    {
        id: 'feed-11',
        filmTitle: 'Arsip Batavia',
        timeAgo: '2mo ago',
        duration: '1h 15m',
        filmId: 103,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAdS283RkA3rnGREMtdALQtBGDs2LpL_1U4vLQfg_4MJZ-xiYwMPiSicGut8g5_9JvOIRNhLPCao_H5uppIQ3K8aAgJ2nGu3YwGcwcTQ-gnXXI4IqVQUUXhYCgKQYUHc1tQ5244-5UgjpmgbKd1tGoYC2841coKEr0XVf5g2MnGfqdi_Zv7ASH8aoUTGM6rwX0cQIoPQMMqO1r7a8pMlXlc874KTLUm41BJCL_51vgLBPsBAuxYjKW7',
        creator: {
            name: 'Farhan Alatas',
            initials: 'FA',
            tier: 'PRO',
        },
    },
    {
        id: 'feed-12',
        filmTitle: 'The Archivist',
        timeAgo: '2mo ago',
        duration: '1h 35m',
        filmId: 103,
        image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
        creator: {
            name: 'Rendra Wijaya',
            initials: 'RW',
            tier: 'VIP',
        },
    },
];

const CREATORS_STORAGE_KEY = 'watoon_user_creators';
const loadCreators = (): Creator[] => {
    if (typeof window === 'undefined') return [...initialCreators];
    try {
        const stored = localStorage.getItem(CREATORS_STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
                return parsed;
            }
        }
        return [...initialCreators];
    } catch {
        return [...initialCreators];
    }
};

const creators = ref<Creator[]>(loadCreators());
const feedItems = ref<CreatorFeedItem[]>([...initialFeedItems]);
const subscribeFilter = ref<'all' | 'new' | 'muted'>('all');
const subscribeSort = ref<'updated' | 'popular' | 'alpha'>('updated');
const isSubscribeSortDropdownOpen = ref(false);
const subscribeSortDropdownRef = ref<HTMLElement | null>(null);
const hasLoadedMoreCreators = ref(false);
const hasLoadedMoreFeed = ref(false);

const activeMessageCreator = ref<Creator | null>(null);
const messageText = ref('');
const lastUnfollowedCreator = ref<Creator | null>(null);

const saveCreatorsToStorage = () => {
    if (typeof window !== 'undefined') {
        try {
            localStorage.setItem(
                CREATORS_STORAGE_KEY,
                JSON.stringify(creators.value),
            );
        } catch {
            // ignore
        }
    }
};

const currentSubscribeSortLabel = computed(() => {
    switch (subscribeSort.value) {
        case 'popular':
            return 'Most popular';
        case 'alpha':
            return 'Alphabetical (A - Z)';
        case 'updated':
        default:
            return 'Recently updated';
    }
});

const displayedCreators = computed(() => {
    let list = creators.value.filter((c) => c.isFollowing);

    if (subscribeFilter.value === 'new') {
        list = list.filter((c) => c.latestContent?.isNew);
    } else if (subscribeFilter.value === 'muted') {
        list = list.filter((c) => !c.notificationsEnabled);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter((c) => {
            return (
                c.name.toLowerCase().includes(q) ||
                c.handle.toLowerCase().includes(q) ||
                c.bio.toLowerCase().includes(q) ||
                (c.latestContent?.title &&
                    c.latestContent.title.toLowerCase().includes(q))
            );
        });
    }

    const sorted = [...list];
    if (subscribeSort.value === 'popular') {
        sorted.sort((a, b) => b.subscribersNumeric - a.subscribersNumeric);
    } else if (subscribeSort.value === 'alpha') {
        sorted.sort((a, b) => a.name.localeCompare(b.name));
    } else {
        // 'updated'
        sorted.sort((a, b) => {
            const timeA = a.latestContent?.timestamp || 0;
            const timeB = b.latestContent?.timestamp || 0;
            return timeB - timeA;
        });
    }

    return sorted;
});

const displayedFeedItems = computed(() => {
    let list = feedItems.value;
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter((item) => {
            return (
                item.filmTitle.toLowerCase().includes(q) ||
                item.creator.name.toLowerCase().includes(q)
            );
        });
    }
    return list;
});

const selectSubscribeSort = (sortKey: 'updated' | 'popular' | 'alpha') => {
    subscribeSort.value = sortKey;
    isSubscribeSortDropdownOpen.value = false;
};

const toggleNotifications = (creator: Creator) => {
    creator.notificationsEnabled = !creator.notificationsEnabled;
    saveCreatorsToStorage();
    if (creator.notificationsEnabled) {
        triggerToast(`Notifikasi untuk ${creator.name} diaktifkan`);
    } else {
        triggerToast(`Notifikasi untuk ${creator.name} dibisukan`);
    }
};

const toggleFollow = (creator: Creator) => {
    creator.isFollowing = !creator.isFollowing;
    saveCreatorsToStorage();
    if (!creator.isFollowing) {
        lastUnfollowedCreator.value = creator;
        triggerToast(`Berhenti mengikuti ${creator.name}`);
    } else {
        lastUnfollowedCreator.value = null;
        triggerToast(`Mengikuti ${creator.name}`);
    }
};

const undoUnfollow = () => {
    if (lastUnfollowedCreator.value) {
        lastUnfollowedCreator.value.isFollowing = true;
        saveCreatorsToStorage();
        triggerToast(`Mengikuti kembali ${lastUnfollowedCreator.value.name}`);
        lastUnfollowedCreator.value = null;
        showToast.value = false;
        nextTick(() => {
            setupScrollObserver();
        });
    }
};

const openDirectMessage = (creator: Creator) => {
    activeMessageCreator.value = creator;
    messageText.value = '';
};

const sendDirectMessage = () => {
    if (!messageText.value.trim() || !activeMessageCreator.value) return;
    const name = activeMessageCreator.value.name;
    activeMessageCreator.value = null;
    messageText.value = '';
    triggerToast(`Pesan berhasil dikirim ke ${name}`);
};

const loadMoreCreators = () => {
    hasLoadedMoreCreators.value = true;
    creators.value.push(...additionalCreators);
    saveCreatorsToStorage();
    nextTick(() => {
        setupScrollObserver();
    });
};

const loadMoreFeed = () => {
    hasLoadedMoreFeed.value = true;
    feedItems.value.push(...additionalFeedItems);
    nextTick(() => {
        setupScrollObserver();
    });
};

// Switch tab smoothly without blank screen
const switchTab = (tab: 'home' | 'favorite' | 'subscribe') => {
    activeTab.value = tab;
    searchQuery.value = '';
    window.scrollTo({ top: 0, behavior: 'instant' });
    nextTick(() => {
        setupScrollObserver();
    });
};

// React to tab changes to sync carousel, URL and scroll observer
watch(activeTab, (newTab) => {
    if (newTab === 'home') {
        restartCarouselTimer();
    } else {
        stopCarouselTimer();
    }

    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        if (newTab === 'favorite') {
            url.searchParams.set('tab', 'favorite');
        } else if (newTab === 'subscribe') {
            url.searchParams.set('tab', 'subscribe');
        } else {
            url.searchParams.delete('tab');
            if (url.hash === '#watchlist') {
                url.hash = '';
            }
        }
        window.history.replaceState({}, '', url.toString());
    }

    nextTick(() => {
        setupScrollObserver();
    });
});

watch(searchQuery, () => {
    nextTick(() => {
        setupScrollObserver();
    });
});

// Switch media type
const selectMediaType = (type: 'film' | 'novel') => {
    mediaType.value = type;
    if (type === 'novel') {
        showNovelNotice.value = true;
    } else {
        showNovelNotice.value = false;
    }
};

// Keyboard listener for Cmd+K / Ctrl+K
const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
    if (e.key === 'Escape') {
        userMenuOpen.value = false;
        isProModalOpen.value = false;
        isSortDropdownOpen.value = false;
        isSubscribeSortDropdownOpen.value = false;
        activeMessageCreator.value = null;
    }
};

const checkUrlTab = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam === 'favorite' || window.location.hash === '#watchlist') {
        activeTab.value = 'favorite';
    } else if (tabParam === 'subscribe') {
        activeTab.value = 'subscribe';
    } else if (tabParam === 'home' || !params.has('tab')) {
        activeTab.value = 'home';
    }
    const qParam = params.get('q') || params.get('search');
    if (qParam) {
        searchQuery.value = qParam;
    }
    nextTick(() => {
        setupScrollObserver();
    });
};

onMounted(() => {
    checkUrlTab();
    window.addEventListener('popstate', checkUrlTab);
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('click', handleClickOutside);
    startCarouselTimer();
    nextTick(() => {
        setupScrollObserver();
    });
});

onUnmounted(() => {
    window.removeEventListener('popstate', checkUrlTab);
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('scroll', handleScroll);
    document.removeEventListener('click', handleClickOutside);
    if (toastTimer) clearTimeout(toastTimer);
    stopCarouselTimer();
    observer?.disconnect();
});
</script>

<template>
    <div
        class="min-h-screen bg-[#0E0F11] font-sans text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white flex flex-col"
    >
        <Head title="Watoon — Pure Cinema & Literature" />

        <!-- [1] FLOATING DETACHED NAVBAR -->
        <header
            class="fixed inset-x-0 z-50 mx-auto max-w-[1240px] px-4 transition-all duration-300 md:px-8"
            :class="isScrolled ? 'top-3' : 'top-5'"
        >
            <nav
                class="flex items-center justify-between rounded-[14px] border px-6 backdrop-blur-md transition-all duration-300"
                :class="
                    isScrolled
                        ? 'h-[58px] border-[rgba(242,239,234,0.14)] bg-[rgba(14,15,17,0.92)] shadow-[0_8px_32px_rgba(0,0,0,0.5)]'
                        : 'h-16 border-[rgba(242,239,234,0.08)] bg-[rgba(14,15,17,0.78)] shadow-[0_1px_2px_rgba(0,0,0,0.25)]'
                "
            >
                <!-- LEFT ZONE -->
                <div class="flex items-center gap-7">
                    <!-- Brand Wordmark with Monoline Film Frame Mark -->
                    <Link
                        :href="dashboard.url()"
                        @click.prevent="switchTab('home')"
                        class="group flex items-center gap-2.5 cursor-pointer"
                    >
                        <div
                            class="flex h-5 w-5 items-center justify-center rounded-[3px] border border-[#F2EFEA] p-0.5 transition-colors group-hover:border-[#1B44E8]"
                        >
                            <div
                                class="flex h-full w-full items-center justify-center border border-dashed border-[#F2EFEA]/60"
                            >
                                <span
                                    class="h-1 w-1 rounded-full bg-[#F2EFEA]"
                                ></span>
                            </div>
                        </div>
                        <span
                            class="text-[17px] font-semibold tracking-[-0.03em] text-[#F2EFEA]"
                        >
                            WATOON
                        </span>
                    </Link>

                    <!-- Navigation Links -->
                    <div
                        class="hidden items-center gap-7 text-[13px] font-medium md:flex"
                    >
                        <button
                            type="button"
                            @click="switchTab('home')"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] transition-colors"
                            :class="
                                activeTab === 'home'
                                    ? 'text-[#F2EFEA]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            Home
                            <span
                                v-if="activeTab === 'home'"
                                class="absolute right-0 -bottom-[6px] left-0 h-[2px] bg-[#1B44E8]"
                            ></span>
                        </button>

                        <button
                            type="button"
                            @click="switchTab('favorite')"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] transition-colors"
                            :class="
                                activeTab === 'favorite'
                                    ? 'text-[#F2EFEA]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            Favorite
                            <span
                                v-if="activeTab === 'favorite'"
                                class="absolute right-0 -bottom-[6px] left-0 h-[2px] bg-[#1B44E8]"
                            ></span>
                        </button>

                        <button
                            type="button"
                            @click="switchTab('subscribe')"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] transition-colors"
                            :class="
                                activeTab === 'subscribe'
                                    ? 'text-[#F2EFEA]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            Subscribe
                            <span
                                v-if="activeTab === 'subscribe'"
                                class="absolute right-0 -bottom-[6px] left-0 h-[2px] bg-[#1B44E8]"
                            ></span>
                        </button>

                        <!-- Search Bar with ⌘K -->
                        <div
                            class="flex h-[36px] w-[210px] cursor-text items-center gap-2 rounded-[10px] border border-[rgba(242,239,234,0.10)] bg-[rgba(242,239,234,0.05)] px-2.5 transition-all focus-within:!border-[#1B44E8] focus-within:ring-2 focus-within:ring-[rgba(27,68,232,0.14)] hover:border-[rgba(242,239,234,0.18)] hover:bg-[rgba(242,239,234,0.07)]"
                        >
                            <Search
                                class="h-4 w-4 shrink-0 text-[#8C8C90]"
                                :size="16"
                            />
                            <input
                                ref="searchInputRef"
                                v-model="searchQuery"
                                class="w-full border-0 bg-transparent p-0 text-[13px] font-normal text-[#F2EFEA] placeholder-[#8C8C90] focus:ring-0 focus:outline-none"
                                :placeholder="
                                    activeTab === 'subscribe'
                                        ? 'Search creators or films…'
                                        : 'Search films…'
                                "
                                type="text"
                            />
                            <kbd
                                class="shrink-0 text-[11px] font-medium text-[#6A6A6E] select-none"
                            >
                                ⌘K
                            </kbd>
                        </div>
                    </div>
                </div>

                <!-- RIGHT ZONE -->
                <div class="flex items-center">
                    <!-- Segmented Film/Novel Toggle -->
                    <div
                        class="mr-3 flex h-[32px] w-[140px] items-center rounded-[10px] bg-[rgba(242,239,234,0.06)] p-[2px] select-none"
                    >
                        <button
                            type="button"
                            @click="selectMediaType('film')"
                            class="flex h-full flex-1 cursor-pointer items-center justify-center rounded-[8px] text-[13px] font-medium transition-all"
                            :class="
                                mediaType === 'film'
                                    ? 'bg-[#2A2B30] text-[#F2EFEA]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            Film
                        </button>
                        <button
                            type="button"
                            @click="selectMediaType('novel')"
                            class="flex h-full flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-[8px] text-[13px] font-medium transition-all"
                            :class="
                                mediaType === 'novel'
                                    ? 'bg-[#2A2B30] text-[#F2EFEA]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA]'
                            "
                        >
                            <span>Novel</span>
                            <span
                                class="h-1 w-1 rounded-full bg-[#1B44E8]"
                            ></span>
                        </button>
                    </div>

                    <!-- Kasta Tier Badge -->
                    <button
                        type="button"
                        @click="isProModalOpen = true"
                        class="mr-4 flex h-[26px] cursor-pointer items-center justify-center rounded-[8px] px-[10px] text-[11px] font-semibold tracking-[0.14em] uppercase transition-all select-none"
                        :class="[
                            currentUserTier === 'VIP'
                                ? 'border border-[#D4B67A]/50 bg-[#D4B67A]/15 text-[#D4B67A] hover:bg-[#D4B67A]/25 shadow-[0_0_12px_rgba(212,182,122,0.15)]'
                                : currentUserTier === 'PRO'
                                ? 'border border-[rgba(27,68,232,0.35)] bg-[rgba(27,68,232,0.14)] text-[#7A9BFF] hover:bg-[rgba(27,68,232,0.22)] shadow-[0_0_12px_rgba(27,68,232,0.15)]'
                                : 'border border-[rgba(242,239,234,0.15)] bg-[rgba(242,239,234,0.06)] text-[#A0A0A5] hover:text-[#F2EFEA] hover:border-[rgba(242,239,234,0.25)]'
                        ]"
                        :title="`Status Kasta: ${currentUserTier} (Klik untuk kelola)`"
                    >
                        {{ currentUserTier }}
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
                            class="absolute right-0 z-50 mt-3 w-60 rounded-xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] p-2 shadow-2xl backdrop-blur-xl"
                        >
                            <div
                                class="border-b border-[rgba(242,239,234,0.08)] px-3 py-2.5"
                            >
                                <p
                                    class="truncate text-sm font-medium text-white"
                                >
                                    {{ currentUser?.name }}
                                </p>
                                <p class="truncate text-xs text-[#8C8C90]">
                                    {{ currentUser?.email }}
                                </p>
                                <div
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-[rgba(27,68,232,0.4)] bg-[rgba(27,68,232,0.12)] px-2 py-0.5 text-[10px] font-medium text-[#7A9BFF]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#1B44E8]"
                                    ></span>
                                    <span>User / Penonton</span>
                                </div>
                            </div>

                            <div class="space-y-0.5 py-1">
                                <Link
                                    :href="edit.url()"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06]"
                                >
                                    <Settings
                                        class="h-3.5 w-3.5 text-[#8C8C90]"
                                    />
                                    <span>Pengaturan Akun</span>
                                </Link>


                                <button
                                    type="button"
                                    @click="
                                        isProModalOpen = true;
                                        userMenuOpen = false;
                                    "
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06]"
                                >
                                    <Crown class="h-3.5 w-3.5 text-[#7A9BFF]" />
                                    <span>Langganan Pro</span>
                                </button>
                            </div>

                            <div
                                class="border-t border-[rgba(242,239,234,0.08)] pt-1"
                            >
                                <Link
                                    :href="logout.url()"
                                    method="post"
                                    as="button"
                                    class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-red-400 transition-colors hover:bg-red-500/10"
                                >
                                    <LogOut class="h-3.5 w-3.5" />
                                    <span>Keluar (Sign Out)</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- NOVEL NOTICE BANNER (if Novel toggled) -->
        <div
            v-if="showNovelNotice"
            class="fixed inset-x-0 top-24 z-40 mx-auto max-w-[800px] px-4"
        >
            <div
                class="flex items-center justify-between rounded-xl border border-[rgba(27,68,232,0.4)] bg-[#17181B]/95 p-3 text-xs text-[#F2EFEA] shadow-lg backdrop-blur-md"
            >
                <div class="flex items-center gap-2.5">
                    <span
                        class="rounded-md bg-[#1B44E8]/20 px-2 py-0.5 font-semibold text-[#7A9BFF]"
                    >
                        NOVEL ARCHIVE
                    </span>
                    <span class="text-[#8C8C90]">
                        Edisi sastra bersambung segera hadir. Saat ini
                        menampilkan film sinematik pilihan.
                    </span>
                </div>
                <button
                    type="button"
                    @click="showNovelNotice = false"
                    class="p-1 text-[#8C8C90] hover:text-white"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <main class="flex-grow pt-28 pb-20">
            <div class="mx-auto max-w-[1280px] px-6 md:px-8">
                <!-- ==================== HOME TAB ==================== -->
                <template v-if="activeTab === 'home'">
                    <!-- ACTIVE SEARCH FILTER CHIP -->
                    <div
                        v-if="searchQuery"
                        class="mb-6 flex items-center justify-between rounded-xl border border-[rgba(242,239,234,0.1)] bg-[#17181B] px-5 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-[#8C8C90]"
                                >Filter Pencarian:</span
                            >
                            <span
                                class="rounded-md bg-white/10 px-2 py-0.5 text-xs text-[#F2EFEA]"
                            >
                                "{{ searchQuery }}"
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="searchQuery = ''"
                            class="cursor-pointer text-xs text-[#8C8C90] underline hover:text-[#F2EFEA]"
                        >
                            Reset Filter
                        </button>
                    </div>

                    <!-- [2] HERO FEATURE (Spotlight Auto-carousel 5s) -->
                    <section
                        v-if="!searchQuery"
                        class="scroll-reveal group relative mb-16 flex min-h-[520px] items-center overflow-hidden rounded-[16px] border border-[rgba(242,239,234,0.10)] bg-[#17181B] transition-all md:mb-20"
                        @mouseenter="isCarouselPaused = true"
                        @mouseleave="isCarouselPaused = false"
                    >
                    <!-- Hero Background Stills with Crossfade -->
                    <div class="absolute inset-0 z-0 overflow-hidden">
                        <div
                            v-for="(spot, idx) in spotlights"
                            :key="spot.id"
                            class="absolute inset-0 transition-all duration-1000 ease-in-out"
                            :class="
                                activeSpotlightIndex === idx
                                    ? 'pointer-events-auto scale-100 opacity-100'
                                    : 'pointer-events-none scale-105 opacity-0'
                            "
                        >
                            <img
                                class="h-full w-full object-cover object-center"
                                :src="spot.image"
                                :alt="spot.title"
                            />
                        </div>
                        <!-- Ink Gradient Overlay -->
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-[#0E0F11]/95 via-[#0E0F11]/75 to-[#0E0F11]/25"
                        ></div>
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-[#0E0F11] via-transparent to-transparent opacity-80"
                        ></div>
                    </div>

                    <!-- Navigation Arrows on Hover -->
                    <button
                        type="button"
                        @click="
                            prevSpotlight();
                            restartCarouselTimer();
                        "
                        class="absolute top-1/2 left-4 z-20 hidden h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-[#0E0F11]/60 text-[#F2EFEA] opacity-0 backdrop-blur-sm transition-all duration-200 group-hover:opacity-100 hover:scale-105 hover:bg-[#0E0F11]/90 md:flex"
                        title="Film Sebelumnya"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        @click="
                            nextSpotlight();
                            restartCarouselTimer();
                        "
                        class="absolute top-1/2 right-4 z-20 hidden h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-[#0E0F11]/60 text-[#F2EFEA] opacity-0 backdrop-blur-sm transition-all duration-200 group-hover:opacity-100 hover:scale-105 hover:bg-[#0E0F11]/90 md:flex"
                        title="Film Selanjutnya"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>

                    <!-- Left Content Area with Transition -->
                    <div class="relative z-10 max-w-[580px] p-8 md:p-14">
                        <Transition name="spotlight-slide" mode="out-in">
                            <div :key="activeSpotlightIndex">
                                <!-- Eyebrow -->
                                <div
                                    class="mb-4 inline-flex items-center gap-2"
                                >
                                    <span
                                        class="text-[11px] font-semibold tracking-[0.12em] text-[#1B44E8] uppercase"
                                    >
                                        SPOTLIGHT · THIS WEEK
                                    </span>
                                </div>

                                <!-- Title -->
                                <h1
                                    class="mb-3 text-3xl font-semibold tracking-[-0.03em] text-[#F2EFEA] md:text-[48px] md:leading-[1.1]"
                                >
                                    <Link
                                        :href="`/films/${activeSpotlight.id}`"
                                        class="transition-colors hover:text-white"
                                    >
                                        {{ activeSpotlight.title }}
                                    </Link>
                                </h1>

                                <!-- Metadata -->
                                <p
                                    class="mb-4 text-[13px] font-normal tracking-[-0.01em] text-[#8C8C90]"
                                >
                                    {{ activeSpotlight.year }} ·
                                    {{ activeSpotlight.duration }} ·
                                    {{ activeSpotlight.genre }}
                                </p>

                                <!-- Synopsis -->
                                <p
                                    class="mb-8 text-[15px] leading-relaxed font-normal text-[#8C8C90]"
                                >
                                    {{ activeSpotlight.synopsis }}
                                </p>

                                <!-- Buttons Row -->
                                <div class="flex items-center gap-3">
                                    <Link
                                        :href="`/films/${activeSpotlight.id}`"
                                        class="flex h-[42px] cursor-pointer items-center gap-2 rounded-[10px] bg-[#F2EFEA] px-5 text-[13px] font-medium text-[#0E0F11] shadow-[0_1px_2px_rgba(0,0,0,0.15)] transition-colors duration-150 hover:bg-white"
                                    >
                                        <Play class="h-4 w-4 fill-current" />
                                        <span>Watch now</span>
                                    </Link>

                                    <button
                                        type="button"
                                        @click="
                                            toggleWatchlist(activeSpotlight.id)
                                        "
                                        class="flex h-[42px] cursor-pointer items-center gap-2 rounded-[10px] border border-[rgba(242,239,234,0.10)] bg-transparent px-4 text-[13px] font-medium text-[#F2EFEA] transition-colors duration-150 hover:bg-white/5"
                                    >
                                        <BookmarkCheck
                                            v-if="
                                                isBookmarked(activeSpotlight.id)
                                            "
                                            class="h-4 w-4 text-[#1B44E8]"
                                        />
                                        <Plus v-else class="h-4 w-4" />
                                        <span>{{
                                            isBookmarked(activeSpotlight.id)
                                                ? 'In Watchlist'
                                                : 'Add to list'
                                        }}</span>
                                    </button>

                                    <Link
                                        :href="`/films/${activeSpotlight.id}`"
                                        class="flex h-[42px] cursor-pointer items-center gap-2 rounded-[10px] border border-[rgba(242,239,234,0.12)] bg-white/5 px-4 text-[13px] font-medium text-[#F2EFEA] transition-colors duration-150 hover:bg-white/10 hover:border-[#1B44E8]"
                                    >
                                        <span>Detail Film</span>
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Bottom-Right Paging Bars with 5s Animated Progress Fill -->
                    <div
                        class="absolute right-8 bottom-6 z-10 hidden items-center gap-2.5 sm:flex"
                    >
                        <button
                            v-for="(_, index) in spotlights"
                            :key="index"
                            type="button"
                            @click="goToSpotlight(index)"
                            class="group relative h-[3px] w-10 cursor-pointer overflow-hidden rounded-full bg-[#F2EFEA]/20 transition-all duration-300 hover:h-[4px] hover:bg-[#F2EFEA]/40"
                            :title="`Slide ${index + 1}`"
                        >
                            <span
                                v-if="activeSpotlightIndex === index"
                                :key="`progress-${activeSpotlightIndex}`"
                                class="carousel-progress-bar absolute inset-0 rounded-full bg-[#F2EFEA]"
                                :style="{
                                    animationPlayState: isCarouselPaused
                                        ? 'paused'
                                        : 'running',
                                }"
                            ></span>
                        </button>
                    </div>
                </section>

                <!-- [3] CONTINUE WATCHING ROW -->
                <section
                    v-if="filteredContinueWatching.length > 0"
                    class="mb-16 md:mb-20"
                >
                    <div
                        class="scroll-reveal mb-5 flex items-center justify-between"
                    >
                        <h2
                            class="text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase"
                        >
                            CONTINUE WATCHING
                        </h2>
                        <button
                            type="button"
                            @click="activeTab = 'favorite'"
                            class="inline-flex cursor-pointer items-center gap-1 text-[13px] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            See all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Horizontal scroll carousel -->
                    <div
                        class="custom-scroll scroll-reveal scroll-delay-1 flex snap-x gap-4 overflow-x-auto pb-4"
                    >
                        <Link
                            v-for="(item, idx) in filteredContinueWatching"
                            :key="item.id"
                            :href="`/films/${item.id}`"
                            class="group block w-[280px] flex-none cursor-pointer snap-start transition-transform duration-300 hover:-translate-y-1"
                        >
                            <div
                                class="relative mb-2.5 aspect-video overflow-hidden rounded-[12px] border border-[rgba(242,239,234,0.10)] bg-[#17181B] transition-all duration-300 group-hover:border-[#1B44E8]"
                            >
                                <img
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    :src="item.image"
                                    :alt="item.title"
                                />
                                <div
                                    class="absolute inset-0 bg-[#0E0F11]/20 transition-colors group-hover:bg-transparent"
                                ></div>

                                <!-- Play overlay trigger on hover -->
                                <div
                                    class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(242,239,234,0.10)] bg-[#0E0F11]/80 text-[#F2EFEA] backdrop-blur-sm transition-transform duration-300 group-hover:scale-110"
                                    >
                                        <Play
                                            class="ml-0.5 h-5 w-5 fill-current"
                                        />
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div
                                    class="absolute inset-x-0 bottom-0 h-1 bg-[#0E0F11]/70"
                                >
                                    <div
                                        class="h-full bg-[#1B44E8]"
                                        :style="{ width: `${item.progress}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <h3
                                    class="truncate text-[14px] font-medium text-[#F2EFEA] transition-colors group-hover:text-white"
                                >
                                    {{ item.title }}
                                </h3>
                                <span
                                    class="ml-2 shrink-0 text-[12px] font-normal text-[#8C8C90]"
                                >
                                    {{ item.left }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- [4] POPULER MINGGU INI (Curated For You Grid - 6 Columns Desktop) -->
                <section
                    v-if="filteredPopularWeekly.length > 0"
                    class="mb-16 md:mb-20"
                >
                    <div
                        class="scroll-reveal mb-5 flex items-center justify-between"
                    >
                        <h2
                            class="text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase"
                        >
                            POPULER MINGGU INI
                        </h2>
                        <a
                            href="#explore-section"
                            class="inline-flex items-center gap-1 text-[13px] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            See all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:gap-5 lg:grid-cols-6"
                    >
                        <Link
                            v-for="(item, idx) in filteredPopularWeekly"
                            :key="item.id"
                            :href="`/films/${item.id}`"
                            class="scroll-reveal group block cursor-pointer transition-all duration-300 hover:-translate-y-1.5"
                            :class="`scroll-delay-${(idx % 6) + 1}`"
                        >
                            <div
                                class="relative mb-2.5 aspect-[2/3] overflow-hidden rounded-[12px] border border-[rgba(242,239,234,0.10)] bg-[#17181B] transition-all duration-300 group-hover:border-[#1B44E8] group-hover:shadow-[0_8px_20px_rgba(27,68,232,0.15)]"
                            >
                                <img
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    :src="item.image"
                                    :alt="item.title"
                                />
                                <div
                                    class="absolute top-3 left-3 rounded-[6px] border border-white/10 bg-[#0E0F11]/70 px-2 py-0.5 backdrop-blur-sm"
                                >
                                    <span
                                        class="text-[20px] leading-none font-semibold text-[#F2EFEA]"
                                    >
                                        {{ item.rank }}
                                    </span>
                                </div>
                            </div>
                            <h3
                                class="truncate text-[14px] font-medium text-[#F2EFEA] transition-colors group-hover:text-white"
                            >
                                {{ item.title }}
                            </h3>
                            <div
                                class="mt-0.5 flex items-center justify-between text-[12px] text-[#8C8C90]"
                            >
                                <span>{{ item.year }} · {{ item.genre }}</span>
                                <div
                                    class="flex items-center gap-1 text-[#8C8C90]"
                                >
                                    <Star
                                        class="h-3 w-3 fill-[#1B44E8] text-[#1B44E8]"
                                    />
                                    <span>{{ item.rating }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- [5] FROM LOCAL CREATORS (BARU RILIS) -->
                <section
                    v-if="filteredNewReleases.length > 0"
                    class="mb-16 md:mb-20"
                >
                    <div
                        class="scroll-reveal mb-5 flex items-center justify-between"
                    >
                        <h2
                            class="text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase"
                        >
                            BARU RILIS
                        </h2>
                        <a
                            href="#explore-section"
                            class="inline-flex items-center gap-1 text-[13px] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            See all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </a>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:gap-5 lg:grid-cols-6"
                    >
                        <Link
                            v-for="(item, idx) in filteredNewReleases"
                            :key="item.id"
                            :href="`/films/${item.id}`"
                            class="scroll-reveal group block cursor-pointer transition-all duration-300 hover:-translate-y-1.5"
                            :class="`scroll-delay-${(idx % 6) + 1}`"
                        >
                            <div
                                class="relative mb-2.5 aspect-[2/3] overflow-hidden rounded-[12px] border border-[rgba(242,239,234,0.10)] bg-[#17181B] transition-all duration-300 group-hover:border-[#1B44E8] group-hover:shadow-[0_8px_20px_rgba(27,68,232,0.15)]"
                            >
                                <img
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    :src="item.image"
                                    :alt="item.title"
                                />
                            </div>
                            <div class="mb-1 flex items-center gap-1.5">
                                <span
                                    class="text-[10px] font-semibold tracking-[0.14em] text-[#8C8C90] uppercase"
                                >
                                    NEW
                                </span>
                            </div>
                            <h3
                                class="truncate text-[14px] font-medium text-[#F2EFEA] transition-colors group-hover:text-white"
                            >
                                {{ item.title }}
                            </h3>
                            <div
                                class="mt-0.5 flex items-center justify-between text-[12px] text-[#8C8C90]"
                            >
                                <span>{{ item.year }} · {{ item.genre }}</span>
                                <div
                                    class="flex items-center gap-1 text-[#8C8C90]"
                                >
                                    <Star
                                        class="h-3 w-3 fill-[#1B44E8] text-[#1B44E8]"
                                    />
                                    <span>{{ item.rating }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- [6] JELAJAHI FILM (3 Rows of 6 Columns = 18 Posters) -->
                <section
                    id="explore-section"
                    v-if="filteredExploreFilms.length > 0"
                    class="mb-16"
                >
                    <div
                        class="scroll-reveal mb-5 flex items-center justify-between"
                    >
                        <h2
                            class="text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase"
                        >
                            JELAJAHI FILM
                        </h2>
                        <span class="text-[13px] text-[#8C8C90]">
                            {{ filteredExploreFilms.length }} Koleksi Film
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-6"
                    >
                        <Link
                            v-for="(item, idx) in filteredExploreFilms"
                            :key="item.id"
                            :href="`/films/${item.id}`"
                            class="scroll-reveal group block cursor-pointer transition-all duration-300 hover:-translate-y-1.5"
                            :class="`scroll-delay-${(idx % 6) + 1}`"
                        >
                            <div
                                class="relative mb-2.5 aspect-[2/3] overflow-hidden rounded-[12px] border border-[rgba(242,239,234,0.10)] bg-[#17181B] transition-all duration-300 group-hover:border-[#1B44E8] group-hover:shadow-[0_8px_20px_rgba(27,68,232,0.15)]"
                            >
                                <img
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    :src="item.image"
                                    :alt="item.title"
                                />
                            </div>
                            <h3
                                class="truncate text-[14px] font-medium text-[#F2EFEA] transition-colors group-hover:text-white"
                            >
                                {{ item.title }}
                            </h3>
                            <div
                                class="mt-0.5 truncate text-[12px] text-[#8C8C90]"
                            >
                                {{ item.year }} · {{ item.genre }}
                            </div>
                        </Link>
                    </div>

                    <!-- Centered quiet Load More link -->
                    <div
                        v-if="
                            !hasLoadedMore && filteredExploreFilms.length >= 18
                        "
                        class="mt-10 flex justify-center"
                    >
                        <button
                            type="button"
                            @click="loadMoreFilms"
                            class="group inline-flex cursor-pointer items-center gap-1.5 text-[13px] text-[#8C8C90] transition-colors duration-150 hover:text-[#F2EFEA]"
                        >
                            <span>Load more</span>
                            <ChevronDown
                                class="h-4 w-4 transition-transform duration-150 group-hover:translate-y-0.5"
                            />
                        </button>
                    </div>
                </section>
                </template>

                <!-- ==================== FAVORITE LIBRARY TAB (Stitch FlixRead Screen Specification) ==================== -->
                <template v-else-if="activeTab === 'favorite'">
                    <div class="space-y-8">
                        <!-- 2. PAGE HEADER -->
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold tracking-[0.14em] text-[#1B44E8] uppercase">LIBRARY</span>
                                <span class="text-[12px] text-[#8C8C90] tabular-nums">{{ displayedFavoriteItems.length }} items</span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1">
                                <h1 class="font-tight text-[32px] md:text-[36px] font-semibold text-[#F2EFEA] tracking-tight leading-none">Favorite</h1>
                                <p class="text-[14px] text-[#8C8C90] font-normal">Films and episodes you've saved for later.</p>
                            </div>
                        </div>

                        <!-- 3. FILTER & SORT BAR -->
                        <div class="flex items-center justify-between border-b border-[rgba(242,239,234,0.08)] pb-3">
                            <!-- Segmented Text Filter -->
                            <div class="flex items-center gap-7">
                                <button
                                    type="button"
                                    @click="favoriteFilter = 'all'"
                                    class="relative text-[14px] font-medium pb-3 transition-colors cursor-pointer"
                                    :class="favoriteFilter === 'all' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                >
                                    All
                                    <span v-if="favoriteFilter === 'all'" class="absolute bottom-[-1px] left-0 right-0 h-[2px] bg-[#1B44E8]"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="favoriteFilter = 'film'"
                                    class="relative text-[14px] font-medium pb-3 transition-colors cursor-pointer"
                                    :class="favoriteFilter === 'film' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                >
                                    Films
                                    <span v-if="favoriteFilter === 'film'" class="absolute bottom-[-1px] left-0 right-0 h-[2px] bg-[#1B44E8]"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="favoriteFilter = 'episode'"
                                    class="relative text-[14px] font-medium pb-3 transition-colors cursor-pointer"
                                    :class="favoriteFilter === 'episode' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                >
                                    Episodes
                                    <span v-if="favoriteFilter === 'episode'" class="absolute bottom-[-1px] left-0 right-0 h-[2px] bg-[#1B44E8]"></span>
                                </button>
                            </div>

                            <!-- Right Side Sort Control -->
                            <div ref="sortDropdownRef" class="relative">
                                <button
                                    type="button"
                                    @click="isSortDropdownOpen = !isSortDropdownOpen"
                                    class="flex items-center gap-1.5 cursor-pointer text-[#8C8C90] hover:text-[#F2EFEA] transition-colors pb-3 select-none text-[13px] font-medium"
                                >
                                    <span>{{ currentSortLabel }}</span>
                                    <ChevronDown class="h-4 w-4 transition-transform duration-150" :class="{ 'rotate-180': isSortDropdownOpen }" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div
                                    v-if="isSortDropdownOpen"
                                    class="absolute right-0 top-full mt-1 w-44 rounded-xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] py-1.5 shadow-2xl z-30 backdrop-blur-md"
                                >
                                    <button
                                        type="button"
                                        @click="selectSort('recent')"
                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-xs font-medium transition-colors hover:bg-white/[0.06] cursor-pointer"
                                        :class="favoriteSort === 'recent' ? 'text-[#7A9BFF]' : 'text-[#F2EFEA]'"
                                    >
                                        <span>Recently added</span>
                                        <Check v-if="favoriteSort === 'recent'" class="h-3.5 w-3.5 text-[#1B44E8]" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectSort('title')"
                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-xs font-medium transition-colors hover:bg-white/[0.06] cursor-pointer"
                                        :class="favoriteSort === 'title' ? 'text-[#7A9BFF]' : 'text-[#F2EFEA]'"
                                    >
                                        <span>Title (A - Z)</span>
                                        <Check v-if="favoriteSort === 'title'" class="h-3.5 w-3.5 text-[#1B44E8]" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectSort('year')"
                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-xs font-medium transition-colors hover:bg-white/[0.06] cursor-pointer"
                                        :class="favoriteSort === 'year' ? 'text-[#7A9BFF]' : 'text-[#F2EFEA]'"
                                    >
                                        <span>Release Year</span>
                                        <Check v-if="favoriteSort === 'year'" class="h-3.5 w-3.5 text-[#1B44E8]" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Active Search Chip in Favorite View -->
                        <div
                            v-if="searchQuery"
                            class="flex items-center justify-between rounded-xl border border-[rgba(242,239,234,0.1)] bg-[#17181B] px-4 py-2.5 text-xs text-[#8C8C90]"
                        >
                            <div class="flex items-center gap-2">
                                <span>Mencari di Favorit:</span>
                                <span class="rounded bg-white/10 px-2 py-0.5 text-[#F2EFEA]">"{{ searchQuery }}"</span>
                            </div>
                            <button
                                type="button"
                                @click="searchQuery = ''"
                                class="underline hover:text-[#F2EFEA] cursor-pointer"
                            >
                                Hapus Pencarian
                            </button>
                        </div>

                        <!-- 4. FAVORITE GRID (18 Items, 6-col desktop layout) -->
                        <section v-if="displayedFavoriteItems.length > 0">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                                <article
                                    v-for="(item, idx) in displayedFavoriteItems"
                                    :key="item.id"
                                    class="scroll-reveal group flex flex-col gap-2.5 transition-all duration-300 hover:-translate-y-1"
                                    :class="`scroll-delay-${(idx % 6) + 1}`"
                                >
                                    <div class="aspect-[2/3] w-full rounded-[12px] overflow-hidden relative bg-[#17181B] border border-[rgba(242,239,234,0.10)] group-hover:border-[rgba(242,239,234,0.25)] transition-all duration-200">
                                        <Link :href="item.href" class="block w-full h-full">
                                            <img
                                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                :src="item.image"
                                                :alt="item.title"
                                            />
                                        </Link>

                                        <!-- Inset Badge FILM -->
                                        <div
                                            v-if="item.type === 'film'"
                                            class="pointer-events-none absolute top-2.5 left-2.5 h-[22px] rounded-[8px] px-2 flex items-center gap-1 bg-[#0E0F11]/55 backdrop-blur-md border border-[rgba(242,239,234,0.28)] bg-[rgba(242,239,234,0.06)]"
                                        >
                                            <Clapperboard class="h-3 w-3 text-[#F2EFEA]" />
                                            <span class="text-[10px] font-semibold text-[#F2EFEA] tracking-[0.14em]">FILM</span>
                                        </div>

                                        <!-- Inset Badge EPISODE -->
                                        <div
                                            v-else
                                            class="pointer-events-none absolute top-2.5 left-2.5 h-[22px] rounded-[8px] px-2 flex items-center gap-1 bg-[#1B44E8]/20 backdrop-blur-md border border-[#1B44E8]/45"
                                        >
                                            <span class="text-[10px] font-semibold text-[#7A9BFF] tracking-[0.14em]">{{ item.episodeCode }}</span>
                                        </div>

                                        <!-- Remove Bookmark Hover Affordance -->
                                        <button
                                            type="button"
                                            @click.stop.prevent="removeFavoriteItem(item)"
                                            :aria-label="`Remove bookmark ${item.title}`"
                                            class="absolute top-2.5 right-2.5 w-7 h-7 rounded-full bg-[#0E0F11]/60 backdrop-blur-md border border-[rgba(242,239,234,0.12)] flex items-center justify-center text-[#F2EFEA] opacity-0 group-hover:opacity-100 transition-opacity hover:bg-[#1B44E8] hover:border-[#1B44E8] cursor-pointer"
                                            title="Hapus dari favorit"
                                        >
                                            <BookmarkCheck class="h-3.5 w-3.5 fill-current text-white" />
                                        </button>
                                    </div>

                                    <div class="flex flex-col gap-0.5">
                                        <Link :href="item.href" class="text-[14px] font-medium text-[#F2EFEA] truncate hover:text-[#7A9BFF] transition-colors">
                                            {{ item.title }}
                                        </Link>
                                        <p v-if="item.type === 'film'" class="text-[12px] text-[#8C8C90] truncate">
                                            {{ item.year }} · {{ item.genre }}
                                        </p>
                                        <p v-else class="text-[12px] text-[#8C8C90] truncate">
                                            From <Link :href="item.parentFilmHref || `/films/${item.parentFilmId || 1}`" class="text-[#7A9BFF] hover:underline">{{ item.parentFilmTitle }}</Link> · {{ item.season || 'S1' }}
                                        </p>
                                        <p class="text-[11px] text-[#8C8C90]/70">
                                            Saved {{ item.savedAgo }}
                                        </p>
                                    </div>
                                </article>
                            </div>

                            <!-- 5. LOAD MORE AFFORDANCE -->
                            <div v-if="!hasLoadedMoreFavorites" class="mt-8 border-t border-[rgba(242,239,234,0.06)] pt-2">
                                <button
                                    type="button"
                                    @click="loadMoreFavorites"
                                    class="py-6 block w-full text-center text-[13px] font-medium text-[#1B44E8] hover:text-[#7A9BFF] transition-colors cursor-pointer"
                                >
                                    Load more →
                                </button>
                            </div>
                        </section>

                        <!-- EMPTY STATE WHEN FAVORITE HAS NO ITEMS -->
                        <div
                            v-else
                            class="my-12 rounded-2xl border border-[rgba(242,239,234,0.08)] bg-[#17181B] p-12 text-center"
                        >
                            <div
                                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-[#1B44E8]/20 bg-[#1B44E8]/10 text-[#7A9BFF]"
                            >
                                <BookmarkCheck class="h-7 w-7 text-[#1B44E8]" />
                            </div>
                            <h3 class="mb-2 text-lg font-semibold text-[#F2EFEA]">
                                Belum Ada Koleksi Favorit
                            </h3>
                            <p class="mx-auto mb-6 max-w-md text-[13px] leading-relaxed text-[#8C8C90]">
                                {{ searchQuery ? 'Tidak ada film atau episode favorit yang cocok dengan pencarian Anda.' : 'Simpan film dan episode yang ingin Anda tonton nanti untuk melihatnya di sini.' }}
                            </p>
                            <button
                                type="button"
                                @click="switchTab('home')"
                                class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#1B44E8] px-6 text-[13px] font-medium text-white shadow-lg shadow-[#1B44E8]/25 transition-all hover:bg-[#1B44E8]/90 cursor-pointer"
                            >
                                Jelajahi Beranda Film
                            </button>
                        </div>
                    </div>
                </template>

                <!-- ==================== SUBSCRIBE / CREATORS TAB (Stitch FlixRead Screen Specification) ==================== -->
                <template v-else-if="activeTab === 'subscribe'">
                    <div class="space-y-8">
                        <!-- 2. PAGE HEADER -->
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold tracking-[0.14em] text-[#1B44E8] uppercase">CREATORS</span>
                                <span class="text-[12px] text-[#8C8C90] tabular-nums">{{ displayedCreators.length }} creators</span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1">
                                <h1 class="font-tight text-[32px] md:text-[36px] font-semibold text-[#F2EFEA] tracking-tight leading-none">Subscribe</h1>
                                <p class="text-[14px] text-[#8C8C90] font-normal">Creators you follow and their latest films.</p>
                            </div>
                        </div>

                        <!-- 3. FILTER & SORT BAR -->
                        <div class="flex items-center justify-between border-b border-[rgba(242,239,234,0.08)] pb-3">
                            <!-- Segmented Text Filter -->
                            <div class="flex items-center gap-7">
                                <button
                                    type="button"
                                    @click="subscribeFilter = 'all'"
                                    class="relative text-[14px] font-medium pb-3 transition-colors cursor-pointer"
                                    :class="subscribeFilter === 'all' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                >
                                    All
                                    <span v-if="subscribeFilter === 'all'" class="absolute bottom-[-1px] left-0 right-0 h-[2px] bg-[#1B44E8]"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="subscribeFilter = 'new'"
                                    class="relative text-[14px] font-medium pb-3 transition-colors cursor-pointer"
                                    :class="subscribeFilter === 'new' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                >
                                    With new content
                                    <span v-if="subscribeFilter === 'new'" class="absolute bottom-[-1px] left-0 right-0 h-[2px] bg-[#1B44E8]"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="subscribeFilter = 'muted'"
                                    class="relative text-[14px] font-medium pb-3 transition-colors cursor-pointer"
                                    :class="subscribeFilter === 'muted' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                >
                                    Muted
                                    <span v-if="subscribeFilter === 'muted'" class="absolute bottom-[-1px] left-0 right-0 h-[2px] bg-[#1B44E8]"></span>
                                </button>
                            </div>

                            <!-- Right Side Sort Control -->
                            <div ref="subscribeSortDropdownRef" class="relative">
                                <button
                                    type="button"
                                    @click="isSubscribeSortDropdownOpen = !isSubscribeSortDropdownOpen"
                                    class="flex items-center gap-1.5 cursor-pointer text-[#8C8C90] hover:text-[#F2EFEA] transition-colors pb-3 select-none text-[13px] font-medium"
                                >
                                    <span>{{ currentSubscribeSortLabel }}</span>
                                    <ChevronDown class="h-4 w-4 transition-transform duration-150" :class="{ 'rotate-180': isSubscribeSortDropdownOpen }" />
                                </button>

                                <!-- Dropdown Menu Popover -->
                                <div
                                    v-if="isSubscribeSortDropdownOpen"
                                    class="absolute right-0 top-full z-40 mt-1 w-52 rounded-xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] py-1.5 shadow-2xl backdrop-blur-md"
                                >
                                    <button
                                        type="button"
                                        @click="selectSubscribeSort('updated')"
                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-[13px] transition-colors cursor-pointer"
                                        :class="subscribeSort === 'updated' ? 'bg-[#1B44E8]/15 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/5'"
                                    >
                                        <span>Recently updated</span>
                                        <Check v-if="subscribeSort === 'updated'" class="h-3.5 w-3.5 text-[#1B44E8]" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectSubscribeSort('popular')"
                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-[13px] transition-colors cursor-pointer"
                                        :class="subscribeSort === 'popular' ? 'bg-[#1B44E8]/15 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/5'"
                                    >
                                        <span>Most popular</span>
                                        <Check v-if="subscribeSort === 'popular'" class="h-3.5 w-3.5 text-[#1B44E8]" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectSubscribeSort('alpha')"
                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-[13px] transition-colors cursor-pointer"
                                        :class="subscribeSort === 'alpha' ? 'bg-[#1B44E8]/15 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/5'"
                                    >
                                        <span>Alphabetical (A - Z)</span>
                                        <Check v-if="subscribeSort === 'alpha'" class="h-3.5 w-3.5 text-[#1B44E8]" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 4. CREATOR LIST -->
                        <div v-if="displayedCreators.length > 0" class="divide-y divide-[rgba(242,239,234,0.06)]">
                            <article
                                v-for="creator in displayedCreators"
                                :key="creator.id"
                                class="py-6 flex flex-col md:flex-row md:items-center justify-between gap-6 group hover:bg-[rgba(242,239,234,0.02)] transition-colors px-3 -mx-3 rounded-xl"
                            >
                                <!-- Column A & B: Avatar, Badge, Bio -->
                                <div class="flex items-start md:items-center gap-4 flex-1 min-w-0">
                                    <!-- Column A: 56px Avatar & Ring Badge -->
                                    <div class="relative shrink-0 w-14 h-14">
                                        <div class="w-14 h-14 rounded-full border border-[rgba(242,239,234,0.15)] bg-[#17181B] flex items-center justify-center text-[#F2EFEA] text-[16px] font-medium">
                                            {{ creator.initials }}
                                        </div>
                                        <span
                                            class="absolute -bottom-[3px] -right-[3px] h-[14px] px-1.5 rounded-[6px] text-[9px] font-semibold tracking-[0.12em] flex items-center justify-center shadow-[0_0_0_2px_#0E0F11]"
                                            :class="
                                                creator.tier === 'VIP'
                                                    ? 'bg-[rgba(184,151,90,0.22)] border border-[rgba(184,151,90,0.50)] text-[#D4B67A]'
                                                    : creator.tier === 'PRO'
                                                    ? 'bg-[rgba(27,68,232,0.20)] border border-[rgba(27,68,232,0.45)] text-[#7A9BFF]'
                                                    : 'bg-[rgba(140,140,144,0.20)] border border-[rgba(140,140,144,0.40)] text-[#A8A8AC]'
                                            "
                                        >
                                            {{ creator.tier }}
                                        </span>
                                    </div>

                                    <!-- Column B: Info -->
                                    <div class="flex flex-col min-w-0 max-w-[440px]">
                                        <div class="flex items-center gap-1.5">
                                            <h2 class="text-[15px] font-medium text-[#F2EFEA] hover:text-[#1B44E8] transition-colors cursor-pointer truncate">
                                                {{ creator.name }}
                                            </h2>
                                            <span v-if="creator.isVerified" class="inline-flex items-center text-[#1B44E8]">
                                                <Check class="h-3.5 w-3.5 stroke-[2.5]" />
                                            </span>
                                        </div>
                                        <div class="text-[13px] text-[#8C8C90] mt-0.5 flex items-center gap-1.5">
                                            <span>{{ creator.handle }}</span>
                                            <span class="text-[9px] opacity-40">●</span>
                                            <span>{{ creator.subscribersCount }}</span>
                                        </div>
                                        <p class="text-[13px] text-[#8C8C90]/85 truncate mt-1">
                                            {{ creator.bio }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Column C: Latest content (240px) & Column D: Actions (150px) -->
                                <div class="flex items-center justify-between md:justify-end gap-6 w-full md:w-auto shrink-0">
                                    <!-- Column C: Latest Content -->
                                    <div class="w-[240px] shrink-0 flex items-center gap-3">
                                        <template v-if="creator.latestContent">
                                            <Link
                                                :href="`/films/${creator.latestContent.filmId || 1}`"
                                                class="flex items-center gap-2 group/thumb cursor-pointer"
                                            >
                                                <span
                                                    v-if="creator.latestContent.isNew"
                                                    class="w-1.5 h-1.5 rounded-full bg-[#1B44E8] shrink-0"
                                                ></span>
                                                <div class="w-[72px] h-[40px] rounded-[6px] overflow-hidden bg-[#17181B] border border-[rgba(242,239,234,0.1)] shrink-0 group-hover/thumb:-translate-y-0.5 transition-transform duration-200">
                                                    <img
                                                        class="w-full h-full object-cover"
                                                        :src="creator.latestContent.image"
                                                        :alt="creator.latestContent.title"
                                                    />
                                                </div>
                                            </Link>
                                            <div class="flex flex-col min-w-0">
                                                <span
                                                    class="text-[10px] font-semibold tracking-[0.14em] uppercase"
                                                    :class="creator.latestContent.isNew ? 'text-[#1B44E8]' : 'text-[#8C8C90]'"
                                                >
                                                    {{ creator.latestContent.isNew ? 'NEW' : 'LATEST' }}
                                                </span>
                                                <Link
                                                    :href="`/films/${creator.latestContent.filmId || 1}`"
                                                    class="text-[13px] font-medium text-[#F2EFEA] hover:text-[#1B44E8] truncate transition-colors"
                                                >
                                                    {{ creator.latestContent.title }}
                                                </Link>
                                                <span class="text-[11px] text-[#8C8C90]">
                                                    {{ creator.latestContent.timeAgo }}
                                                </span>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span class="text-[12px] text-[#8C8C90]">No films yet</span>
                                        </template>
                                    </div>

                                    <!-- Column D: Actions -->
                                    <div class="w-[150px] shrink-0 flex flex-col items-end justify-center">
                                        <div class="flex items-center gap-2">
                                            <!-- Direct Message Button -->
                                            <Link
                                                :href="messages.index.url({ query: { creator: creator.id } })"
                                                class="w-9 h-9 flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-[rgba(242,239,234,0.06)] rounded-[8px] transition-all duration-180 ease-out cursor-pointer"
                                                title="Buka Obrolan / Direct Message"
                                            >
                                                <MessageSquare class="h-4 w-4" />
                                            </Link>

                                            <!-- Notification Toggle Button -->
                                            <button
                                                type="button"
                                                @click="toggleNotifications(creator)"
                                                class="relative w-9 h-9 flex items-center justify-center hover:bg-[rgba(242,239,234,0.06)] rounded-[8px] transition-all duration-180 ease-out cursor-pointer"
                                                :class="creator.notificationsEnabled ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                                                :title="creator.notificationsEnabled ? 'Notification active' : 'Notifications muted'"
                                            >
                                                <Bell v-if="creator.notificationsEnabled" class="h-4 w-4" />
                                                <BellOff v-else class="h-4 w-4" />
                                                <span
                                                    v-if="creator.notificationsEnabled"
                                                    class="absolute top-2 right-2 w-1.5 h-1.5 rounded-full bg-[#1B44E8]"
                                                ></span>
                                            </button>
                                        </div>

                                        <!-- Following Toggle -->
                                        <button
                                            type="button"
                                            @click="toggleFollow(creator)"
                                            class="flex items-center gap-1 text-[12px] text-[#8C8C90] hover:text-[#F2EFEA] font-medium mt-1.5 transition-colors cursor-pointer"
                                        >
                                            <span>Following</span>
                                            <ChevronDown class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <!-- Empty State for Creators -->
                        <div
                            v-else
                            class="rounded-2xl border border-[rgba(242,239,234,0.08)] bg-[#17181B]/50 px-6 py-16 text-center"
                        >
                            <div
                                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-[#1B44E8]/20 bg-[#1B44E8]/10 text-[#7A9BFF]"
                            >
                                <UserCheck class="h-7 w-7 text-[#1B44E8]" />
                            </div>
                            <h3 class="mb-2 text-lg font-semibold text-[#F2EFEA]">
                                Tidak Ada Kreator yang Ditemukan
                            </h3>
                            <p class="mx-auto mb-6 max-w-md text-[13px] leading-relaxed text-[#8C8C90]">
                                {{ searchQuery ? 'Tidak ada kreator yang cocok dengan filter atau kata kunci pencarian Anda.' : 'Kreator yang Anda ikuti akan muncul di daftar ini.' }}
                            </p>
                            <button
                                v-if="subscribeFilter !== 'all'"
                                type="button"
                                @click="subscribeFilter = 'all'"
                                class="inline-flex h-9 items-center gap-2 rounded-xl bg-[#1B44E8] px-5 text-[12px] font-medium text-white transition-all hover:bg-[#1B44E8]/90 cursor-pointer"
                            >
                                Lihat Semua Kreator
                            </button>
                        </div>

                        <!-- LOAD MORE CREATORS -->
                        <div v-if="!hasLoadedMoreCreators && displayedCreators.length >= 6" class="mt-10 text-center">
                            <button
                                type="button"
                                @click="loadMoreCreators"
                                class="text-[#1B44E8] hover:text-[#7A9BFF] text-[13px] font-medium transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>Load more creators</span>
                                <ArrowRight class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <!-- 5. FEED SECTION ("LATEST FROM YOUR CREATORS") -->
                        <section class="mt-16 pt-12 border-t border-[rgba(242,239,234,0.08)]">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-[11px] text-[#1B44E8] uppercase font-semibold tracking-[0.12em]">
                                    LATEST FROM YOUR CREATORS
                                </h2>
                                <span class="text-[13px] text-[#8C8C90]">
                                    {{ displayedFeedItems.length }} films
                                </span>
                            </div>

                            <!-- Feed Poster Grid: 4 columns desktop, 2 columns mobile -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <Link
                                    v-for="item in displayedFeedItems"
                                    :key="item.id"
                                    :href="`/films/${item.filmId}`"
                                    class="group flex flex-col cursor-pointer transition-all duration-300"
                                >
                                    <div class="relative w-full aspect-[2/3] rounded-[12px] overflow-hidden bg-[#17181B] border border-[rgba(242,239,234,0.08)] group-hover:border-[#1B44E8] group-hover:shadow-[0_8px_20px_rgba(27,68,232,0.15)] transition-all">
                                        <img
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                                            :src="item.image"
                                            :alt="item.filmTitle"
                                        />
                                        <!-- Attribution Chip (bottom-left) -->
                                        <div class="absolute bottom-2.5 left-2.5 flex items-center gap-1.5 px-2 h-[22px] rounded-[8px] bg-[#0E0F11]/60 backdrop-blur-[6px] border border-[rgba(242,239,234,0.12)]">
                                            <div class="w-4 h-4 rounded-full bg-[#17181B] flex items-center justify-center text-[8px] text-[#F2EFEA] font-semibold">
                                                {{ item.creator.initials }}
                                            </div>
                                            <span
                                                class="h-[10px] px-1 rounded-[3px] text-[7px] font-semibold tracking-wider flex items-center"
                                                :class="
                                                    item.creator.tier === 'VIP'
                                                        ? 'bg-[rgba(184,151,90,0.22)] border border-[rgba(184,151,90,0.50)] text-[#D4B67A]'
                                                        : item.creator.tier === 'PRO'
                                                        ? 'bg-[rgba(27,68,232,0.20)] border border-[rgba(27,68,232,0.45)] text-[#7A9BFF]'
                                                        : 'bg-[rgba(140,140,144,0.20)] border border-[rgba(140,140,144,0.40)] text-[#A8A8AC]'
                                                "
                                            >
                                                {{ item.creator.tier }}
                                            </span>
                                            <span class="text-[11px] font-medium text-[#F2EFEA] ml-0.5 truncate max-w-[90px]">
                                                {{ item.creator.name }}
                                            </span>
                                        </div>
                                    </div>
                                    <h3 class="text-[14px] font-medium text-[#F2EFEA] mt-2.5 truncate group-hover:text-[#1B44E8] transition-colors">
                                        {{ item.filmTitle }}
                                    </h3>
                                    <p class="text-[12px] text-[#8C8C90] mt-0.5">
                                        {{ item.timeAgo }} · {{ item.duration }}
                                    </p>
                                </Link>
                            </div>

                            <!-- Feed Load More -->
                            <div v-if="!hasLoadedMoreFeed && displayedFeedItems.length >= 8" class="mt-10 text-center">
                                <button
                                    type="button"
                                    @click="loadMoreFeed"
                                    class="text-[#1B44E8] hover:text-[#7A9BFF] text-[13px] font-medium transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                                >
                                    <span>Load more</span>
                                    <ArrowRight class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </section>
                    </div>
                </template>
            </div>
        </main>

        <!-- WEB FOOTER (Aligned with Shared Components JSON) -->
        <footer class="bg-[#17181B] border-t border-[rgba(242,239,234,0.08)] mt-auto">
            <div class="max-w-7xl mx-auto px-6 py-12 flex flex-col md:flex-row justify-between items-center gap-6 w-full">
                <div class="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                    <span class="text-[16px] font-tight font-medium text-[#F2EFEA]">WATOON</span>
                    <span class="hidden sm:inline text-[#8C8C90] text-[12px]">|</span>
                    <p class="text-[12px] text-[#8C8C90]">
                        © 2025 WATOON Inc. All rights reserved. Platform kurasi sinematik dan literatur digital.
                    </p>
                </div>
                <!-- Footer Shared Component Links -->
                <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">Editorial Index</a>
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">Streaming Platform</a>
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">Reader Mode</a>
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">Pricing Plans</a>
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">Privacy Policy</a>
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">Terms of Service</a>
                    <a class="text-[#8C8C90] text-[12px] hover:text-[#F2EFEA] transition-colors duration-150" href="#">System Status</a>
                </nav>
            </div>
        </footer>

        <!-- FLOATING TOAST FEEDBACK WITH UNDO -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="showToast"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl border border-[rgba(242,239,234,0.12)] bg-[#17181B]/95 px-4 py-3 text-xs text-[#F2EFEA] shadow-2xl backdrop-blur-md"
            >
                <span>{{ toastMessage }}</span>
                <button
                    v-if="lastRemovedItem"
                    type="button"
                    @click="undoRemoveFavorite"
                    class="flex items-center gap-1 font-semibold text-[#7A9BFF] hover:underline cursor-pointer"
                >
                    <Undo2 class="h-3.5 w-3.5" />
                    <span>Urungkan</span>
                </button>
                <button
                    v-else-if="lastUnfollowedCreator"
                    type="button"
                    @click="undoUnfollow"
                    class="flex items-center gap-1 font-semibold text-[#7A9BFF] hover:underline cursor-pointer"
                >
                    <Undo2 class="h-3.5 w-3.5" />
                    <span>Urungkan</span>
                </button>
                <button
                    type="button"
                    @click="showToast = false"
                    class="ml-1 text-[#8C8C90] hover:text-white cursor-pointer"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </Transition>

        <!-- DIRECT MESSAGE MODAL -->
        <div
            v-if="activeMessageCreator"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
            @click.self="activeMessageCreator = null"
        >
            <div
                class="relative w-full max-w-md rounded-2xl border border-[rgba(242,239,234,0.14)] bg-[#17181B] p-6 shadow-2xl"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full border border-[rgba(242,239,234,0.15)] bg-[#0E0F11] flex items-center justify-center text-[#F2EFEA] text-[14px] font-medium">
                                {{ activeMessageCreator.initials }}
                            </div>
                            <span
                                class="absolute -bottom-1 -right-1 h-[12px] px-1 rounded-[4px] text-[8px] font-semibold tracking-wider flex items-center shadow-[0_0_0_2px_#17181B]"
                                :class="
                                    activeMessageCreator.tier === 'VIP'
                                        ? 'bg-[rgba(184,151,90,0.22)] border border-[rgba(184,151,90,0.50)] text-[#D4B67A]'
                                        : activeMessageCreator.tier === 'PRO'
                                        ? 'bg-[rgba(27,68,232,0.20)] border border-[rgba(27,68,232,0.45)] text-[#7A9BFF]'
                                        : 'bg-[rgba(140,140,144,0.20)] border border-[rgba(140,140,144,0.40)] text-[#A8A8AC]'
                                "
                            >
                                {{ activeMessageCreator.tier }}
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-[15px] font-medium text-[#F2EFEA]">
                                    {{ activeMessageCreator.name }}
                                </h3>
                                <Check v-if="activeMessageCreator.isVerified" class="h-3.5 w-3.5 text-[#1B44E8]" />
                            </div>
                            <p class="text-[11px] text-[#8C8C90]">
                                {{ activeMessageCreator.handle }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="activeMessageCreator = null"
                        class="text-[#8C8C90] hover:text-white cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-4">
                    <label class="block text-[12px] font-medium text-[#8C8C90] mb-2">
                        Kirim Pesan Langsung
                    </label>
                    <textarea
                        v-model="messageText"
                        rows="4"
                        class="w-full rounded-xl border border-[rgba(242,239,234,0.10)] bg-[#0E0F11] p-3 text-[13px] text-[#F2EFEA] placeholder-[#8C8C90] focus:border-[#1B44E8] focus:outline-none focus:ring-1 focus:ring-[#1B44E8]"
                        placeholder="Tulis pesan atau pertanyaan Anda untuk kreator ini…"
                        @keydown.enter.exact.prevent="sendDirectMessage"
                    ></textarea>
                    <p class="mt-1 text-[11px] text-[#8C8C90]">
                        Tekan Enter untuk mengirim pesan.
                    </p>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="activeMessageCreator = null"
                        class="rounded-xl border border-[rgba(242,239,234,0.12)] px-4 py-2 text-[12px] font-medium text-[#8C8C90] transition-colors hover:bg-white/5 hover:text-[#F2EFEA] cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="sendDirectMessage"
                        :disabled="!messageText.trim()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-[#1B44E8] px-4 py-2 text-[12px] font-medium text-white transition-opacity hover:opacity-95 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                    >
                        <Send class="h-3.5 w-3.5" />
                        <span>Kirim Pesan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- WATOON 3-KASTA UPGRADE / MANAGEMENT MODAL -->
        <div
            v-if="isProModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
            @click.self="isProModalOpen = false"
        >
            <div
                class="relative w-full max-w-2xl rounded-2xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] p-6 shadow-2xl max-h-[90vh] overflow-y-auto custom-scroll"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1B44E8]/20 text-[#7A9BFF]"
                        >
                            <Crown class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                Kasta Akun Penonton WATOON
                            </h3>
                            <p class="text-xs text-[#8C8C90]">
                                Kasta akun Anda saat ini: <strong class="text-white">{{ currentUserTier }}</strong>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isProModalOpen = false"
                        class="text-[#8C8C90] hover:text-white transition-colors cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-4 p-3 rounded-xl bg-[rgba(242,239,234,0.04)] border border-[rgba(242,239,234,0.08)] text-xs text-[#8C8C90] leading-relaxed">
                    <strong class="text-[#F2EFEA]">Hirarki Kasta Penonton:</strong>
                    VIP (Paling Atas) dapat mengobrol dengan seluruh kreator (VIP, Pro, Free). Pro (Di Tengah) dapat mengobrol dengan kreator Pro & Free. Free (Paling Bawah / Default) hanya dapat mengobrol dengan sesama kreator Free.
                </div>

                <!-- 3 Kasta Cards Grid -->
                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <!-- KASTA 1: FREE -->
                    <div
                        class="p-4 rounded-xl border flex flex-col justify-between transition-all"
                        :class="[
                            currentUserTier === 'FREE'
                                ? 'bg-white/[0.05] border-white/30 shadow-md ring-1 ring-white/20'
                                : 'bg-[#0E0F11]/60 border-[rgba(242,239,234,0.08)] hover:border-white/20'
                        ]"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-white/10 text-[#8C8C90]">
                                    FREE
                                </span>
                                <span v-if="currentUserTier === 'FREE'" class="text-[10px] text-emerald-400 font-medium">
                                    Aktif
                                </span>
                            </div>
                            <h4 class="text-sm font-semibold text-white">Penonton Dasar</h4>
                            <p class="text-[11px] text-[#8C8C90] mt-1 leading-snug">
                                Kasta default awal semua penonton baru.
                            </p>

                            <ul class="mt-3 space-y-1.5 text-[11px] text-[#8C8C90]">
                                <li class="flex items-center gap-1.5">
                                    <Check class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                                    <span>Chat kreator FREE</span>
                                </li>
                                <li class="flex items-center gap-1.5 text-[#8C8C90]/60 line-through">
                                    <Lock class="w-3.5 h-3.5 text-red-400/60 shrink-0" />
                                    <span>Chat kreator PRO & VIP</span>
                                </li>
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="switchUserTier('FREE')"
                            :disabled="currentUserTier === 'FREE' || isUpgradingTier"
                            class="mt-4 w-full py-2 rounded-lg text-xs font-medium transition-all cursor-pointer disabled:opacity-40 disabled:cursor-default"
                            :class="[
                                currentUserTier === 'FREE'
                                    ? 'bg-white/10 text-white'
                                    : 'bg-white/[0.07] text-[#8C8C90] hover:text-white hover:bg-white/[0.12]'
                            ]"
                        >
                            {{ currentUserTier === 'FREE' ? 'Kasta Saat Ini' : 'Pilih Free' }}
                        </button>
                    </div>

                    <!-- KASTA 2: PRO -->
                    <div
                        class="p-4 rounded-xl border flex flex-col justify-between transition-all relative"
                        :class="[
                            currentUserTier === 'PRO'
                                ? 'bg-[rgba(27,68,232,0.14)] border-[#7A9BFF]/50 shadow-md ring-1 ring-[#7A9BFF]/30'
                                : 'bg-[#0E0F11]/60 border-[rgba(242,239,234,0.08)] hover:border-[#7A9BFF]/30'
                        ]"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#1B44E8]/20 border border-[#1B44E8]/40 text-[#7A9BFF]">
                                    PRO
                                </span>
                                <span v-if="currentUserTier === 'PRO'" class="text-[10px] text-[#7A9BFF] font-medium">
                                    Aktif
                                </span>
                            </div>
                            <h4 class="text-sm font-semibold text-white">Member Pro</h4>
                            <p class="text-[11px] text-[#8C8C90] mt-1 leading-snug">
                                Akses tingkat menengah untuk penikmat karya.
                            </p>

                            <ul class="mt-3 space-y-1.5 text-[11px] text-[#8C8C90]">
                                <li class="flex items-center gap-1.5">
                                    <Check class="w-3.5 h-3.5 text-[#7A9BFF] shrink-0" />
                                    <span>Chat kreator PRO & FREE</span>
                                </li>
                                <li class="flex items-center gap-1.5 text-[#8C8C90]/60 line-through">
                                    <Lock class="w-3.5 h-3.5 text-red-400/60 shrink-0" />
                                    <span>Chat kreator VIP</span>
                                </li>
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="switchUserTier('PRO')"
                            :disabled="currentUserTier === 'PRO' || isUpgradingTier"
                            class="mt-4 w-full py-2 rounded-lg text-xs font-medium transition-all cursor-pointer disabled:opacity-40 disabled:cursor-default"
                            :class="[
                                currentUserTier === 'PRO'
                                    ? 'bg-[#1B44E8] text-white'
                                    : 'bg-[#1B44E8] text-white hover:bg-[#1537C2]'
                            ]"
                        >
                            {{ currentUserTier === 'PRO' ? 'Kasta Saat Ini' : 'Aktifkan Pro' }}
                        </button>
                    </div>

                    <!-- KASTA 3: VIP -->
                    <div
                        class="p-4 rounded-xl border flex flex-col justify-between transition-all relative overflow-hidden"
                        :class="[
                            currentUserTier === 'VIP'
                                ? 'bg-gradient-to-br from-[#242017] to-[#17181B] border-[#D4B67A]/60 shadow-md ring-1 ring-[#D4B67A]/40'
                                : 'bg-[#0E0F11]/60 border-[rgba(242,239,234,0.08)] hover:border-[#D4B67A]/40'
                        ]"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#D4B67A]/20 border border-[#D4B67A]/40 text-[#D4B67A]">
                                    VIP
                                </span>
                                <span v-if="currentUserTier === 'VIP'" class="text-[10px] text-[#D4B67A] font-medium">
                                    Aktif
                                </span>
                            </div>
                            <h4 class="text-sm font-semibold text-white">Patron VIP</h4>
                            <p class="text-[11px] text-[#8C8C90] mt-1 leading-snug">
                                Kasta tertinggi dengan hak akses tanpa batas.
                            </p>

                            <ul class="mt-3 space-y-1.5 text-[11px] text-[#8C8C90]">
                                <li class="flex items-center gap-1.5">
                                    <Check class="w-3.5 h-3.5 text-[#D4B67A] shrink-0" />
                                    <span>Chat SEMUA kreator (VIP, PRO, FREE)</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <Sparkles class="w-3.5 h-3.5 text-[#D4B67A] shrink-0" />
                                    <span>Prioritas balasan & diskusi eksklusif</span>
                                </li>
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="switchUserTier('VIP')"
                            :disabled="currentUserTier === 'VIP' || isUpgradingTier"
                            class="mt-4 w-full py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer disabled:opacity-40 disabled:cursor-default"
                            :class="[
                                currentUserTier === 'VIP'
                                    ? 'bg-[#D4B67A] text-[#0E0F11]'
                                    : 'bg-gradient-to-r from-[#D4B67A] to-[#F3DEB0] text-[#0E0F11] hover:opacity-95'
                            ]"
                        >
                            {{ currentUserTier === 'VIP' ? 'Kasta Saat Ini' : 'Aktifkan VIP' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    height: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: rgba(14, 15, 17, 0.6);
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: rgba(242, 239, 234, 0.15);
    border-radius: 2px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(242, 239, 234, 0.3);
}

/* 5s Carousel Progress Fill Keyframes */
@keyframes carousel-progress-fill {
    0% {
        width: 0%;
    }
    100% {
        width: 100%;
    }
}
.carousel-progress-bar {
    animation: carousel-progress-fill 5s linear forwards;
}

/* Spotlight Slide Text Crossfade */
.spotlight-slide-enter-active,
.spotlight-slide-leave-active {
    transition:
        opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1),
        transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
.spotlight-slide-enter-from {
    opacity: 0;
    transform: translateY(14px);
}
.spotlight-slide-leave-to {
    opacity: 0;
    transform: translateY(-14px);
}

/* Scroll reveal animations (triggered on both scroll up and scroll down) */
.scroll-reveal {
    opacity: 0;
    transform: translateY(28px);
    transition:
        opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1),
        transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}

.scroll-reveal.is-revealed {
    opacity: 1;
    transform: translateY(0);
}

.scroll-delay-1 {
    transition-delay: 60ms;
}
.scroll-delay-2 {
    transition-delay: 120ms;
}
.scroll-delay-3 {
    transition-delay: 180ms;
}
.scroll-delay-4 {
    transition-delay: 240ms;
}
.scroll-delay-5 {
    transition-delay: 300ms;
}
.scroll-delay-6 {
    transition-delay: 360ms;
}
</style>
