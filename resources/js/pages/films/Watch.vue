<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bookmark,
    BookmarkCheck,
    Check,
    ChevronRight,
    CornerDownRight,
    Flag,
    Maximize,
    Minimize,
    MoreHorizontal,
    Pause,
    Pin,
    Play,
    Plus,
    RotateCcw,
    RotateCw,
    Send,
    Share2,
    Sliders,
    Star,
    Subtitles,
    ThumbsUp,
    Tv,
    Volume2,
    VolumeX,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
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

interface CommentUser {
    name: string;
    initials: string;
    badge: 'PRO' | 'VIP' | 'FREE';
    is_verified: boolean;
}

interface Comment {
    id: number;
    user: CommentUser;
    is_pinned: boolean;
    content: string;
    created_at: string;
    likes: number;
    is_liked: boolean;
}

const props = defineProps<{
    film: Film;
    currentEpisode: Episode;
    episodes: Episode[];
    comments: Comment[];
}>();

const page = usePage<{ auth: { user: User } }>();
const currentUser = computed(() => page.props.auth?.user);

// Video Player references & states
const videoRef = ref<HTMLVideoElement | null>(null);
const playerContainerRef = ref<HTMLElement | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(0.85);
const isMuted = ref(false);
const isFullscreen = ref(false);
const isTheaterMode = ref(false);
const showControls = ref(true);
const isCaptionsActive = ref(true);
let controlsTimeout: ReturnType<typeof setTimeout> | null = null;

// UI States
const isReadMore = ref(false);
const isBookmarked = ref(false);
const isFollowingCreator = ref(false);
const showShareToast = ref(false);
const showReportModal = ref(false);
const reportSent = ref(false);
const sortCommentsBy = ref<'top' | 'newest'>('top');

// Comments system
const newCommentText = ref('');
const commentsList = ref<Comment[]>([...props.comments]);

const userInitials = computed(() => {
    const name = currentUser.value?.name || 'User';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

// Format seconds into MM:SS
const formatTime = (seconds: number) => {
    if (isNaN(seconds) || seconds <= 0) return '00:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// Play / Pause toggle
const togglePlay = () => {
    if (!videoRef.value) return;
    if (videoRef.value.paused) {
        videoRef.value.play();
        isPlaying.value = true;
    } else {
        videoRef.value.pause();
        isPlaying.value = false;
    }
};

// Seek forward / backward
const seekRelative = (deltaSeconds: number) => {
    if (!videoRef.value) return;
    videoRef.value.currentTime = Math.max(
        0,
        Math.min(videoRef.value.duration || 0, videoRef.value.currentTime + deltaSeconds),
    );
};

// Handle scrubber seek click
const handleScrubberClick = (e: MouseEvent) => {
    const bar = e.currentTarget as HTMLElement;
    const rect = bar.getBoundingClientRect();
    const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    if (videoRef.value && videoRef.value.duration) {
        videoRef.value.currentTime = percent * videoRef.value.duration;
    }
};

// Volume controls
const setVolume = (e: Event) => {
    const val = parseFloat((e.target as HTMLInputElement).value);
    volume.value = val;
    if (videoRef.value) {
        videoRef.value.volume = val;
        videoRef.value.muted = val === 0;
        isMuted.value = val === 0;
    }
};

const toggleMute = () => {
    if (!videoRef.value) return;
    isMuted.value = !isMuted.value;
    videoRef.value.muted = isMuted.value;
    if (!isMuted.value && volume.value === 0) {
        volume.value = 0.5;
        videoRef.value.volume = 0.5;
    }
};

// Fullscreen toggle
const toggleFullscreen = () => {
    if (!playerContainerRef.value) return;
    if (!document.fullscreenElement) {
        playerContainerRef.value.requestFullscreen().catch(() => {});
        isFullscreen.value = true;
    } else {
        document.exitFullscreen().catch(() => {});
        isFullscreen.value = false;
    }
};

// Theater mode toggle
const toggleTheaterMode = () => {
    isTheaterMode.value = !isTheaterMode.value;
};

// Auto hide controls
const handleMouseMove = () => {
    showControls.value = true;
    if (controlsTimeout) clearTimeout(controlsTimeout);
    if (isPlaying.value) {
        controlsTimeout = setTimeout(() => {
            showControls.value = false;
        }, 3200);
    }
};

// Video event bindings
const onTimeUpdate = () => {
    if (videoRef.value) {
        currentTime.value = videoRef.value.currentTime;
    }
};

const onLoadedMetadata = () => {
    if (videoRef.value) {
        duration.value = videoRef.value.duration;
        videoRef.value.volume = volume.value;
    }
};

const onVideoEnded = () => {
    isPlaying.value = false;
    showControls.value = true;
};

// Switch episode
const switchEpisode = (ep: Episode) => {
    router.visit(`/films/${props.film.id}/episodes/${ep.number}`, {
        preserveScroll: true,
    });
};

// Bookmark toggle
const toggleWatchlist = () => {
    isBookmarked.value = !isBookmarked.value;
};

// Share URL
const shareEpisode = async () => {
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

// Comments actions
const submitComment = () => {
    const text = newCommentText.value.trim();
    if (!text) return;

    const newComment: Comment = {
        id: Date.now(),
        user: {
            name: currentUser.value?.name || 'User',
            initials: userInitials.value,
            badge: 'PRO',
            is_verified: false,
        },
        is_pinned: false,
        content: text,
        created_at: 'Just now',
        likes: 0,
        is_liked: false,
    };

    commentsList.value.unshift(newComment);
    newCommentText.value = '';
};

const toggleLikeComment = (comment: Comment) => {
    comment.is_liked = !comment.is_liked;
    comment.likes += comment.is_liked ? 1 : -1;
};

// Keyboard listener
const handleKeyDown = (e: KeyboardEvent) => {
    const target = e.target as HTMLElement;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') return;

    if (e.key === ' ' || e.key.toLowerCase() === 'k') {
        e.preventDefault();
        togglePlay();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        seekRelative(-10);
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        seekRelative(10);
    } else if (e.key.toLowerCase() === 'm') {
        e.preventDefault();
        toggleMute();
    } else if (e.key.toLowerCase() === 'f') {
        e.preventDefault();
        toggleFullscreen();
    } else if (e.key.toLowerCase() === 't') {
        e.preventDefault();
        toggleTheaterMode();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (controlsTimeout) clearTimeout(controlsTimeout);
});
</script>

<template>
    <div
        class="h-screen w-screen overflow-hidden bg-[#0E0F11] font-sans text-[#F2EFEA] antialiased selection:bg-[#1B44E8] selection:text-white"
    >
        <Head :title="`Nonton ${currentEpisode.code}: ${currentEpisode.title} - ${film.title} | WATOON`" />

        <!-- MAIN CINEMA CANVAS (Two-Column Desktop View) -->
        <main
            id="cinema-canvas"
            class="m-0 flex h-screen w-screen flex-col overflow-hidden p-0 md:flex-row"
        >
            <!-- [LEFT COLUMN] CINEMA VIDEO PLAYER -->
            <section
                ref="playerContainerRef"
                id="player-container"
                class="relative flex h-full flex-col overflow-hidden bg-black select-none transition-all duration-300"
                :class="isTheaterMode ? 'w-full' : 'w-full md:w-[68%]'"
                @mousemove="handleMouseMove"
                @mouseleave="isPlaying ? (showControls = false) : null"
            >
                <!-- HTML5 Real Video Element with Poster -->
                <div class="relative h-full w-full flex-1 overflow-hidden bg-black">
                    <video
                        ref="videoRef"
                        class="h-full w-full object-contain"
                        :src="currentEpisode.video_url || 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'"
                        :poster="film.backdrop || currentEpisode.thumbnail"
                        playsinline
                        @timeupdate="onTimeUpdate"
                        @loadedmetadata="onLoadedMetadata"
                        @ended="onVideoEnded"
                        @click="togglePlay"
                    ></video>

                    <!-- Ink Radial Vignette -->
                    <div
                        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#0E0F11]/90 via-transparent to-black/80"
                    ></div>
                </div>

                <!-- TOP BAR OVERLAY -->
                <div
                    class="absolute top-0 right-0 left-0 z-30 flex items-center justify-between p-5 bg-gradient-to-b from-black/95 via-black/50 to-transparent transition-opacity duration-300"
                    :class="showControls ? 'opacity-100' : 'pointer-events-none opacity-0'"
                >
                    <div class="flex items-center gap-3">
                        <Link
                            :href="`/films/${film.id}`"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-[#F2EFEA] transition-all hover:bg-white/10"
                            title="Kembali ke Detail Film"
                        >
                            <ArrowLeft class="h-5 w-5" />
                        </Link>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold tracking-wide text-[#7A9BFF]">
                                {{ currentEpisode.code }}
                            </span>
                            <span class="text-xs text-[#8C8C90]">·</span>
                            <span class="text-sm font-medium text-[#F2EFEA]">
                                {{ currentEpisode.title }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Theater Mode Toggle -->
                        <button
                            type="button"
                            @click="toggleTheaterMode"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-[#F2EFEA] transition-all hover:bg-white/10"
                            :title="isTheaterMode ? 'Mode Standar (T)' : 'Mode Teater (T)'"
                        >
                            <Tv
                                class="h-4 w-4"
                                :class="isTheaterMode ? 'text-[#1B44E8]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                            />
                        </button>

                        <!-- Bookmark Button -->
                        <button
                            type="button"
                            @click="toggleWatchlist"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-[#F2EFEA] transition-all hover:bg-white/10"
                            :title="isBookmarked ? 'Hapus dari Watchlist' : 'Tambah ke Watchlist'"
                        >
                            <BookmarkCheck
                                v-if="isBookmarked"
                                class="h-5 w-5 text-[#1B44E8]"
                            />
                            <Bookmark
                                v-else
                                class="h-5 w-5 text-[#8C8C90] hover:text-[#F2EFEA]"
                            />
                        </button>

                        <!-- Share Button -->
                        <button
                            type="button"
                            @click="shareEpisode"
                            class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-[#8C8C90] transition-all hover:bg-white/10 hover:text-[#F2EFEA]"
                            title="Bagikan Episode"
                        >
                            <Share2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- CENTER PLAY / PAUSE BUTTON GLYPH -->
                <div
                    class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center transition-opacity duration-300"
                    :class="!isPlaying || showControls ? 'opacity-100' : 'opacity-0'"
                >
                    <button
                        type="button"
                        @click="togglePlay"
                        class="pointer-events-auto flex h-20 w-20 cursor-pointer items-center justify-center rounded-full bg-black/40 text-[#F2EFEA] backdrop-blur-sm transition-all duration-200 hover:scale-110 hover:bg-[#1B44E8] hover:text-white"
                        title="Putar / Jeda (Spasi)"
                    >
                        <Pause
                            v-if="isPlaying"
                            class="h-10 w-10 fill-current"
                        />
                        <Play
                            v-else
                            class="ml-1.5 h-10 w-10 fill-current"
                        />
                    </button>
                </div>

                <!-- BOTTOM CONTROLS OVERLAY -->
                <div
                    class="absolute right-0 bottom-0 left-0 z-30 flex flex-col gap-3.5 bg-gradient-to-t from-black/95 via-black/75 to-transparent px-6 pt-8 pb-5 transition-opacity duration-300"
                    :class="showControls ? 'opacity-100' : 'pointer-events-none opacity-0'"
                >
                    <!-- PROGRESS SCRUBBER BAR -->
                    <div
                        class="group/scrubber flex w-full cursor-pointer items-center py-1"
                        @click="handleScrubberClick"
                    >
                        <div class="relative h-[3px] w-full rounded-full bg-white/20 transition-all group-hover/scrubber:h-[5px]">
                            <!-- Played progress -->
                            <div
                                class="absolute top-0 bottom-0 left-0 flex items-center justify-end rounded-full bg-[#1B44E8]"
                                :style="{ width: `${duration ? (currentTime / duration) * 100 : 0}%` }"
                            >
                                <span
                                    class="-mr-[6px] h-3 w-3 rounded-full bg-[#1B44E8] ring-2 ring-white shadow-md transition-transform group-hover/scrubber:scale-125"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <!-- CONTROLS ROW -->
                    <div class="flex items-center justify-between text-[#F2EFEA]">
                        <!-- Left Controls -->
                        <div class="flex items-center gap-4">
                            <button
                                type="button"
                                @click="togglePlay"
                                class="flex cursor-pointer items-center justify-center text-[#F2EFEA] transition-colors hover:text-[#1B44E8]"
                                title="Putar / Jeda (Spasi)"
                            >
                                <Pause
                                    v-if="isPlaying"
                                    class="h-5 w-5 fill-current"
                                />
                                <Play
                                    v-else
                                    class="h-5 w-5 fill-current"
                                />
                            </button>

                            <button
                                type="button"
                                @click="seekRelative(-10)"
                                class="flex cursor-pointer items-center justify-center text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                                title="Mundur 10 detik"
                            >
                                <RotateCcw class="h-4 w-4" />
                            </button>

                            <button
                                type="button"
                                @click="seekRelative(10)"
                                class="flex cursor-pointer items-center justify-center text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                                title="Maju 10 detik"
                            >
                                <RotateCw class="h-4 w-4" />
                            </button>

                            <!-- Volume Slider -->
                            <div class="group/vol flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="toggleMute"
                                    class="flex cursor-pointer items-center justify-center text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                                    title="Mute / Unmute (M)"
                                >
                                    <VolumeX
                                        v-if="isMuted || volume === 0"
                                        class="h-4 w-4 text-red-400"
                                    />
                                    <Volume2
                                        v-else
                                        class="h-4 w-4"
                                    />
                                </button>
                                <input
                                    type="range"
                                    min="0"
                                    max="1"
                                    step="0.05"
                                    :value="isMuted ? 0 : volume"
                                    @input="setVolume"
                                    class="h-1 w-16 cursor-pointer accent-[#1B44E8]"
                                />
                            </div>

                            <!-- Timecode -->
                            <div class="ml-1 font-mono text-xs text-[#8C8C90]">
                                <span class="font-medium text-[#F2EFEA]">{{ formatTime(currentTime) }}</span>
                                / {{ formatTime(duration || 2880) }}
                            </div>
                        </div>

                        <!-- Right Controls -->
                        <div class="flex items-center gap-3.5 text-[#8C8C90]">
                            <!-- Subtitles / CC Badge -->
                            <button
                                type="button"
                                @click="isCaptionsActive = !isCaptionsActive"
                                class="flex cursor-pointer items-center rounded px-1.5 py-0.5 text-[10px] font-semibold tracking-wider transition-colors"
                                :class="
                                    isCaptionsActive
                                        ? 'border border-[rgba(27,68,232,0.45)] bg-[rgba(27,68,232,0.2)] text-[#7A9BFF]'
                                        : 'border border-white/10 bg-white/5 text-[#8C8C90] hover:text-[#F2EFEA]'
                                "
                                title="Teks Terjemahan (CC)"
                            >
                                CC
                            </button>

                            <!-- Theater Mode Toggle -->
                            <button
                                type="button"
                                @click="toggleTheaterMode"
                                class="flex cursor-pointer items-center justify-center transition-colors hover:text-[#F2EFEA]"
                                :title="isTheaterMode ? 'Mode Standar (T)' : 'Mode Teater (T)'"
                            >
                                <Tv
                                    class="h-4 w-4"
                                    :class="isTheaterMode ? 'text-[#1B44E8]' : ''"
                                />
                            </button>

                            <!-- Fullscreen Toggle -->
                            <button
                                type="button"
                                @click="toggleFullscreen"
                                class="flex cursor-pointer items-center justify-center transition-colors hover:text-[#F2EFEA]"
                                title="Layar Penuh (F)"
                            >
                                <Minimize
                                    v-if="isFullscreen"
                                    class="h-5 w-5"
                                />
                                <Maximize
                                    v-else
                                    class="h-5 w-5"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- [RIGHT COLUMN] EPISODE DETAILS & COMMENTS ASIDE -->
            <aside
                id="info-panel"
                class="custom-scroll flex h-full flex-col space-y-6 overflow-y-auto border-l border-[rgba(242,239,234,0.08)] bg-[#0E0F11] px-6 pt-6 pb-12 transition-all duration-300"
                :class="isTheaterMode ? 'hidden w-0 p-0 border-0 overflow-hidden' : 'w-full md:w-[32%]'"
            >
                <!-- EPISODE METADATA STACK -->
                <div class="space-y-2.5">
                    <span class="block text-[11px] font-semibold tracking-[0.12em] text-[#1B44E8] uppercase">
                        EPISODE {{ currentEpisode.number }}
                    </span>

                    <h1 class="text-2xl font-semibold tracking-tight text-[#F2EFEA]">
                        <Link
                            :href="`/films/${film.id}`"
                            class="transition-colors hover:text-[#1B44E8]"
                            title="Buka Halaman Film"
                        >
                            {{ film.title }}
                        </Link>
                    </h1>

                    <p class="text-sm font-medium text-[#8C8C90]">
                        {{ currentEpisode.title }}
                    </p>

                    <!-- Metadata Row -->
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-[#8C8C90]">
                        <span class="text-[#F2EFEA]">{{ film.year }}</span>
                        <span>·</span>
                        <span>{{ currentEpisode.duration }}</span>
                        <span>·</span>
                        <span class="rounded border border-[rgba(242,239,234,0.1)] bg-[rgba(242,239,234,0.06)] px-1.5 py-0.5 text-[10px] text-[#F2EFEA]">
                            {{ film.age_rating }}
                        </span>
                        <span>·</span>
                        <span class="flex items-center gap-1 text-[#D4B67A]">
                            <Star class="h-3 w-3 fill-current" />
                            <span class="font-medium">{{ film.rating }}</span>
                        </span>
                        <span>·</span>
                        <span class="text-[11px] text-[#7A9BFF]">
                            {{ film.genres[0] || 'Drama' }}
                        </span>
                    </div>

                    <!-- Short Narrative Description -->
                    <div class="pt-1">
                        <p class="text-xs leading-relaxed text-[#8C8C90]">
                            {{
                                isReadMore
                                    ? currentEpisode.synopsis
                                    : currentEpisode.synopsis.slice(0, 150) +
                                      (currentEpisode.synopsis.length > 150 ? '...' : '')
                            }}
                            <button
                                v-if="currentEpisode.synopsis.length > 150"
                                type="button"
                                @click="isReadMore = !isReadMore"
                                class="ml-1 font-medium text-[#1B44E8] hover:underline"
                            >
                                {{ isReadMore ? 'Tutup' : 'Baca selengkapnya' }}
                            </button>
                        </p>
                    </div>

                    <!-- Creator Strip -->
                    <div class="flex items-center justify-between pt-2">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full border border-[rgba(242,239,234,0.12)] bg-[#1E1F23] text-[10px] font-medium text-[#F2EFEA]">
                                {{ film.creator?.initials || 'KA' }}
                            </div>
                            <div class="text-xs text-[#F2EFEA]">
                                <span class="text-[#8C8C90]">oleh </span>
                                {{ film.creator?.name || 'Kaelen Ashford' }}
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="isFollowingCreator = !isFollowingCreator"
                            class="cursor-pointer text-xs font-medium transition-colors"
                            :class="isFollowingCreator ? 'text-[#8C8C90] hover:text-white' : 'text-[#1B44E8] hover:text-[#7A9BFF]'"
                        >
                            {{ isFollowingCreator ? 'Mengikuti' : 'Ikuti' }}
                        </button>
                    </div>
                </div>

                <!-- Hairline Divider -->
                <div class="h-px w-full bg-[rgba(242,239,234,0.08)]"></div>

                <!-- QUICK ACTIONS ROW -->
                <div class="grid grid-cols-3 gap-2 py-1">
                    <button
                        type="button"
                        @click="toggleWatchlist"
                        class="flex h-9 cursor-pointer items-center justify-center gap-1.5 rounded-[10px] border border-[rgba(242,239,234,0.15)] text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-[#1E1F23]"
                    >
                        <BookmarkCheck
                            v-if="isBookmarked"
                            class="h-3.5 w-3.5 text-[#1B44E8]"
                        />
                        <Plus v-else class="h-3.5 w-3.5" />
                        <span>{{ isBookmarked ? 'Tersimpan' : 'Daftar' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="shareEpisode"
                        class="flex h-9 cursor-pointer items-center justify-center gap-1.5 rounded-[10px] border border-[rgba(242,239,234,0.15)] text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-[#1E1F23]"
                    >
                        <Share2 class="h-3.5 w-3.5" />
                        <span>Bagikan</span>
                    </button>

                    <button
                        type="button"
                        @click="showReportModal = true"
                        class="flex h-9 cursor-pointer items-center justify-center gap-1.5 rounded-[10px] border border-[rgba(242,239,234,0.15)] text-xs font-medium text-[#8C8C90] transition-colors hover:bg-[#1E1F23] hover:text-[#F2EFEA]"
                    >
                        <Flag class="h-3.5 w-3.5" />
                        <span>Lapor</span>
                    </button>
                </div>

                <!-- Hairline Divider -->
                <div class="h-px w-full bg-[rgba(242,239,234,0.08)]"></div>

                <!-- EPISODES CAROUSEL ROW -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold tracking-[0.12em] text-[#1B44E8] uppercase">
                                EPISODE
                            </span>
                            <span class="text-[11px] text-[#8C8C90]">Season 1</span>
                        </div>
                        <Link
                            :href="`/films/${film.id}`"
                            class="flex items-center gap-1 text-xs text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            <span>Lihat semua</span>
                            <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <!-- Horizontal Carousel (180px cards, 16:9 thumbnails, tier badges) -->
                    <div class="custom-scroll -mx-1 flex gap-3 overflow-x-auto px-1 pb-2 snap-x">
                        <div
                            v-for="ep in episodes"
                            :key="ep.number"
                            @click="switchEpisode(ep)"
                            class="group relative w-[180px] shrink-0 cursor-pointer snap-start rounded-[8px] bg-[#17181B] p-2 transition-all hover:bg-[#1E1F23]"
                            :class="
                                ep.number === currentEpisode.number
                                    ? 'border-2 border-[#1B44E8]'
                                    : 'border border-[rgba(242,239,234,0.08)]'
                            "
                        >
                            <div class="relative mb-2 aspect-video w-full overflow-hidden rounded-[4px] bg-black">
                                <img
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    :src="ep.thumbnail"
                                    :alt="ep.title"
                                />

                                <!-- Badge (VIP, PRO, FREE) -->
                                <span
                                    v-if="ep.badge === 'VIP'"
                                    class="absolute top-1.5 left-1.5 flex h-[14px] items-center rounded-[6px] border border-[rgba(184,151,90,0.50)] bg-[rgba(184,151,90,0.22)] px-[5px] text-[9px] font-semibold tracking-[0.12em] text-[#D4B67A] uppercase"
                                >
                                    VIP
                                </span>
                                <span
                                    v-else-if="ep.badge === 'PRO'"
                                    class="absolute top-1.5 left-1.5 flex h-[14px] items-center rounded-[6px] border border-[rgba(27,68,232,0.45)] bg-[rgba(27,68,232,0.20)] px-[5px] text-[9px] font-semibold tracking-[0.12em] text-[#7A9BFF] uppercase"
                                >
                                    PRO
                                </span>
                                <span
                                    v-else
                                    class="absolute top-1.5 left-1.5 flex h-[14px] items-center rounded-[6px] border border-[rgba(140,140,144,0.40)] bg-[rgba(140,140,144,0.20)] px-[5px] text-[9px] font-semibold tracking-[0.12em] text-[#A8A8AC] uppercase"
                                >
                                    FREE
                                </span>

                                <!-- Active Playing Indicator -->
                                <div
                                    v-if="ep.number === currentEpisode.number"
                                    class="absolute inset-0 flex items-center justify-center bg-black/40"
                                >
                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#1B44E8] text-white shadow-lg">
                                        <Play class="ml-0.5 h-3.5 w-3.5 fill-current" />
                                    </div>
                                </div>
                            </div>

                            <div class="mb-1 flex items-center gap-1.5">
                                <span
                                    v-if="ep.number === currentEpisode.number"
                                    class="h-1.5 w-1.5 rounded-full bg-[#1B44E8]"
                                ></span>
                                <span
                                    class="text-[11px] font-semibold"
                                    :class="ep.number === currentEpisode.number ? 'text-[#F2EFEA]' : 'text-[#8C8C90]'"
                                >
                                    {{ ep.code }}
                                </span>
                                <span class="ml-auto text-[10px] text-[#8C8C90]">{{ ep.duration }}</span>
                            </div>

                            <p class="truncate text-xs font-medium text-[#F2EFEA]">
                                {{ ep.title }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Hairline Divider -->
                <div class="h-px w-full bg-[rgba(242,239,234,0.08)]"></div>

                <!-- COMMENTS SECTION -->
                <div class="space-y-4">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-semibold tracking-[0.12em] text-[#1B44E8] uppercase">
                                KOMENTAR
                            </span>
                            <span class="text-xs text-[#8C8C90]">
                                {{ commentsList.length }} komentar
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="sortCommentsBy = sortCommentsBy === 'top' ? 'newest' : 'top'"
                            class="flex cursor-pointer items-center gap-1 text-xs text-[#8C8C90] transition-colors hover:text-[#F2EFEA]"
                        >
                            <Sliders class="h-3 w-3" />
                            <span>{{ sortCommentsBy === 'top' ? 'Terpopuler' : 'Terbaru' }}</span>
                        </button>
                    </div>

                    <!-- Comment Input Form -->
                    <form @submit.prevent="submitComment" class="flex items-center gap-3 pt-1">
                        <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[rgba(242,239,234,0.15)] bg-[#1E1F23] text-[11px] text-[#F2EFEA]">
                            {{ userInitials }}
                            <span class="absolute -right-1 -bottom-1 flex h-[14px] items-center rounded-[6px] border border-[rgba(27,68,232,0.45)] bg-[rgba(27,68,232,0.20)] px-[4px] text-[8px] font-semibold tracking-wider text-[#7A9BFF] uppercase ring-2 ring-[#0E0F11]">
                                PRO
                            </span>
                        </div>

                        <div class="relative flex flex-1 items-center">
                            <input
                                v-model="newCommentText"
                                type="text"
                                placeholder="Tulis komentar..."
                                class="w-full border-0 border-b border-[rgba(242,239,234,0.15)] bg-transparent pr-8 pb-1.5 text-xs text-[#F2EFEA] placeholder-[#8C8C90] transition-colors focus:border-[#1B44E8] focus:ring-0"
                            />
                            <button
                                type="submit"
                                :disabled="!newCommentText.trim()"
                                class="absolute right-0 cursor-pointer p-1 text-[#1B44E8] transition-colors hover:text-[#7A9BFF] disabled:cursor-not-allowed disabled:text-[#8C8C90]/40"
                                title="Kirim Komentar"
                            >
                                <Send class="h-4 w-4" />
                            </button>
                        </div>
                    </form>

                    <!-- Comments List -->
                    <div class="space-y-4 pt-2">
                        <div
                            v-for="comm in commentsList"
                            :key="comm.id"
                            class="transition-all"
                            :class="
                                comm.is_pinned
                                    ? 'rounded-[10px] border border-[rgba(27,68,232,0.2)] bg-[rgba(27,68,232,0.05)] p-3 space-y-2'
                                    : 'flex items-start gap-3'
                            "
                        >
                            <!-- Pinned Comment Variant -->
                            <template v-if="comm.is_pinned">
                                <div class="flex items-start gap-3">
                                    <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[rgba(27,68,232,0.3)] bg-[#17181B] text-[10px] font-medium text-[#7A9BFF]">
                                        {{ comm.user.initials }}
                                        <span class="absolute -right-1 -bottom-1 flex h-[14px] items-center rounded-[6px] border border-[rgba(27,68,232,0.45)] bg-[rgba(27,68,232,0.20)] px-[4px] text-[8px] font-semibold text-[#7A9BFF] ring-2 ring-[#0E0F11]">
                                            PRO
                                        </span>
                                    </div>

                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <span class="flex items-center gap-0.5 rounded-[3px] bg-[rgba(27,68,232,0.15)] px-1.5 py-0.5 text-[9px] font-semibold tracking-wider text-[#7A9BFF] uppercase">
                                                    <Pin class="h-2.5 w-2.5" />
                                                    PINNED
                                                </span>
                                                <span class="text-xs font-medium text-[#F2EFEA]">
                                                    {{ comm.user.name }}
                                                </span>
                                                <Check class="h-3 w-3 text-[#7A9BFF]" />
                                            </div>
                                            <span class="text-[11px] text-[#8C8C90]">{{ comm.created_at }}</span>
                                        </div>

                                        <p class="text-xs leading-relaxed text-[#F2EFEA]">
                                            {{ comm.content }}
                                        </p>
                                    </div>
                                </div>
                            </template>

                            <!-- Regular Comment Variant -->
                            <template v-else>
                                <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[rgba(242,239,234,0.1)] bg-[#23252A] text-[10px] font-medium text-[#F2EFEA]">
                                    {{ comm.user.initials }}
                                    <span
                                        v-if="comm.user.badge === 'VIP'"
                                        class="absolute -right-1 -bottom-1 flex h-[14px] items-center rounded-[6px] border border-[rgba(184,151,90,0.50)] bg-[rgba(184,151,90,0.22)] px-[4px] text-[8px] font-semibold text-[#D4B67A] ring-2 ring-[#0E0F11]"
                                    >
                                        VIP
                                    </span>
                                    <span
                                        v-else-if="comm.user.badge === 'PRO'"
                                        class="absolute -right-1 -bottom-1 flex h-[14px] items-center rounded-[6px] border border-[rgba(27,68,232,0.45)] bg-[rgba(27,68,232,0.20)] px-[4px] text-[8px] font-semibold text-[#7A9BFF] ring-2 ring-[#0E0F11]"
                                    >
                                        PRO
                                    </span>
                                    <span
                                        v-else
                                        class="absolute -right-1 -bottom-1 flex h-[14px] items-center rounded-[6px] border border-[rgba(140,140,144,0.40)] bg-[rgba(140,140,144,0.20)] px-[4px] text-[8px] font-semibold text-[#A8A8AC] ring-2 ring-[#0E0F11]"
                                    >
                                        FREE
                                    </span>
                                </div>

                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-[#F2EFEA]">
                                                {{ comm.user.name }}
                                            </span>
                                            <span class="text-[10px] text-[#8C8C90]">{{ comm.created_at }}</span>
                                        </div>
                                        <button type="button" class="cursor-pointer text-[#8C8C90] hover:text-[#F2EFEA]">
                                            <MoreHorizontal class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    <p class="text-xs leading-relaxed text-[#D8D6D2]">
                                        {{ comm.content }}
                                    </p>

                                    <div class="flex items-center gap-4 pt-0.5 text-[11px] text-[#8C8C90]">
                                        <button
                                            type="button"
                                            @click="toggleLikeComment(comm)"
                                            class="flex cursor-pointer items-center gap-1 transition-colors hover:text-[#F2EFEA]"
                                            :class="comm.is_liked ? 'text-[#1B44E8]' : ''"
                                        >
                                            <ThumbsUp
                                                class="h-3 w-3"
                                                :class="comm.is_liked ? 'fill-current' : ''"
                                            />
                                            <span>{{ comm.likes }}</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="cursor-pointer transition-colors hover:text-[#F2EFEA]"
                                        >
                                            Balas
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Load more comments button -->
                    <div class="pt-3 text-center">
                        <button
                            type="button"
                            class="cursor-pointer py-1 text-xs font-medium text-[#1B44E8] transition-colors hover:text-[#7A9BFF]"
                        >
                            Muat komentar lainnya
                        </button>
                    </div>
                </div>
            </aside>
        </main>

        <!-- REPORT MODAL -->
        <div
            v-if="showReportModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
            @click.self="showReportModal = false"
        >
            <div class="relative w-full max-w-sm rounded-2xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-white">Laporkan Masalah</h3>
                    <button
                        type="button"
                        @click="showReportModal = false"
                        class="text-[#8C8C90] hover:text-white"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <p class="mb-4 text-xs text-[#8C8C90]">
                    Pilih kendala yang kamu temukan pada pemutaran episode {{ currentEpisode.code }}:
                </p>
                <div class="space-y-2 text-xs">
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg bg-white/5 hover:bg-white/10">
                        <input type="radio" name="report_reason" checked class="accent-[#1B44E8]" />
                        <span>Video tidak dapat diputar atau buffer terus</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg bg-white/5 hover:bg-white/10">
                        <input type="radio" name="report_reason" class="accent-[#1B44E8]" />
                        <span>Teks terjemahan (subtitle) tidak sinkron</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg bg-white/5 hover:bg-white/10">
                        <input type="radio" name="report_reason" class="accent-[#1B44E8]" />
                        <span>Masalah kualitas audio atau video</span>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showReportModal = false"
                        class="rounded-lg px-4 py-2 text-xs text-[#8C8C90] hover:bg-white/5 hover:text-white"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="
                            reportSent = true;
                            showReportModal = false;
                        "
                        class="rounded-lg bg-[#1B44E8] px-4 py-2 text-xs font-medium text-white hover:bg-[#1B44E8]/90"
                    >
                        Kirim Laporan
                    </button>
                </div>
            </div>
        </div>

        <!-- SHARE TOAST -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-2 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-2 opacity-0"
        >
            <div
                v-if="showShareToast || reportSent"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-xl border border-[rgba(27,68,232,0.3)] bg-[#17181B] px-4 py-3 text-xs text-[#F2EFEA] shadow-2xl backdrop-blur-md"
            >
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-[#1B44E8] text-white">
                    <Check class="h-3 w-3 stroke-[3]" />
                </div>
                <span>{{
                    reportSent
                        ? 'Laporan berhasil dikirim. Terima kasih!'
                        : 'Tautan episode berhasil disalin ke clipboard!'
                }}</span>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: rgba(14, 15, 17, 0.4);
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: rgba(242, 239, 234, 0.15);
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(242, 239, 234, 0.28);
}
</style>
