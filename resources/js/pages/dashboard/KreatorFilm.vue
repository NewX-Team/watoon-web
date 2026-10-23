<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import {
    Plus,
    Bell,
    ChevronDown,
    ChevronRight,
    MessageSquare,
    UserPlus,
    Star,
    Film,
    Clapperboard,
    Wallet,
    UploadCloud,
    BarChart3,
    X,
    ExternalLink,
    BookOpen,
    Check,
    LogOut,
    Settings,
    ArrowUpRight,
    Search,
    List,
    Grid,
    MoreHorizontal,
    Edit,
    Share2,
    Copy,
    Trash2,
    Calendar,
    Loader2,
    CheckSquare,
    Square,
    Play,
    Eye,
    TrendingUp,
    Clock,
    DollarSign,
    Sparkles,
} from '@lucide/vue';
import { dashboard, logout } from '@/routes';
import type { Auth } from '@/types/auth';

const props = withDefaults(
    defineProps<{
        creatorType?: string;
        initialTab?: string;
    }>(),
    {
        creatorType: 'film',
        initialTab: 'dashboard',
    }
);

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth?.user);

const userName = computed(() => user.value?.name || 'Rendra');
const userInitials = computed(() => {
    const name = userName.value.trim();
    const parts = name.split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase() || 'CR';
});

// Navigation state: 'dashboard' | 'content' | 'analytics' | 'earnings'
const activeTab = ref<'dashboard' | 'content' | 'analytics' | 'earnings'>(
    props.initialTab === 'content' ? 'content' : 'dashboard'
);

const switchTab = (tab: 'dashboard' | 'content' | 'analytics' | 'earnings') => {
    activeTab.value = tab;
    // Keep URL synchronized
    const url = new URL(window.location.href);
    if (tab === 'dashboard') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }
    window.history.replaceState({}, '', url.toString());
};

// Date Range Filter state for Overview Dashboard
const timeRange = ref<'7D' | '30D' | '90D' | 'All'>('30D');
const isDateDropdownOpen = ref(false);
const selectedPeriodLabel = computed(() => {
    switch (timeRange.value) {
        case '7D':
            return 'Last 7 days';
        case '30D':
            return 'Last 30 days';
        case '90D':
            return 'Last 90 days';
        case 'All':
            return 'All time';
    }
});

// Interactive chart hover state
const hoveredPoint = ref<{
    x: number;
    y: number;
    date: string;
    views: string;
} | null>({
    x: 720,
    y: 45,
    date: '18 Oct 2024',
    views: '18,420 views',
});

// Chart data sets
const chartDataSets = {
    '7D': {
        points: '0,210 160,180 320,160 480,120 640,90 800,60 1000,45',
        poly: '0,239 0,210 160,180 320,160 480,120 640,90 800,60 1000,45 1000,239',
        hoverX: 800,
        hoverY: 60,
        hoverDate: 'Yesterday',
        hoverViews: '19,100 views',
        timeline: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Today'],
    },
    '30D': {
        points: '0,180 80,170 160,190 240,140 320,150 400,110 480,125 560,90 640,95 720,45 800,70 880,50 960,35 1000,40',
        poly: '0,239 0,180 80,170 160,190 240,140 320,150 400,110 480,125 560,90 640,95 720,45 800,70 880,50 960,35 1000,40 1000,239',
        hoverX: 720,
        hoverY: 45,
        hoverDate: '18 Oct 2024',
        hoverViews: '18,420 views',
        timeline: ['24 Sep', '1 Oct', '8 Oct', '15 Oct', '22 Oct', 'Today'],
    },
    '90D': {
        points: '0,200 150,170 300,160 450,130 600,100 750,80 900,55 1000,30',
        poly: '0,239 0,200 150,170 300,160 450,130 600,100 750,80 900,55 1000,30 1000,239',
        hoverX: 750,
        hoverY: 80,
        hoverDate: '12 Sep 2024',
        hoverViews: '16,890 views',
        timeline: ['Jul', 'Aug', 'Sep', 'Oct', 'Today'],
    },
    All: {
        points: '0,220 200,180 400,140 600,90 800,50 1000,25',
        poly: '0,239 0,220 200,180 400,140 600,90 800,50 1000,25 1000,239',
        hoverX: 800,
        hoverY: 50,
        hoverDate: '2024 YTD',
        hoverViews: '248,320 views',
        timeline: ['2023 Q4', '2024 Q1', '2024 Q2', '2024 Q3', 'Today'],
    },
};

const currentChart = computed(() => chartDataSets[timeRange.value]);

const selectTimeRange = (range: '7D' | '30D' | '90D' | 'All') => {
    timeRange.value = range;
    isDateDropdownOpen.value = false;
    hoveredPoint.value = {
        x: currentChart.value.hoverX,
        y: currentChart.value.hoverY,
        date: currentChart.value.hoverDate,
        views: currentChart.value.hoverViews,
    };
};

// UI Modals & Popover state
const isUploadModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isPayoutModalOpen = ref(false);
const isProfileMenuOpen = ref(false);
const isNotificationsOpen = ref(false);
const toastMessage = ref('');

const triggerToast = (msg: string) => {
    toastMessage.value = msg;
    setTimeout(() => {
        toastMessage.value = '';
    }, 2800);
};

// ==========================================
// CONTENT LIBRARY STATE & DATA
// ==========================================
type FilmStatus = 'published' | 'draft' | 'scheduled' | 'processing';

type FilmItem = {
    id: number;
    title: string;
    type: string;
    genre: string;
    year: string;
    episodes?: string;
    duration: string;
    status: FilmStatus;
    statusLabel: string;
    scheduledDate?: string;
    progress?: number;
    views: number;
    viewsFormatted: string;
    revenue: number;
    revenueFormatted: string;
    thumb: string;
    poster: string;
    synopsis: string;
    lastEdited?: string;
};

const initialFilms: FilmItem[] = [
    {
        id: 1,
        title: 'Cakrawala Senja',
        type: 'Series',
        genre: 'Thriller',
        year: '2025',
        episodes: '6 episodes',
        duration: '02:14:10',
        status: 'published',
        statusLabel: 'Published',
        views: 84210,
        viewsFormatted: '84.2K',
        revenue: 1200000,
        revenueFormatted: 'Rp 1.2M',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC2iFQda1gck8tnFtgLcvBhIoi3mCUJ8tErv5lvfYCnmTOIX_zZhHJ8_t-DhH7VYkeGQrMHgmLYr2sG-lQx-NnwQtLYKkI20vqDbf-gj9pkx0Rm1v1A35kAOSoC8CnFrnHwLnIe08Tw22M16TlSenPB2GrkJ62f922r4vaCoh1a-zeNmbANT354gxdNNAnUSS0OzBIMJ8OPqRW8R9st-7ZifSdGV13SSqGa-AuM583CgRmC2jn5Y3Tt',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDPxLBNomo_8NsqFuOuX7TWY_G5xdJDboeIhADolyZj1zm4SKjfvPM_Ar14rStAt8MpSbGx1vW3y2xt6BDqJ6hBUUR9d6S8z4zjLJZIFn_FXoUwMB6mDgMGVsXByIWlqRokL526Zix-8M25R4eb9SitAVvQICjievPjaCawe81Js5keTzqL7JYVtVwWC0PBLTtzmAoPQNk2SLXh8uDVBY1Im6raAr59Hgxjd59bChLMBzTTHkLUGdF4',
        synopsis: 'Sebuah paviliun modern di tepi tebing curam menjadi saksi bisu pertemuan dua kurator yang menelusuri rekaman masa lalu.',
    },
    {
        id: 2,
        title: 'Detik Terakhir',
        type: 'Series',
        genre: 'Mystery',
        year: '2025',
        episodes: '4 episodes',
        duration: '01:48:32',
        status: 'published',
        statusLabel: 'Published',
        views: 41820,
        viewsFormatted: '41.8K',
        revenue: 680000,
        revenueFormatted: 'Rp 680K',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDN3BaTBRDSpxdru6vmo8RdhPLDUWLZazNTq3ez5A_IDePp7IsbTEuKPFneUFc4RqYTDK5FgyU8wuHJ16AGloMJnU9qiGGTZHL3cQBkcox6spIdMAatsoL9-Bte-wquPu73nFIEjQ8ECvPUH7g0MHYlDsFjWyXIzS8Bf2RcxOmQY24bmYKLIB37xfYpdfe_Muaif4rTyJhGwL7bqDUT91Bzgi8SrhYOMfjKo3QkADcTHEwgIjsq3zdy',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA_FtjJzkCS3LfaZqfAW7jI3Z9AjFit5g0r0Resw0E6BltW2HZmLY01op10uZ8RJbnxoAOmaaJSSykIaNcfoUVVe_Ko6CkZb-ONaCsbOdGAz0R3k0xAn0G_pnHmgJ1JsjHJV0VkDi9tQ39of4qvgOK1ec_Wb7xYFuJCYnvMyfUnUSamqHP9GlKGy5Vf-7pwiKrWdXx0EJOoX2HVUJ1M7xfKr9pe4Ph8B4xi9SN1YLysDz_jKHeWcyPs',
        synopsis: 'Neo-noir atmosferik tentang seorang detektif yang memecahkan teka-teki pembunuhan berantai di tengah hujan metropolitan.',
    },
    {
        id: 3,
        title: 'Rumah di Ujung Jalan',
        type: 'Series',
        genre: 'Drama',
        year: '2024',
        episodes: '8 episodes',
        duration: '03:02:18',
        status: 'published',
        statusLabel: 'Published',
        views: 52440,
        viewsFormatted: '52.4K',
        revenue: 910000,
        revenueFormatted: 'Rp 910K',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDodt4mPLA2P_RkmDvUvUruHiGlgW1Y4jx7aSy47LbFT6xLRTdPGbTLoBUXZT739-Y9xqorzk6RZOOYuYjAcW3LoNOV0oCMIgt2_TCgmkxQPZlhXsjWyMgZk5h1GLRJWHyc3Et1hN3olE1hbch1G7-eroTfvmZ_dsn0rd7dxnnc7bzdhkN7N6-yCzj269tF4f0C0EHN0yIIyHgOgwCta69YrLo8ZOTMDw-m3zkjmCPA7JuvGYHfZ2OE',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCVLheXaC9IAFPuCbpnUsKollqqkQEpY3dOuUhiEgq9-7S-dMY_p0jbCh6-ob3sKuIvpomwJ-NaHQUR9pGbTogqRu5MK2Niz29R1wLepy-4d6Lr4XuwFjZ9TWWmt4DciK4ciyR9bMIUlWwOdc0YOT8f1VaK0gBaz33dS3-awhC8E8wGZHOvWvMZHNcnofHCYf1XG0Wz12IdUTZCa1SLYBkq71hiyM9DeHaRlVWKhf2coirG1TIWgWrx',
        synopsis: 'Sebuah vila tua di kelokan bukit berkabut menyimpan rahasia antar generasi keluarga seniman terkemuka.',
    },
    {
        id: 4,
        title: 'Bayang Semu',
        type: 'Feature Film',
        genre: 'Psychological Thriller',
        year: '2025',
        duration: '01:25:00',
        status: 'draft',
        statusLabel: 'Draft',
        lastEdited: 'Draft · Last edited 2h ago',
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDv8zKp89Ba98n4iesC0SGSlW85bMHD9YG4DsAChN45Clfkf1ZHJs_EXyvgB6eGqikCx0sA8X-rDmeiZMRgrVdH4f5zgD3AkkYT12lle0WSqv-7YRUqGAsffviSCUJHFGCLqxsFPu96NIj-OUDmxLgBqFX5JrL6ptJDiO42MYVP03gPu682thMHXNwdnDUUcTf497l-v7CF1Tn4jvPHo3sS3WEXx91zObfv9CtDx3INEigTlcOnAY7d',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCNRB7XvSei3KWYMX3gNXuHFg9_ttujmorBjYDH21JL6rYrI9zawflhqc9-qYo55uVJCESM5rMPtu4YTWeGRf8OaqmqMDRgQozvPLP-NFLp7UnzD4JvajXWSUyEv2bEids1YJpxZanuPUSIDT80qWw-cqMY1EyYYU2I2TrIy01XZll5YYPL7R2IebnN_hMZHKm0Nwe1sQXRMTIU78IsNK-3KkiSIC7x5vovBZEGgV5Gw_iSg1cDKoBw',
        synopsis: 'Eksplorasi optik dan rekaman pita seluloid 35mm yang merekam entitas bayangan tak dikenal di ruang gelap studio.',
    },
    {
        id: 5,
        title: 'Langit Kedua',
        type: 'Feature Film',
        genre: 'Drama Cinema',
        year: '2025',
        duration: '02:05:40',
        status: 'scheduled',
        statusLabel: 'Scheduled',
        scheduledDate: 'Scheduled for 24 Oct, 20:00',
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDDl4rM1NnbL0SOoZoFXzWHTAhNo8O-l51TOvVrf3RJRvcnnpqqbKlPzC_q53n_fueppOrZtSXSiHAvqWJLvRZooHAG4PJIS56Q5b6lGkucj90YPUS-p_MrgQP5QgvKN9jAE5rIx7XMaAQTt4A7t5ik7G8_8X3E_qPHGkbvsGq4UIcuyvk4P0PHCPy-bdP1pLA8xYcperryblqlv59oat2YlJP7lFbkXIxRwZo8n_pS0iJpbQGhGmtM',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAMSCJ3-4ZFwXMw8fGiCyoEw7NCLd14tXBVDufA44sH3PVHW9fNQADF_Buz1yvxIkeIOG5u2rcbpapxh-mNLnXgAVe9B2VhWv9Um2zu-sDLEJUJChPCGNPcE-81NlXDIltJ3JJVVUiYZGvcqbgtkpJjB2Nbyo5RQGpK_4oYJX9FxAoKu7DhX3iRJ3V-EnvBmLmbK40nFR1yD8lFP3r0dIkOJhARpBw817LRT1MIblwAyv4bsmQA-fPr',
        synopsis: 'Pemandangan senja pesisir berbatu dengan arsitektur hening, merefleksikan perpisahan emosional dua insan pembuat film.',
    },
    {
        id: 6,
        title: 'Sunyi',
        type: 'Short Film',
        genre: 'Experimental Noir',
        year: '2025',
        duration: '01:15:20',
        status: 'processing',
        statusLabel: 'Processing',
        progress: 68,
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBIeDNufrQDI1tirsHT2WHdu7z-0pvjiiAdDpWwTV5YhjGn9yzCOIhDHpVtbIGtHLHF3KdKIO6VYrJFY-qHrAV4tf_1hPjP7kPw9gqxUKrRoEKr794JFR9Tkt6kX91dJtRFIXAkDZLiOMJdJcxmQAIiUimWMB3qWigsfqliF80uEv8eqSVU61PGIhNyVT2y9Me0tp-54_dXs9pIpgT1DD__u4qnplHD3EAt6s1EjWaaO_2YBxpGZZep',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPJZ3HaMzt88VuI8vveBpWUJoKNTWgqhdO9meVjJbOHb6zYz9cYXzl4K44CvvkxCc5gYDMM5m0aoRfIDvhXOUOSlaRTzanr1qwTqDM-am9HkiVvyKQfVQOU2cYu3uaf1c8X8h6uhzDTAifeg2SQfJft6K3IvDn1jRlha5bcJWMKF1hdRiSuqceW-PfM84956wFZS5RMBTVCRAidanh5P0lzHw1w4VAlRV8sOOO1pnWBjy-9ic8e-x3',
        synopsis: 'Eksperimen audio visual di aula kosong dengan tata cahaya terarah, mengeksplorasi gema keheningan batin.',
    },
    {
        id: 7,
        title: 'Senja di Kota Tua',
        type: 'Documentary',
        genre: 'Retrospective',
        year: '2024',
        duration: '01:42:15',
        status: 'published',
        statusLabel: 'Published',
        views: 32150,
        viewsFormatted: '32.1K',
        revenue: 510000,
        revenueFormatted: 'Rp 510K',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA5tQEyjRo0lPH_wh7HlnHZzBJMIHn5vy5CJ9YGXu0ZU5l4C1x080xIErtEGrDsj80hzZPrtJs70V_73f1kgDkEg6-ozuzpAZd7h-73033Uis65ZpY8gHI6mkQjE-V7vR7vUUgfIvpmODOTUNE1eTQTbiawhIlpexC47hFqnwYnrVtBaMz2zQIVQKwXfA3A50nRGh2bCH8RD3Jch0II3_MHd8vOWYS7Rf1Iqfv4ujcSeXfzD-5zq_EZ',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
        synopsis: 'Dokumenter retrospektif tentang denyut dermaga pelabuhan tua dan sejarah maritim yang mulai terlupakan.',
    },
    {
        id: 8,
        title: 'The Quiet Archipelago',
        type: 'Feature Film',
        genre: 'Drama / Mystery',
        year: '2024',
        duration: '01:48:00',
        status: 'published',
        statusLabel: 'Published',
        views: 28500,
        viewsFormatted: '28.5K',
        revenue: 460000,
        revenueFormatted: 'Rp 460K',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB2dzXB12HNvw1e9ezUXYpdNA0s5yLtyBahoSktTTbcSEtSeb0Xh92V63MsChwWyZoEzIP-Suo-62wg_kL03t1u9PyHMPTsx_j4lRFHTbLONSO1zXgqgjKSiCIrxozzNR0eeIZDalqogJ18c8Bf1EQj_jRWdlY3DXKbZvgSfDe-v5gDJRluN2BCKaxgdo_PRJfKwgdBeNg1hsh9DIO7SpolItM1_NB4C29rDJbYQ-KMJD-5cAzR-K3z',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbymq42E2as9jVxukwfG_XSzIeuTXeDPC53gRNTHhqbVSIXQLTwvFf6NE0qs8a5kO78jXHKfmEIRgw4JZZwIkzxPi-c_sPgltCJNOIXR_PCVyGl-O69ntD1Unq7MzV6JxdP7_ryPTuPsriznJ_EWYUO-_TJKbX38gPpGHoG_6tJRJw1Kl__HVKcmhmF3oylSyw9HUL5hEieT9F1oL5Qzk6ExxqfximmbzPE9xbwqZJxq3EMEyGpz44',
        synopsis: 'Arsiparis suara yang mengumpulkan rekaman gelombang di pesisir utara mengungkap anomali akustik kuno.',
    },
    {
        id: 9,
        title: 'The Archivist',
        type: 'Feature Film',
        genre: 'Noir Thriller',
        year: '2024',
        duration: '01:35:10',
        status: 'published',
        statusLabel: 'Published',
        views: 22000,
        viewsFormatted: '22.0K',
        revenue: 350000,
        revenueFormatted: 'Rp 350K',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
        synopsis: 'Pita seluloid tanpa label di ruang arsip tua menyingkap sejarah kelam sebuah konspirasi kota tua.',
    },
    {
        id: 10,
        title: 'Cold Meridian',
        type: 'Feature Film',
        genre: 'Maritime Drama',
        year: '2024',
        duration: '02:04:00',
        status: 'published',
        statusLabel: 'Published',
        views: 19300,
        viewsFormatted: '19.3K',
        revenue: 310000,
        revenueFormatted: 'Rp 310K',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn',
        synopsis: 'Pelayaran kapal kargo tua menembus fjord beku di samudra Atlantik Utara.',
    },
    {
        id: 11,
        title: 'Garis Pantai',
        type: 'Short Film',
        genre: 'Poetic Realism',
        year: '2025',
        duration: '00:54:10',
        status: 'draft',
        statusLabel: 'Draft',
        lastEdited: 'Draft · Edited yesterday',
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuASTnYlwz-XgSy_4rEBeFRgHFycKZ6Sb7onmQDNXpXtfMOqJF94be_tDc3scYnOCMNmigYXBZbuC57Nvbgf6RT7YuY835qD21wqjINTUZjgJnqXYMySflkC-SgDSlkZAbR-KGTuqK6WbEZ_O8FBh8bnk3Xdt3RYAKKZ6y7MJ8YRfC6pl5J0UTm_x4m3M258qwt1d9V1aOW9Lgmg3J0tuu2MYGhFcrbubhMC0Yh-kAotPXi699bk8onP',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuASTnYlwz-XgSy_4rEBeFRgHFycKZ6Sb7onmQDNXpXtfMOqJF94be_tDc3scYnOCMNmigYXBZbuC57Nvbgf6RT7YuY835qD21wqjINTUZjgJnqXYMySflkC-SgDSlkZAbR-KGTuqK6WbEZ_O8FBh8bnk3Xdt3RYAKKZ6y7MJ8YRfC6pl5J0UTm_x4m3M258qwt1d9V1aOW9Lgmg3J0tuu2MYGhFcrbubhMC0Yh-kAotPXi699bk8onP',
        synopsis: 'Kisah sunyi di perkampungan nelayan yang terancam abrasi pasang surut air laut.',
    },
    {
        id: 12,
        title: 'Noktah Merah',
        type: 'Feature Film',
        genre: 'Psychological Noir',
        year: '2025',
        duration: '01:10:00',
        status: 'draft',
        statusLabel: 'Draft',
        lastEdited: 'Draft · Edited 3 days ago',
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDodt4mPLA2P_RkmDvUvUruHiGlgW1Y4jx7aSy47LbFT6xLRTdPGbTLoBUXZT739-Y9xqorzk6RZOOYuYjAcW3LoNOV0oCMIgt2_TCgmkxQPZlhXsjWyMgZk5h1GLRJWHyc3Et1hN3olE1hbch1G7-eroTfvmZ_dsn0rd7dxnnc7bzdhkN7N6-yCzj269tF4f0C0EHN0yIIyHgOgwCta69YrLo8ZOTMDw-m3zkjmCPA7JuvGYHfZ2OE',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDodt4mPLA2P_RkmDvUvUruHiGlgW1Y4jx7aSy47LbFT6xLRTdPGbTLoBUXZT739-Y9xqorzk6RZOOYuYjAcW3LoNOV0oCMIgt2_TCgmkxQPZlhXsjWyMgZk5h1GLRJWHyc3Et1hN3olE1hbch1G7-eroTfvmZ_dsn0rd7dxnnc7bzdhkN7N6-yCzj269tF4f0C0EHN0yIIyHgOgwCta69YrLo8ZOTMDw-m3zkjmCPA7JuvGYHfZ2OE',
        synopsis: 'Misteri tinta merah tak terhapus pada naskah skenario film adaptasi novel klasik.',
    },
];

const allFilms = ref<FilmItem[]>([...initialFilms]);

// Content filter & search state
const contentStatusFilter = ref<'all' | 'published' | 'draft' | 'scheduled' | 'processing'>('all');
const searchQuery = ref('');
const sortBy = ref<'recently_updated' | 'most_viewed' | 'highest_revenue' | 'alphabetical'>('recently_updated');
const isSortDropdownOpen = ref(false);
const viewMode = ref<'list' | 'grid'>('list');

// Bulk Selection state
const selectedFilmIds = ref<number[]>([1, 2, 3]);

const isAllSelected = computed(() => {
    return (
        displayedFilms.value.length > 0 &&
        displayedFilms.value.every((f) => selectedFilmIds.value.includes(f.id))
    );
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedFilmIds.value = [];
    } else {
        selectedFilmIds.value = displayedFilms.value.map((f) => f.id);
    }
};

const toggleSelectFilm = (id: number) => {
    const idx = selectedFilmIds.value.indexOf(id);
    if (idx >= 0) {
        selectedFilmIds.value.splice(idx, 1);
    } else {
        selectedFilmIds.value.push(id);
    }
};

// Filtered & Sorted Films
const filteredFilms = computed(() => {
    let result = allFilms.value;

    // Filter by status tab
    if (contentStatusFilter.value !== 'all') {
        result = result.filter((f) => f.status === contentStatusFilter.value);
    }

    // Filter by search query
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(
            (f) =>
                f.title.toLowerCase().includes(q) ||
                f.genre.toLowerCase().includes(q) ||
                f.type.toLowerCase().includes(q) ||
                f.year.includes(q)
        );
    }

    // Sort
    return [...result].sort((a, b) => {
        if (sortBy.value === 'most_viewed') return b.views - a.views;
        if (sortBy.value === 'highest_revenue') return b.revenue - a.revenue;
        if (sortBy.value === 'alphabetical') return a.title.localeCompare(b.title);
        return b.id - a.id;
    });
});

// Pagination / Load more
const displayLimit = ref(6);
const displayedFilms = computed(() => filteredFilms.value.slice(0, displayLimit.value));

const loadMore = () => {
    displayLimit.value = Math.min(allFilms.value.length, displayLimit.value + 6);
};

// Row Action Dropdown state
const activeDropdownFilmId = ref<number | null>(null);

const toggleRowDropdown = (id: number) => {
    activeDropdownFilmId.value = activeDropdownFilmId.value === id ? null : id;
};

// Bulk Actions
const bulkPublish = () => {
    allFilms.value.forEach((f) => {
        if (selectedFilmIds.value.includes(f.id)) {
            f.status = 'published';
            f.statusLabel = 'Published';
        }
    });
    triggerToast(`${selectedFilmIds.value.length} film berhasil dipublikasikan.`);
};

const bulkMoveToDraft = () => {
    allFilms.value.forEach((f) => {
        if (selectedFilmIds.value.includes(f.id)) {
            f.status = 'draft';
            f.statusLabel = 'Draft';
        }
    });
    triggerToast(`${selectedFilmIds.value.length} film dipindahkan ke Draf.`);
};

const bulkDelete = () => {
    const count = selectedFilmIds.value.length;
    allFilms.value = allFilms.value.filter((f) => !selectedFilmIds.value.includes(f.id));
    selectedFilmIds.value = [];
    triggerToast(`${count} film berhasil dihapus dari library.`);
};

const cancelSelection = () => {
    selectedFilmIds.value = [];
};

// Row Single Actions
const activeEditFilm = ref<FilmItem | null>(null);

const openEditModal = (film: FilmItem) => {
    activeEditFilm.value = { ...film };
    activeDropdownFilmId.value = null;
    isEditModalOpen.value = true;
};

const saveEditFilm = () => {
    if (!activeEditFilm.value) return;
    const idx = allFilms.value.findIndex((f) => f.id === activeEditFilm.value!.id);
    if (idx >= 0) {
        allFilms.value[idx] = { ...activeEditFilm.value };
    }
    isEditModalOpen.value = false;
    triggerToast(`Perubahan pada "${activeEditFilm.value.title}" berhasil disimpan.`);
};

const duplicateFilm = (film: FilmItem) => {
    activeDropdownFilmId.value = null;
    const newFilm: FilmItem = {
        ...film,
        id: Date.now(),
        title: `${film.title} (Copy)`,
        status: 'draft',
        statusLabel: 'Draft',
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        lastEdited: 'Draft · Duplicated just now',
    };
    allFilms.value.unshift(newFilm);
    triggerToast(`Film "${film.title}" berhasil diduplikasi sebagai draf.`);
};

const shareFilm = (film: FilmItem) => {
    activeDropdownFilmId.value = null;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(`${window.location.origin}/films/${film.id}`);
    }
    triggerToast(`Tautan film "${film.title}" disalin ke papan klip.`);
};

const deleteSingleFilm = (film: FilmItem) => {
    activeDropdownFilmId.value = null;
    allFilms.value = allFilms.value.filter((f) => f.id !== film.id);
    selectedFilmIds.value = selectedFilmIds.value.filter((id) => id !== film.id);
    triggerToast(`Film "${film.title}" telah dihapus.`);
};

// Dynamic Count Stats for Tabs
const countAll = computed(() => allFilms.value.length);
const countPublished = computed(() => allFilms.value.filter((f) => f.status === 'published').length);
const countDrafts = computed(() => allFilms.value.filter((f) => f.status === 'draft').length);
const countScheduled = computed(() => allFilms.value.filter((f) => f.status === 'scheduled').length);
const countProcessing = computed(() => allFilms.value.filter((f) => f.status === 'processing').length);

// Status Chip Styling Helper
const getStatusChipClass = (status: FilmStatus) => {
    switch (status) {
        case 'published':
            return 'bg-[#7A9BFF]/15 border border-[#7A9BFF]/30 text-[#7A9BFF]';
        case 'draft':
            return 'bg-[#A8A8AC]/15 border border-[#A8A8AC]/30 text-[#A8A8AC]';
        case 'scheduled':
            return 'bg-[#D4B67A]/15 border border-[#D4B67A]/30 text-[#D4B67A]';
        case 'processing':
            return 'bg-[#8C8C90]/15 border border-[#8C8C90]/30 text-[#8C8C90]';
    }
};

// Film Upload Form state
const uploadForm = ref({
    title: '',
    type: 'Feature Film',
    genre: 'Drama / Mystery',
    synopsis: '',
    duration: '',
});

const submitUpload = () => {
    if (!uploadForm.value.title.trim()) return;
    const newFilm: FilmItem = {
        id: Date.now(),
        title: uploadForm.value.title,
        type: uploadForm.value.type,
        genre: uploadForm.value.genre,
        year: '2025',
        duration: uploadForm.value.duration || '01:30:00',
        status: 'draft',
        statusLabel: 'Draft',
        lastEdited: 'Draft · Baru diunggah',
        views: 0,
        viewsFormatted: '—',
        revenue: 0,
        revenueFormatted: '—',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC2iFQda1gck8tnFtgLcvBhIoi3mCUJ8tErv5lvfYCnmTOIX_zZhHJ8_t-DhH7VYkeGQrMHgmLYr2sG-lQx-NnwQtLYKkI20vqDbf-gj9pkx0Rm1v1A35kAOSoC8CnFrnHwLnIe08Tw22M16TlSenPB2GrkJ62f922r4vaCoh1a-zeNmbANT354gxdNNAnUSS0OzBIMJ8OPqRW8R9st-7ZifSdGV13SSqGa-AuM583CgRmC2jn5Y3Tt',
        poster: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDPxLBNomo_8NsqFuOuX7TWY_G5xdJDboeIhADolyZj1zm4SKjfvPM_Ar14rStAt8MpSbGx1vW3y2xt6BDqJ6hBUUR9d6S8z4zjLJZIFn_FXoUwMB6mDgMGVsXByIWlqRokL526Zix-8M25R4eb9SitAVvQICjievPjaCawe81Js5keTzqL7JYVtVwWC0PBLTtzmAoPQNk2SLXh8uDVBY1Im6raAr59Hgxjd59bChLMBzTTHkLUGdF4',
        synopsis: uploadForm.value.synopsis || 'Naskah karya sinematik WATOON.',
    };
    allFilms.value.unshift(newFilm);
    isUploadModalOpen.value = false;
    triggerToast(`Film "${uploadForm.value.title}" berhasil diunggah ke pustaka studio.`);
    uploadForm.value = {
        title: '',
        type: 'Feature Film',
        genre: 'Drama / Mystery',
        synopsis: '',
        duration: '',
    };
};

const submitPayout = () => {
    isPayoutModalOpen.value = false;
    triggerToast('Permintaan penarikan Rp 4.240.000 sedang diproses ke rekening Anda.');
};

const handleLogout = () => {
    router.post(logout.url());
};
</script>

<template>
    <div class="min-h-screen bg-[#0E0F11] text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white pb-24">
        <Head :title="activeTab === 'content' ? 'FlixRead Creator Studio — Film Library' : 'FlixRead Creator Studio — Film Dashboard'" />

        <!-- Toast Feedback Notification -->
        <transition
            enter-active-class="transform ease-out duration-200 transition"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl border border-white/15 bg-[#17181B] px-4 py-3 shadow-2xl text-xs font-medium text-[#F2EFEA]"
            >
                <Sparkles class="h-4 w-4 text-[#7A9BFF]" />
                <span>{{ toastMessage }}</span>
            </div>
        </transition>

        <!-- ============================================================== -->
        <!-- [1] CREATOR FLOATING DETACHED NAVBAR                           -->
        <!-- ============================================================== -->
        <header
            class="sticky top-4 z-40 mx-auto flex w-[calc(100%-2rem)] max-w-7xl items-center justify-between rounded-xl border border-white/[0.08] bg-[#17181B]/95 px-6 py-2.5 backdrop-blur-md shadow-2xl transition-all"
        >
            <!-- Left Zone: Wordmark & Core Nav Links -->
            <div class="flex items-center space-x-8">
                <div class="flex items-baseline space-x-2">
                    <span class="text-base font-semibold tracking-tight uppercase text-[#F2EFEA]">
                        WATOON
                    </span>
                    <span
                        class="select-none rounded border border-white/10 bg-white/[0.04] px-1.5 py-0.5 font-mono text-[9px] tracking-[0.18em] uppercase text-[#8E8FA2]"
                    >
                        CREATOR
                    </span>
                </div>

                <!-- Navigation links -->
                <nav class="hidden md:flex items-center space-x-6 pt-0.5 text-sm font-medium">
                    <button
                        type="button"
                        @click="switchTab('dashboard')"
                        class="cursor-pointer"
                        :class="
                            activeTab === 'dashboard'
                                ? 'border-b border-[#1B44E8] pb-0.5 text-[#F2EFEA]'
                                : 'text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors'
                        "
                    >
                        Dashboard
                    </button>
                    <button
                        type="button"
                        @click="switchTab('content')"
                        class="cursor-pointer"
                        :class="
                            activeTab === 'content'
                                ? 'border-b border-[#1B44E8] pb-0.5 text-[#F2EFEA]'
                                : 'text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors'
                        "
                    >
                        Content
                    </button>
                    <button
                        type="button"
                        @click="switchTab('analytics')"
                        class="cursor-pointer"
                        :class="
                            activeTab === 'analytics'
                                ? 'border-b border-[#1B44E8] pb-0.5 text-[#F2EFEA]'
                                : 'text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors'
                        "
                    >
                        Analytics
                    </button>
                    <button
                        type="button"
                        @click="switchTab('earnings')"
                        class="cursor-pointer"
                        :class="
                            activeTab === 'earnings'
                                ? 'border-b border-[#1B44E8] pb-0.5 text-[#F2EFEA]'
                                : 'text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors'
                        "
                    >
                        Earnings
                    </button>
                </nav>
            </div>

            <!-- Right Zone: Upload action, novel switcher, notifications, creator avatar -->
            <div class="flex items-center space-x-3">
                <!-- Upload film primary action -->
                <button
                    type="button"
                    @click="isUploadModalOpen = true"
                    class="flex items-center space-x-1.5 rounded-lg bg-[#1B44E8] px-3.5 py-1.5 text-xs sm:text-sm font-medium text-white transition-all duration-100 hover:bg-[#1B44E8]/90 active:scale-[0.98] shadow-sm cursor-pointer"
                >
                    <Plus class="h-4 w-4" />
                    <span class="tracking-normal">Upload film</span>
                </button>

                <!-- Switch to Novel Creator Dashboard -->
                <Link
                    :href="dashboard.url({ query: { type: 'novel' } })"
                    class="hidden lg:inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/[0.03] px-3 py-1.5 font-mono text-[11px] text-[#8E8FA2] hover:border-amber-500/40 hover:bg-amber-500/10 hover:text-amber-400 transition-colors"
                    title="Beralih ke Dashboard Kreator Novel"
                >
                    <BookOpen class="h-3.5 w-3.5" />
                    <span>Mode Novel</span>
                </Link>

                <!-- Notifications Button with Dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isNotificationsOpen = !isNotificationsOpen"
                        aria-label="Notifications"
                        class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 text-[#8E8FA2] transition-colors hover:bg-white/[0.04] hover:text-[#F2EFEA] cursor-pointer"
                    >
                        <Bell class="h-4 w-4" />
                        <span class="absolute top-2 right-2 h-1.5 w-1.5 rounded-full bg-[#1B44E8] ring-2 ring-[#17181B]"></span>
                    </button>

                    <!-- Notifications Dropdown -->
                    <div
                        v-if="isNotificationsOpen"
                        class="absolute right-0 mt-2 w-80 rounded-xl border border-white/10 bg-[#17181B] p-4 shadow-2xl z-50"
                    >
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <span class="font-mono text-xs uppercase tracking-wider text-[#8E8FA2]">Notifikasi Studio</span>
                            <span class="text-[10px] text-[#7A9BFF]">3 Belum dibaca</span>
                        </div>
                        <div class="mt-3 space-y-2.5 text-xs">
                            <div class="rounded-lg bg-white/[0.02] p-2 hover:bg-white/[0.05] transition-colors">
                                <p class="font-medium text-[#F2EFEA]">Alya Kusuma mengirim pesan baru</p>
                                <span class="font-mono text-[10px] text-[#8E8FA2]">12 menit lalu</span>
                            </div>
                            <div class="rounded-lg bg-white/[0.02] p-2 hover:bg-white/[0.05] transition-colors">
                                <p class="font-medium text-[#F2EFEA]">3 penonton baru berlangganan karya Anda</p>
                                <span class="font-mono text-[10px] text-[#8E8FA2]">1 jam lalu</span>
                            </div>
                            <div class="rounded-lg bg-white/[0.02] p-2 hover:bg-white/[0.05] transition-colors">
                                <p class="font-medium text-[#F2EFEA]">Cakrawala Senja EP 08 mencapai 10.000 views</p>
                                <span class="font-mono text-[10px] text-[#8E8FA2]">Kemarin</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Creator Avatar with Dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isProfileMenuOpen = !isProfileMenuOpen"
                        class="relative flex items-center cursor-pointer focus:outline-none"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/[0.06] font-mono text-xs font-semibold text-[#F2EFEA] hover:border-white/30 transition-colors select-none"
                            title="Creator Avatar"
                        >
                            {{ userInitials }}
                        </div>
                        <span
                            class="absolute -bottom-1 -right-1 scale-90 rounded border border-white/15 bg-[#17181B] px-1 py-0.2 font-mono text-[8px] tracking-wider uppercase text-[#A8A8AC]"
                        >
                            CREATOR
                        </span>
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <div
                        v-if="isProfileMenuOpen"
                        class="absolute right-0 mt-2 w-56 rounded-xl border border-white/10 bg-[#17181B] p-2 shadow-2xl z-50 text-xs"
                    >
                        <div class="px-3 py-2 border-b border-white/10">
                            <p class="font-semibold text-[#F2EFEA] truncate">{{ userName }}</p>
                            <p class="text-[11px] text-[#8E8FA2] truncate">{{ user?.email || 'kreator@watoon.id' }}</p>
                            <span class="inline-block mt-1 text-[10px] font-mono text-[#7A9BFF]">Kreator Film Terverifikasi</span>
                        </div>

                        <div class="py-1">
                            <Link
                                :href="dashboard.url()"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-[#8E8FA2] hover:bg-white/[0.04] hover:text-[#F2EFEA] transition-colors"
                            >
                                <Film class="h-4 w-4" />
                                <span>Lihat Mode Penonton</span>
                            </Link>
                            <Link
                                :href="dashboard.url({ query: { type: 'novel' } })"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-[#8E8FA2] hover:bg-white/[0.04] hover:text-amber-400 transition-colors"
                            >
                                <BookOpen class="h-4 w-4" />
                                <span>Beralih ke Kreator Novel</span>
                            </Link>
                            <Link
                                href="/settings"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-[#8E8FA2] hover:bg-white/[0.04] hover:text-[#F2EFEA] transition-colors"
                            >
                                <Settings class="h-4 w-4" />
                                <span>Pengaturan Akun</span>
                            </Link>
                        </div>

                        <div class="border-t border-white/10 pt-1">
                            <button
                                type="button"
                                @click="handleLogout"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-rose-400 hover:bg-rose-500/10 transition-colors"
                            >
                                <LogOut class="h-4 w-4" />
                                <span>Keluar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ============================================================== -->
        <!-- VIEW 1: OVERVIEW DASHBOARD                                     -->
        <!-- ============================================================== -->
        <main v-if="activeTab === 'dashboard'" class="mx-auto max-w-7xl space-y-8 px-4 pt-10 pb-16 md:px-8">
            <!-- PAGE HEADER -->
            <div class="flex flex-col justify-between gap-4 border-b border-white/[0.08] pb-6 md:flex-row md:items-end">
                <div class="space-y-1">
                    <div class="font-mono text-[11px] tracking-[0.14em] uppercase text-[#8E8FA2]">
                        DASHBOARD
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-[#F2EFEA] sm:text-3xl">
                        Welcome back, {{ userName }}.
                    </h1>
                    <p class="text-sm text-[#8E8FA2]">
                        Here's how your films are performing this week.
                    </p>
                </div>

                <!-- Date Range Filter Dropdown Trigger -->
                <div class="relative flex items-center self-start md:self-auto">
                    <button
                        type="button"
                        @click="isDateDropdownOpen = !isDateDropdownOpen"
                        class="flex items-center space-x-1.5 rounded-lg border border-white/10 bg-[#141518] px-3.5 py-1.5 text-sm text-[#F2EFEA] transition-colors hover:bg-[#1E2023] cursor-pointer"
                    >
                        <span>{{ selectedPeriodLabel }}</span>
                        <ChevronDown class="h-4 w-4 text-[#8E8FA2]" />
                    </button>

                    <!-- Range Menu -->
                    <div
                        v-if="isDateDropdownOpen"
                        class="absolute right-0 top-full mt-1.5 w-40 rounded-xl border border-white/10 bg-[#17181B] py-1 shadow-2xl z-30 font-mono text-xs"
                    >
                        <button
                            type="button"
                            @click="selectTimeRange('7D')"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="timeRange === '7D' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8E8FA2]'"
                        >
                            <span>Last 7 days</span>
                            <Check v-if="timeRange === '7D'" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="selectTimeRange('30D')"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="timeRange === '30D' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8E8FA2]'"
                        >
                            <span>Last 30 days</span>
                            <Check v-if="timeRange === '30D'" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="selectTimeRange('90D')"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="timeRange === '90D' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8E8FA2]'"
                        >
                            <span>Last 90 days</span>
                            <Check v-if="timeRange === '90D'" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="selectTimeRange('All')"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="timeRange === 'All' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8E8FA2]'"
                        >
                            <span>All time</span>
                            <Check v-if="timeRange === 'All'" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 STAT CARDS ROW -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15">
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">TOTAL VIEWS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path d="M0 16 L8 12 L16 15 L24 8 L32 10 L40 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">248,320</div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +12.4%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15">
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">WATCH HOURS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path d="M0 18 L10 14 L18 11 L28 12 L34 6 L40 4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">8,412</div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +8.2%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15">
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">SUBSCRIBERS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path d="M0 15 L8 16 L18 10 L26 11 L32 7 L40 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">12,047</div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +3.1%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15">
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">ESTIMATED EARNINGS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path d="M0 17 L9 13 L17 14 L25 8 L33 9 L40 2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-baseline space-x-1 font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">
                            <span class="text-sm font-normal text-[#8E8FA2]">Rp</span>
                            <span>4.240.000</span>
                        </div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +15.8%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERFORMANCE CHART -->
            <div class="rounded-xl border border-white/[0.06] bg-[#141518] p-6">
                <div class="flex flex-col justify-between gap-3 border-b border-white/[0.08] pb-6 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-[#F2EFEA]">Views over time</h2>
                        <p class="text-xs text-[#8E8FA2]">{{ selectedPeriodLabel }} telemetry stream</p>
                    </div>

                    <div class="flex items-center space-x-4 font-mono text-xs text-[#8E8FA2]">
                        <button
                            type="button"
                            @click="selectTimeRange('7D')"
                            :class="timeRange === '7D' ? 'border-b-2 border-[#1B44E8] pb-0.5 font-semibold text-[#F2EFEA]' : 'hover:text-[#F2EFEA] transition-colors'"
                        >
                            7D
                        </button>
                        <button
                            type="button"
                            @click="selectTimeRange('30D')"
                            :class="timeRange === '30D' ? 'border-b-2 border-[#1B44E8] pb-0.5 font-semibold text-[#F2EFEA]' : 'hover:text-[#F2EFEA] transition-colors'"
                        >
                            30D
                        </button>
                        <button
                            type="button"
                            @click="selectTimeRange('90D')"
                            :class="timeRange === '90D' ? 'border-b-2 border-[#1B44E8] pb-0.5 font-semibold text-[#F2EFEA]' : 'hover:text-[#F2EFEA] transition-colors'"
                        >
                            90D
                        </button>
                        <button
                            type="button"
                            @click="selectTimeRange('All')"
                            :class="timeRange === 'All' ? 'border-b-2 border-[#1B44E8] pb-0.5 font-semibold text-[#F2EFEA]' : 'hover:text-[#F2EFEA] transition-colors'"
                        >
                            All
                        </button>
                    </div>
                </div>

                <div class="relative mt-6 h-64 w-full">
                    <svg class="h-full w-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 1000 240">
                        <defs>
                            <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#1B44E8" stop-opacity="0.14"></stop>
                                <stop offset="100%" stop-color="#1B44E8" stop-opacity="0.00"></stop>
                            </linearGradient>
                        </defs>

                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="40" y2="40"></line>
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="90" y2="90"></line>
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="140" y2="140"></line>
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="190" y2="190"></line>
                        <line stroke="rgba(242, 239, 234, 0.12)" stroke-width="1" x1="0" x2="1000" y1="239" y2="239"></line>

                        <polygon fill="url(#chartGradient)" :points="currentChart.poly"></polygon>
                        <polyline fill="none" :points="currentChart.points" stroke="#1B44E8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></polyline>

                        <line
                            v-if="hoveredPoint"
                            :x1="hoveredPoint.x"
                            :x2="hoveredPoint.x"
                            y1="0"
                            y2="240"
                            stroke="rgba(122, 155, 255, 0.35)"
                            stroke-dasharray="3 3"
                            stroke-width="1"
                        ></line>
                        <circle
                            v-if="hoveredPoint"
                            :cx="hoveredPoint.x"
                            :cy="hoveredPoint.y"
                            fill="#1B44E8"
                            r="4.5"
                            stroke="#0E0F11"
                            stroke-width="2"
                        ></circle>
                    </svg>

                    <div
                        v-if="hoveredPoint"
                        class="absolute top-2 -translate-x-1/2 rounded border border-white/15 bg-[#1E1F23] px-3 py-2 shadow-xl pointer-events-none transition-all duration-150"
                        :style="{ left: `${hoveredPoint.x / 10}%` }"
                    >
                        <div class="font-mono text-[10px] uppercase text-[#8E8FA2]">{{ hoveredPoint.date }}</div>
                        <div class="flex items-center space-x-1.5 font-mono text-xs font-semibold text-[#F2EFEA]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#1B44E8]"></span>
                            <span>{{ hoveredPoint.views }}</span>
                        </div>
                    </div>

                    <div class="pointer-events-none absolute bottom-6 left-0 top-0 flex flex-col justify-between pr-2 font-mono text-[10px] text-[#8E8FA2]">
                        <span>20K</span>
                        <span>15K</span>
                        <span>10K</span>
                        <span>5K</span>
                        <span>0</span>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-white/[0.08] pt-3 font-mono text-[10px] text-[#8E8FA2]">
                    <span
                        v-for="(label, idx) in currentChart.timeline"
                        :key="idx"
                        :class="idx === currentChart.timeline.length - 1 ? 'font-medium text-[#F2EFEA]' : ''"
                    >
                        {{ label }}
                    </span>
                </div>
            </div>

            <!-- TWO-COLUMN SECTION -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-6 lg:col-span-7">
                    <div>
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-4">
                            <h2 class="text-base font-semibold text-[#F2EFEA]">Top performing films</h2>
                            <button
                                type="button"
                                @click="switchTab('content')"
                                class="flex items-center space-x-1 font-mono text-xs text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors cursor-pointer"
                            >
                                <span>View all in Library</span>
                                <ArrowUpRight class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <div class="mt-2 divide-y divide-white/[0.06]">
                            <div
                                v-for="film in allFilms.slice(0, 4)"
                                :key="film.id"
                                class="group flex items-center justify-between rounded-lg px-2 py-3 transition-colors hover:bg-white/[0.03]"
                            >
                                <div class="flex items-center space-x-3.5 min-w-0">
                                    <div class="h-9 w-16 flex-shrink-0 overflow-hidden rounded border border-white/10 bg-[#1E2023]">
                                        <img :src="film.thumb" :alt="film.title" class="h-full w-full object-cover transition-transform group-hover:scale-105 duration-200" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-[#F2EFEA] group-hover:text-[#7A9BFF] transition-colors">
                                            {{ film.title }}
                                        </div>
                                        <div class="truncate font-mono text-[11px] text-[#8E8FA2]">
                                            {{ film.genre }} · {{ film.year }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4 flex-shrink-0">
                                    <div class="text-right">
                                        <div class="font-mono text-xs font-medium text-[#F2EFEA]">{{ film.viewsFormatted }}</div>
                                        <div class="font-mono text-[11px] text-[#7A9BFF]">+18%</div>
                                    </div>
                                    <ChevronRight class="h-4 w-4 text-[#8E8FA2] opacity-40 transition-opacity group-hover:opacity-100" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-white/[0.06] pt-3 font-mono text-[11px] text-[#8E8FA2]">
                        <span>Active streaming titles: {{ countPublished }}</span>
                        <span>Platform rank: Top 5%</span>
                    </div>
                </div>

                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-6 lg:col-span-5">
                    <div>
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-4">
                            <h2 class="text-base font-semibold text-[#F2EFEA]">Recent activity</h2>
                            <span class="font-mono text-xs text-[#8E8FA2]">Realtime</span>
                        </div>

                        <div class="mt-3 space-y-3.5">
                            <div class="flex items-start space-x-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/10 bg-white/[0.04] text-[#8E8FA2]">
                                    <MessageSquare class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm leading-tight text-[#F2EFEA]">Alya Kusuma sent you a message</p>
                                    <span class="font-mono text-[10px] text-[#8E8FA2]">12 min ago</span>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/10 bg-white/[0.04] text-[#8E8FA2]">
                                    <UserPlus class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm leading-tight text-[#F2EFEA]">3 new subscribers</p>
                                    <span class="font-mono text-[10px] text-[#8E8FA2]">1 hour ago</span>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/10 bg-white/[0.04] text-[#8E8FA2]">
                                    <Star class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm leading-tight text-[#F2EFEA]">Rangga left a 5-star review on Detik Terakhir</p>
                                    <span class="font-mono text-[10px] text-[#8E8FA2]">3 hours ago</span>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/10 bg-white/[0.04] text-[#8E8FA2]">
                                    <Film class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm leading-tight text-[#F2EFEA]">Cakrawala Senja EP 08 reached 10,000 views</p>
                                    <span class="font-mono text-[10px] text-[#8E8FA2]">Yesterday</span>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/10 bg-white/[0.04] text-[#8E8FA2]">
                                    <Wallet class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm leading-tight text-[#F2EFEA]">Payout of Rp 2.500.000 completed</p>
                                    <span class="font-mono text-[10px] text-[#8E8FA2]">2 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-white/[0.06] pt-3 text-right">
                        <button
                            type="button"
                            @click="isNotificationsOpen = true"
                            class="font-mono text-xs text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors"
                        >
                            Activity archive →
                        </button>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <button
                    type="button"
                    @click="isUploadModalOpen = true"
                    class="group flex items-center space-x-4 rounded-xl border border-white/[0.06] bg-[#141518] p-4 text-left transition-all hover:border-white/15 hover:bg-[#1A1C1F] cursor-pointer"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-[#1E2023] text-[#F2EFEA] transition-colors group-hover:border-[#1B44E8]">
                        <UploadCloud class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#F2EFEA]">Upload new film</div>
                        <p class="text-xs text-[#8E8FA2]">Publish to your library</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="switchTab('content')"
                    class="group flex items-center space-x-4 rounded-xl border border-white/[0.06] bg-[#141518] p-4 text-left transition-all hover:border-white/15 hover:bg-[#1A1C1F] cursor-pointer"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-[#1E2023] text-[#F2EFEA] transition-colors group-hover:border-[#1B44E8]">
                        <BarChart3 class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#F2EFEA]">Manage Film Library</div>
                        <p class="text-xs text-[#8E8FA2]">{{ countAll }} total titles cataloged</p>
                    </div>
                </button>

                <button
                    type="button"
                    @click="isPayoutModalOpen = true"
                    class="group flex items-center space-x-4 rounded-xl border border-white/[0.06] bg-[#141518] p-4 text-left transition-all hover:border-white/15 hover:bg-[#1A1C1F] cursor-pointer"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-[#1E2023] text-[#F2EFEA] transition-colors group-hover:border-[#1B44E8]">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#F2EFEA]">Request payout</div>
                        <p class="text-xs text-[#8E8FA2]">Withdraw your earnings</p>
                    </div>
                </button>
            </div>
        </main>

        <!-- ============================================================== -->
        <!-- VIEW 2: CONTENT LIBRARY (YOUR FILMS)                           -->
        <!-- ============================================================== -->
        <main v-else-if="activeTab === 'content'" class="mx-auto max-w-[1280px] px-4 sm:px-8 mt-6">
            <!-- PAGE HEADER -->
            <div class="flex flex-col justify-between gap-4 pb-6 md:flex-row md:items-end">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-[#8C8C90] mb-1 font-semibold">
                        CONTENT
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight text-[#F2EFEA] sm:text-3xl">
                        Your films
                    </h1>
                    <p class="text-sm text-[#8C8C90] mt-0.5">
                        {{ countAll }} films · {{ countDrafts }} drafts · {{ countScheduled }} scheduled · {{ countProcessing }} processing
                    </p>
                </div>

                <button
                    type="button"
                    @click="isUploadModalOpen = true"
                    class="self-start md:self-auto flex items-center gap-1.5 px-4 py-2 bg-[#1B44E8] hover:bg-[#1B44E8]/90 active:scale-[0.99] transition-all rounded-[10px] text-white text-sm font-medium cursor-pointer shadow-sm"
                >
                    <Plus class="h-4 w-4" />
                    <span>Upload film</span>
                </button>
            </div>

            <!-- TABS & SORT ROW -->
            <div class="border-b border-white/[0.08] flex flex-wrap items-center justify-between gap-4 pt-2">
                <!-- Status Filter Tabs -->
                <div class="flex items-center gap-7 overflow-x-auto scrollbar-none">
                    <button
                        type="button"
                        @click="contentStatusFilter = 'all'"
                        class="relative pb-3 text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                        :class="contentStatusFilter === 'all' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        All ({{ countAll }})
                        <div v-if="contentStatusFilter === 'all'" class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"></div>
                    </button>

                    <button
                        type="button"
                        @click="contentStatusFilter = 'published'"
                        class="relative pb-3 text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                        :class="contentStatusFilter === 'published' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        Published ({{ countPublished }})
                        <div v-if="contentStatusFilter === 'published'" class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"></div>
                    </button>

                    <button
                        type="button"
                        @click="contentStatusFilter = 'draft'"
                        class="relative pb-3 text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                        :class="contentStatusFilter === 'draft' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        Drafts ({{ countDrafts }})
                        <div v-if="contentStatusFilter === 'draft'" class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"></div>
                    </button>

                    <button
                        type="button"
                        @click="contentStatusFilter = 'scheduled'"
                        class="relative pb-3 text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                        :class="contentStatusFilter === 'scheduled' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        Scheduled ({{ countScheduled }})
                        <div v-if="contentStatusFilter === 'scheduled'" class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"></div>
                    </button>

                    <button
                        type="button"
                        @click="contentStatusFilter = 'processing'"
                        class="relative pb-3 text-sm font-medium transition-colors whitespace-nowrap cursor-pointer"
                        :class="contentStatusFilter === 'processing' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        Processing ({{ countProcessing }})
                        <div v-if="contentStatusFilter === 'processing'" class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"></div>
                    </button>
                </div>

                <!-- Sorting Control -->
                <div class="relative pb-3">
                    <button
                        type="button"
                        @click="isSortDropdownOpen = !isSortDropdownOpen"
                        class="flex items-center gap-1.5 text-xs text-[#8C8C90] hover:text-[#F2EFEA] cursor-pointer transition-colors"
                    >
                        <span>Sort by:</span>
                        <span class="text-[#F2EFEA] font-medium capitalize">
                            {{ sortBy.replace('_', ' ') }}
                        </span>
                        <ChevronDown class="h-3.5 w-3.5" />
                    </button>

                    <div
                        v-if="isSortDropdownOpen"
                        class="absolute right-0 top-full mt-1 w-44 rounded-xl border border-white/10 bg-[#17181B] py-1 shadow-2xl z-30 font-mono text-xs"
                    >
                        <button
                            type="button"
                            @click="sortBy = 'recently_updated'; isSortDropdownOpen = false"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="sortBy === 'recently_updated' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8C8C90]'"
                        >
                            <span>Recently updated</span>
                            <Check v-if="sortBy === 'recently_updated'" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="sortBy = 'most_viewed'; isSortDropdownOpen = false"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="sortBy === 'most_viewed' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8C8C90]'"
                        >
                            <span>Most viewed</span>
                            <Check v-if="sortBy === 'most_viewed'" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="sortBy = 'highest_revenue'; isSortDropdownOpen = false"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="sortBy === 'highest_revenue' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8C8C90]'"
                        >
                            <span>Highest revenue</span>
                            <Check v-if="sortBy === 'highest_revenue'" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="sortBy = 'alphabetical'; isSortDropdownOpen = false"
                            class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-white/[0.04] transition-colors"
                            :class="sortBy === 'alphabetical' ? 'text-[#7A9BFF] font-semibold' : 'text-[#8C8C90]'"
                        >
                            <span>Alphabetical (A-Z)</span>
                            <Check v-if="sortBy === 'alphabetical'" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- TOOLBAR (Search, Bulk Selection, View Switcher) -->
            <div class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Search Field -->
                    <div class="relative w-full sm:w-[280px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-[#8C8C90] h-4 w-4" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search your films…"
                            class="w-full h-9 pl-9 pr-3 rounded-[10px] bg-white/[0.04] border border-white/10 text-[#F2EFEA] placeholder:text-[#5A5B60] text-xs focus:outline-none focus:border-[#1B44E8] transition-all"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#8C8C90] hover:text-[#F2EFEA]"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Bulk Selection Indicators & Actions -->
                    <div
                        v-if="selectedFilmIds.length > 0"
                        class="flex items-center gap-3 bg-[#17181B] px-3 py-1.5 rounded-lg border border-white/10 animate-in fade-in duration-150"
                    >
                        <span class="font-mono text-[11px] text-[#F2EFEA] font-medium">
                            {{ selectedFilmIds.length }} selected
                        </span>
                        <span class="w-[1px] h-3 bg-white/15"></span>

                        <div class="flex items-center gap-3 text-xs">
                            <button
                                type="button"
                                @click="bulkPublish"
                                class="text-[#7A9BFF] hover:underline transition-colors cursor-pointer"
                            >
                                Publish
                            </button>
                            <span class="w-[1px] h-3 bg-white/15"></span>
                            <button
                                type="button"
                                @click="bulkMoveToDraft"
                                class="text-[#8C8C90] hover:text-[#F2EFEA] transition-colors cursor-pointer"
                            >
                                Move to draft
                            </button>
                            <span class="w-[1px] h-3 bg-white/15"></span>
                            <button
                                type="button"
                                @click="bulkDelete"
                                class="text-[#E85C4A] hover:underline transition-colors cursor-pointer"
                            >
                                Delete
                            </button>
                            <span class="w-[1px] h-3 bg-white/15"></span>
                            <button
                                type="button"
                                @click="cancelSelection"
                                class="text-[#8C8C90] hover:text-[#F2EFEA] transition-colors cursor-pointer"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- View Switcher -->
                <div class="flex items-center gap-1 self-end md:self-auto">
                    <button
                        type="button"
                        @click="viewMode = 'list'"
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors cursor-pointer"
                        :class="
                            viewMode === 'list'
                                ? 'bg-white/[0.08] text-[#F2EFEA] border border-white/10'
                                : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-white/[0.04]'
                        "
                        title="List view"
                    >
                        <List class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'grid'"
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors cursor-pointer"
                        :class="
                            viewMode === 'grid'
                                ? 'bg-white/[0.08] text-[#F2EFEA] border border-white/10'
                                : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-white/[0.04]'
                        "
                        title="Grid view"
                    >
                        <Grid class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- EMPTY STATE (If no films match) -->
            <div
                v-if="displayedFilms.length === 0"
                class="bg-[#17181B] rounded-[14px] border border-white/[0.08] p-12 text-center max-w-lg mx-auto my-8"
            >
                <div class="w-12 h-12 rounded-full bg-[#1E1F23] border border-white/10 flex items-center justify-center text-[#8C8C90] mx-auto mb-4">
                    <Film class="h-6 w-6" />
                </div>
                <h4 class="text-base font-semibold text-[#F2EFEA]">Nothing found in this filter</h4>
                <p class="text-xs text-[#8C8C90] mt-1 mb-5">
                    No films matched "{{ searchQuery }}" in {{ contentStatusFilter }} tab. Try clearing your search or status filter.
                </p>
                <button
                    type="button"
                    @click="searchQuery = ''; contentStatusFilter = 'all'"
                    class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/15 text-xs font-medium text-[#F2EFEA] transition-colors"
                >
                    Clear search &amp; filters
                </button>
            </div>

            <!-- ============================================================== -->
            <!-- LIST VIEW (7-COLUMN TABLE)                                     -->
            <!-- ============================================================== -->
            <div
                v-else-if="viewMode === 'list'"
                class="bg-[#17181B] rounded-[14px] border border-white/[0.08] overflow-visible"
            >
                <!-- Table Header -->
                <div class="hidden lg:grid grid-cols-[40px_120px_1fr_110px_110px_120px_40px] items-center px-5 py-2.5 border-b border-white/[0.08] text-[#8C8C90] font-mono text-[11px] uppercase tracking-wider">
                    <div>
                        <input
                            type="checkbox"
                            :checked="isAllSelected"
                            @change="toggleSelectAll"
                            class="w-4 h-4 rounded bg-[#17181B] border-white/20 text-[#1B44E8] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        />
                    </div>
                    <div>Preview</div>
                    <div>Title &amp; Classification</div>
                    <div>Status</div>
                    <div class="text-right pr-4">Views</div>
                    <div class="text-right pr-4">Gross Revenue</div>
                    <div class="text-center">Action</div>
                </div>

                <!-- Film Rows -->
                <div
                    v-for="film in displayedFilms"
                    :key="film.id"
                    class="relative flex flex-col lg:grid lg:grid-cols-[40px_120px_1fr_110px_110px_120px_40px] items-start lg:items-center p-4 lg:px-5 lg:py-3.5 border-b border-white/[0.06] hover:bg-white/[0.02] transition-colors gap-3 lg:gap-0"
                    :class="selectedFilmIds.includes(film.id) ? 'bg-[#1B44E8]/[0.03]' : ''"
                >
                    <!-- Checkbox & Mobile status badge -->
                    <div class="flex items-center justify-between w-full lg:w-auto">
                        <input
                            type="checkbox"
                            :checked="selectedFilmIds.includes(film.id)"
                            @change="toggleSelectFilm(film.id)"
                            class="w-4 h-4 rounded bg-[#17181B] border-white/20 text-[#1B44E8] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                        />
                        <span
                            class="lg:hidden px-2.5 py-0.5 rounded-full text-[10px] font-mono font-medium"
                            :class="getStatusChipClass(film.status)"
                        >
                            {{ film.statusLabel }}
                        </span>
                    </div>

                    <!-- 16:9 Thumbnail -->
                    <div class="w-full sm:w-[108px] h-16 rounded-[6px] overflow-hidden bg-[#0E0F11] relative shrink-0 border border-white/10">
                        <img
                            :src="film.thumb"
                            :alt="film.title"
                            class="w-full h-full object-cover"
                            :class="film.status === 'processing' ? 'filter brightness-50' : ''"
                        />
                        <div
                            v-if="film.status === 'processing'"
                            class="absolute inset-0 bg-[#0E0F11]/55 flex items-center justify-center"
                        >
                            <Loader2 class="h-5 w-5 text-[#7A9BFF] animate-spin" />
                        </div>
                        <div
                            v-else
                            class="absolute bottom-1 right-1 bg-black/80 font-mono text-[9px] px-1 rounded text-white font-medium"
                        >
                            {{ film.duration }}
                        </div>
                    </div>

                    <!-- Title & Classification -->
                    <div class="lg:px-4 flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-[#F2EFEA] tracking-tight truncate">
                            {{ film.title }}
                        </h3>
                        <p class="text-xs text-[#8C8C90] mt-0.5 flex flex-wrap items-center gap-1.5 truncate">
                            <span v-if="film.episodes">{{ film.episodes }} · </span>
                            <span>{{ film.genre }} · </span>
                            <span>{{ film.year }}</span>
                            <template v-if="film.status === 'published'">
                                <span>·</span>
                                <Link
                                    :href="`/films/${film.id}`"
                                    class="text-[#7A9BFF] hover:underline inline-flex items-center gap-0.5"
                                >
                                    <span>View on site</span>
                                    <span class="text-xs">→</span>
                                </Link>
                            </template>
                            <span v-else-if="film.scheduledDate" class="text-amber-400">· {{ film.scheduledDate }}</span>
                            <span v-else-if="film.progress" class="text-[#7A9BFF]">· Processing video… {{ film.progress }}%</span>
                        </p>
                    </div>

                    <!-- Status Chip Desktop -->
                    <div class="hidden lg:block">
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-medium whitespace-nowrap"
                            :class="getStatusChipClass(film.status)"
                        >
                            {{ film.statusLabel }}
                        </span>
                    </div>

                    <!-- Views -->
                    <div class="w-full lg:w-auto flex lg:block justify-between items-center text-left lg:text-right lg:pr-4">
                        <span class="lg:hidden text-[#8C8C90] font-mono text-[11px]">Views</span>
                        <span class="font-mono text-sm font-medium text-[#F2EFEA]">
                            {{ film.viewsFormatted }}
                        </span>
                    </div>

                    <!-- Gross Revenue -->
                    <div class="w-full lg:w-auto flex lg:block justify-between items-center text-left lg:text-right lg:pr-4">
                        <span class="lg:hidden text-[#8C8C90] font-mono text-[11px]">Revenue</span>
                        <span class="font-mono text-sm font-medium text-[#F2EFEA]">
                            {{ film.revenueFormatted }}
                        </span>
                    </div>

                    <!-- Row Action Dropdown Trigger -->
                    <div class="self-end lg:self-center relative flex justify-center">
                        <button
                            type="button"
                            @click="toggleRowDropdown(film.id)"
                            class="w-8 h-8 rounded flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-white/[0.06] transition-colors cursor-pointer"
                        >
                            <MoreHorizontal class="h-4 w-4" />
                        </button>

                        <!-- Contextual Popover Dropdown -->
                        <div
                            v-if="activeDropdownFilmId === film.id"
                            class="absolute right-0 top-9 z-30 w-44 rounded-xl border border-white/10 bg-[#1E1F23] py-1.5 shadow-2xl font-mono text-xs"
                        >
                            <button
                                type="button"
                                @click="openEditModal(film)"
                                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-left text-[#F2EFEA] hover:bg-white/[0.06] transition-colors"
                            >
                                <Edit class="h-3.5 w-3.5 text-[#8C8C90]" />
                                <span>Edit</span>
                            </button>
                            <button
                                type="button"
                                @click="switchTab('analytics'); activeDropdownFilmId = null"
                                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-left text-[#F2EFEA] hover:bg-white/[0.06] transition-colors"
                            >
                                <BarChart3 class="h-3.5 w-3.5 text-[#8C8C90]" />
                                <span>Analytics</span>
                            </button>
                            <button
                                type="button"
                                @click="shareFilm(film)"
                                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-left text-[#F2EFEA] hover:bg-white/[0.06] transition-colors"
                            >
                                <Share2 class="h-3.5 w-3.5 text-[#8C8C90]" />
                                <span>Share link</span>
                            </button>
                            <button
                                type="button"
                                @click="duplicateFilm(film)"
                                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-left text-[#F2EFEA] hover:bg-white/[0.06] transition-colors"
                            >
                                <Copy class="h-3.5 w-3.5 text-[#8C8C90]" />
                                <span>Duplicate</span>
                            </button>

                            <div class="my-1 border-t border-white/10"></div>

                            <button
                                type="button"
                                @click="deleteSingleFilm(film)"
                                class="w-full flex items-center gap-2.5 px-3 py-1.5 text-left text-[#E85C4A] hover:bg-red-500/10 transition-colors"
                            >
                                <Trash2 class="h-3.5 w-3.5 text-[#E85C4A]" />
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- GRID VIEW (2:3 VERTICAL POSTERS)                               -->
            <!-- ============================================================== -->
            <div
                v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5"
            >
                <div
                    v-for="film in displayedFilms"
                    :key="film.id"
                    class="bg-[#17181B] rounded-[12px] border border-white/[0.08] overflow-hidden group hover:border-white/20 transition-all flex flex-col justify-between"
                >
                    <div class="relative aspect-[2/3] bg-[#0E0F11] overflow-hidden">
                        <img
                            :src="film.poster || film.thumb"
                            :alt="film.title"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />

                        <!-- Status badge on poster -->
                        <div class="absolute top-2.5 left-2.5">
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-mono font-medium backdrop-blur-md"
                                :class="getStatusChipClass(film.status)"
                            >
                                {{ film.statusLabel }}
                            </span>
                        </div>

                        <!-- Checkbox on poster -->
                        <div class="absolute top-2.5 right-2.5">
                            <input
                                type="checkbox"
                                :checked="selectedFilmIds.includes(film.id)"
                                @change="toggleSelectFilm(film.id)"
                                class="w-4 h-4 rounded bg-[#17181B]/80 border-white/30 text-[#1B44E8] focus:ring-0 cursor-pointer"
                            />
                        </div>

                        <!-- Action button trigger -->
                        <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                type="button"
                                @click="toggleRowDropdown(film.id)"
                                class="w-8 h-8 rounded-lg bg-[#17181B]/90 border border-white/15 flex items-center justify-center text-[#F2EFEA] hover:bg-[#1B44E8] transition-colors cursor-pointer"
                            >
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="p-3.5">
                        <h4 class="text-sm font-semibold text-[#F2EFEA] truncate">
                            {{ film.title }}
                        </h4>
                        <p class="text-[11px] text-[#8C8C90] mt-0.5 truncate">
                            {{ film.genre }} · {{ film.year }}
                        </p>
                        <div class="mt-2.5 pt-2 border-t border-white/[0.08] flex items-center justify-between font-mono text-[11px]">
                            <span class="text-[#8C8C90]">{{ film.viewsFormatted }} views</span>
                            <span class="text-[#7A9BFF] font-medium">{{ film.revenueFormatted }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAGINATION / LOAD MORE -->
            <div class="mt-6 flex items-center justify-between text-[#8C8C90] text-xs px-2 border-t border-white/[0.06] pt-4">
                <span class="font-mono">
                    Showing {{ displayedFilms.length }} of {{ filteredFilms.length }} films
                </span>

                <button
                    v-if="displayedFilms.length < filteredFilms.length"
                    type="button"
                    @click="loadMore"
                    class="text-[#7A9BFF] hover:text-white font-medium inline-flex items-center gap-1 transition-colors cursor-pointer"
                >
                    <span>Load more</span>
                    <ChevronDown class="h-4 w-4" />
                </button>
            </div>
        </main>

        <!-- ============================================================== -->
        <!-- VIEW 3: ANALYTICS (TELEMETRY TAB)                              -->
        <!-- ============================================================== -->
        <main v-else-if="activeTab === 'analytics'" class="mx-auto max-w-7xl space-y-8 px-4 pt-10 pb-16 md:px-8">
            <div class="flex items-center justify-between border-b border-white/[0.08] pb-6">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-[#8C8C90] mb-1 font-semibold">ANALYTICS</p>
                    <h1 class="text-2xl font-semibold tracking-tight text-[#F2EFEA] sm:text-3xl">Audience &amp; Revenue Telemetry</h1>
                    <p class="text-sm text-[#8C8C90] mt-0.5">Analitik retensi tayangan, distribusi geografis, dan demografi penonton.</p>
                </div>
                <button
                    type="button"
                    @click="triggerToast('Laporan analitik bulan ini diekspor ke format CSV.')"
                    class="px-4 py-2 rounded-lg border border-white/15 bg-white/[0.04] text-xs font-mono text-[#F2EFEA] hover:bg-white/[0.08] transition-colors"
                >
                    Export CSV
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="p-6 rounded-xl bg-[#141518] border border-white/[0.06]">
                    <span class="font-mono text-xs text-[#8C8C90] uppercase">Average Completion Rate</span>
                    <div class="font-mono text-3xl font-semibold text-[#F2EFEA] mt-2">78.4%</div>
                    <span class="text-xs text-[#7A9BFF] mt-1 block">↑ +4.2% di atas rata-rata industri bioskop</span>
                </div>
                <div class="p-6 rounded-xl bg-[#141518] border border-white/[0.06]">
                    <span class="font-mono text-xs text-[#8C8C90] uppercase">Repeat Viewer Ratio</span>
                    <div class="font-mono text-3xl font-semibold text-[#F2EFEA] mt-2">42.1%</div>
                    <span class="text-xs text-[#7A9BFF] mt-1 block">Penonton yang menonton lebih dari 2 episode</span>
                </div>
                <div class="p-6 rounded-xl bg-[#141518] border border-white/[0.06]">
                    <span class="font-mono text-xs text-[#8C8C90] uppercase">Peak Streaming Hours</span>
                    <div class="font-mono text-3xl font-semibold text-[#F2EFEA] mt-2">20:00 - 23:30</div>
                    <span class="text-xs text-[#8C8C90] mt-1 block">WIB (Waktu Indonesia Barat)</span>
                </div>
            </div>
        </main>

        <!-- ============================================================== -->
        <!-- VIEW 4: EARNINGS TAB                                           -->
        <!-- ============================================================== -->
        <main v-else-if="activeTab === 'earnings'" class="mx-auto max-w-7xl space-y-8 px-4 pt-10 pb-16 md:px-8">
            <div class="flex items-center justify-between border-b border-white/[0.08] pb-6">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-[#8C8C90] mb-1 font-semibold">EARNINGS</p>
                    <h1 class="text-2xl font-semibold tracking-tight text-[#F2EFEA] sm:text-3xl">Royalty Ledger &amp; Payouts</h1>
                    <p class="text-sm text-[#8C8C90] mt-0.5">Penetapan bagi hasil streaming, royalti episode berbayar, dan riwayat transfer.</p>
                </div>
                <button
                    type="button"
                    @click="isPayoutModalOpen = true"
                    class="px-4 py-2 bg-[#1B44E8] rounded-lg text-white font-medium text-xs hover:bg-[#1B44E8]/90 transition-all"
                >
                    Request Payout
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 rounded-xl bg-[#141518] border border-white/[0.06]">
                    <span class="font-mono text-xs text-[#8C8C90] uppercase">Saldo Belum Dicairkan</span>
                    <div class="font-mono text-3xl font-semibold text-[#F2EFEA] mt-2">
                        <span class="text-lg font-normal text-[#8C8C90]">Rp</span> 4.240.000
                    </div>
                    <p class="text-xs text-emerald-400 mt-2">Tersedia untuk ditarik ke rekening terdaftar kapan saja.</p>
                </div>
                <div class="p-6 rounded-xl bg-[#141518] border border-white/[0.06]">
                    <span class="font-mono text-xs text-[#8C8C90] uppercase">Total Penghasilan Sepanjang Masa</span>
                    <div class="font-mono text-3xl font-semibold text-[#F2EFEA] mt-2">
                        <span class="text-lg font-normal text-[#8C8C90]">Rp</span> 38.650.000
                    </div>
                    <p class="text-xs text-[#8C8C90] mt-2">Dari 12 karya film kurasi dan 248.320 penonton terverifikasi.</p>
                </div>
            </div>
        </main>

        <!-- ============================================================== -->
        <!-- [MODAL 1] UPLOAD NEW FILM MODAL                                -->
        <!-- ============================================================== -->
        <div
            v-if="isUploadModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        >
            <div
                class="w-full max-w-lg rounded-2xl border border-white/15 bg-[#17181B] p-6 shadow-2xl relative"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[#F2EFEA]">Unggah Karya Film Baru</h3>
                        <p class="text-xs text-[#8E8FA2]">Katalog studio WATOON Cinema</p>
                    </div>
                    <button
                        type="button"
                        @click="isUploadModalOpen = false"
                        class="rounded-lg p-1 text-[#8E8FA2] hover:bg-white/5 hover:text-[#F2EFEA] transition-colors"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitUpload" class="mt-4 space-y-4">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                            Judul Film
                        </label>
                        <input
                            v-model="uploadForm.title"
                            type="text"
                            required
                            placeholder="Contoh: Sang Kala di Lembah Sunyi"
                            class="w-full rounded-lg border border-white/15 bg-black/40 px-3.5 py-2 text-sm text-[#F2EFEA] placeholder-[#8E8FA2]/50 focus:border-[#1B44E8] focus:outline-none focus:ring-1 focus:ring-[#1B44E8]"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                                Tipe Konten
                            </label>
                            <select
                                v-model="uploadForm.type"
                                class="w-full rounded-lg border border-white/15 bg-[#17181B] px-3 py-2 text-sm text-[#F2EFEA] focus:border-[#1B44E8] focus:outline-none"
                            >
                                <option value="Feature Film">Feature Film (Bioskop)</option>
                                <option value="Short Film">Short Film (Film Pendek)</option>
                                <option value="Series">Drama Series (Serial)</option>
                                <option value="Documentary">Dokumenter</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                                Genre
                            </label>
                            <input
                                v-model="uploadForm.genre"
                                type="text"
                                placeholder="Noir / Thriller"
                                class="w-full rounded-lg border border-white/15 bg-black/40 px-3.5 py-2 text-sm text-[#F2EFEA] focus:border-[#1B44E8] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                            Sinopsis Cerita
                        </label>
                        <textarea
                            v-model="uploadForm.synopsis"
                            rows="3"
                            placeholder="Ringkasan cerita naratif film..."
                            class="w-full rounded-lg border border-white/15 bg-black/40 px-3.5 py-2 text-sm text-[#F2EFEA] placeholder-[#8E8FA2]/50 focus:border-[#1B44E8] focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Upload dropzone -->
                    <div class="rounded-xl border border-dashed border-white/20 p-5 text-center bg-white/[0.01]">
                        <UploadCloud class="mx-auto h-8 w-8 text-[#8E8FA2]" />
                        <p class="mt-2 text-xs font-medium text-[#F2EFEA]">Tarik dan lepaskan file master film (ProRes / MP4 4K)</p>
                        <p class="text-[10px] text-[#8E8FA2]">Maksimal 12 GB per file episode</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-white/10">
                        <button
                            type="button"
                            @click="isUploadModalOpen = false"
                            class="rounded-lg border border-white/15 px-4 py-2 text-xs text-[#8E8FA2] hover:bg-white/5 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#1B44E8] px-5 py-2 text-xs font-semibold text-white hover:bg-[#1B44E8]/90 transition-all shadow-md"
                        >
                            Kirim ke Kurasi Studio
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- [MODAL 2] EDIT FILM MODAL                                      -->
        <!-- ============================================================== -->
        <div
            v-if="isEditModalOpen && activeEditFilm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        >
            <div
                class="w-full max-w-lg rounded-2xl border border-white/15 bg-[#17181B] p-6 shadow-2xl relative"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[#F2EFEA]">Edit Data Film</h3>
                        <p class="text-xs text-[#8E8FA2]">{{ activeEditFilm.title }}</p>
                    </div>
                    <button
                        type="button"
                        @click="isEditModalOpen = false"
                        class="rounded-lg p-1 text-[#8E8FA2] hover:bg-white/5 hover:text-[#F2EFEA] transition-colors"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="saveEditFilm" class="mt-4 space-y-4">
                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                            Judul Film
                        </label>
                        <input
                            v-model="activeEditFilm.title"
                            type="text"
                            required
                            class="w-full rounded-lg border border-white/15 bg-black/40 px-3.5 py-2 text-sm text-[#F2EFEA] focus:border-[#1B44E8] focus:outline-none"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                                Status Rilis
                            </label>
                            <select
                                v-model="activeEditFilm.status"
                                class="w-full rounded-lg border border-white/15 bg-[#17181B] px-3 py-2 text-sm text-[#F2EFEA] focus:border-[#1B44E8] focus:outline-none"
                            >
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="processing">Processing</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                                Genre
                            </label>
                            <input
                                v-model="activeEditFilm.genre"
                                type="text"
                                class="w-full rounded-lg border border-white/15 bg-black/40 px-3.5 py-2 text-sm text-[#F2EFEA] focus:border-[#1B44E8] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2] mb-1">
                            Sinopsis
                        </label>
                        <textarea
                            v-model="activeEditFilm.synopsis"
                            rows="3"
                            class="w-full rounded-lg border border-white/15 bg-black/40 px-3.5 py-2 text-sm text-[#F2EFEA] focus:border-[#1B44E8] focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-white/10">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="rounded-lg border border-white/15 px-4 py-2 text-xs text-[#8E8FA2] hover:bg-white/5 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#1B44E8] px-5 py-2 text-xs font-semibold text-white hover:bg-[#1B44E8]/90 transition-all shadow-md"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- [MODAL 3] REQUEST PAYOUT MODAL                                 -->
        <!-- ============================================================== -->
        <div
            v-if="isPayoutModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-white/15 bg-[#17181B] p-6 shadow-2xl relative"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[#F2EFEA]">Tarik Royalti Kreator</h3>
                        <p class="text-xs text-[#8E8FA2]">Pendapatan streaming &amp; langganan penonton</p>
                    </div>
                    <button
                        type="button"
                        @click="isPayoutModalOpen = false"
                        class="rounded-lg p-1 text-[#8E8FA2] hover:bg-white/5 hover:text-[#F2EFEA] transition-colors"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-xl border border-white/10 bg-[#0E0F11] p-4 text-center">
                        <span class="font-mono text-[11px] uppercase tracking-wider text-[#8E8FA2]">Saldo Tersedia</span>
                        <div class="font-mono text-3xl font-semibold text-[#F2EFEA] mt-1">
                            <span class="text-base text-[#8E8FA2] font-normal">Rp</span> 4.240.000
                        </div>
                        <span class="text-[10px] text-emerald-400 mt-1 block">Telah diverifikasi · Siap ditarik</span>
                    </div>

                    <div class="rounded-lg border border-white/10 p-3 bg-white/[0.02] text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-[#8E8FA2]">Rekening Tujuan:</span>
                            <span class="font-mono font-medium text-[#F2EFEA]">BCA · 8820 9182 10</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#8E8FA2]">Atas Nama:</span>
                            <span class="font-medium text-[#F2EFEA]">{{ userName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#8E8FA2]">Estimasi Masuk:</span>
                            <span class="text-[#7A9BFF]">1×24 Jam Kerja</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-white/10">
                        <button
                            type="button"
                            @click="isPayoutModalOpen = false"
                            class="rounded-lg border border-white/15 px-4 py-2 text-xs text-[#8E8FA2] hover:bg-white/5 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitPayout"
                            class="rounded-lg bg-[#1B44E8] px-5 py-2 text-xs font-semibold text-white hover:bg-[#1B44E8]/90 transition-all shadow-md"
                        >
                            Konfirmasi Penarikan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
