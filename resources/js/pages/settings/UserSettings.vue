<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Check,
    ChevronDown,
    ChevronRight,
    CreditCard,
    Download,
    Eye,
    EyeOff,
    Laptop,
    Lock,
    LogOut,
    Mail,
    Phone,
    Search,
    Shield,
    Sliders,
    Smartphone,
    Trash2,
    User as UserIcon,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { dashboard, logout, pricing } from '@/routes';
import { destroy as destroyProfile, update as updateProfile } from '@/routes/profile';
import { update as updatePassword } from '@/routes/user-password';
import type { User } from '@/types/auth';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string | null;
}>();

const page = usePage<{ auth: { user: User } }>();
const currentUser = computed(() => page.props.auth.user);

const normalizeTier = (val?: string | null): 'FREE' | 'PRO' | 'VIP' => {
    if (!val) return 'FREE';
    const up = val.toUpperCase();
    if (up === 'VIP') return 'VIP';
    if (up === 'PRO') return 'PRO';
    return 'FREE';
};

const getInitialTier = (): 'FREE' | 'PRO' | 'VIP' => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('watoon_user_tier');
        if (stored) return normalizeTier(stored);
    }
    return normalizeTier(currentUser.value?.tier);
};

const currentUserTier = ref<'FREE' | 'PRO' | 'VIP'>(getInitialTier());

// Active section tab
const activeTab = ref<'profile' | 'account' | 'subscription' | 'preferences' | 'privacy'>('profile');

// Modals state
const isSearchOpen = ref(false);
const isUserMenuOpen = ref(false);
const isPasswordModalOpen = ref(false);
const isEmailModalOpen = ref(false);
const isPhoneModalOpen = ref(false);
const isPlanModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const is2faModalOpen = ref(false);
const isBillingExpanded = ref(false);
const toastMessage = ref<string | null>(null);

// Forms
const profileForm = useForm({
    name: currentUser.value?.name || '',
    username: currentUser.value?.username || (currentUser.value?.email ? currentUser.value.email.split('@')[0] : 'rianprtm'),
    email: currentUser.value?.email || '',
    bio: currentUser.value?.bio || 'Film archivist and cinema essayist. Currently diving into 1970s Southeast Asian cinema and novel adaptations.',
    phone: currentUser.value?.phone || '+62 812 3456 7890',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({
    password: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);

// Preferences state
const selectedLanguage = ref('Bahasa Indonesia');
const selectedQuality = ref('Auto (1080p)');
const isLanguageModalOpen = ref(false);
const isQualityModalOpen = ref(false);

const preferences = ref({
    autoplayNext: true,
    emailNotifications: true,
    pushNotifications: false,
    autoplayPreviews: true,
});

// Privacy state
const profileVisibility = ref('Public');
const directMessages = ref('People I follow');
const showWatchHistory = ref(false);
const isVisibilityModalOpen = ref(false);
const isDmModalOpen = ref(false);

// Active sessions
const sessions = ref([
    {
        id: 1,
        device: 'MacBook Pro · Jakarta, Indonesia',
        meta: 'WATOON Web App · Chrome 128',
        isCurrent: true,
    },
    {
        id: 2,
        device: 'iPhone 15 Pro · Jakarta, Indonesia',
        meta: 'Active 2 days ago · iOS Native App',
        isCurrent: false,
    },
]);

// Search query
const searchQuery = ref('');
const sampleFilms = [
    { id: 101, title: 'The Silent Melody', genre: 'Poetic Cinema' },
    { id: 102, title: 'Echoes of Jakarta', genre: 'Modern Noir' },
    { id: 103, title: 'The Archivist', genre: 'Psychological Thriller' },
    { id: 104, title: 'Cold Meridian', genre: 'Arctic Maritime' },
];

const filteredFilms = computed(() => {
    if (!searchQuery.value) return sampleFilms;
    return sampleFilms.filter(f =>
        f.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        f.genre.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// User Initials
const initials = computed(() => {
    const name = currentUser.value?.name || 'Rian Pratama';
    return name
        .split(' ')
        .map(n => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

// Toast notification helper
const showToast = (message: string) => {
    toastMessage.value = message;
    setTimeout(() => {
        if (toastMessage.value === message) {
            toastMessage.value = null;
        }
    }, 4000);
};

// Switch Tab smooth scroll
const switchTab = (tab: 'profile' | 'account' | 'subscription' | 'preferences' | 'privacy') => {
    activeTab.value = tab;
    const el = document.getElementById(`section-${tab}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Intersection Observer for scroll highlight
let observer: IntersectionObserver | null = null;
onMounted(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            isSearchOpen.value = true;
        }
        if (e.key === 'Escape') {
            isSearchOpen.value = false;
            isUserMenuOpen.value = false;
            isPasswordModalOpen.value = false;
            isEmailModalOpen.value = false;
            isPhoneModalOpen.value = false;
            isPlanModalOpen.value = false;
            isDeleteModalOpen.value = false;
            isLanguageModalOpen.value = false;
            isQualityModalOpen.value = false;
            isVisibilityModalOpen.value = false;
            isDmModalOpen.value = false;
            is2faModalOpen.value = false;
        }
    };
    window.addEventListener('keydown', handleKeyDown);

    const sections = ['profile', 'account', 'subscription', 'preferences', 'privacy'];
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.id.replace('section-', '') as typeof activeTab.value;
                    if (sections.includes(id)) {
                        activeTab.value = id;
                    }
                }
            });
        },
        { rootMargin: '-20% 0px -70% 0px' }
    );

    sections.forEach((id) => {
        const el = document.getElementById(`section-${id}`);
        if (el) observer?.observe(el);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
        observer?.disconnect();
    });
});

// Profile submit
const submitProfile = () => {
    profileForm.patch(updateProfile().url, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Profil berhasil disimpan');
        },
    });
};

const resetProfile = () => {
    profileForm.reset();
    profileForm.name = currentUser.value?.name || '';
    profileForm.username = currentUser.value?.username || (currentUser.value?.email ? currentUser.value.email.split('@')[0] : 'rianprtm');
    profileForm.email = currentUser.value?.email || '';
    profileForm.bio = currentUser.value?.bio || 'Film archivist and cinema essayist. Currently diving into 1970s Southeast Asian cinema and novel adaptations.';
    profileForm.phone = currentUser.value?.phone || '+62 812 3456 7890';
    showToast('Perubahan dibatalkan');
};

// Password submit
const submitPassword = () => {
    passwordForm.put(updatePassword().url, {
        preserveScroll: true,
        onSuccess: () => {
            isPasswordModalOpen.value = false;
            passwordForm.reset();
            showToast('Kata sandi berhasil diperbarui');
        },
    });
};

// Delete account submit
const submitDelete = () => {
    deleteForm.delete(destroyProfile().url, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        },
    });
};

// Remove session
const signOutSession = (id: number) => {
    sessions.value = sessions.value.filter(s => s.id !== id);
    showToast('Sesi perangkat berhasil dikeluarkan');
};

// Download invoice simulated
const downloadInvoice = (date: string) => {
    showToast(`Invoice WATOON (${date}) berhasil diunduh`);
};
</script>

<template>
    <div class="min-h-screen bg-[#0E0F11] font-sans text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white">
        <Head title="Account Settings - WATOON" />

        <!-- ==================== 1. FLOATING DETACHED NAVBAR ==================== -->
        <header class="fixed top-4 left-0 right-0 z-50 px-4 pointer-events-none">
            <nav class="pointer-events-auto max-w-[1240px] mx-auto h-14 bg-[#17181B]/90 backdrop-blur-md border border-[rgba(242,239,234,0.08)] rounded-[12px] px-5 flex items-center justify-between shadow-[0_8px_30px_rgb(0,0,0,0.45)]">
                <!-- Brand & Primary Nav -->
                <div class="flex items-center gap-7">
                    <Link :href="dashboard.url()" class="flex items-center gap-2 group">
                        <span class="text-base font-semibold tracking-tight text-[#F2EFEA] group-hover:text-white transition-colors duration-150">WATOON</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1B44E8]"></span>
                    </Link>

                    <div class="hidden md:flex items-center gap-6 text-[13px] font-medium text-[#8C8C90]">
                        <Link :href="dashboard.url()" class="hover:text-[#F2EFEA] transition-colors duration-150">Home</Link>
                        <Link :href="dashboard.url({ query: { tab: 'favorite' } })" class="hover:text-[#F2EFEA] transition-colors duration-150">Favorite</Link>
                        <Link :href="dashboard.url({ query: { tab: 'subscribe' } })" class="hover:text-[#F2EFEA] transition-colors duration-150">Subscribe</Link>
                    </div>

                    <!-- Global Search Input Trigger -->
                    <div class="relative hidden sm:block">
                        <button
                            type="button"
                            @click="isSearchOpen = true"
                            class="flex items-center w-[200px] h-[36px] bg-[#1E1F23] border border-[rgba(242,239,234,0.08)] rounded-[10px] px-2.5 text-[#8C8C90] hover:border-[rgba(242,239,234,0.2)] focus:border-[#1B44E8] transition-colors duration-150"
                        >
                            <Search class="w-4 h-4 mr-2 text-[#8C8C90]" />
                            <span class="text-[12px] text-[#8C8C90] text-left flex-1">Search films...</span>
                            <kbd class="text-[10px] font-mono border border-[rgba(242,239,234,0.15)] rounded px-1 text-[#8C8C90] bg-[#17181B]">⌘K</kbd>
                        </button>
                    </div>
                </div>

                <!-- Right Nav Cluster: Switcher, PRO badge, User Profile -->
                <div class="flex items-center gap-3">
                    <!-- Film / Novel Segmented Control -->
                    <div class="bg-[#1E1F23] p-1 rounded-[8px] border border-[rgba(242,239,234,0.06)] flex items-center text-[12px] font-medium">
                        <button class="px-2.5 py-1 rounded-[6px] bg-[#17181B] text-[#F2EFEA] shadow-sm border border-[rgba(242,239,234,0.08)]" type="button">Films</button>
                        <button class="px-2.5 py-1 rounded-[6px] text-[#8C8C90] hover:text-[#F2EFEA] transition-colors" type="button">Novels</button>
                    </div>

                    <!-- User Tier Chip -> Navigate to Pricing -->
                    <Link
                        :href="pricing.url()"
                        class="hidden lg:flex items-center px-2.5 py-1 rounded-[6px] text-[11px] font-semibold tracking-wide uppercase transition-all select-none hover:opacity-90"
                        :class="[
                            currentUserTier === 'VIP'
                                ? 'border border-[#D4B67A]/50 bg-[#D4B67A]/15 text-[#D4B67A]'
                                : currentUserTier === 'PRO'
                                ? 'border border-[rgba(27,68,232,0.45)] bg-[rgba(27,68,232,0.18)] text-[#7A9BFF]'
                                : 'border border-[rgba(242,239,234,0.15)] bg-[rgba(242,239,234,0.06)] text-[#A0A0A5]'
                        ]"
                        :title="`Status Kasta: ${currentUserTier} (Klik untuk buka halaman paket langganan)`"
                    >
                        {{ currentUserTier }} PLAN
                    </Link>

                    <!-- User Avatar with Settings Active Indicator -->
                    <div class="relative pl-1">
                        <button
                            type="button"
                            @click="isUserMenuOpen = !isUserMenuOpen"
                            class="w-8 h-8 rounded-full bg-[#1E1F23] border border-[rgba(242,239,234,0.15)] flex items-center justify-center text-[12px] font-semibold text-[#F2EFEA] overflow-hidden focus:outline-none hover:border-[rgba(242,239,234,0.3)] transition-colors"
                        >
                            {{ initials }}
                        </button>
                        <!-- Cobalt Active Setting Pip -->
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-[#1B44E8] rounded-full border-2 border-[#17181B]" title="Currently in Account Settings"></span>

                        <!-- User Dropdown Menu -->
                        <div
                            v-if="isUserMenuOpen"
                            class="absolute right-0 mt-3 w-52 rounded-xl bg-[#17181B] border border-[rgba(242,239,234,0.10)] p-1.5 shadow-2xl z-50 text-[13px]"
                        >
                            <div class="px-3 py-2 border-b border-[rgba(242,239,234,0.08)] mb-1">
                                <p class="font-medium text-[#F2EFEA] truncate">{{ currentUser.name }}</p>
                                <p class="text-[11px] text-[#8C8C90] truncate">{{ currentUser.email }}</p>
                            </div>
                            <Link
                                :href="dashboard.url()"
                                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-white/[0.04] transition-colors"
                            >
                                <Laptop class="w-4 h-4 text-[#8C8C90]" />
                                <span>Dashboard</span>
                            </Link>
                            <button
                                type="button"
                                @click="isPlanModalOpen = true; isUserMenuOpen = false;"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-white/[0.04] transition-colors"
                            >
                                <div class="flex items-center gap-2.5">
                                    <CreditCard class="w-4 h-4 text-[#7A9BFF]" />
                                    <span>Langganan Pro</span>
                                </div>
                                <span class="text-[10px] font-semibold text-[#7A9BFF] bg-[rgba(27,68,232,0.2)] px-1.5 py-0.5 rounded">PRO</span>
                            </button>
                            <div class="border-t border-[rgba(242,239,234,0.08)] my-1"></div>
                            <Link
                                :href="logout.url()"
                                method="post"
                                as="button"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#E85C4A]/80 hover:text-[#E85C4A] hover:bg-[#E85C4A]/10 transition-colors text-left"
                            >
                                <LogOut class="w-4 h-4 text-[#E85C4A]" />
                                <span>Keluar</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Navbar Spacing -->
        <div class="h-24"></div>

        <!-- ==================== 2. PAGE HEADER ==================== -->
        <section class="max-w-[1200px] mx-auto px-6 sm:px-8 pt-8 pb-10">
            <div class="border-b border-[rgba(242,239,234,0.08)] pb-8">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[11px] text-[#1B44E8] uppercase tracking-[0.12em] font-semibold">ACCOUNT</span>
                    <span class="text-[#8C8C90] text-xs">/</span>
                    <span class="text-[11px] uppercase tracking-[0.12em] text-[#8C8C90]">VIEWER PORTAL</span>
                </div>
                <h1 class="text-3xl font-semibold text-[#F2EFEA] tracking-tight">Settings</h1>
                <p class="text-[15px] text-[#8C8C90] mt-1.5">Manage your profile, subscription, and preferences.</p>
            </div>
        </section>

        <!-- ==================== 3. TWO-COLUMN LAYOUT ==================== -->
        <main class="max-w-[1200px] mx-auto px-6 sm:px-8 pb-28">
            <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-12 lg:gap-16 items-start">
                <!-- ==================== LEFT SIDEBAR ==================== -->
                <aside class="sticky top-24 space-y-1">
                    <!-- Interactive Sidebar Navigation Tabs -->
                    <nav class="space-y-1">
                        <button
                            type="button"
                            @click="switchTab('profile')"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-[8px] text-[13px] font-medium transition-all text-left',
                                activeTab === 'profile'
                                    ? 'text-[#F2EFEA] bg-[rgba(242,239,234,0.05)] border-l-2 border-[#1B44E8]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-[rgba(242,239,234,0.02)] border-l-2 border-transparent',
                            ]"
                        >
                            <UserIcon class="w-[18px] h-[18px]" />
                            <span>Profile</span>
                        </button>

                        <button
                            type="button"
                            @click="switchTab('account')"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-[8px] text-[13px] font-medium transition-all text-left',
                                activeTab === 'account'
                                    ? 'text-[#F2EFEA] bg-[rgba(242,239,234,0.05)] border-l-2 border-[#1B44E8]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-[rgba(242,239,234,0.02)] border-l-2 border-transparent',
                            ]"
                        >
                            <BadgeCheck class="w-[18px] h-[18px]" />
                            <span>Account</span>
                        </button>

                        <button
                            type="button"
                            @click="switchTab('subscription')"
                            :class="[
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-[8px] text-[13px] font-medium transition-all text-left',
                                activeTab === 'subscription'
                                    ? 'text-[#F2EFEA] bg-[rgba(242,239,234,0.05)] border-l-2 border-[#1B44E8]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-[rgba(242,239,234,0.02)] border-l-2 border-transparent',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <CreditCard class="w-[18px] h-[18px]" />
                                <span>Subscription</span>
                            </div>
                            <span class="text-[10px] font-semibold text-[#7A9BFF] bg-[rgba(27,68,232,0.2)] px-1.5 py-0.5 rounded">PRO</span>
                        </button>

                        <button
                            type="button"
                            @click="switchTab('preferences')"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-[8px] text-[13px] font-medium transition-all text-left',
                                activeTab === 'preferences'
                                    ? 'text-[#F2EFEA] bg-[rgba(242,239,234,0.05)] border-l-2 border-[#1B44E8]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-[rgba(242,239,234,0.02)] border-l-2 border-transparent',
                            ]"
                        >
                            <Sliders class="w-[18px] h-[18px]" />
                            <span>Preferences</span>
                        </button>

                        <button
                            type="button"
                            @click="switchTab('privacy')"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2.5 rounded-[8px] text-[13px] font-medium transition-all text-left',
                                activeTab === 'privacy'
                                    ? 'text-[#F2EFEA] bg-[rgba(242,239,234,0.05)] border-l-2 border-[#1B44E8]'
                                    : 'text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-[rgba(242,239,234,0.02)] border-l-2 border-transparent',
                            ]"
                        >
                            <Shield class="w-[18px] h-[18px]" />
                            <span>Privacy &amp; Security</span>
                        </button>
                    </nav>

                    <div class="pt-6 mt-6 border-t border-[rgba(242,239,234,0.08)]">
                        <a
                            href="#delete-section"
                            class="flex items-center gap-2.5 px-3 py-2 text-[13px] font-medium text-[#E85C4A]/80 hover:text-[#E85C4A] hover:bg-[#E85C4A]/5 rounded-[8px] transition-colors"
                        >
                            <Trash2 class="w-[18px] h-[18px]" />
                            <span>Delete account</span>
                        </a>
                    </div>

                    <!-- Sidebar Plan Status Callout -->
                    <div class="mt-8 p-3.5 rounded-[10px] bg-[#17181B] border border-[rgba(242,239,234,0.06)] text-[12px]">
                        <div class="flex items-center justify-between text-[#8C8C90] mb-1">
                            <span>Storage / History</span>
                            <span class="text-[#F2EFEA]">78%</span>
                        </div>
                        <div class="w-full bg-[#1E1F23] h-1.5 rounded-full overflow-hidden">
                            <div class="bg-[#1B44E8] h-full w-[78%]"></div>
                        </div>
                        <p class="text-[11px] text-[#8C8C90] mt-2">1,240 saved bookmarks and 48 curated film reviews.</p>
                    </div>
                </aside>

                <!-- ==================== RIGHT CONTENT AREA ==================== -->
                <div class="w-full max-w-[760px] space-y-16">
                    <!-- SECTION: Profile -->
                    <section class="scroll-mt-28 space-y-8" id="section-profile">
                        <div>
                            <h2 class="text-lg font-semibold text-[#F2EFEA]">Profile</h2>
                            <p class="text-[13px] text-[#8C8C90] mt-0.5">This is how you appear across WATOON.</p>
                        </div>

                        <!-- Avatar Row with 72px Circular Avatar & attached PRO Tier Badge -->
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <div class="w-[72px] h-[72px] rounded-full bg-[#1E1F23] border border-[rgba(242,239,234,0.15)] flex items-center justify-center text-[22px] font-semibold text-[#F2EFEA] tracking-tight">
                                    {{ initials }}
                                </div>
                                <!-- Attached PRO Tier Badge with 2px #0E0F11 isolation ring -->
                                <span class="absolute -bottom-1 -right-1 h-[20px] px-2 rounded-full bg-[#1B44E8] text-[#FFFFFF] text-[10px] font-bold tracking-wider flex items-center justify-center border-2 border-[#0E0F11] shadow-sm">
                                    PRO
                                </span>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="showToast('Fitur unggah avatar siap digunakan')"
                                        class="h-[36px] px-4 rounded-[10px] bg-transparent border border-[rgba(242,239,234,0.12)] hover:border-[rgba(242,239,234,0.3)] hover:bg-[#1E1F23] text-[13px] font-medium text-[#F2EFEA] transition-all"
                                    >
                                        Change photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="showToast('Foto profil direset')"
                                        class="h-[36px] px-3 text-[13px] text-[#8C8C90] hover:text-[#E85C4A] transition-colors"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <p class="text-[12px] text-[#8C8C90]">JPG, PNG · Max 2MB recommended</p>
                            </div>
                        </div>

                        <!-- Profile Form Fields (Bottom-border only) -->
                        <form class="space-y-6 pt-2" @submit.prevent="submitProfile">
                            <!-- Display Name -->
                            <div class="border-b border-[rgba(242,239,234,0.10)] focus-within:border-[#1B44E8] transition-colors pb-1">
                                <label for="display_name" class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase mb-1">Display name</label>
                                <input
                                    id="display_name"
                                    v-model="profileForm.name"
                                    class="w-full bg-transparent border-0 p-0 h-[38px] text-[15px] font-medium text-[#F2EFEA] focus:ring-0 focus:outline-none"
                                    type="text"
                                    required
                                />
                                <span v-if="profileForm.errors.name" class="text-xs text-[#E85C4A] mt-1 block">{{ profileForm.errors.name }}</span>
                            </div>

                            <!-- Username -->
                            <div class="border-b border-[rgba(242,239,234,0.10)] focus-within:border-[#1B44E8] transition-colors pb-1">
                                <label for="username" class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase mb-1">Username</label>
                                <div class="flex items-center">
                                    <span class="text-[#8C8C90] text-[15px] mr-1">@</span>
                                    <input
                                        id="username"
                                        v-model="profileForm.username"
                                        class="w-full bg-transparent border-0 p-0 h-[38px] text-[15px] font-medium text-[#F2EFEA] focus:ring-0 focus:outline-none"
                                        type="text"
                                    />
                                </div>
                                <span class="text-[12px] text-[#8C8C90] mt-1 block">Public URL: <span class="text-[#7A9BFF]">watoon.com/u/{{ profileForm.username || 'username' }}</span></span>
                                <span v-if="profileForm.errors.username" class="text-xs text-[#E85C4A] mt-1 block">{{ profileForm.errors.username }}</span>
                            </div>

                            <!-- Bio -->
                            <div class="border-b border-[rgba(242,239,234,0.10)] focus-within:border-[#1B44E8] transition-colors pb-2">
                                <div class="flex justify-between items-center mb-1">
                                    <label for="bio" class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase">Bio</label>
                                    <span class="text-[11px] text-[#8C8C90]">{{ (profileForm.bio || '').length }} / 240</span>
                                </div>
                                <textarea
                                    id="bio"
                                    v-model="profileForm.bio"
                                    maxlength="240"
                                    class="w-full bg-transparent border-0 p-0 text-[14px] text-[#F2EFEA] placeholder-[#8C8C90]/60 resize-none focus:ring-0 focus:outline-none leading-relaxed"
                                    rows="3"
                                    placeholder="Tell the community about your cinema and reading tastes..."
                                ></textarea>
                            </div>

                            <!-- Action Row -->
                            <div class="flex items-center gap-4 pt-3">
                                <button
                                    :disabled="profileForm.processing"
                                    class="h-[40px] px-5 rounded-[10px] bg-[#F2EFEA] text-[#0E0F11] font-medium text-[13px] hover:bg-white transition-all shadow-[0_1px_2px_rgba(0,0,0,0.2)] disabled:opacity-50"
                                    type="submit"
                                >
                                    {{ profileForm.processing ? 'Saving...' : 'Save changes' }}
                                </button>
                                <button
                                    type="button"
                                    @click="resetProfile"
                                    class="text-[13px] text-[#8C8C90] hover:text-[#F2EFEA] transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Divider -->
                    <hr class="border-t border-[rgba(242,239,234,0.08)] my-10" />

                    <!-- SECTION: Account -->
                    <section class="scroll-mt-28 space-y-6" id="section-account">
                        <div>
                            <h2 class="text-lg font-semibold text-[#F2EFEA]">Account</h2>
                            <p class="text-[13px] text-[#8C8C90] mt-0.5">Manage your login credentials and contact info.</p>
                        </div>

                        <div class="space-y-0 divide-y divide-[rgba(242,239,234,0.08)]">
                            <!-- Email Row -->
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase">EMAIL</span>
                                    <div class="flex items-center gap-2.5 mt-1">
                                        <span class="text-[14px] text-[#F2EFEA] font-medium">{{ currentUser.email }}</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-[#1B44E8]/10 text-[#7A9BFF] border border-[#1B44E8]/20">
                                            <Check class="w-3.5 h-3.5 text-[#1B44E8]" />
                                            Verified
                                        </span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="isEmailModalOpen = true"
                                    class="text-[13px] font-medium text-[#1B44E8] hover:text-[#7A9BFF] self-start sm:self-center transition-colors"
                                >
                                    Change email
                                </button>
                            </div>

                            <!-- Password Row -->
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase">PASSWORD</span>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-[15px] text-[#F2EFEA] tracking-wider">••••••••••••</span>
                                        <span class="text-[12px] text-[#8C8C90]">Last changed 3 months ago</span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="isPasswordModalOpen = true"
                                    class="text-[13px] font-medium text-[#1B44E8] hover:text-[#7A9BFF] self-start sm:self-center transition-colors"
                                >
                                    Change password
                                </button>
                            </div>

                            <!-- Phone Row -->
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase">PHONE</span>
                                    <span class="block text-[14px] text-[#F2EFEA] font-medium mt-1">{{ currentUser.phone || profileForm.phone || '+62 812 3456 7890' }}</span>
                                </div>
                                <button
                                    type="button"
                                    @click="isPhoneModalOpen = true"
                                    class="text-[13px] font-medium text-[#1B44E8] hover:text-[#7A9BFF] self-start sm:self-center transition-colors"
                                >
                                    Change
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Divider -->
                    <hr class="border-t border-[rgba(242,239,234,0.08)] my-10" />

                    <!-- SECTION: Subscription -->
                    <section class="scroll-mt-28 space-y-6" id="section-subscription">
                        <div>
                            <h2 class="text-lg font-semibold text-[#F2EFEA]">Subscription</h2>
                            <p class="text-[13px] text-[#8C8C90] mt-0.5">Your current plan and billing details.</p>
                        </div>

                        <!-- Current Plan Block -->
                        <div class="p-6 rounded-[12px] bg-[#17181B] border border-[rgba(242,239,234,0.08)]">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-[rgba(242,239,234,0.06)]">
                                <div>
                                    <span class="text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase block">CURRENT PLAN</span>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <span class="text-2xl font-semibold text-[#F2EFEA]">Pro</span>
                                        <span class="px-2.5 py-0.5 rounded-[6px] bg-[rgba(27,68,232,0.20)] border border-[rgba(27,68,232,0.45)] text-[#7A9BFF] text-[11px] font-semibold">
                                            PRO
                                        </span>
                                    </div>
                                    <p class="text-[15px] font-medium text-[#F2EFEA] mt-1">Rp 80.000 <span class="text-[#8C8C90] font-normal text-[13px]">/ month</span></p>
                                    <p class="text-[12px] text-[#8C8C90] mt-0.5">Renews automatically on <span class="text-[#F2EFEA]">14 Nov 2026</span></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="isPlanModalOpen = true"
                                        class="h-[36px] px-4 rounded-[10px] bg-transparent border border-[rgba(242,239,234,0.12)] hover:border-[rgba(242,239,234,0.25)] hover:bg-[#1E1F23] text-[13px] font-medium text-[#F2EFEA] transition-all"
                                    >
                                        Change plan
                                    </button>
                                </div>
                            </div>

                            <!-- Plan Features List -->
                            <div class="pt-5 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div class="flex items-center gap-2.5 text-[13px] text-[#F2EFEA]">
                                    <Check class="w-4 h-4 text-[#1B44E8]" />
                                    <span>Full access to the film library</span>
                                </div>
                                <div class="flex items-center gap-2.5 text-[13px] text-[#F2EFEA]">
                                    <Check class="w-4 h-4 text-[#1B44E8]" />
                                    <span>Private chat with film creators</span>
                                </div>
                                <div class="flex items-center gap-2.5 text-[13px] text-[#F2EFEA]">
                                    <Check class="w-4 h-4 text-[#1B44E8]" />
                                    <span>HD streaming quality (1080p)</span>
                                </div>
                                <div class="flex items-center gap-2.5 text-[13px] text-[#F2EFEA]">
                                    <Check class="w-4 h-4 text-[#1B44E8]" />
                                    <span>Ad-free cinema experience</span>
                                </div>
                            </div>

                            <!-- Upgrade Hint with VIP Brass Accents -->
                            <div class="mt-6 p-3.5 rounded-[8px] bg-[#1E1F23] border border-[rgba(212,182,122,0.2)] flex items-center justify-between text-[12px]">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-[#D4B67A]"></span>
                                    <span class="text-[#F2EFEA]">Upgrade to VIP for unlimited film screening &amp; full novel catalogs.</span>
                                </div>
                                <button
                                    type="button"
                                    @click="isPlanModalOpen = true"
                                    class="text-[#7A9BFF] hover:text-white font-medium flex items-center gap-1"
                                >
                                    Upgrade &rarr;
                                </button>
                            </div>
                        </div>

                        <!-- Collapsible Billing History -->
                        <div class="pt-2">
                            <button
                                type="button"
                                @click="isBillingExpanded = !isBillingExpanded"
                                class="w-full flex items-center justify-between py-2 text-[13px] font-medium text-[#8C8C90] hover:text-[#F2EFEA] transition-colors select-none"
                            >
                                <div class="flex items-center gap-2">
                                    <ChevronDown
                                        :class="['w-[18px] h-[18px] transition-transform duration-200', isBillingExpanded ? 'rotate-180' : '']"
                                    />
                                    <span>Billing history</span>
                                </div>
                                <span class="text-[12px] text-[#8C8C90]">Last 3 payments</span>
                            </button>

                            <div v-if="isBillingExpanded" class="mt-3 space-y-0 divide-y divide-[rgba(242,239,234,0.06)] border-t border-[rgba(242,239,234,0.06)] transition-all">
                                <div class="py-3 flex items-center justify-between text-[13px]">
                                    <div>
                                        <span class="font-medium text-[#F2EFEA]">14 Oct 2026</span>
                                        <span class="text-[#8C8C90] text-[11px] block">WATOON Pro · Monthly</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="font-mono text-[13px] text-[#F2EFEA]">Rp 80.000</span>
                                        <button
                                            type="button"
                                            @click="downloadInvoice('14 Oct 2026')"
                                            class="text-[#8C8C90] hover:text-[#7A9BFF] transition-colors"
                                            title="Download invoice"
                                        >
                                            <Download class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </div>
                                <div class="py-3 flex items-center justify-between text-[13px]">
                                    <div>
                                        <span class="font-medium text-[#F2EFEA]">14 Sep 2026</span>
                                        <span class="text-[#8C8C90] text-[11px] block">WATOON Pro · Monthly</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="font-mono text-[13px] text-[#F2EFEA]">Rp 80.000</span>
                                        <button
                                            type="button"
                                            @click="downloadInvoice('14 Sep 2026')"
                                            class="text-[#8C8C90] hover:text-[#7A9BFF] transition-colors"
                                            title="Download invoice"
                                        >
                                            <Download class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </div>
                                <div class="py-3 flex items-center justify-between text-[13px]">
                                    <div>
                                        <span class="font-medium text-[#F2EFEA]">14 Aug 2026</span>
                                        <span class="text-[#8C8C90] text-[11px] block">WATOON Pro · Monthly</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="font-mono text-[13px] text-[#F2EFEA]">Rp 80.000</span>
                                        <button
                                            type="button"
                                            @click="downloadInvoice('14 Aug 2026')"
                                            class="text-[#8C8C90] hover:text-[#7A9BFF] transition-colors"
                                            title="Download invoice"
                                        >
                                            <Download class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Divider -->
                    <hr class="border-t border-[rgba(242,239,234,0.08)] my-10" />

                    <!-- SECTION: Preferences -->
                    <section class="scroll-mt-28 space-y-6" id="section-preferences">
                        <div>
                            <h2 class="text-lg font-semibold text-[#F2EFEA]">Preferences</h2>
                            <p class="text-[13px] text-[#8C8C90] mt-0.5">Fine-tune your WATOON playback and notifications.</p>
                        </div>

                        <div class="space-y-0 divide-y divide-[rgba(242,239,234,0.08)]">
                            <!-- 1) Language -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Language</span>
                                    <p class="text-[12px] text-[#8C8C90]">Interface and audio default</p>
                                </div>
                                <button
                                    type="button"
                                    @click="isLanguageModalOpen = true"
                                    class="flex items-center gap-1.5 cursor-pointer text-[13px] text-[#F2EFEA] hover:text-[#7A9BFF] transition-colors"
                                >
                                    <span>{{ selectedLanguage }}</span>
                                    <ChevronRight class="w-4 h-4 text-[#8C8C90]" />
                                </button>
                            </div>

                            <!-- 2) Playback Quality -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Playback quality</span>
                                    <p class="text-[12px] text-[#8C8C90]">Dynamic stream adjustment</p>
                                </div>
                                <button
                                    type="button"
                                    @click="isQualityModalOpen = true"
                                    class="flex items-center gap-1.5 cursor-pointer text-[13px] text-[#F2EFEA] hover:text-[#7A9BFF] transition-colors"
                                >
                                    <span>{{ selectedQuality }}</span>
                                    <ChevronRight class="w-4 h-4 text-[#8C8C90]" />
                                </button>
                            </div>

                            <!-- 3) Autoplay next episode -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Autoplay next episode</span>
                                    <p class="text-[12px] text-[#8C8C90]">Immediately queue consecutive episodes</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="preferences.autoplayNext"
                                    @click="preferences.autoplayNext = !preferences.autoplayNext; showToast('Pengaturan autoplay diperbarui');"
                                    :class="[
                                        'w-10 h-6 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-200',
                                        preferences.autoplayNext ? 'bg-[#1B44E8]' : 'bg-[rgba(242,239,234,0.15)]',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-200',
                                            preferences.autoplayNext ? 'translate-x-4' : 'translate-x-0',
                                        ]"
                                    ></div>
                                </button>
                            </div>

                            <!-- 4) Email notifications -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Email notifications</span>
                                    <p class="text-[12px] text-[#8C8C90]">Curated weekend digest and director drops</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="preferences.emailNotifications"
                                    @click="preferences.emailNotifications = !preferences.emailNotifications; showToast('Notifikasi email diperbarui');"
                                    :class="[
                                        'w-10 h-6 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-200',
                                        preferences.emailNotifications ? 'bg-[#1B44E8]' : 'bg-[rgba(242,239,234,0.15)]',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-200',
                                            preferences.emailNotifications ? 'translate-x-4' : 'translate-x-0',
                                        ]"
                                    ></div>
                                </button>
                            </div>

                            <!-- 5) Push notifications -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Push notifications</span>
                                    <p class="text-[12px] text-[#8C8C90]">Device alerts for real-time live premiere chats</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="preferences.pushNotifications"
                                    @click="preferences.pushNotifications = !preferences.pushNotifications; showToast('Notifikasi push diperbarui');"
                                    :class="[
                                        'w-10 h-6 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-200',
                                        preferences.pushNotifications ? 'bg-[#1B44E8]' : 'bg-[rgba(242,239,234,0.15)]',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-200',
                                            preferences.pushNotifications ? 'translate-x-4' : 'translate-x-0',
                                        ]"
                                    ></div>
                                </button>
                            </div>

                            <!-- 6) Autoplay previews -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Autoplay previews</span>
                                    <p class="text-[12px] text-[#8C8C90]">Play teaser loops while browsing catalog</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="preferences.autoplayPreviews"
                                    @click="preferences.autoplayPreviews = !preferences.autoplayPreviews; showToast('Autoplay preview diperbarui');"
                                    :class="[
                                        'w-10 h-6 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-200',
                                        preferences.autoplayPreviews ? 'bg-[#1B44E8]' : 'bg-[rgba(242,239,234,0.15)]',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-200',
                                            preferences.autoplayPreviews ? 'translate-x-4' : 'translate-x-0',
                                        ]"
                                    ></div>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Divider -->
                    <hr class="border-t border-[rgba(242,239,234,0.08)] my-10" />

                    <!-- SECTION: Privacy & Security -->
                    <section class="scroll-mt-28 space-y-6" id="section-privacy">
                        <div>
                            <h2 class="text-lg font-semibold text-[#F2EFEA]">Privacy &amp; Security</h2>
                            <p class="text-[13px] text-[#8C8C90] mt-0.5">Control visibility and active signed-in sessions.</p>
                        </div>

                        <div class="space-y-0 divide-y divide-[rgba(242,239,234,0.08)]">
                            <!-- Visibility -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Profile visibility</span>
                                    <p class="text-[12px] text-[#8C8C90]">Who can discover your profile &amp; watchlists</p>
                                </div>
                                <button
                                    type="button"
                                    @click="isVisibilityModalOpen = true"
                                    class="flex items-center gap-1.5 cursor-pointer text-[13px] text-[#F2EFEA] hover:text-[#7A9BFF] transition-colors"
                                >
                                    <span>{{ profileVisibility }}</span>
                                    <ChevronRight class="w-4 h-4 text-[#8C8C90]" />
                                </button>
                            </div>

                            <!-- DMs -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Direct messages</span>
                                    <p class="text-[12px] text-[#8C8C90]">Who can start a private discussion</p>
                                </div>
                                <button
                                    type="button"
                                    @click="isDmModalOpen = true"
                                    class="flex items-center gap-1.5 cursor-pointer text-[13px] text-[#F2EFEA] hover:text-[#7A9BFF] transition-colors"
                                >
                                    <span>{{ directMessages }}</span>
                                    <ChevronRight class="w-4 h-4 text-[#8C8C90]" />
                                </button>
                            </div>

                            <!-- Watch history toggle -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Show watch history publicly</span>
                                    <p class="text-[12px] text-[#8C8C90]">Display recently finished titles on profile</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="showWatchHistory"
                                    @click="showWatchHistory = !showWatchHistory; showToast('Visibilitas riwayat tontonan diperbarui');"
                                    :class="[
                                        'w-10 h-6 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-200',
                                        showWatchHistory ? 'bg-[#1B44E8]' : 'bg-[rgba(242,239,234,0.15)]',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-200',
                                            showWatchHistory ? 'translate-x-4' : 'translate-x-0',
                                        ]"
                                    ></div>
                                </button>
                            </div>

                            <!-- 2FA -->
                            <div class="py-4 flex items-center justify-between">
                                <div>
                                    <span class="text-[14px] font-medium text-[#F2EFEA]">Two-factor authentication</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] font-medium uppercase tracking-wider text-[#8C8C90] bg-[#1E1F23] px-1.5 py-0.5 rounded">NOT ENABLED</span>
                                        <span class="text-[12px] text-[#8C8C90]">Add an extra security layer</span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="is2faModalOpen = true"
                                    class="text-[13px] font-medium text-[#1B44E8] hover:text-[#7A9BFF] transition-colors"
                                >
                                    Set up
                                </button>
                            </div>

                            <!-- Active Sessions -->
                            <div class="py-5 space-y-3">
                                <span class="block text-[11px] font-semibold tracking-[0.12em] text-[#8C8C90] uppercase">ACTIVE SESSIONS</span>

                                <div
                                    v-for="sess in sessions"
                                    :key="sess.id"
                                    class="p-3.5 rounded-[10px] bg-[#17181B] border border-[rgba(242,239,234,0.06)] flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <component :is="sess.isCurrent ? Laptop : Smartphone" class="w-5 h-5 text-[#8C8C90]" />
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[13px] font-medium text-[#F2EFEA]">{{ sess.device }}</span>
                                                <span v-if="sess.isCurrent" class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-[#1B44E8]/20 text-[#7A9BFF]">Current</span>
                                            </div>
                                            <span class="text-[11px] text-[#8C8C90] block">{{ sess.meta }}</span>
                                        </div>
                                    </div>
                                    <span v-if="sess.isCurrent" class="w-2 h-2 rounded-full bg-[#1B44E8]" title="Current session"></span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="signOutSession(sess.id)"
                                        class="text-[12px] text-[#8C8C90] hover:text-[#E85C4A] transition-colors"
                                    >
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Divider -->
                    <hr class="border-t border-[rgba(242,239,234,0.08)] my-10" />

                    <!-- SECTION: Delete Account (Quiet danger zone) -->
                    <section class="pt-2 space-y-4" id="delete-section">
                        <div>
                            <h2 class="text-lg font-semibold text-[#F2EFEA]">Delete account</h2>
                            <p class="text-[13px] text-[#8C8C90] mt-1 leading-relaxed">
                                Permanently delete your account and all associated data. This action is irreversible and cancels any active subscriptions.
                            </p>
                        </div>
                        <div class="pt-2">
                            <button
                                type="button"
                                @click="isDeleteModalOpen = true"
                                class="h-[40px] px-5 rounded-[10px] bg-transparent border border-[rgba(232,92,74,0.35)] text-[#E85C4A] hover:bg-[#E85C4A]/10 text-[13px] font-medium transition-all"
                            >
                                Delete account
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <!-- ==================== TOAST NOTIFICATION ==================== -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div
                v-if="toastMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-[#17181B] border border-[rgba(242,239,234,0.15)] text-[#F2EFEA] px-4 py-3 rounded-xl shadow-2xl text-[13px]"
            >
                <span class="w-2 h-2 rounded-full bg-[#1B44E8]"></span>
                <span>{{ toastMessage }}</span>
                <button type="button" @click="toastMessage = null" class="text-[#8C8C90] hover:text-white ml-2">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </transition>

        <!-- ==================== SEARCH MODAL (⌘K) ==================== -->
        <div v-if="isSearchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-xl bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center px-4 py-3.5 border-b border-[rgba(242,239,234,0.08)]">
                    <Search class="w-4 h-4 text-[#8C8C90] mr-3" />
                    <input
                        v-model="searchQuery"
                        v-focus
                        type="text"
                        placeholder="Search films, series, genres..."
                        class="w-full bg-transparent border-0 p-0 text-[14px] text-[#F2EFEA] placeholder-[#8C8C90] focus:ring-0 focus:outline-none"
                    />
                    <button type="button" @click="isSearchOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-2 max-h-80 overflow-y-auto">
                    <div v-if="filteredFilms.length === 0" class="p-4 text-center text-xs text-[#8C8C90]">
                        Tidak ada film yang cocok dengan "{{ searchQuery }}"
                    </div>
                    <Link
                        v-for="film in filteredFilms"
                        :key="film.id"
                        :href="`/films/${film.id}`"
                        @click="isSearchOpen = false"
                        class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-white/[0.04] transition-colors group"
                    >
                        <div>
                            <p class="text-[13px] font-medium text-[#F2EFEA] group-hover:text-white">{{ film.title }}</p>
                            <p class="text-[11px] text-[#8C8C90]">{{ film.genre }}</p>
                        </div>
                        <span class="text-[11px] text-[#7A9BFF]">Tonton &rarr;</span>
                    </Link>
                </div>
                <div class="px-4 py-2 bg-[#131417] border-t border-[rgba(242,239,234,0.06)] flex items-center justify-between text-[11px] text-[#8C8C90]">
                    <span>Tekan <kbd class="font-mono bg-[#1E1F23] px-1 py-0.5 rounded border border-white/10">ESC</kbd> untuk menutup</span>
                    <span>WATOON Search</span>
                </div>
            </div>
        </div>

        <!-- ==================== CHANGE PASSWORD MODAL ==================== -->
        <div v-if="isPasswordModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Change Password</h3>
                    <button type="button" @click="isPasswordModalOpen = false; passwordForm.reset();" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-[13px] text-[#8C8C90] mb-6">Ensure your account is using a long, random password to stay secure.</p>

                <form @submit.prevent="submitPassword" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-[#8C8C90] uppercase tracking-wider block">Current Password</label>
                        <div class="relative">
                            <input
                                v-model="passwordForm.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                required
                                class="w-full bg-[#1E1F23] border border-[rgba(242,239,234,0.12)] rounded-lg px-3 py-2 text-[13px] text-[#F2EFEA] focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                            />
                            <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute right-3 top-2.5 text-[#8C8C90] hover:text-white">
                                <component :is="showCurrentPassword ? EyeOff : Eye" class="w-4 h-4" />
                            </button>
                        </div>
                        <span v-if="passwordForm.errors.current_password" class="text-xs text-[#E85C4A] mt-1 block">{{ passwordForm.errors.current_password }}</span>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-[#8C8C90] uppercase tracking-wider block">New Password</label>
                        <div class="relative">
                            <input
                                v-model="passwordForm.password"
                                :type="showNewPassword ? 'text' : 'password'"
                                required
                                class="w-full bg-[#1E1F23] border border-[rgba(242,239,234,0.12)] rounded-lg px-3 py-2 text-[13px] text-[#F2EFEA] focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                            />
                            <button type="button" @click="showNewPassword = !showNewPassword" class="absolute right-3 top-2.5 text-[#8C8C90] hover:text-white">
                                <component :is="showNewPassword ? EyeOff : Eye" class="w-4 h-4" />
                            </button>
                        </div>
                        <span v-if="passwordForm.errors.password" class="text-xs text-[#E85C4A] mt-1 block">{{ passwordForm.errors.password }}</span>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-[#8C8C90] uppercase tracking-wider block">Confirm Password</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            class="w-full bg-[#1E1F23] border border-[rgba(242,239,234,0.12)] rounded-lg px-3 py-2 text-[13px] text-[#F2EFEA] focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="isPasswordModalOpen = false; passwordForm.reset();"
                            class="px-4 py-2 rounded-lg text-[13px] text-[#8C8C90] hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-4 py-2 rounded-lg bg-[#1B44E8] text-white font-medium text-[13px] hover:bg-[#1B44E8]/90 transition-all disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== CHANGE EMAIL MODAL ==================== -->
        <div v-if="isEmailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Change Email Address</h3>
                    <button type="button" @click="isEmailModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-[13px] text-[#8C8C90] mb-5">A verification link will be sent to your new email address upon saving.</p>

                <form @submit.prevent="profileForm.patch(updateProfile().url, { onSuccess: () => { isEmailModalOpen = false; showToast('Email berhasil diubah'); } })" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-[#8C8C90] uppercase tracking-wider block">New Email Address</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            required
                            class="w-full bg-[#1E1F23] border border-[rgba(242,239,234,0.12)] rounded-lg px-3 py-2 text-[13px] text-[#F2EFEA] focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                        />
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" @click="isEmailModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] text-[#8C8C90] hover:text-white">Cancel</button>
                        <button type="submit" :disabled="profileForm.processing" class="px-4 py-2 rounded-lg bg-[#1B44E8] text-white font-medium text-[13px] hover:bg-[#1B44E8]/90 transition-all">Save Email</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== CHANGE PHONE MODAL ==================== -->
        <div v-if="isPhoneModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Change Phone Number</h3>
                    <button type="button" @click="isPhoneModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-[13px] text-[#8C8C90] mb-5">Used for SMS security notifications and purchase verifications.</p>

                <form @submit.prevent="profileForm.patch(updateProfile().url, { onSuccess: () => { isPhoneModalOpen = false; showToast('Nomor telepon diperbarui'); } })" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-[#8C8C90] uppercase tracking-wider block">Phone Number</label>
                        <input
                            v-model="profileForm.phone"
                            type="text"
                            placeholder="+62 812 ..."
                            required
                            class="w-full bg-[#1E1F23] border border-[rgba(242,239,234,0.12)] rounded-lg px-3 py-2 text-[13px] text-[#F2EFEA] focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                        />
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" @click="isPhoneModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] text-[#8C8C90] hover:text-white">Cancel</button>
                        <button type="submit" :disabled="profileForm.processing" class="px-4 py-2 rounded-lg bg-[#1B44E8] text-white font-medium text-[13px] hover:bg-[#1B44E8]/90 transition-all">Save Phone</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== PLAN MODAL ==================== -->
        <div v-if="isPlanModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-lg bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-[#F2EFEA]">Select Your Membership</h3>
                        <p class="text-[12px] text-[#8C8C90] mt-0.5">Flexible plans with cinema quality playback and novel catalog access.</p>
                    </div>
                    <button type="button" @click="isPlanModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-3 my-5">
                    <!-- Pro Plan Card -->
                    <div class="p-4 rounded-xl bg-[#1E1F23] border-2 border-[#1B44E8] relative flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-white">WATOON Pro</span>
                                <span class="px-2 py-0.2 rounded text-[10px] font-semibold bg-[#1B44E8] text-white">CURRENT</span>
                            </div>
                            <p class="text-xs text-[#8C8C90]">1080p Full HD, ad-free streaming &amp; creator chats.</p>
                            <p class="text-sm font-semibold text-[#F2EFEA] mt-2">Rp 80.000 <span class="text-xs font-normal text-[#8C8C90]">/ bulan</span></p>
                        </div>
                        <Check class="w-5 h-5 text-[#1B44E8]" />
                    </div>

                    <!-- VIP Plan Card -->
                    <div class="p-4 rounded-xl bg-[#1E1F23] border border-[rgba(212,182,122,0.3)] hover:border-[#D4B67A] transition-colors flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-white">WATOON VIP</span>
                                <span class="px-2 py-0.2 rounded text-[10px] font-semibold bg-[rgba(212,182,122,0.2)] text-[#D4B67A] border border-[rgba(212,182,122,0.4)]">ALL ACCESS</span>
                            </div>
                            <p class="text-xs text-[#8C8C90]">4K HDR Cinema, unlimited novel library, director screenings.</p>
                            <p class="text-sm font-semibold text-[#D4B67A] mt-2">Rp 149.000 <span class="text-xs font-normal text-[#8C8C90]">/ bulan</span></p>
                        </div>
                        <button
                            type="button"
                            @click="isPlanModalOpen = false; showToast('Upgrade ke VIP diproses');"
                            class="px-3.5 py-1.5 rounded-lg bg-[#D4B67A] text-[#0E0F11] font-semibold text-xs hover:bg-[#D4B67A]/90 transition-all"
                        >
                            Upgrade
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" @click="isPlanModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] text-[#8C8C90] hover:text-white">Close</button>
                </div>
            </div>
        </div>

        <!-- ==================== LANGUAGE MODAL ==================== -->
        <div v-if="isLanguageModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Select Language</h3>
                    <button type="button" @click="isLanguageModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-1 my-3">
                    <button
                        v-for="lang in ['Bahasa Indonesia', 'English (US)', '日本語 (Japanese)', '한국어 (Korean)']"
                        :key="lang"
                        type="button"
                        @click="selectedLanguage = lang; isLanguageModalOpen = false; showToast('Bahasa diubah ke ' + lang);"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[13px] transition-colors',
                            selectedLanguage === lang ? 'bg-[#1B44E8]/20 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/[0.04]',
                        ]"
                    >
                        <span>{{ lang }}</span>
                        <Check v-if="selectedLanguage === lang" class="w-4 h-4 text-[#1B44E8]" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== PLAYBACK QUALITY MODAL ==================== -->
        <div v-if="isQualityModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Playback Quality</h3>
                    <button type="button" @click="isQualityModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-1 my-3">
                    <button
                        v-for="quality in ['Auto (1080p)', '4K Ultra HD (HDR)', '1080p Full HD', '720p HD', 'Data Saver (480p)']"
                        :key="quality"
                        type="button"
                        @click="selectedQuality = quality; isQualityModalOpen = false; showToast('Kualitas video diatur ke ' + quality);"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[13px] transition-colors',
                            selectedQuality === quality ? 'bg-[#1B44E8]/20 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/[0.04]',
                        ]"
                    >
                        <span>{{ quality }}</span>
                        <Check v-if="selectedQuality === quality" class="w-4 h-4 text-[#1B44E8]" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== PROFILE VISIBILITY MODAL ==================== -->
        <div v-if="isVisibilityModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Profile Visibility</h3>
                    <button type="button" @click="isVisibilityModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-1 my-3">
                    <button
                        v-for="vis in ['Public', 'Followers only', 'Private']"
                        :key="vis"
                        type="button"
                        @click="profileVisibility = vis; isVisibilityModalOpen = false; showToast('Visibilitas diubah ke ' + vis);"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[13px] transition-colors',
                            profileVisibility === vis ? 'bg-[#1B44E8]/20 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/[0.04]',
                        ]"
                    >
                        <span>{{ vis }}</span>
                        <Check v-if="profileVisibility === vis" class="w-4 h-4 text-[#1B44E8]" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== DM PRIVACY MODAL ==================== -->
        <div v-if="isDmModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Direct Messages</h3>
                    <button type="button" @click="isDmModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-1 my-3">
                    <button
                        v-for="dm in ['Everyone', 'People I follow', 'Nobody']"
                        :key="dm"
                        type="button"
                        @click="directMessages = dm; isDmModalOpen = false; showToast('Pengaturan pesan diubah ke ' + dm);"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[13px] transition-colors',
                            directMessages === dm ? 'bg-[#1B44E8]/20 text-[#7A9BFF] font-medium' : 'text-[#F2EFEA] hover:bg-white/[0.04]',
                        ]"
                    >
                        <span>{{ dm }}</span>
                        <Check v-if="directMessages === dm" class="w-4 h-4 text-[#1B44E8]" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== 2FA MODAL ==================== -->
        <div v-if="is2faModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-[#17181B] border border-[rgba(242,239,234,0.12)] rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-[#F2EFEA]">Two-Factor Authentication</h3>
                    <button type="button" @click="is2faModalOpen = false" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-[13px] text-[#8C8C90] mb-4">
                    Two-factor authentication adds an extra layer of security to your WATOON account using an authenticator app (Google Authenticator, 1Password, etc.).
                </p>
                <div class="p-3.5 bg-[#1E1F23] rounded-xl border border-[rgba(242,239,234,0.06)] text-xs text-[#8C8C90] space-y-1 mb-5">
                    <p class="font-medium text-[#F2EFEA]">Status: Tidak aktif</p>
                    <p>Setelah diaktifkan, Anda akan diminta memasukkan kode OTP 6 digit saat login.</p>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="is2faModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] text-[#8C8C90] hover:text-white">Cancel</button>
                    <button type="button" @click="is2faModalOpen = false; showToast('Silakan konfirmasi kata sandi untuk mengaktifkan 2FA');" class="px-4 py-2 rounded-lg bg-[#1B44E8] text-white font-medium text-[13px] hover:bg-[#1B44E8]/90 transition-all">Enable 2FA</button>
                </div>
            </div>
        </div>

        <!-- ==================== DELETE ACCOUNT MODAL ==================== -->
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-[#17181B] border border-[#E85C4A]/30 rounded-2xl p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-[#E85C4A]">Delete Account</h3>
                    <button type="button" @click="isDeleteModalOpen = false; deleteForm.reset();" class="text-[#8C8C90] hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-[13px] text-[#8C8C90] mb-4 leading-relaxed">
                    Are you sure you want to delete your account? All of your saved bookmarks, curated reviews, and subscriptions will be permanently purged.
                </p>

                <form @submit.prevent="submitDelete" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-semibold text-[#8C8C90] uppercase tracking-wider block">Confirm Your Password</label>
                        <input
                            v-model="deleteForm.password"
                            type="password"
                            placeholder="Enter password to confirm"
                            required
                            class="w-full bg-[#1E1F23] border border-[rgba(242,239,234,0.12)] rounded-lg px-3 py-2 text-[13px] text-[#F2EFEA] focus:border-[#E85C4A] focus:ring-0 focus:outline-none"
                        />
                        <span v-if="deleteForm.errors.password" class="text-xs text-[#E85C4A] mt-1 block">{{ deleteForm.errors.password }}</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button
                            type="button"
                            @click="isDeleteModalOpen = false; deleteForm.reset();"
                            class="px-4 py-2 rounded-lg text-[13px] text-[#8C8C90] hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="deleteForm.processing"
                            class="px-4 py-2 rounded-lg bg-[#E85C4A] text-white font-medium text-[13px] hover:bg-[#E85C4A]/90 transition-all disabled:opacity-50"
                        >
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete permanently' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
