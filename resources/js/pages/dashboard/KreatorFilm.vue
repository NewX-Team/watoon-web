<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    Sparkles,
} from '@lucide/vue';
import { dashboard, logout } from '@/routes';
import type { Auth } from '@/types/auth';

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

// Navigation state
const activeTab = ref<'dashboard' | 'content' | 'analytics' | 'earnings'>('dashboard');

// Date Range Filter state
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

// UI Modals state
const isUploadModalOpen = ref(false);
const isPayoutModalOpen = ref(false);
const isProfileMenuOpen = ref(false);
const isNotificationsOpen = ref(false);
const uploadSuccessMessage = ref('');
const payoutSuccessMessage = ref('');

// Film upload form state
const uploadForm = ref({
    title: '',
    type: 'Feature Film',
    genre: 'Drama / Mystery',
    synopsis: '',
    duration: '',
});

const submitUpload = () => {
    if (!uploadForm.value.title.trim()) return;
    uploadSuccessMessage.value = `Film "${uploadForm.value.title}" berhasil diunggah ke antrean kurasi WATOON.`;
    setTimeout(() => {
        isUploadModalOpen.value = false;
        uploadSuccessMessage.value = '';
        uploadForm.value = {
            title: '',
            type: 'Feature Film',
            genre: 'Drama / Mystery',
            synopsis: '',
            duration: '',
        };
    }, 1600);
};

const submitPayout = () => {
    payoutSuccessMessage.value = 'Permintaan penarikan Rp 4.240.000 sedang diproses ke rekening Anda.';
    setTimeout(() => {
        isPayoutModalOpen.value = false;
        payoutSuccessMessage.value = '';
    }, 1800);
};

// Films list
const films = [
    {
        id: 1,
        title: 'Cakrawala Senja',
        director: 'Directed by Rendra Wijaya · Feature',
        views: '84,210 views',
        growth: '+18%',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuASTnYlwz-XgSy_4rEBeFRgHFycKZ6Sb7onmQDNXpXtfMOqJF94be_tDc3scYnOCMNmigYXBZbuC57Nvbgf6RT7YuY835qD21wqjINTUZjgJnqXYMySflkC-SgDSlkZAbR-KGTuqK6WbEZ_O8FBh8bnk3Xdt3RYAKKZ6y7MJ8YRfC6pl5J0UTm_x4m3M258qwt1d9V1aOW9Lgmg3J0tuu2MYGhFcrbubhMC0Yh-kAotPXi699bk8onP',
    },
    {
        id: 2,
        title: 'Rumah di Ujung Jalan',
        director: 'Short film · Psychological Noir',
        views: '52,440 views',
        growth: '+9%',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCVLheXaC9IAFPuCbpnUsKollqqkQEpY3dOuUhiEgq9-7S-dMY_p0jbCh6-ob3sKuIvpomwJ-NaHQUR9pGbTogqRu5MK2Niz29R1wLepy-4d6Lr4XuwFjZ9TWWmt4DciK4ciyR9bMIUlWwOdc0YOT8f1VaK0gBaz33dS3-awhC8E8wGZHOvWvMZHNcnofHCYf1XG0Wz12IdUTZCa1SLYBkq71hiyM9DeHaRlVWKhf2coirG1TIWgWrx',
    },
    {
        id: 3,
        title: 'Detik Terakhir',
        director: 'Drama series · Season Finale',
        views: '41,820 views',
        growth: '+6%',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuk-0QSQsh71jEqpkE9cpN2kAyPO73ZDAJ67atXgolm7epeZ49FZHMdDTIiWXXi-Jc4SYY5vk4FmOR6iAFRTDkoyudHx5uGJ5SMSl6q0n9Dj2vBJn7cdg4adZPo25CweWS8ESPsOap1L7mgx7Y_-lY586Xrfu0TJlyCCU2ElnkcHjdB_EnYEFVRanOMMMcdDAm45EpmxZnZwtxSfzSoiAg7N85yGvyZix-bYZF85cS_-Fy96hnNGdX',
    },
    {
        id: 4,
        title: 'Senja di Kota Tua',
        director: 'Documentary retrospective',
        views: '32,150 views',
        growth: '+2%',
        thumb: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA5tQEyjRo0lPH_wh7HlnHZzBJMIHn5vy5CJ9YGXu0ZU5l4C1x080xIErtEGrDsj80hzZPrtJs70V_73f1kgDkEg6-ozuzpAZd7h-73033Uis65ZpY8gHI6mkQjE-V7vR7vUUgfIvpmODOTUNE1eTQTbiawhIlpexC47hFqnwYnrVtBaMz2zQIVQKwXfA3A50nRGh2bCH8RD3Jch0II3_MHd8vOWYS7Rf1Iqfv4ujcSeXfzD-5zq_EZ',
    },
];

// Activity items
const activities = [
    {
        id: 1,
        title: 'Alya Kusuma sent you a message',
        time: '12 min ago',
        icon: MessageSquare,
    },
    {
        id: 2,
        title: '3 new subscribers',
        time: '1 hour ago',
        icon: UserPlus,
    },
    {
        id: 3,
        title: 'Rangga left a 5-star review on Detik Terakhir',
        time: '3 hours ago',
        icon: Star,
    },
    {
        id: 4,
        title: 'Cakrawala Senja EP 08 reached 10,000 views',
        time: 'Yesterday',
        icon: Film,
    },
    {
        id: 5,
        title: 'Payout of Rp 2.500.000 completed',
        time: '2 days ago',
        icon: Wallet,
    },
];

const handleLogout = () => {
    router.post(logout.url());
};
</script>

<template>
    <div class="min-h-screen bg-[#0E0F11] text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white">
        <Head title="FlixRead Creator Studio — Film Dashboard" />

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
                        @click="activeTab = 'dashboard'"
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
                        @click="activeTab = 'content'"
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
                        @click="activeTab = 'analytics'"
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
                        @click="activeTab = 'earnings'"
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
                        <!-- 4px cobalt dot indicator -->
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
        <!-- MAIN WORKSPACE CANVAS                                          -->
        <!-- ============================================================== -->
        <main class="mx-auto max-w-7xl space-y-8 px-4 pt-10 pb-16 md:px-8">
            <!-- [2] PAGE HEADER -->
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

            <!-- [3] 4 STAT CARDS ROW -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Card 1: TOTAL VIEWS -->
                <div
                    class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15"
                >
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">TOTAL VIEWS</span>
                        <!-- Micro sparkline SVG -->
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path
                                d="M0 16 L8 12 L16 15 L24 8 L32 10 L40 3"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                            ></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">
                            248,320
                        </div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +12.4%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: WATCH HOURS -->
                <div
                    class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15"
                >
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">WATCH HOURS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path
                                d="M0 18 L10 14 L18 11 L28 12 L34 6 L40 4"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                            ></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">
                            8,412
                        </div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +8.2%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: SUBSCRIBERS -->
                <div
                    class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15"
                >
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">SUBSCRIBERS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path
                                d="M0 15 L8 16 L18 10 L26 11 L32 7 L40 5"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                            ></path>
                        </svg>
                    </div>
                    <div class="mt-4">
                        <div class="font-mono text-2xl font-semibold tracking-tight text-[#F2EFEA]">
                            12,047
                        </div>
                        <div class="mt-1 flex items-center space-x-1.5 text-xs">
                            <span class="flex items-center font-mono font-medium text-[#7A9BFF]">
                                <span class="mr-0.5 text-[12px]">↑</span> +3.1%
                            </span>
                            <span class="text-[#8E8FA2]">vs last period</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: ESTIMATED EARNINGS -->
                <div
                    class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-5 transition-colors hover:border-white/15"
                >
                    <div class="flex items-center justify-between text-[#8E8FA2]">
                        <span class="font-mono text-[10px] tracking-wider uppercase">ESTIMATED EARNINGS</span>
                        <svg class="h-5 w-10 text-[#7A9BFF] opacity-70" fill="none" viewBox="0 0 40 20">
                            <path
                                d="M0 17 L9 13 L17 14 L25 8 L33 9 L40 2"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                            ></path>
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

            <!-- [4] PERFORMANCE CHART: VIEWS OVER TIME -->
            <div class="rounded-xl border border-white/[0.06] bg-[#141518] p-6">
                <!-- Chart Header -->
                <div class="flex flex-col justify-between gap-3 border-b border-white/[0.08] pb-6 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-[#F2EFEA]">Views over time</h2>
                        <p class="text-xs text-[#8E8FA2]">{{ selectedPeriodLabel }} telemetry stream</p>
                    </div>

                    <!-- Range Tabs -->
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

                <!-- Chart Viewport Canvas -->
                <div class="relative mt-6 h-64 w-full">
                    <!-- SVG Coordinate Grid -->
                    <svg class="h-full w-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 1000 240">
                        <defs>
                            <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#1B44E8" stop-opacity="0.14"></stop>
                                <stop offset="100%" stop-color="#1B44E8" stop-opacity="0.00"></stop>
                            </linearGradient>
                        </defs>

                        <!-- Horizontal reference hairline guides -->
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="40" y2="40"></line>
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="90" y2="90"></line>
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="140" y2="140"></line>
                        <line stroke="rgba(242, 239, 234, 0.04)" stroke-width="1" x1="0" x2="1000" y1="190" y2="190"></line>
                        <line stroke="rgba(242, 239, 234, 0.12)" stroke-width="1" x1="0" x2="1000" y1="239" y2="239"></line>

                        <!-- Filled under-path -->
                        <polygon fill="url(#chartGradient)" :points="currentChart.poly"></polygon>

                        <!-- Pure crisp 2px cobalt stroke -->
                        <polyline
                            fill="none"
                            :points="currentChart.points"
                            stroke="#1B44E8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                        ></polyline>

                        <!-- Active Sample Hover Line (Technical Inspection indicator) -->
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

                    <!-- Active Hairline Tooltip Card pinned near active coordinate -->
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

                    <!-- Y-Axis labels (Recessed left gutter) -->
                    <div class="pointer-events-none absolute bottom-6 left-0 top-0 flex flex-col justify-between pr-2 font-mono text-[10px] text-[#8E8FA2]">
                        <span>20K</span>
                        <span>15K</span>
                        <span>10K</span>
                        <span>5K</span>
                        <span>0</span>
                    </div>
                </div>

                <!-- X-Axis timeline labels -->
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

            <!-- [5] TWO-COLUMN BLOCK: TOP PERFORMING FILMS + RECENT ACTIVITY -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <!-- LEFT: Top Performing Films (7/12) -->
                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-6 lg:col-span-7">
                    <div>
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-4">
                            <h2 class="text-base font-semibold text-[#F2EFEA]">Top performing films</h2>
                            <button
                                type="button"
                                @click="isUploadModalOpen = true"
                                class="flex items-center space-x-1 font-mono text-xs text-[#8E8FA2] hover:text-[#F2EFEA] transition-colors"
                            >
                                <span>Manage catalog</span>
                                <ArrowUpRight class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <!-- Film Rows -->
                        <div class="mt-2 divide-y divide-white/[0.06]">
                            <div
                                v-for="film in films"
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
                                            {{ film.director }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4 flex-shrink-0">
                                    <div class="text-right">
                                        <div class="font-mono text-xs font-medium text-[#F2EFEA]">{{ film.views }}</div>
                                        <div class="font-mono text-[11px] text-[#7A9BFF]">{{ film.growth }}</div>
                                    </div>
                                    <ChevronRight class="h-4 w-4 text-[#8E8FA2] opacity-40 transition-opacity group-hover:opacity-100" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Left Card Footer -->
                    <div class="mt-4 flex items-center justify-between border-t border-white/[0.06] pt-3 font-mono text-[11px] text-[#8E8FA2]">
                        <span>Active streaming titles: 12</span>
                        <span>Platform rank: Top 5%</span>
                    </div>
                </div>

                <!-- RIGHT: Recent Activity (5/12) -->
                <div class="flex flex-col justify-between rounded-xl border border-white/[0.06] bg-[#141518] p-6 lg:col-span-5">
                    <div>
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-4">
                            <h2 class="text-base font-semibold text-[#F2EFEA]">Recent activity</h2>
                            <span class="font-mono text-xs text-[#8E8FA2]">Realtime</span>
                        </div>

                        <!-- Feed Items -->
                        <div class="mt-3 space-y-3.5">
                            <div
                                v-for="act in activities"
                                :key="act.id"
                                class="flex items-start space-x-3"
                            >
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/10 bg-white/[0.04] text-[#8E8FA2]">
                                    <component :is="act.icon" class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm leading-tight text-[#F2EFEA]">{{ act.title }}</p>
                                    <span class="font-mono text-[10px] text-[#8E8FA2]">{{ act.time }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Card Footer -->
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

            <!-- [6] QUICK ACTIONS ROW -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Action 1: Upload new film -->
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

                <!-- Action 2: View analytics -->
                <button
                    type="button"
                    @click="activeTab = 'analytics'"
                    class="group flex items-center space-x-4 rounded-xl border border-white/[0.06] bg-[#141518] p-4 text-left transition-all hover:border-white/15 hover:bg-[#1A1C1F] cursor-pointer"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-[#1E2023] text-[#F2EFEA] transition-colors group-hover:border-[#1B44E8]">
                        <BarChart3 class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#F2EFEA]">View analytics</div>
                        <p class="text-xs text-[#8E8FA2]">Deep dive into performance</p>
                    </div>
                </button>

                <!-- Action 3: Request payout -->
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

                <div v-if="uploadSuccessMessage" class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-center">
                    <p class="text-sm font-medium text-emerald-400">{{ uploadSuccessMessage }}</p>
                </div>

                <form v-else @submit.prevent="submitUpload" class="mt-4 space-y-4">
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

                    <!-- Upload dropzone aesthetic -->
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
        <!-- [MODAL 2] REQUEST PAYOUT MODAL                                 -->
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

                <div v-if="payoutSuccessMessage" class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-center">
                    <p class="text-sm font-medium text-emerald-400">{{ payoutSuccessMessage }}</p>
                </div>

                <div v-else class="mt-5 space-y-4">
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
