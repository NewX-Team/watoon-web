<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bookmark,
    BookmarkCheck,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Crown,
    LogOut,
    Play,
    Plus,
    Search,
    Settings,
    Star,
    X,
} from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types/auth';

const page = usePage<{ auth: { user: User } }>();
const currentUser = computed(() => page.props.auth?.user);

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
const watchModalFilm = ref<any | null>(null);
const myWatchlist = ref<number[]>([1, 4]); // default saved items
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
    )
        return;

    observer?.disconnect();
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                } else {
                    const rect = entry.boundingClientRect;
                    // Re-trigger reveal on both scroll up & down when out of viewport
                    if (rect.top > window.innerHeight || rect.bottom < 0) {
                        entry.target.classList.remove('is-revealed');
                    }
                }
            });
        },
        {
            threshold: 0.08,
            rootMargin: '0px 0px -40px 0px',
        },
    );

    document.querySelectorAll('.scroll-reveal').forEach((el) => {
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

// Filtered lists based on search & favorite tab
const matchesSearch = (title: string, genre: string) => {
    if (!searchQuery.value.trim()) return true;
    const q = searchQuery.value.toLowerCase();
    return title.toLowerCase().includes(q) || genre.toLowerCase().includes(q);
};

const filteredContinueWatching = computed(() =>
    continueWatching.filter((item) => {
        const matches = matchesSearch(item.title, item.genre);
        if (activeTab.value === 'favorite') {
            return matches && myWatchlist.value.includes(item.id);
        }
        return matches;
    }),
);

const filteredPopularWeekly = computed(() =>
    popularWeekly.filter((item) => {
        const matches = matchesSearch(item.title, item.genre);
        if (activeTab.value === 'favorite') {
            return matches && myWatchlist.value.includes(item.id);
        }
        return matches;
    }),
);

const filteredNewReleases = computed(() =>
    newReleases.filter((item) => {
        const matches = matchesSearch(item.title, item.genre);
        if (activeTab.value === 'favorite') {
            return matches && myWatchlist.value.includes(item.id);
        }
        return matches;
    }),
);

const filteredExploreFilms = computed(() =>
    exploreFilms.value.filter((item) => {
        const matches = matchesSearch(item.title, item.genre);
        if (activeTab.value === 'favorite') {
            return matches && myWatchlist.value.includes(item.id);
        }
        return matches;
    }),
);

// Toggle watchlist
const toggleWatchlist = (id: number) => {
    if (myWatchlist.value.includes(id)) {
        myWatchlist.value = myWatchlist.value.filter((x) => x !== id);
    } else {
        myWatchlist.value.push(id);
    }
};

const isBookmarked = (id: number) => myWatchlist.value.includes(id);

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
        watchModalFilm.value = null;
        isProModalOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('scroll', handleScroll, { passive: true });
    startCarouselTimer();
    nextTick(() => {
        setupScrollObserver();
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('scroll', handleScroll);
    stopCarouselTimer();
    observer?.disconnect();
});
</script>

<template>
    <div
        class="min-h-screen bg-[#0E0F11] font-sans text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white"
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
                        :href="dashboard()"
                        class="group flex items-center gap-2.5"
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
                            @click="activeTab = 'home'"
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
                            @click="activeTab = 'favorite'"
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
                            @click="isProModalOpen = true"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            Subscribe
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
                                placeholder="Search films…"
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

                    <!-- PRO Plan Badge -->
                    <button
                        type="button"
                        @click="isProModalOpen = true"
                        class="mr-4 flex h-[26px] cursor-pointer items-center justify-center rounded-[8px] border border-[rgba(27,68,232,0.35)] bg-[rgba(27,68,232,0.14)] px-[10px] text-[11px] font-semibold tracking-[0.14em] text-[#7A9BFF] uppercase transition-colors select-none hover:bg-[rgba(27,68,232,0.20)]"
                    >
                        PRO
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
                                    :href="edit()"
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
                                    :href="logout()"
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

        <main class="pt-28 pb-20">
            <div class="mx-auto max-w-[1280px] px-6 md:px-8">
                <!-- ACTIVE FILTER CHIP WHEN SEARCHING OR FAVORITING -->
                <div
                    v-if="searchQuery || activeTab === 'favorite'"
                    class="mb-6 flex items-center justify-between rounded-xl border border-[rgba(242,239,234,0.1)] bg-[#17181B] px-5 py-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-[#8C8C90]"
                            >Filter Aktif:</span
                        >
                        <span
                            v-if="activeTab === 'favorite'"
                            class="rounded-md bg-[#1B44E8]/20 px-2 py-0.5 text-xs font-medium text-[#7A9BFF]"
                        >
                            Koleksi Favorit
                        </span>
                        <span
                            v-if="searchQuery"
                            class="rounded-md bg-white/10 px-2 py-0.5 text-xs text-[#F2EFEA]"
                        >
                            "{{ searchQuery }}"
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="
                            searchQuery = '';
                            activeTab = 'home';
                        "
                        class="text-xs text-[#8C8C90] underline hover:text-[#F2EFEA]"
                    >
                        Reset Filter
                    </button>
                </div>

                <!-- [2] HERO FEATURE (Spotlight Auto-carousel 5s) -->
                <section
                    v-if="!searchQuery && activeTab === 'home'"
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
                                    {{ activeSpotlight.title }}
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
                                    <button
                                        type="button"
                                        @click="
                                            watchModalFilm = activeSpotlight
                                        "
                                        class="flex h-[42px] cursor-pointer items-center gap-2 rounded-[10px] bg-[#F2EFEA] px-5 text-[13px] font-medium text-[#0E0F11] shadow-[0_1px_2px_rgba(0,0,0,0.15)] transition-colors duration-150 hover:bg-white"
                                    >
                                        <Play class="h-4 w-4 fill-current" />
                                        <span>Watch now</span>
                                    </button>

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
                        <div
                            v-for="(item, idx) in filteredContinueWatching"
                            :key="item.id"
                            @click="watchModalFilm = item"
                            class="group w-[280px] flex-none cursor-pointer snap-start transition-transform duration-300 hover:-translate-y-1"
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
                        </div>
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
                        <div
                            v-for="(item, idx) in filteredPopularWeekly"
                            :key="item.id"
                            @click="watchModalFilm = item"
                            class="scroll-reveal group cursor-pointer transition-all duration-300 hover:-translate-y-1.5"
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
                        </div>
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
                        <div
                            v-for="(item, idx) in filteredNewReleases"
                            :key="item.id"
                            @click="watchModalFilm = item"
                            class="scroll-reveal group cursor-pointer transition-all duration-300 hover:-translate-y-1.5"
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
                        </div>
                    </div>
                </section>

                <!-- [6] JELAJAHI FILM (3 Rows of 6 Columns = 18 Posters) -->
                <section id="explore-section" class="mb-16">
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
                        <div
                            v-for="(item, idx) in filteredExploreFilms"
                            :key="item.id"
                            @click="watchModalFilm = item"
                            class="scroll-reveal group cursor-pointer transition-all duration-300 hover:-translate-y-1.5"
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
                        </div>
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
            </div>
        </main>

        <!-- WATCH / PREVIEW MODAL -->
        <div
            v-if="watchModalFilm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
            @click.self="watchModalFilm = null"
        >
            <div
                class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] shadow-2xl"
            >
                <div
                    class="relative aspect-video w-full overflow-hidden bg-black"
                >
                    <img
                        :src="watchModalFilm.image"
                        :alt="watchModalFilm.title"
                        class="h-full w-full object-cover opacity-80"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-[#17181B] via-transparent to-transparent"
                    ></div>
                    <button
                        type="button"
                        @click="watchModalFilm = null"
                        class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-black/60 text-white transition-colors hover:bg-black/90"
                    >
                        <X class="h-4 w-4" />
                    </button>
                    <div
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full border border-white/20 bg-[#1B44E8] text-white shadow-xl transition-transform hover:scale-110"
                        >
                            <Play class="ml-1 h-7 w-7 fill-current" />
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-semibold text-white">
                                {{ watchModalFilm.title }}
                            </h2>
                            <p class="mt-1 text-xs text-[#8C8C90]">
                                {{ watchModalFilm.year }} ·
                                {{ watchModalFilm.genre || 'Film Sinematik' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="toggleWatchlist(watchModalFilm.id)"
                            class="flex items-center gap-1.5 rounded-lg border border-[rgba(242,239,234,0.15)] bg-white/5 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white/10"
                        >
                            <BookmarkCheck
                                v-if="isBookmarked(watchModalFilm.id)"
                                class="h-4 w-4 text-[#1B44E8]"
                            />
                            <Bookmark v-else class="h-4 w-4" />
                            <span>{{
                                isBookmarked(watchModalFilm.id)
                                    ? 'Tersimpan'
                                    : 'Simpan'
                            }}</span>
                        </button>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-[#8C8C90]">
                        {{
                            watchModalFilm.synopsis ||
                            'Tayangan sinematik eksklusif Watoon dengan visual 4K HDR dan komposisi audio beresolusi tinggi.'
                        }}
                    </p>

                    <div
                        class="mt-6 flex items-center justify-end gap-3 border-t border-[rgba(242,239,234,0.08)] pt-4"
                    >
                        <button
                            type="button"
                            @click="watchModalFilm = null"
                            class="rounded-lg border border-[rgba(242,239,234,0.15)] px-4 py-2 text-xs font-medium text-[#F2EFEA] hover:bg-white/5"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-[#1B44E8] px-5 py-2 text-xs font-medium text-white hover:bg-[#1B44E8]/90"
                        >
                            <Play class="h-3.5 w-3.5 fill-current" />
                            <span>Mulai Memutar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- WATOON PRO MODAL -->
        <div
            v-if="isProModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
            @click.self="isProModalOpen = false"
        >
            <div
                class="relative w-full max-w-md rounded-2xl border border-[rgba(27,68,232,0.3)] bg-[#17181B] p-6 shadow-2xl"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1B44E8]/20 text-[#7A9BFF]"
                        >
                            <Crown class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                WATOON PRO
                            </h3>
                            <p class="text-xs text-[#8C8C90]">
                                Akses Penuh Film & Sastra Sinematik
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isProModalOpen = false"
                        class="text-[#8C8C90] hover:text-white"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span
                            >Katalog film kurasi independen tanpa batas (4K
                            HDR)</span
                        >
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span>Akses awal novel bersambung bab per bab</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span>Bebas gangguan iklan dan algoritma feed</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span
                            >Dukungan langsung kepada sineas dan kreator
                            lokal</span
                        >
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border border-[rgba(242,239,234,0.08)] bg-black/40 p-4 text-center"
                >
                    <div class="text-2xl font-bold text-white">
                        Rp 49.000<span
                            class="text-xs font-normal text-[#8C8C90]"
                        >
                            / bulan</span
                        >
                    </div>
                    <p class="mt-1 text-[11px] text-[#8C8C90]">
                        Bisa dibatalkan kapan saja tanpa komitmen jangka panjang
                    </p>
                </div>

                <button
                    type="button"
                    @click="isProModalOpen = false"
                    class="mt-5 w-full rounded-xl bg-[#1B44E8] py-2.5 text-center text-xs font-semibold text-white transition-opacity hover:opacity-95"
                >
                    Mulai Langganan Pro
                </button>
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
