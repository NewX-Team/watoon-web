<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bookmark,
    BookmarkCheck,
    Check,
    ChevronDown,
    Crown,
    LogOut,
    Play,
    Plus,
    Search,
    Settings,
    Share2,
    Sparkles,
    Star,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { dashboard, logout, pricing } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types/auth';

interface Episode {
    number: string;
    code: string;
    title: string;
    synopsis: string;
    badge: 'VIP' | 'PRO' | 'FREE';
    duration: string;
    thumbnail: string;
    video_url?: string;
}

interface Film {
    id: number;
    title: string;
    year: string;
    duration: string;
    age_rating: string;
    rating: string;
    category: string;
    genres: string[];
    subtitles: string;
    director: string;
    studio: string;
    creator: {
        name: string;
        initials: string;
        avatar?: string | null;
    };
    synopsis: string;
    full_synopsis?: string;
    poster: string;
    backdrop: string;
}

const props = defineProps<{
    film: Film;
    episodes: Episode[];
}>();

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

// Search & UI states
const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);
const userMenuRef = ref<HTMLElement | null>(null);
const activeTab = ref<'home' | 'favorite' | 'subscribe'>('home');
const mediaType = ref<'film' | 'novel'>('film');
const isProModalOpen = ref(false);

const normalizeTier = (val?: string | null): 'FREE' | 'PRO' | 'VIP' => {
    if (!val) return 'FREE';
    const up = val.toUpperCase();
    if (up === 'VIP') return 'VIP';
    if (up === 'PRO') return 'PRO';
    return 'FREE';
};

const getInitialTier = (): 'FREE' | 'PRO' | 'VIP' => {
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

const currentUserTier = ref<'FREE' | 'PRO' | 'VIP'>(getInitialTier());

watch(
    () => currentUser.value?.tier,
    (newTier) => {
        if (newTier) {
            const normalized = normalizeTier(newTier);
            currentUserTier.value = normalized;
            if (typeof window !== 'undefined') {
                localStorage.setItem('watoon_user_tier', normalized);
            }
        }
    },
    { immediate: true },
);
const userMenuOpen = ref(false);
const isScrolled = ref(false);
const isReadMore = ref(false);
const isNewestFirst = ref(true);
const showNovelNotice = ref(false);
const showShareToast = ref(false);
const showBookmarkToast = ref(false);
const bookmarkToastMessage = ref('');
let bookmarkToastTimer: ReturnType<typeof setTimeout> | null = null;

// Watchlist synchronization with localStorage
const loadIsBookmarked = (): boolean => {
    if (typeof window === 'undefined') return false;
    try {
        const stored = localStorage.getItem('watoon_watchlist');
        const list: number[] = stored ? JSON.parse(stored) : [1, 4];
        return list.includes(props.film.id);
    } catch {
        return false;
    }
};

const isBookmarked = ref(loadIsBookmarked());

// Sorted & filtered episodes list
const displayedEpisodes = computed(() => {
    let list = [...props.episodes];

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(
            (ep) =>
                ep.title.toLowerCase().includes(q) ||
                ep.code.toLowerCase().includes(q) ||
                ep.synopsis.toLowerCase().includes(q),
        );
    }

    if (isNewestFirst.value) {
        return list; // EP 08 to EP 01
    }
    return list.reverse(); // EP 01 to EP 08
});

const toggleSort = () => {
    isNewestFirst.value = !isNewestFirst.value;
};

// Toggle watchlist & persist
const toggleWatchlist = () => {
    isBookmarked.value = !isBookmarked.value;
    if (typeof window !== 'undefined') {
        try {
            const stored = localStorage.getItem('watoon_watchlist');
            let list: number[] = stored ? JSON.parse(stored) : [1, 4];
            if (isBookmarked.value) {
                if (!list.includes(props.film.id)) {
                    list.push(props.film.id);
                }
            } else {
                list = list.filter((id) => id !== props.film.id);
            }
            localStorage.setItem('watoon_watchlist', JSON.stringify(list));

            // Sync with watoon_user_favorites if available
            const favStored = localStorage.getItem('watoon_user_favorites');
            if (favStored) {
                let favList = JSON.parse(favStored);
                if (Array.isArray(favList)) {
                    if (isBookmarked.value) {
                        const exists = favList.some(
                            (f: any) =>
                                f.id === `fav-${props.film.id}` ||
                                f.title === props.film.title,
                        );
                        if (!exists) {
                            favList.unshift({
                                id: `fav-${props.film.id}`,
                                type: 'film',
                                title: props.film.title,
                                year: props.film.year,
                                genre: props.film.genres?.[0] || 'Drama',
                                savedAgo: 'Just now',
                                savedTimestamp: Date.now(),
                                image: props.film.poster,
                                href: `/films/${props.film.id}`,
                            });
                        }
                    } else {
                        favList = favList.filter(
                            (f: any) =>
                                f.id !== `fav-${props.film.id}` &&
                                f.title !== props.film.title,
                        );
                    }
                    localStorage.setItem(
                        'watoon_user_favorites',
                        JSON.stringify(favList),
                    );
                }
            }
        } catch {
            // ignore
        }
    }

    bookmarkToastMessage.value = isBookmarked.value
        ? `"${props.film.title}" ditambahkan ke Watchlist`
        : `"${props.film.title}" dihapus dari Watchlist`;
    showBookmarkToast.value = true;
    if (bookmarkToastTimer) clearTimeout(bookmarkToastTimer);
    bookmarkToastTimer = setTimeout(() => {
        showBookmarkToast.value = false;
    }, 3000);
};

// Share film URL
const shareFilm = async () => {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(window.location.href);
        }
        showShareToast.value = true;
        setTimeout(() => {
            showShareToast.value = false;
        }, 3000);
    } catch {
        showShareToast.value = true;
        setTimeout(() => {
            showShareToast.value = false;
        }, 3000);
    }
};

// Search films from header input on Enter
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(
            `/dashboard?q=${encodeURIComponent(searchQuery.value.trim())}`,
        );
    }
};

// Media type switch
const selectMediaType = (type: 'film' | 'novel') => {
    mediaType.value = type;
    if (type === 'novel') {
        showNovelNotice.value = true;
    } else {
        showNovelNotice.value = false;
    }
};

// Scroll listener
const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

// Click outside user menu listener
const handleClickOutside = (e: MouseEvent) => {
    if (
        userMenuRef.value &&
        !userMenuRef.value.contains(e.target as Node)
    ) {
        userMenuOpen.value = false;
    }
};

// Keydown shortcuts
const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
    if (e.key === 'Escape') {
        userMenuOpen.value = false;
        isProModalOpen.value = false;
        showNovelNotice.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('scroll', handleScroll);
    document.removeEventListener('click', handleClickOutside);
    if (bookmarkToastTimer) clearTimeout(bookmarkToastTimer);
});
</script>

<template>
    <div
        class="relative min-h-screen bg-[#0E0F11] font-sans text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white"
    >
        <Head :title="`${film.title} — Watoon`" />

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
                <!-- LEFT ZONE: Brand + Links + Search -->
                <div class="flex items-center gap-7">
                    <!-- Brand Wordmark with Monoline Film Frame Mark -->
                    <Link
                        :href="dashboard.url()"
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
                        <Link
                            :href="dashboard.url()"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            Home
                        </Link>

                        <Link
                            :href="dashboard.url({ query: { tab: 'favorite' } })"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            Favorite
                        </Link>

                        <Link
                            :href="dashboard.url({ query: { tab: 'subscribe' } })"
                            class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            Subscribe
                        </Link>

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
                                @keydown.enter="handleSearch"
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

                <!-- RIGHT ZONE: Toggle, PRO badge, Avatar -->
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

                    <!-- Kasta Tier Badge -> Navigate to Pricing -->
                    <Link
                        :href="pricing.url()"
                        class="mr-4 flex h-[28px] cursor-pointer items-center justify-center gap-1.5 rounded-[8px] px-3 text-[11px] font-bold tracking-[0.14em] uppercase transition-all select-none hover:scale-105 active:scale-95"
                        :class="[
                            currentUserTier === 'VIP'
                                ? 'border border-[#D4B67A]/60 bg-[#D4B67A]/20 text-[#D4B67A] hover:bg-[#D4B67A]/30 shadow-[0_0_16px_rgba(212,182,122,0.3)]'
                                : currentUserTier === 'PRO'
                                ? 'border border-[#1B44E8]/60 bg-[#1B44E8]/20 text-[#7A9BFF] hover:bg-[#1B44E8]/30 shadow-[0_0_16px_rgba(27,68,232,0.3)]'
                                : 'border border-[rgba(242,239,234,0.18)] bg-[rgba(242,239,234,0.07)] text-[#A0A0A5] hover:text-[#F2EFEA] hover:border-white/30'
                        ]"
                        :title="`Status Kasta: ${currentUserTier} (Klik untuk buka halaman paket langganan)`"
                    >
                        <Crown v-if="currentUserTier === 'VIP'" class="w-3.5 h-3.5 text-[#D4B67A]" />
                        <Sparkles v-else-if="currentUserTier === 'PRO'" class="w-3.5 h-3.5 text-[#7A9BFF]" />
                        <span v-else class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>{{ currentUserTier }}</span>
                    </Link>

                    <!-- User Avatar & Dropdown -->
                    <div ref="userMenuRef" class="relative">
                        <button
                            type="button"
                            @click="userMenuOpen = !userMenuOpen"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-[rgba(242,239,234,0.15)] bg-[#17181B] text-[13px] font-medium text-[#F2EFEA] select-none transition-colors hover:border-[#F2EFEA]/40"
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
                                <p class="truncate text-sm font-medium text-white">
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
                                    :href="dashboard.url()"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06]"
                                >
                                    <ArrowLeft class="h-3.5 w-3.5 text-[#8C8C90]" />
                                    <span>Kembali ke Dashboard</span>
                                </Link>

                                <Link
                                    :href="edit.url()"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06]"
                                >
                                    <Settings class="h-3.5 w-3.5 text-[#8C8C90]" />
                                    <span>Pengaturan Akun</span>
                                </Link>


                                <Link
                                    :href="pricing.url()"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06]"
                                >
                                    <Crown class="h-3.5 w-3.5 text-[#7A9BFF]" />
                                    <span>Paket Langganan & Kasta</span>
                                </Link>
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

        <!-- NOVEL NOTICE BANNER -->
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
                        Edisi sastra bersambung segera hadir. Saat ini menampilkan film sinematik pilihan.
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

        <!-- SHARE TOAST NOTIFICATION -->
        <div
            v-if="showShareToast"
            class="fixed bottom-8 right-8 z-50 flex items-center gap-2.5 rounded-xl border border-[rgba(27,68,232,0.4)] bg-[#17181B] px-4 py-3 text-xs text-white shadow-2xl backdrop-blur-xl"
        >
            <Check class="h-4 w-4 text-[#1B44E8]" />
            <span>Tautan film berhasil disalin ke papan klip!</span>
        </div>

        <!-- BOOKMARK TOAST NOTIFICATION -->
        <div
            v-if="showBookmarkToast"
            class="fixed bottom-8 right-8 z-50 flex items-center gap-2.5 rounded-xl border border-[rgba(27,68,232,0.4)] bg-[#17181B] px-4 py-3 text-xs text-white shadow-2xl backdrop-blur-xl"
        >
            <BookmarkCheck class="h-4 w-4 text-[#1B44E8]" />
            <span>{{ bookmarkToastMessage }}</span>
        </div>

        <!-- [2] HERO BACKGROUND STRIP -->
        <div
            class="pointer-events-none absolute inset-x-0 top-0 z-0 h-[380px] overflow-hidden select-none"
        >
            <div
                class="h-full w-full bg-cover bg-center opacity-15 filter contrast-125 saturate-50"
                :style="{ backgroundImage: `url('${film.backdrop}')` }"
            ></div>
            <div class="absolute inset-0 bg-[#0E0F11]/85"></div>
            <div
                class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-b from-transparent to-[#0E0F11]"
            ></div>
        </div>

        <!-- MAIN CANVAS -->
        <main class="relative z-10 mx-auto max-w-[1280px] px-6 pt-28 pb-20 md:px-8">
            <!-- Breadcrumb Navigation back to Dashboard -->
            <div class="mb-8 flex items-center justify-between">
                <Link
                    :href="dashboard.url()"
                    class="group inline-flex items-center gap-2 text-xs font-medium text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                >
                    <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                    <span>Kembali ke Dashboard</span>
                </Link>

                <div class="text-[11px] font-semibold tracking-[0.14em] text-[#8C8C90] uppercase">
                    KATALOG FILM EKSKLUSIF
                </div>
            </div>

            <!-- [3] TOP SECTION — TWO COLUMNS -->
            <section class="grid grid-cols-1 items-start gap-12 lg:grid-cols-12">
                <!-- LEFT COLUMN: Sticky Poster (380px fixed width on desktop) -->
                <div
                    class="flex flex-col items-center lg:sticky lg:top-28 lg:col-span-5 lg:items-start"
                >
                    <div class="w-full max-w-[340px] sm:max-w-[380px]">
                        <div
                            class="group relative aspect-[2/3] w-full overflow-hidden rounded-[14px] border border-[rgba(242,239,234,0.10)] bg-[#17181B] shadow-[0_24px_48px_rgba(0,0,0,0.35)] transition-all duration-300 hover:border-[#1B44E8]"
                        >
                            <img
                                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                                :src="film.poster"
                                :alt="film.title"
                            />
                            <div
                                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#0E0F11]/40 via-transparent to-transparent"
                            ></div>
                        </div>
                        <p
                            class="mt-3 text-center text-[11px] font-semibold tracking-widest text-[#8C8C90] uppercase"
                        >
                            POSTER · {{ film.year }}
                        </p>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Film Metadata & Details (max-w ~680px) -->
                <div class="max-w-[680px] lg:col-span-7">
                    <!-- Eyebrow -->
                    <span
                        class="block text-[11px] font-semibold tracking-widest text-[#1B44E8] uppercase"
                    >
                        {{ film.category || 'FILM' }}
                    </span>

                    <!-- Title -->
                    <h1
                        class="mt-1 text-3xl font-semibold tracking-tight text-[#F2EFEA] leading-tight md:text-5xl"
                    >
                        {{ film.title }}
                    </h1>

                    <!-- Metadata row -->
                    <div
                        class="mt-3 flex items-center gap-3 text-[13px] text-[#8C8C90]"
                    >
                        <span>{{ film.year }}</span>
                        <span
                            class="inline-block h-3 w-[1px] bg-[rgba(242,239,234,0.15)]"
                        ></span>
                        <span>{{ film.duration }}</span>
                        <span
                            class="inline-block h-3 w-[1px] bg-[rgba(242,239,234,0.15)]"
                        ></span>
                        <span>{{ film.age_rating || '18+' }}</span>
                        <span
                            class="inline-block h-3 w-[1px] bg-[rgba(242,239,234,0.15)]"
                        ></span>
                        <span class="flex items-center gap-1 text-[#F2EFEA]">
                            <Star class="h-3.5 w-3.5 fill-[#1B44E8] text-[#1B44E8]" />
                            <span class="font-medium">{{ film.rating }}</span>
                        </span>
                    </div>

                    <!-- Description with Read more toggle -->
                    <p
                        class="mt-4 text-[15px] leading-relaxed text-[#8C8C90]"
                    >
                        <span>{{ isReadMore ? film.full_synopsis || film.synopsis : film.synopsis }}</span>
                        <button
                            v-if="film.full_synopsis && film.full_synopsis !== film.synopsis"
                            type="button"
                            @click="isReadMore = !isReadMore"
                            class="ml-1 inline-block cursor-pointer font-medium text-[#1B44E8] hover:underline"
                        >
                            {{ isReadMore ? 'Read less' : 'Read more' }}
                        </button>
                    </p>

                    <!-- Genre chips row -->
                    <div class="mt-5 flex flex-wrap gap-2">
                        <span
                            v-for="genre in film.genres"
                            :key="genre"
                            class="rounded-[8px] border border-[rgba(242,239,234,0.08)] bg-[rgba(242,239,234,0.06)] px-3 py-1.5 text-xs text-[#F2EFEA]"
                        >
                            {{ genre }}
                        </span>
                    </div>

                    <!-- Hairline divider -->
                    <div
                        class="my-6 h-[1px] w-full bg-[rgba(242,239,234,0.10)]"
                    ></div>

                    <!-- Creator block -->
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(242,239,234,0.12)] bg-[#1E1F23] text-xs font-medium text-[#F2EFEA]"
                        >
                            {{ film.creator.initials }}
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-[11px] font-semibold tracking-widest text-[#8C8C90] uppercase leading-none"
                            >
                                CREATED BY
                            </span>
                            <span
                                class="mt-1 text-sm font-medium text-[#F2EFEA] leading-snug"
                            >
                                {{ film.creator.name }}
                            </span>
                            <span
                                class="mt-1 text-[12px] font-medium text-[#1B44E8] leading-none"
                            >
                                Terverifikasi · Sineas Independen
                            </span>
                        </div>
                    </div>

                    <!-- Actions row -->
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <Link
                            :href="`/films/${film.id}/episodes/01`"
                            class="flex h-11 cursor-pointer items-center gap-2 rounded-[10px] bg-[#F2EFEA] px-5 text-sm font-medium text-[#0E0F11] shadow-[0_1px_2px_rgba(0,0,0,0.1)] transition-colors hover:bg-white"
                        >
                            <Play class="h-4 w-4 fill-current" />
                            <span>Watch episode 1</span>
                        </Link>

                        <button
                            type="button"
                            @click="toggleWatchlist"
                            class="flex h-11 cursor-pointer items-center gap-2 rounded-[10px] border border-[rgba(242,239,234,0.15)] bg-transparent px-4 text-sm font-medium text-[#F2EFEA] transition-colors hover:bg-[rgba(242,239,234,0.04)]"
                        >
                            <BookmarkCheck
                                v-if="isBookmarked"
                                class="h-4 w-4 text-[#1B44E8]"
                            />
                            <Plus v-else class="h-4 w-4" />
                            <span>{{ isBookmarked ? 'In Watchlist' : 'Add to list' }}</span>
                        </button>

                        <button
                            type="button"
                            aria-label="Share"
                            @click="shareFilm"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-[10px] border border-[rgba(242,239,234,0.15)] bg-transparent text-[#F2EFEA] transition-colors hover:bg-[rgba(242,239,234,0.04)]"
                            title="Bagikan Tautan Film"
                        >
                            <Share2 class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Details info line (Fixed branding bug to Watoon Originals) -->
                    <div
                        class="mt-6 border-t border-[rgba(242,239,234,0.08)] pt-4"
                    >
                        <p class="text-xs text-[#8C8C90]">
                            Subtitles: {{ film.subtitles }} &nbsp;·&nbsp;
                            Director: {{ film.director }} &nbsp;·&nbsp; Studio:
                            {{ film.studio || 'Watoon Originals' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- [4] EPISODE LIST -->
            <section class="mt-20">
                <!-- Section Header with Working Sorting Toggle -->
                <div
                    class="mb-6 flex items-center justify-between border-b border-[rgba(242,239,234,0.08)] pb-3"
                >
                    <span
                        class="text-[11px] font-semibold tracking-widest text-[#1B44E8] uppercase"
                    >
                        EPISODES ({{ displayedEpisodes.length }})
                    </span>

                    <button
                        type="button"
                        @click="toggleSort"
                        class="flex cursor-pointer items-center gap-1.5 text-xs text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                    >
                        <span>{{ isNewestFirst ? 'Newest first' : 'Oldest first' }}</span>
                        <ChevronDown
                            class="h-4 w-4 transition-transform duration-200"
                            :class="isNewestFirst ? '' : 'rotate-180'"
                        />
                    </button>
                </div>

                <!-- Episode List Container -->
                <div
                    class="flex flex-col divide-y divide-[rgba(242,239,234,0.08)]"
                >
                    <Link
                        v-for="ep in displayedEpisodes"
                        :key="ep.number"
                        :href="`/films/${film.id}/episodes/${ep.number}`"
                        class="group flex min-h-[104px] cursor-pointer items-center justify-between rounded-[8px] px-3 py-4 transition-colors hover:bg-[rgba(242,239,234,0.03)]"
                    >
                        <div class="flex min-w-0 flex-1 items-center pr-4">
                            <!-- Thumbnail with Play Hover Overlay -->
                            <div
                                class="relative h-[90px] w-[160px] shrink-0 overflow-hidden rounded-[10px] border border-[rgba(242,239,234,0.08)] bg-[#1E1F23]"
                            >
                                <img
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    :src="ep.thumbnail"
                                    :alt="ep.title"
                                />
                                <div
                                    class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1B44E8] text-white shadow-lg"
                                    >
                                        <Play class="h-5 w-5 fill-current ml-0.5" />
                                    </div>
                                </div>
                            </div>

                            <!-- Metadata Stack -->
                            <div class="ml-5 flex min-w-0 flex-1 flex-col justify-center">
                                <div class="mb-1 flex items-center gap-1.5">
                                    <span
                                        class="text-[11px] font-semibold tracking-wider text-[#8C8C90] uppercase"
                                    >
                                        {{ ep.code }}
                                    </span>
                                    <span
                                        class="inline-block h-1 w-1 rounded-full bg-[#1B44E8]"
                                    ></span>
                                </div>
                                <h3
                                    class="truncate text-[15px] font-medium text-[#F2EFEA] transition-colors group-hover:text-[#1B44E8]"
                                >
                                    {{ ep.title }}
                                </h3>
                                <p
                                    class="mt-0.5 max-w-[540px] truncate text-xs text-[#8C8C90]"
                                >
                                    {{ ep.synopsis }}
                                </p>
                            </div>
                        </div>

                        <!-- Right Cluster: Stacked Badge & Duration + Hover Play Icon -->
                        <div class="flex shrink-0 items-center gap-4 pr-3">
                            <div class="flex flex-col items-end justify-center gap-1.5">
                                <!-- Badge VIP / PRO / FREE -->
                                <span
                                    v-if="ep.badge === 'VIP'"
                                    class="inline-flex h-[22px] items-center justify-center rounded-[8px] px-2 text-[10px] font-semibold tracking-[0.14em] uppercase leading-none"
                                    style="background-color: rgba(184, 151, 90, 0.14); border: 1px solid rgba(184, 151, 90, 0.38); color: #D4B67A;"
                                >
                                    VIP
                                </span>
                                <span
                                    v-else-if="ep.badge === 'PRO'"
                                    class="inline-flex h-[22px] items-center justify-center rounded-[8px] px-2 text-[10px] font-semibold tracking-[0.14em] uppercase leading-none"
                                    style="background-color: rgba(27, 68, 232, 0.14); border: 1px solid rgba(27, 68, 232, 0.35); color: #7A9BFF;"
                                >
                                    PRO
                                </span>
                                <span
                                    v-else
                                    class="inline-flex h-[22px] items-center justify-center rounded-[8px] px-2 text-[10px] font-semibold tracking-[0.14em] uppercase leading-none"
                                    style="background-color: rgba(140, 140, 144, 0.12); border: 1px solid rgba(140, 140, 144, 0.30); color: #A8A8AC;"
                                >
                                    FREE
                                </span>

                                <span
                                    class="font-mono text-xs text-[#8C8C90] leading-none"
                                >
                                    {{ ep.duration }}
                                </span>
                            </div>

                            <Play
                                class="h-4 w-4 text-[#F2EFEA] opacity-0 transition-opacity group-hover:opacity-100"
                            />
                        </div>
                    </Link>
                </div>

                <!-- Subtle fade at bottom edge of list -->
                <div
                    class="pointer-events-none h-10 w-full bg-gradient-to-b from-transparent to-[#0E0F11]/60"
                ></div>
            </section>
        </main>

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
                        <span>Katalog film kurasi independen tanpa batas (4K HDR)</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span>Buka seluruh episode VIP dan PRO tanpa jeda</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span>Akses awal novel bersambung bab per bab</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#F2EFEA]">
                        <Check class="h-4 w-4 text-[#1B44E8]" />
                        <span>Dukungan langsung kepada sineas dan kreator lokal</span>
                    </div>
                </div>

                <div
                    class="mt-6 rounded-xl border border-[rgba(242,239,234,0.08)] bg-black/40 p-4 text-center"
                >
                    <div class="text-2xl font-bold text-white">
                        Rp 49.000<span class="text-xs font-normal text-[#8C8C90]">
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
