<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowUp,
    Check,
    CheckCheck,
    Crown,
    Info,
    Lock,
    LogOut,
    MoreVertical,
    Paperclip,
    Phone,
    Search,
    Settings,
    Sparkles,
    SquarePen,
    Trash2,
    Video,
    Volume2,
    VolumeX,
    X,
} from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { dashboard, logout, pricing } from '@/routes';
import { edit } from '@/routes/profile';
import tierRoute from '@/routes/subscription/tier';
import type { User } from '@/types/auth';

type KastaTier = 'FREE' | 'PRO' | 'VIP';

interface MessageItem {
    id: string;
    sender: 'user' | 'creator';
    text: string;
    timestamp: string;
    status?: string;
    attachment?: {
        name: string;
        size: string;
        type: 'image' | 'file';
        url?: string;
    };
}

interface Conversation {
    id: string;
    creatorId: string;
    name: string;
    handle: string;
    initials: string;
    bio: string;
    tier: KastaTier;
    isVerified: boolean;
    isOnline: boolean;
    lastSeen?: string;
    timeAgo: string;
    lastMessage: string;
    unread: boolean;
    notificationsEnabled: boolean;
    messages: MessageItem[];
}

const props = defineProps<{
    selectedCreatorId?: string | null;
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

// ==========================================
// 3-KASTA SYSTEM (FREE < PRO < VIP)
// ==========================================
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

// Default user tier is FREE (unless previously set / stored / loaded from DB)
const currentUserTier = ref<KastaTier>(getInitialTier());
const isKastaModalOpen = ref(false);
const targetUpgradeTier = ref<KastaTier>('PRO');
const isUpgrading = ref(false);

watch(
    () => currentUser.value?.tier,
    (newTier) => {
        if (newTier) {
            currentUserTier.value = normalizeTier(newTier);
        }
    },
);

const tierRank = (t: string): number => {
    const upper = t.toUpperCase();
    if (upper === 'VIP') return 3;
    if (upper === 'PRO') return 2;
    return 1; // FREE
};

/**
 * Access rule:
 * VIP rank 3 -> can access VIP (3), PRO (2), and FREE (1)
 * PRO rank 2 -> can access PRO (2) and FREE (1). Cannot access VIP (3)
 * FREE rank 1 -> can only access FREE (1). Cannot access PRO (2) or VIP (3)
 */
const canChatWithCreator = (creatorTier: KastaTier): boolean => {
    return tierRank(currentUserTier.value) >= tierRank(creatorTier);
};

const openUpgradeModal = (requiredTier: KastaTier) => {
    targetUpgradeTier.value = requiredTier === 'FREE' ? 'PRO' : requiredTier;
    isKastaModalOpen.value = true;
};

const switchUserTier = (newTier: KastaTier) => {
    isUpgrading.value = true;
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
                isUpgrading.value = false;
                isKastaModalOpen.value = false;
                triggerToast(`Kasta Anda berhasil diatur ke ${newTier}`);
            },
            onError: () => {
                isUpgrading.value = false;
                isKastaModalOpen.value = false;
                triggerToast(`Kasta berhasil diaktifkan ke ${newTier}`);
            },
        },
    );
};

// General UI State
const userMenuOpen = ref(false);
const mediaType = ref<'film' | 'novel'>('film');
const activeTab = ref<'all' | 'unread'>('all');
const conversationSearch = ref('');
const navSearchQuery = ref('');
const messageInput = ref('');
const messagesContainerRef = ref<HTMLElement | null>(null);
const messageInputRef = ref<HTMLTextAreaElement | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isTyping = ref(false);
const showMoreMenu = ref(false);
const showNewChatModal = ref(false);
const chatSearchQuery = ref('');
const isSearchingInChat = ref(false);
const pendingAttachment = ref<{ name: string; size: string; type: 'image' | 'file' } | null>(null);

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
    }, 4000);
};

// Initial conversations matching Stitch screen and WATOON creators with their tiers
const initialConversations: Conversation[] = [
    {
        id: 'conv-1',
        creatorId: 'creator-1',
        name: 'Rendra Wijaya',
        handle: '@rendrawijaya',
        initials: 'RW',
        bio: 'Cinematic storyteller based in Bandung. Director of Cakrawala Senja.',
        tier: 'VIP',
        isVerified: true,
        isOnline: true,
        timeAgo: '2h',
        lastMessage: 'Boleh banget. Nanti aku share foto dari set ya.',
        unread: true,
        notificationsEnabled: true,
        messages: [
            {
                id: 'm1-1',
                sender: 'creator',
                text: 'Hai! Terima kasih sudah subscribe. Ada yang bisa dibantu?',
                timestamp: '14:28',
            },
            {
                id: 'm1-2',
                sender: 'creator',
                text: 'Btw kalau kamu suka thriller, episode baru minggu depan akan lebih intens.',
                timestamp: '14:30',
            },
            {
                id: 'm1-3',
                sender: 'user',
                text: 'Wah iya, saya tunggu! Btw boleh nanya tentang behind-the-scenes episode 8?',
                timestamp: '14:31',
            },
            {
                id: 'm1-4',
                sender: 'creator',
                text: 'Boleh banget. Nanti aku share beberapa foto dari set di chat ini ya.',
                timestamp: '14:32',
            },
            {
                id: 'm1-5',
                sender: 'user',
                text: 'Siap, ditunggu!',
                timestamp: '14:35',
                status: 'SEEN 2H AGO',
            },
        ],
    },
    {
        id: 'conv-2',
        creatorId: 'creator-2',
        name: 'Alya Kusuma',
        handle: '@alyakusuma',
        initials: 'AK',
        bio: 'Short films & documentary director. Creator of Rumah di Ujung Jalan.',
        tier: 'PRO',
        isVerified: false,
        isOnline: true,
        timeAgo: '1d',
        lastMessage: 'You: Terima kasih banyak!',
        unread: false,
        notificationsEnabled: true,
        messages: [
            {
                id: 'm2-1',
                sender: 'creator',
                text: 'Halo! Terima kasih banyak sudah berlangganan dan mendukung produksi independen kami.',
                timestamp: 'Kemarin 10:15',
            },
            {
                id: 'm2-2',
                sender: 'user',
                text: 'Terima kasih banyak!',
                timestamp: 'Kemarin 10:18',
                status: 'SEEN 1D AGO',
            },
        ],
    },
    {
        id: 'conv-3',
        creatorId: 'creator-3',
        name: 'Bagas Prasetyo',
        handle: '@bagasfilm',
        initials: 'BP',
        bio: 'Genre-bending thrillers & noir fiction. Creator of Detik Terakhir.',
        tier: 'VIP',
        isVerified: true,
        isOnline: false,
        lastSeen: '1 jam lalu',
        timeAgo: '2d',
        lastMessage: 'Episode baru minggu depan ya.',
        unread: true,
        notificationsEnabled: false,
        messages: [
            {
                id: 'm3-1',
                sender: 'user',
                text: 'Halo Mas Bagas, kapan rilis episode lanjutan Detik Terakhir?',
                timestamp: '2d lalu 16:20',
            },
            {
                id: 'm3-2',
                sender: 'creator',
                text: 'Episode baru minggu depan ya.',
                timestamp: '2d lalu 17:05',
            },
        ],
    },
    {
        id: 'conv-4',
        creatorId: 'creator-4',
        name: 'Sinta Maharani',
        handle: '@sintamaharani',
        initials: 'SM',
        bio: 'Women-led dramas & intimate character studies. Creator of Pulang.',
        tier: 'PRO',
        isVerified: false,
        isOnline: false,
        lastSeen: '3 jam lalu',
        timeAgo: '3d',
        lastMessage: 'You: Siap, ditunggu!',
        unread: false,
        notificationsEnabled: true,
        messages: [
            {
                id: 'm4-1',
                sender: 'creator',
                text: 'Halo! Bab lanjutan untuk novel bersambung sudah masuk proses kurasi editor.',
                timestamp: '3d lalu 09:30',
            },
            {
                id: 'm4-2',
                sender: 'user',
                text: 'Siap, ditunggu!',
                timestamp: '3d lalu 09:42',
                status: 'SEEN 3D AGO',
            },
        ],
    },
    {
        id: 'conv-5',
        creatorId: 'creator-5',
        name: 'Dimas Aditya',
        handle: '@dimasaditya',
        initials: 'DA',
        bio: 'Emerging indie filmmaker. Director of Senja di Kota Tua.',
        tier: 'FREE',
        isVerified: false,
        isOnline: false,
        lastSeen: 'Kemarin',
        timeAgo: '1w',
        lastMessage: 'Ada rekomendasi film lain?',
        unread: false,
        notificationsEnabled: true,
        messages: [
            {
                id: 'm5-1',
                sender: 'creator',
                text: 'Halo kawan! Ada rekomendasi film lain yang ingin kamu tonton di katalog watoon?',
                timestamp: '1w lalu 11:20',
            },
        ],
    },
    {
        id: 'conv-6',
        creatorId: 'creator-6',
        name: 'Nadia Rahma',
        handle: '@nadiarahma',
        initials: 'NR',
        bio: 'Experimental & poetic cinema curator. Creator of Bayang Khatulistiwa.',
        tier: 'VIP',
        isVerified: true,
        isOnline: false,
        lastSeen: '5 hari lalu',
        timeAgo: '2w',
        lastMessage: 'You: Karya kamu keren banget.',
        unread: false,
        notificationsEnabled: true,
        messages: [
            {
                id: 'm6-1',
                sender: 'user',
                text: 'Karya kamu keren banget.',
                timestamp: '2w lalu 20:10',
                status: 'SEEN 2W AGO',
            },
        ],
    },
    {
        id: 'conv-7',
        creatorId: 'creator-7',
        name: 'Rangga Pratama',
        handle: '@ranggapratama',
        initials: 'RP',
        bio: 'Sci-fi & speculative fiction director. Creator of Cold Meridian.',
        tier: 'PRO',
        isVerified: false,
        isOnline: false,
        lastSeen: '1 minggu lalu',
        timeAgo: '3w',
        lastMessage: 'Terima kasih sudah subscribe!',
        unread: false,
        notificationsEnabled: true,
        messages: [
            {
                id: 'm7-1',
                sender: 'creator',
                text: 'Terima kasih sudah subscribe!',
                timestamp: '3w lalu 15:40',
            },
        ],
    },
    {
        id: 'conv-8',
        creatorId: 'creator-8',
        name: 'Kirana Dewi',
        handle: '@kiranadewi',
        initials: 'KD',
        bio: 'Documentary researcher & essay films. Creator of Arus Tenang.',
        tier: 'FREE',
        isVerified: false,
        isOnline: false,
        lastSeen: '2 minggu lalu',
        timeAgo: '1mo',
        lastMessage: 'You: Boleh minta link episode 4?',
        unread: false,
        notificationsEnabled: false,
        messages: [
            {
                id: 'm8-1',
                sender: 'user',
                text: 'Boleh minta link episode 4?',
                timestamp: '1mo lalu 13:00',
                status: 'SEEN 1MO AGO',
            },
        ],
    },
];

const STORAGE_KEY = 'watoon_messages_threads_v1';

const loadSavedConversations = (): Conversation[] => {
    if (typeof window === 'undefined') return initialConversations;
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
                return parsed;
            }
        }
    } catch {
        // fallback
    }
    return initialConversations;
};

const conversations = ref<Conversation[]>(loadSavedConversations());
const activeConversationId = ref<string | null>('conv-1');

const saveConversationsToStorage = () => {
    if (typeof window === 'undefined') return;
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(conversations.value));
    } catch {
        // ignore
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);

    let targetCreator = props.selectedCreatorId;
    if (!targetCreator && typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        targetCreator = urlParams.get('creator');
    }

    if (targetCreator) {
        const matched = conversations.value.find(
            (c) =>
                c.creatorId === targetCreator ||
                c.id === targetCreator ||
                c.handle.toLowerCase().includes(targetCreator!.toLowerCase().replace('@', '')) ||
                c.name.toLowerCase().includes(targetCreator!.toLowerCase()),
        );
        if (matched) {
            selectConversation(matched.id);
        }
    }

    scrollToBottom();
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
    if (toastTimer) clearTimeout(toastTimer);
});

const handleGlobalKeydown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        const navInput = document.getElementById('nav-search-input');
        if (navInput) navInput.focus();
    }
    if (e.key === 'Escape') {
        userMenuOpen.value = false;
        showMoreMenu.value = false;
        showNewChatModal.value = false;
        isKastaModalOpen.value = false;
        isSearchingInChat.value = false;
    }
};

// Filtered conversations list
const filteredConversations = computed(() => {
    let list = conversations.value;

    if (activeTab.value === 'unread') {
        list = list.filter((c) => c.unread);
    }

    if (conversationSearch.value.trim()) {
        const query = conversationSearch.value.toLowerCase().trim();
        list = list.filter(
            (c) =>
                c.name.toLowerCase().includes(query) ||
                c.handle.toLowerCase().includes(query) ||
                c.lastMessage.toLowerCase().includes(query) ||
                c.messages.some((m) => m.text.toLowerCase().includes(query)),
        );
    }

    return list;
});

const unreadCount = computed(() => {
    return conversations.value.filter((c) => c.unread).length;
});

const activeConversation = computed(() => {
    return conversations.value.find((c) => c.id === activeConversationId.value) || null;
});

// Messages in active conversation, optionally filtered by in-chat search
const activeMessages = computed(() => {
    if (!activeConversation.value) return [];
    const list = activeConversation.value.messages;
    if (!isSearchingInChat.value || !chatSearchQuery.value.trim()) {
        return list;
    }
    const q = chatSearchQuery.value.toLowerCase().trim();
    return list.filter((m) => m.text.toLowerCase().includes(q));
});

const selectConversation = (id: string) => {
    activeConversationId.value = id;
    showMoreMenu.value = false;
    isSearchingInChat.value = false;
    chatSearchQuery.value = '';

    const conv = conversations.value.find((c) => c.id === id);
    if (conv && conv.unread) {
        conv.unread = false;
        saveConversationsToStorage();
    }

    nextTick(() => {
        scrollToBottom();
        if (messageInputRef.value && canChatWithCreator(conv?.tier || 'FREE')) {
            messageInputRef.value.focus();
        }
    });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainerRef.value) {
            messagesContainerRef.value.scrollTop = messagesContainerRef.value.scrollHeight;
        }
    });
};

// Sending messages with Kasta access enforcement
const handleSendMessage = () => {
    if (!activeConversation.value) return;

    if (!canChatWithCreator(activeConversation.value.tier)) {
        openUpgradeModal(activeConversation.value.tier);
        triggerToast(`Perlu upgrade ke ${activeConversation.value.tier} untuk mengirim pesan ke kreator ini.`);
        return;
    }

    const text = messageInput.value.trim();
    if (!text && !pendingAttachment.value) return;

    const conv = activeConversation.value;
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeStr = `${hours}:${minutes}`;

    const newMsg: MessageItem = {
        id: `m-${Date.now()}`,
        sender: 'user',
        text: text,
        timestamp: timeStr,
        status: 'SENT',
        attachment: pendingAttachment.value ? { ...pendingAttachment.value } : undefined,
    };

    conv.messages.push(newMsg);
    conv.lastMessage = text || `[Mengirim berkas: ${pendingAttachment.value?.name}]`;
    conv.timeAgo = 'Baru saja';
    messageInput.value = '';
    pendingAttachment.value = null;

    saveConversationsToStorage();
    scrollToBottom();

    // Auto simulated creator reply for realistic feedback
    simulateCreatorReply(conv);
};

const simulateCreatorReply = (conv: Conversation) => {
    isTyping.value = true;
    setTimeout(() => {
        isTyping.value = false;
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const timeStr = `${hours}:${minutes}`;

        const replies = [
            `Terima kasih sudah mengirim pesan! Senang sekali bisa berdiskusi langsung dengan penonton setia WATOON.`,
            `Catatan bagus! Aku sudah sampaikan ini ke tim kreatif produksi untuk episode berikutnya.`,
            `Pasti! Jangan lupa pantau tab Subscribe untuk behind-the-scenes dan jadwal penayangan terbarunya.`,
            `Sip! Salam hangat untuk sesama penikmat sinema independen berkualitas di WATOON.`,
        ];
        const randomReply = replies[Math.floor(Math.random() * replies.length)];

        conv.messages.push({
            id: `reply-${Date.now()}`,
            sender: 'creator',
            text: randomReply,
            timestamp: timeStr,
        });
        conv.lastMessage = randomReply;
        conv.timeAgo = 'Baru saja';

        // Update user message seen status
        const lastUserMsg = [...conv.messages].reverse().find((m) => m.sender === 'user');
        if (lastUserMsg) {
            lastUserMsg.status = `SEEN ${timeStr}`;
        }

        saveConversationsToStorage();
        scrollToBottom();
    }, 1800);
};

const handleInputKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSendMessage();
    }
};

const triggerFileUpload = () => {
    if (!activeConversation.value) return;
    if (!canChatWithCreator(activeConversation.value.tier)) {
        openUpgradeModal(activeConversation.value.tier);
        triggerToast(`Perlu upgrade ke ${activeConversation.value.tier} untuk melampirkan berkas.`);
        return;
    }
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const onFileSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        const isImg = file.type.startsWith('image/');
        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
        pendingAttachment.value = {
            name: file.name,
            size: `${sizeMb} MB`,
            type: isImg ? 'image' : 'file',
        };
        triggerToast(`Berkas "${file.name}" siap dilampirkan`);
    }
};

const clearPendingAttachment = () => {
    pendingAttachment.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

// Action button handlers with Kasta verification
const handleCallAction = (type: 'voice' | 'video') => {
    if (!activeConversation.value) return;
    const creator = activeConversation.value.name;
    const reqTier = activeConversation.value.tier;

    if (!canChatWithCreator(reqTier)) {
        openUpgradeModal(reqTier);
        triggerToast(`Panggilan dengan sineas ${reqTier} memerlukan kasta ${reqTier} atau lebih tinggi.`);
        return;
    }

    triggerToast(
        type === 'voice'
            ? `Memulai panggilan suara eksklusif dengan ${creator}...`
            : `Menghubungkan ruang video interaktif dengan ${creator}...`,
    );
};

const toggleNotifications = () => {
    if (!activeConversation.value) return;
    activeConversation.value.notificationsEnabled = !activeConversation.value.notificationsEnabled;
    saveConversationsToStorage();
    triggerToast(
        activeConversation.value.notificationsEnabled
            ? `Notifikasi dari ${activeConversation.value.name} diaktifkan`
            : `Notifikasi dari ${activeConversation.value.name} dibisukan`,
    );
    showMoreMenu.value = false;
};

const clearCurrentChat = () => {
    if (!activeConversation.value) return;
    if (confirm(`Hapus seluruh riwayat pesan dengan ${activeConversation.value.name}?`)) {
        activeConversation.value.messages = [];
        activeConversation.value.lastMessage = 'Percakapan dibersihkan.';
        saveConversationsToStorage();
        triggerToast('Riwayat chat berhasil dibersihkan.');
        showMoreMenu.value = false;
    }
};

const closeCurrentConversation = () => {
    activeConversationId.value = null;
    showMoreMenu.value = false;
};

const startConversationWith = (creator: Conversation) => {
    showNewChatModal.value = false;
    selectConversation(creator.id);
    if (!canChatWithCreator(creator.tier)) {
        triggerToast(`Membuka percakapan dengan ${creator.name} (Memerlukan kasta ${creator.tier})`);
    } else {
        triggerToast(`Membuka percakapan dengan ${creator.name}`);
    }
};

const selectMediaType = (type: 'film' | 'novel') => {
    mediaType.value = type;
    if (type === 'novel') {
        triggerToast('Mode Novel: Menampilkan novel serial & catatan penulis');
    }
};
</script>

<template>
    <div
        class="h-screen w-screen overflow-hidden bg-[#0E0F11] font-sans text-[#F2EFEA] antialiased flex flex-col selection:bg-[#1B44E8] selection:text-white overscroll-none"
    >
        <Head title="WATOON — Direct Messages & Dialogues" />

        <!-- Hidden input for file attachment -->
        <input
            ref="fileInputRef"
            type="file"
            class="hidden"
            accept="image/*,.pdf,.doc,.docx,.txt"
            @change="onFileSelected"
        />

        <!-- ========================================================================= -->
        <!-- 1. FULL-WIDTH TOP HEADER (Standard Platform Navbar)                      -->
        <!-- ========================================================================= -->
        <header
            class="shrink-0 h-16 border-b border-[rgba(242,239,234,0.08)] bg-[#0E0F11]/95 backdrop-blur-md px-4 md:px-6 flex items-center justify-between z-30 shadow-[0_2px_12px_rgba(0,0,0,0.4)]"
        >
            <!-- LEFT ZONE: Brand + Standard Nav Links (Home, Favorite, Subscribe) -->
            <div class="flex items-center gap-7">
                <!-- Brand Wordmark with Monoline Film Frame Mark -->
                <Link
                    :href="dashboard.url()"
                    class="group flex items-center gap-2.5 cursor-pointer"
                >
                    <div
                        class="flex h-5 w-5 items-center justify-center rounded-[3px] border border-[#F2EFEA] p-0.5 transition-colors group-hover:border-[#1B44E8]"
                    >
                        <div
                            class="flex h-full w-full items-center justify-center border border-dashed border-[#F2EFEA]/60"
                        >
                            <span class="h-1 w-1 rounded-full bg-[#F2EFEA]"></span>
                        </div>
                    </div>
                    <span class="text-[17px] font-semibold tracking-[-0.03em] text-[#F2EFEA]">
                        WATOON
                    </span>
                </Link>

                <!-- Navigation Links (Standard 3: Home, Favorite, Subscribe) -->
                <nav class="hidden items-center gap-7 text-[13px] font-medium md:flex">
                    <Link
                        :href="dashboard.url()"
                        class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#8C8C90] hover:text-[#F2EFEA] transition-colors"
                    >
                        Home
                    </Link>

                    <Link
                        :href="dashboard.url({ query: { tab: 'favorite' } })"
                        class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#8C8C90] hover:text-[#F2EFEA] transition-colors"
                    >
                        Favorite
                    </Link>

                    <!-- Subscribe is marked active because Messages belongs to Subscribe -->
                    <Link
                        :href="dashboard.url({ query: { tab: 'subscribe' } })"
                        class="relative cursor-pointer py-1 tracking-[-0.01em] text-[#F2EFEA] transition-colors font-medium"
                    >
                        Subscribe
                        <span
                            class="absolute right-0 -bottom-[6px] left-0 h-[2px] bg-[#1B44E8]"
                        ></span>
                    </Link>

                    <!-- Search Bar with ⌘K -->
                    <div
                        class="flex h-[36px] w-[220px] cursor-text items-center gap-2 rounded-[10px] border border-[rgba(242,239,234,0.10)] bg-[rgba(242,239,234,0.05)] px-2.5 transition-all focus-within:!border-[#1B44E8] focus-within:ring-2 focus-within:ring-[rgba(27,68,232,0.14)] hover:border-[rgba(242,239,234,0.18)] hover:bg-[rgba(242,239,234,0.07)]"
                    >
                        <Search class="h-4 w-4 shrink-0 text-[#8C8C90]" :size="16" />
                        <input
                            id="nav-search-input"
                            v-model="navSearchQuery"
                            class="w-full border-0 bg-transparent p-0 text-[13px] font-normal text-[#F2EFEA] placeholder-[#8C8C90] focus:ring-0 focus:outline-none"
                            placeholder="Search films or creators…"
                            type="text"
                            @keydown.enter="router.visit(dashboard.url({ query: { q: navSearchQuery } }))"
                        />
                        <kbd class="shrink-0 text-[11px] font-medium text-[#6A6A6E] select-none">
                            ⌘K
                        </kbd>
                    </div>
                </nav>
            </div>

            <!-- RIGHT ZONE: Media Toggle + Dynamic User Tier Badge + User Avatar -->
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
                        <span class="h-1 w-1 rounded-full bg-[#1B44E8]"></span>
                    </button>
                </div>

                <!-- Dynamic User Kasta Badge Chip (FREE / PRO / VIP) -> Navigate to Pricing -->
                <Link
                    :href="pricing.url()"
                    class="mr-4 flex h-[26px] cursor-pointer items-center justify-center gap-1.5 rounded-[8px] border px-2.5 text-[11px] font-semibold tracking-[0.12em] uppercase transition-all select-none"
                    :class="[
                        currentUserTier === 'VIP'
                            ? 'border-[#D4B67A]/50 bg-[#D4B67A]/15 text-[#D4B67A] hover:bg-[#D4B67A]/25 shadow-[0_0_12px_rgba(212,182,122,0.15)]'
                            : currentUserTier === 'PRO'
                            ? 'border-[rgba(27,68,232,0.35)] bg-[rgba(27,68,232,0.14)] text-[#7A9BFF] hover:bg-[rgba(27,68,232,0.22)] shadow-[0_0_12px_rgba(27,68,232,0.15)]'
                            : 'border-[rgba(242,239,234,0.12)] bg-[rgba(242,239,234,0.06)] text-[#8C8C90] hover:bg-[rgba(242,239,234,0.10)] hover:text-[#F2EFEA]'
                    ]"
                    :title="`Kasta Anda saat ini: ${currentUserTier}. Klik untuk buka halaman paket langganan.`"
                >
                    <Crown v-if="currentUserTier === 'VIP'" class="w-3 h-3 text-[#D4B67A]" />
                    <Sparkles v-else-if="currentUserTier === 'PRO'" class="w-3 h-3 text-[#7A9BFF]" />
                    <span v-else class="w-1.5 h-1.5 rounded-full bg-[#8C8C90]"></span>
                    <span>{{ currentUserTier }}</span>
                </Link>

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
                        <div class="border-b border-[rgba(242,239,234,0.08)] px-3 py-2.5">
                            <p class="truncate text-sm font-medium text-white">
                                {{ currentUser?.name }}
                            </p>
                            <p class="truncate text-xs text-[#8C8C90]">
                                {{ currentUser?.email }}
                            </p>
                            <!-- Current Tier Chip in Dropdown -->
                            <div class="mt-2 flex items-center justify-between">
                                <div
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                    :class="[
                                        currentUserTier === 'VIP'
                                            ? 'border-[#D4B67A]/40 bg-[#D4B67A]/15 text-[#D4B67A]'
                                            : currentUserTier === 'PRO'
                                            ? 'border-[rgba(27,68,232,0.4)] bg-[rgba(27,68,232,0.12)] text-[#7A9BFF]'
                                            : 'border-[rgba(242,239,234,0.15)] bg-[rgba(242,239,234,0.06)] text-[#8C8C90]'
                                    ]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="[
                                            currentUserTier === 'VIP'
                                                ? 'bg-[#D4B67A]'
                                                : currentUserTier === 'PRO'
                                                ? 'bg-[#1B44E8]'
                                                : 'bg-[#8C8C90]'
                                        ]"
                                    ></span>
                                    <span>Kasta {{ currentUserTier }}</span>
                                </div>
                                <Link
                                    :href="pricing.url()"
                                    class="text-[10px] text-[#7A9BFF] hover:underline cursor-pointer"
                                >
                                    Kelola
                                </Link>
                            </div>
                        </div>

                        <div class="space-y-0.5 py-1">
                            <Link
                                :href="edit.url()"
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06]"
                            >
                                <Settings class="h-3.5 w-3.5 text-[#8C8C90]" />
                                <span>Pengaturan Akun</span>
                            </Link>

                            <Link
                                :href="pricing.url()"
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] transition-colors hover:bg-white/[0.06] cursor-pointer"
                            >
                                <Crown class="h-3.5 w-3.5 text-[#7A9BFF]" />
                                <span>Paket Langganan & Kasta</span>
                            </Link>
                        </div>

                        <div class="border-t border-[rgba(242,239,234,0.08)] pt-1">
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
        </header>

        <!-- ========================================================================= -->
        <!-- 2. FULLSCREEN CHAT WORKSPACE (LAYAR PENUH TANPA KEPOTONG)                -->
        <!-- ========================================================================= -->
        <main class="flex-1 w-full min-h-0 flex overflow-hidden bg-[#17181B] relative">
            <!-- ===================================================================== -->
            <!-- COLUMN A: SIDEBAR CONVERSATION LIST                                   -->
            <!-- ===================================================================== -->
            <aside
                class="h-full flex flex-col min-h-0 border-r border-[rgba(242,239,234,0.08)] bg-[#0E0F11]/70 shrink-0 transition-all duration-200"
                :class="[
                    activeConversationId
                        ? 'hidden md:flex md:w-[350px] lg:w-[380px]'
                        : 'w-full md:w-[350px] lg:w-[380px] flex',
                ]"
            >
                <!-- Sidebar Header: Title + Compose Button -->
                <div class="h-16 px-5 border-b border-[rgba(242,239,234,0.08)] flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-[20px] font-semibold text-[#F2EFEA] tracking-tight">
                            Messages
                        </h1>
                        <span
                            class="px-2 py-0.5 rounded-[5px] bg-[rgba(242,239,234,0.08)] text-[#8C8C90] text-[11px] font-mono font-medium"
                        >
                            {{ conversations.length }}
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="showNewChatModal = true"
                        class="w-9 h-9 rounded-[8px] border border-[rgba(242,239,234,0.08)] hover:bg-[rgba(242,239,234,0.06)] hover:border-[#F2EFEA]/30 flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] transition-all cursor-pointer"
                        title="New dialogue with creator"
                    >
                        <SquarePen class="h-4 w-4" />
                    </button>
                </div>

                <!-- Search Input Bar -->
                <div class="p-3 border-b border-[rgba(242,239,234,0.06)] shrink-0">
                    <div class="relative flex items-center w-full">
                        <Search
                            class="absolute left-3 text-[#8C8C90] pointer-events-none"
                            :size="15"
                        />
                        <input
                            v-model="conversationSearch"
                            class="w-full h-9 rounded-[10px] bg-[rgba(242,239,234,0.05)] border border-[rgba(242,239,234,0.08)] pl-9 pr-8 text-[13px] text-[#F2EFEA] placeholder-[#8C8C90]/70 focus:outline-none focus:border-[#1B44E8] focus:ring-1 focus:ring-[#1B44E8]/30 transition-all"
                            placeholder="Search conversations…"
                            type="text"
                        />
                        <button
                            v-if="conversationSearch"
                            type="button"
                            @click="conversationSearch = ''"
                            class="absolute right-2.5 text-[#8C8C90] hover:text-white"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Filter Tabs (All / Unread) -->
                <div class="px-5 pt-3 pb-0 flex items-center gap-5 border-b border-[rgba(242,239,234,0.06)] shrink-0">
                    <button
                        type="button"
                        @click="activeTab = 'all'"
                        class="relative pb-2.5 text-[13px] font-medium transition-colors cursor-pointer"
                        :class="activeTab === 'all' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        All
                        <span
                            v-if="activeTab === 'all'"
                            class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"
                        ></span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'unread'"
                        class="relative pb-2.5 text-[13px] font-medium transition-colors cursor-pointer flex items-center gap-1.5"
                        :class="activeTab === 'unread' ? 'text-[#F2EFEA]' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                    >
                        <span>Unread</span>
                        <span
                            v-if="unreadCount > 0"
                            class="px-1.5 py-0.5 rounded-full bg-[#1B44E8] text-white text-[10px] font-bold leading-none"
                        >
                            {{ unreadCount }}
                        </span>
                        <span
                            v-if="activeTab === 'unread'"
                            class="absolute bottom-0 left-0 right-0 h-[2px] bg-[#1B44E8]"
                        ></span>
                    </button>
                </div>

                <!-- Conversation Scrollable Rows -->
                <div class="flex-1 overflow-y-auto min-h-0 divide-y divide-[rgba(242,239,234,0.06)] custom-scroll">
                    <article
                        v-for="conv in filteredConversations"
                        :key="conv.id"
                        @click="selectConversation(conv.id)"
                        class="h-[76px] px-4 flex items-center gap-3.5 relative cursor-pointer group transition-colors select-none"
                        :class="[
                            activeConversationId === conv.id
                                ? 'bg-[rgba(242,239,234,0.06)]'
                                : 'hover:bg-[rgba(242,239,234,0.025)]',
                        ]"
                    >
                        <!-- 2.5px Cobalt Indicator Bar on Left Edge -->
                        <span
                            v-if="activeConversationId === conv.id"
                            class="absolute left-0 top-0 bottom-0 w-[2.5px] bg-[#1B44E8]"
                        ></span>

                        <!-- Avatar with Tier Badge Chip -->
                        <div class="relative shrink-0">
                            <div
                                class="w-11 h-11 rounded-full p-[2px] flex items-center justify-center transition-transform group-hover:scale-[1.02]"
                                :class="[
                                    conv.tier === 'VIP'
                                        ? 'bg-gradient-to-br from-[#2c271b] to-[#1E1F23] ring-1 ring-[#D4B67A]/70'
                                        : conv.tier === 'PRO'
                                        ? 'bg-[#1E1F23] ring-1 ring-[#7A9BFF]/60'
                                        : 'bg-[#1E1F23] ring-1 ring-[#8C8C90]/40',
                                ]"
                            >
                                <div
                                    class="w-full h-full rounded-full bg-[#17181B] flex items-center justify-center text-[13px] font-semibold"
                                    :class="[
                                        conv.tier === 'VIP'
                                            ? 'text-[#D4B67A]'
                                            : conv.tier === 'PRO'
                                            ? 'text-[#7A9BFF]'
                                            : 'text-[#8C8C90]',
                                    ]"
                                >
                                    {{ conv.initials }}
                                </div>
                            </div>

                            <!-- Tier chip -->
                            <span
                                class="absolute -bottom-0.5 -right-0.5 px-1 py-[1px] rounded-[3px] bg-[#0E0F11] text-[8px] font-mono font-bold leading-none border"
                                :class="[
                                    conv.tier === 'VIP'
                                        ? 'border-[#D4B67A] text-[#D4B67A]'
                                        : conv.tier === 'PRO'
                                        ? 'border-[#7A9BFF] text-[#7A9BFF]'
                                        : 'border-[#8C8C90] text-[#8C8C90]',
                                ]"
                            >
                                {{ conv.tier }}
                            </span>
                        </div>

                        <!-- Content Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span
                                        class="text-[13px] font-semibold text-[#F2EFEA] truncate"
                                        :class="{ 'text-white font-bold': conv.unread }"
                                    >
                                        {{ conv.name }}
                                    </span>
                                    <!-- Verified badge -->
                                    <span
                                        v-if="conv.isVerified"
                                        class="inline-flex items-center justify-center text-[#1B44E8] shrink-0"
                                        title="Verified Creator"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 fill-[#1B44E8]"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                                            />
                                        </svg>
                                    </span>

                                    <!-- Kasta Lock Indicator if Creator tier is above user tier -->
                                    <span
                                        v-if="!canChatWithCreator(conv.tier)"
                                        class="inline-flex items-center gap-0.5 text-[9px] font-mono px-1 py-0.2 rounded border bg-[rgba(242,239,234,0.04)] text-[#8C8C90] border-[rgba(242,239,234,0.10)] shrink-0"
                                        :title="`Perlu kasta ${conv.tier} untuk chat`"
                                    >
                                        <Lock class="w-2.5 h-2.5" />
                                        <span>{{ conv.tier }}</span>
                                    </span>
                                </div>
                                <time class="font-mono text-[11px] text-[#8C8C90] whitespace-nowrap ml-2">
                                    {{ conv.timeAgo }}
                                </time>
                            </div>

                            <div class="flex items-center justify-between">
                                <p
                                    class="text-[12px] truncate leading-snug"
                                    :class="[
                                        conv.unread
                                            ? 'text-[#F2EFEA] font-medium'
                                            : 'text-[#8C8C90]',
                                    ]"
                                >
                                    {{ conv.lastMessage }}
                                </p>
                                <!-- 8px cobalt unread dot -->
                                <span
                                    v-if="conv.unread"
                                    class="w-2 h-2 rounded-full bg-[#1B44E8] ml-2 shrink-0 animate-pulse"
                                ></span>
                            </div>
                        </div>
                    </article>

                    <!-- Empty state when no filtered conversations -->
                    <div
                        v-if="filteredConversations.length === 0"
                        class="p-8 text-center flex flex-col items-center justify-center text-[#8C8C90]"
                    >
                        <Search class="h-8 w-8 text-[#8C8C90]/40 mb-2" />
                        <p class="text-xs font-medium text-[#F2EFEA]">
                            Tidak ada percakapan ditemukan
                        </p>
                        <p class="text-[11px] mt-1 text-[#8C8C90]">
                            Coba ubah kata kunci pencarian atau tab filter.
                        </p>
                    </div>
                </div>
            </aside>

            <!-- ===================================================================== -->
            <!-- COLUMN B: RIGHT CHAT THREAD AREA                                      -->
            <!-- ===================================================================== -->
            <section
                v-if="activeConversation"
                class="flex-1 h-full min-h-0 flex flex-col bg-[#17181B] relative"
            >
                <!-- Chat Header (72px tall, padding 0 24px) -->
                <div
                    class="h-16 md:h-[72px] px-4 md:px-6 border-b border-[rgba(242,239,234,0.08)] bg-[#17181B] flex items-center justify-between shrink-0"
                >
                    <div class="flex items-center gap-3">
                        <!-- Mobile Back Button -->
                        <button
                            type="button"
                            @click="activeConversationId = null"
                            class="md:hidden w-8 h-8 rounded-lg flex items-center justify-center text-[#8C8C90] hover:text-white hover:bg-[rgba(242,239,234,0.06)] cursor-pointer"
                            title="Kembali ke daftar pesan"
                        >
                            <ArrowLeft class="h-4 w-4" />
                        </button>

                        <!-- Circular Avatar with Tier Badge -->
                        <div class="relative shrink-0">
                            <div
                                class="w-10 h-10 rounded-full p-[2px] flex items-center justify-center"
                                :class="[
                                    activeConversation.tier === 'VIP'
                                        ? 'bg-gradient-to-br from-[#2c271b] to-[#1E1F23] ring-1 ring-[#D4B67A]/80'
                                        : activeConversation.tier === 'PRO'
                                        ? 'bg-[#1E1F23] ring-1 ring-[#7A9BFF]/80'
                                        : 'bg-[#1E1F23] ring-1 ring-[#8C8C90]/60',
                                ]"
                            >
                                <div
                                    class="w-full h-full rounded-full bg-[#0E0F11] flex items-center justify-center text-[13px] font-semibold"
                                    :class="[
                                        activeConversation.tier === 'VIP'
                                            ? 'text-[#D4B67A]'
                                            : activeConversation.tier === 'PRO'
                                            ? 'text-[#7A9BFF]'
                                            : 'text-[#8C8C90]',
                                    ]"
                                >
                                    {{ activeConversation.initials }}
                                </div>
                            </div>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 px-1 py-[1px] rounded-[3px] bg-[#0E0F11] text-[7.5px] font-mono font-bold leading-none border"
                                :class="[
                                    activeConversation.tier === 'VIP'
                                        ? 'border-[#D4B67A] text-[#D4B67A]'
                                        : activeConversation.tier === 'PRO'
                                        ? 'border-[#7A9BFF] text-[#7A9BFF]'
                                        : 'border-[#8C8C90] text-[#8C8C90]',
                                ]"
                            >
                                {{ activeConversation.tier }}
                            </span>
                        </div>

                        <!-- Name & Status -->
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h2 class="text-[15px] font-semibold text-[#F2EFEA]">
                                    {{ activeConversation.name }}
                                </h2>
                                <span
                                    v-if="activeConversation.isVerified"
                                    class="text-[#1B44E8]"
                                    title="Verified Creator"
                                >
                                    <svg
                                        class="w-3.5 h-3.5 fill-[#1B44E8]"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                                        />
                                    </svg>
                                </span>

                                <!-- Lock Chip in Header if above user's tier -->
                                <button
                                    v-if="!canChatWithCreator(activeConversation.tier)"
                                    type="button"
                                    @click="openUpgradeModal(activeConversation.tier)"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium border transition-colors cursor-pointer"
                                    :class="[
                                        activeConversation.tier === 'VIP'
                                            ? 'bg-[#D4B67A]/15 border-[#D4B67A]/40 text-[#D4B67A] hover:bg-[#D4B67A]/25'
                                            : 'bg-[#7A9BFF]/15 border-[#7A9BFF]/40 text-[#7A9BFF] hover:bg-[#7A9BFF]/25'
                                    ]"
                                    :title="`Klik untuk upgrade ke ${activeConversation.tier}`"
                                >
                                    <Lock class="w-3 h-3" />
                                    <span>Butuh {{ activeConversation.tier }}</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px] font-mono">
                                <template v-if="activeConversation.isOnline">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#7A9BFF] animate-pulse"></span>
                                    <span class="text-[#7A9BFF]">Online</span>
                                </template>
                                <template v-else>
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#8C8C90]/60"></span>
                                    <span class="text-[#8C8C90]">Aktif {{ activeConversation.lastSeen || 'beberapa waktu lalu' }}</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Header Right Actions: Voice, Video, Search in chat, More -->
                    <div class="flex items-center gap-1.5 md:gap-2 relative">
                        <!-- In-chat search box toggle -->
                        <div
                            v-if="isSearchingInChat"
                            class="hidden sm:flex items-center gap-1.5 bg-[#0E0F11] border border-[rgba(242,239,234,0.12)] rounded-[8px] px-2 py-1"
                        >
                            <input
                                v-model="chatSearchQuery"
                                class="w-32 md:w-44 bg-transparent border-0 text-xs text-white placeholder-[#8C8C90] p-0 focus:ring-0"
                                placeholder="Cari dalam chat..."
                                type="text"
                                autofocus
                            />
                            <button
                                type="button"
                                @click="isSearchingInChat = false; chatSearchQuery = ''"
                                class="text-[#8C8C90] hover:text-white"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="handleCallAction('voice')"
                            class="w-9 h-9 rounded-[8px] border border-[rgba(242,239,234,0.06)] hover:bg-[rgba(242,239,234,0.05)] hover:border-[#F2EFEA]/20 flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] transition-all cursor-pointer"
                            :title="canChatWithCreator(activeConversation.tier) ? 'Panggilan Suara' : `Perlu ${activeConversation.tier}`"
                        >
                            <Phone class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="handleCallAction('video')"
                            class="w-9 h-9 rounded-[8px] border border-[rgba(242,239,234,0.06)] hover:bg-[rgba(242,239,234,0.05)] hover:border-[#F2EFEA]/20 flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] transition-all cursor-pointer"
                            :title="canChatWithCreator(activeConversation.tier) ? 'Panggilan Video' : `Perlu ${activeConversation.tier}`"
                        >
                            <Video class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            @click="isSearchingInChat = !isSearchingInChat"
                            class="w-9 h-9 rounded-[8px] border border-[rgba(242,239,234,0.06)] hover:bg-[rgba(242,239,234,0.05)] hover:border-[#F2EFEA]/20 flex items-center justify-center transition-all cursor-pointer"
                            :class="isSearchingInChat ? 'text-[#1B44E8] bg-[rgba(27,68,232,0.12)] border-[#1B44E8]/40' : 'text-[#8C8C90] hover:text-[#F2EFEA]'"
                            title="Cari dalam obrolan"
                        >
                            <Search class="h-4 w-4" />
                        </button>

                        <!-- More menu toggle -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="showMoreMenu = !showMoreMenu"
                                class="w-9 h-9 rounded-[8px] border border-[rgba(242,239,234,0.06)] hover:bg-[rgba(242,239,234,0.05)] hover:border-[#F2EFEA]/20 flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] transition-all cursor-pointer"
                                title="Pilihan lainnya"
                            >
                                <MoreVertical class="h-4 w-4" />
                            </button>

                            <!-- Dropdown popup -->
                            <div
                                v-if="showMoreMenu"
                                class="absolute right-0 top-11 z-50 w-56 rounded-xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] p-1.5 shadow-2xl backdrop-blur-xl"
                            >
                                <Link
                                    :href="dashboard.url({ query: { tab: 'subscribe' } })"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] hover:bg-white/[0.06] transition-colors"
                                >
                                    <Info class="h-3.5 w-3.5 text-[#8C8C90]" />
                                    <span>Lihat Profil Kreator</span>
                                </Link>

                                <button
                                    type="button"
                                    @click="toggleNotifications"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#F2EFEA] hover:bg-white/[0.06] transition-colors cursor-pointer text-left"
                                >
                                    <VolumeX
                                        v-if="activeConversation.notificationsEnabled"
                                        class="h-3.5 w-3.5 text-[#8C8C90]"
                                    />
                                    <Volume2
                                        v-else
                                        class="h-3.5 w-3.5 text-[#1B44E8]"
                                    />
                                    <span>
                                        {{
                                            activeConversation.notificationsEnabled
                                                ? 'Bisukan Notifikasi'
                                                : 'Bunyikan Notifikasi'
                                        }}
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    @click="clearCurrentChat"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-amber-400 hover:bg-amber-400/10 transition-colors cursor-pointer text-left"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    <span>Bersihkan Riwayat Chat</span>
                                </button>

                                <div class="border-t border-[rgba(242,239,234,0.08)] my-1"></div>

                                <button
                                    type="button"
                                    @click="closeCurrentConversation"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-[#8C8C90] hover:text-white hover:bg-white/[0.06] transition-colors cursor-pointer text-left"
                                >
                                    <X class="h-3.5 w-3.5" />
                                    <span>Tutup Obrolan Ini</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Stream Area (Scrollable Message Thread) -->
                <div
                    ref="messagesContainerRef"
                    class="flex-1 p-4 md:p-6 overflow-y-auto min-h-0 space-y-4 custom-scroll"
                >
                    <!-- Date Divider -->
                    <div class="flex items-center gap-4 py-2">
                        <div class="flex-1 h-[1px] bg-[rgba(242,239,234,0.08)]"></div>
                        <span class="text-[#8C8C90]/70 font-mono tracking-[0.14em] uppercase text-[11px] font-medium">
                            TODAY
                        </span>
                        <div class="flex-1 h-[1px] bg-[rgba(242,239,234,0.08)]"></div>
                    </div>

                    <!-- Search results notice if search active -->
                    <div
                        v-if="isSearchingInChat && chatSearchQuery.trim()"
                        class="px-3 py-1.5 rounded-lg bg-[rgba(27,68,232,0.12)] border border-[#1B44E8]/30 text-center text-xs text-[#7A9BFF]"
                    >
                        Menampilkan hasil untuk: "{{ chatSearchQuery }}" ({{ activeMessages.length }} pesan)
                    </div>

                    <!-- Render Messages -->
                    <div
                        v-for="msg in activeMessages"
                        :key="msg.id"
                        class="flex flex-col max-w-[85%] md:max-w-[65%]"
                        :class="[msg.sender === 'user' ? 'items-end ml-auto' : 'items-start mr-auto']"
                    >
                        <!-- Message Bubble -->
                        <div
                            class="px-4 py-3 rounded-[14px] shadow-sm relative transition-all"
                            :class="[
                                msg.sender === 'user'
                                    ? 'bg-[rgba(27,68,232,0.18)] border border-[rgba(27,68,232,0.35)] rounded-br-[4px] text-[#F2EFEA]'
                                    : 'bg-[#1E1F23] border border-[rgba(242,239,234,0.08)] rounded-bl-[4px] text-[#F2EFEA]',
                            ]"
                        >
                            <!-- File attachment preview if present -->
                            <div
                                v-if="msg.attachment"
                                class="mb-2 p-2 rounded-lg bg-black/30 border border-white/10 flex items-center gap-2.5"
                            >
                                <Paperclip class="h-4 w-4 text-[#7A9BFF] shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-white truncate">
                                        {{ msg.attachment.name }}
                                    </p>
                                    <p class="text-[10px] text-[#8C8C90]">
                                        {{ msg.attachment.size }}
                                    </p>
                                </div>
                            </div>

                            <p class="text-[14px] leading-[1.55] whitespace-pre-line">
                                {{ msg.text }}
                            </p>
                        </div>

                        <!-- Meta Timestamp & Status -->
                        <div
                            class="mt-1 flex items-center gap-1.5 text-[10px] font-mono text-[#8C8C90]/70"
                            :class="[msg.sender === 'user' ? 'pr-1' : 'pl-1']"
                        >
                            <span>{{ msg.timestamp }}</span>
                            <template v-if="msg.sender === 'user'">
                                <span>·</span>
                                <span
                                    v-if="msg.status?.includes('SEEN')"
                                    class="text-[#7A9BFF] font-medium flex items-center gap-0.5 tracking-wider uppercase"
                                >
                                    <CheckCheck class="h-3 w-3 inline" />
                                    <span>{{ msg.status }}</span>
                                </span>
                                <span v-else class="text-[#8C8C90] flex items-center gap-0.5 tracking-wider uppercase">
                                    <Check class="h-3 w-3 inline" />
                                    <span>DELIVERED</span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Typing indicator animation -->
                    <div
                        v-if="isTyping"
                        class="flex flex-col items-start mr-auto max-w-[60%]"
                    >
                        <div
                            class="bg-[#1E1F23] border border-[rgba(242,239,234,0.08)] px-4 py-3 rounded-[14px] rounded-bl-[4px] shadow-sm flex items-center gap-1.5"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8C8C90] animate-bounce"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8C8C90] animate-bounce [animation-delay:0.2s]"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8C8C90] animate-bounce [animation-delay:0.4s]"></span>
                        </div>
                        <span class="text-[10px] font-mono text-[#8C8C90]/60 mt-1 pl-1">
                            {{ activeConversation.name }} sedang mengetik…
                        </span>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- BOTTOM CHAT AREA: DOCKED INPUT OR KASTA UPGRADE CALLOUT       -->
                <!-- ============================================================= -->

                <!-- [CASE 1]: KASTA RESTRICTED (Creator tier > User tier) -->
                <div
                    v-if="!canChatWithCreator(activeConversation.tier)"
                    class="p-4 md:p-5 border-t border-[rgba(242,239,234,0.08)] bg-[#17181B] shrink-0"
                >
                    <div
                        class="rounded-[14px] p-4 md:p-5 border relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl"
                        :class="[
                            activeConversation.tier === 'VIP'
                                ? 'bg-gradient-to-r from-[#17181B] via-[#242017] to-[#17181B] border-[#D4B67A]/35 shadow-[#D4B67A]/5'
                                : 'bg-gradient-to-r from-[#17181B] via-[#141b33] to-[#17181B] border-[#7A9BFF]/35 shadow-[#1B44E8]/5'
                        ]"
                    >
                        <div class="flex items-start gap-3.5 max-w-xl">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                                :class="[
                                    activeConversation.tier === 'VIP'
                                        ? 'bg-[#D4B67A]/15 border-[#D4B67A]/40 text-[#D4B67A]'
                                        : 'bg-[#7A9BFF]/15 border-[#7A9BFF]/40 text-[#7A9BFF]'
                                ]"
                            >
                                <Lock class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-semibold text-[#F2EFEA]">
                                        Akses Chat Khusus Member {{ activeConversation.tier }}
                                    </h4>
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold uppercase"
                                        :class="[
                                            activeConversation.tier === 'VIP'
                                                ? 'bg-[#D4B67A]/20 text-[#D4B67A] border border-[#D4B67A]/40'
                                                : 'bg-[#7A9BFF]/20 text-[#7A9BFF] border border-[#7A9BFF]/40'
                                        ]"
                                    >
                                        {{ activeConversation.tier }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#8C8C90] mt-1 leading-relaxed">
                                    Sineas ini memiliki lencana <strong class="text-white">{{ activeConversation.tier }}</strong>, sedangkan kasta Anda saat ini adalah <strong class="text-[#F2EFEA] underline">{{ currentUserTier }}</strong>. Tingkatkan akun Anda ke <strong class="text-white">{{ activeConversation.tier }}</strong> atau lebih tinggi untuk dapat mengirim pesan dan berdiskusi langsung dengan {{ activeConversation.name }}.
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="pricing.url({ query: { highlight: activeConversation.tier.toLowerCase() } })"
                            class="w-full md:w-auto px-5 py-2.5 rounded-xl font-medium text-xs flex items-center justify-center gap-2 transition-all cursor-pointer shadow-lg shrink-0"
                            :class="[
                                activeConversation.tier === 'VIP'
                                    ? 'bg-gradient-to-r from-[#D4B67A] to-[#F3DEB0] text-[#0E0F11] font-semibold hover:opacity-95 shadow-[#D4B67A]/20'
                                    : 'bg-[#1B44E8] text-white hover:bg-[#1537C2] shadow-[#1B44E8]/30'
                            ]"
                        >
                            <Crown v-if="activeConversation.tier === 'VIP'" class="w-4 h-4" />
                            <Sparkles v-else class="w-4 h-4" />
                            <span>Upgrade ke {{ activeConversation.tier }}</span>
                        </Link>
                    </div>
                </div>

                <!-- [CASE 2]: UNLOCKED INPUT BAR (User tier >= Creator tier) -->
                <div
                    v-else
                    class="p-3 md:p-4 border-t border-[rgba(242,239,234,0.08)] bg-[#17181B] shrink-0"
                >
                    <!-- Attachment badge if present -->
                    <div
                        v-if="pendingAttachment"
                        class="mb-2 max-w-fit px-3 py-1.5 rounded-lg bg-[rgba(27,68,232,0.15)] border border-[#1B44E8]/40 flex items-center gap-2 text-xs text-[#7A9BFF]"
                    >
                        <Paperclip class="h-3.5 w-3.5" />
                        <span class="truncate font-medium">{{ pendingAttachment.name }} ({{ pendingAttachment.size }})</span>
                        <button
                            type="button"
                            @click="clearPendingAttachment"
                            class="text-[#8C8C90] hover:text-white ml-1 cursor-pointer"
                        >
                            <X class="h-3 w-3" />
                        </button>
                    </div>

                    <div
                        class="flex items-center gap-2 md:gap-3 bg-[#0E0F11]/70 border border-[rgba(242,239,234,0.08)] focus-within:border-[#1B44E8]/60 focus-within:ring-1 focus-within:ring-[#1B44E8]/30 rounded-[12px] px-2.5 py-1.5 md:px-3 md:py-2 transition-all"
                    >
                        <!-- Monoline Paperclip Attach Button -->
                        <button
                            type="button"
                            @click="triggerFileUpload"
                            class="w-8 h-8 md:w-9 md:h-9 rounded-[8px] flex items-center justify-center text-[#8C8C90] hover:text-[#F2EFEA] hover:bg-white/[0.05] transition-colors cursor-pointer shrink-0"
                            title="Lampirkan foto, naskah, atau berkas"
                        >
                            <Paperclip class="h-4 w-4 md:h-5 md:w-5" />
                        </button>

                        <!-- Textarea for message -->
                        <textarea
                            ref="messageInputRef"
                            v-model="messageInput"
                            @keydown="handleInputKeydown"
                            class="flex-1 bg-transparent border-0 text-[#F2EFEA] placeholder-[#8C8C90]/60 text-[14px] focus:ring-0 resize-none p-1 md:p-1.5 leading-normal max-h-28 min-h-[38px]"
                            placeholder="Write a message…"
                            rows="1"
                        ></textarea>

                        <!-- Send Button (Cobalt circle with arrow up) -->
                        <button
                            type="button"
                            @click="handleSendMessage"
                            :disabled="!messageInput.trim() && !pendingAttachment"
                            class="w-8 h-8 md:w-9 md:h-9 rounded-full bg-[#1B44E8] hover:bg-[#1537C2] disabled:opacity-40 disabled:hover:bg-[#1B44E8] disabled:cursor-not-allowed text-white flex items-center justify-center shadow-[0_2px_8px_rgba(27,68,232,0.4)] transition-all cursor-pointer shrink-0"
                            title="Kirim pesan"
                        >
                            <ArrowUp class="h-4 w-4 md:h-4.5 md:w-4.5 font-bold" />
                        </button>
                    </div>

                    <!-- Shortcut Hint -->
                    <div class="mt-2 text-center hidden sm:block">
                        <span class="text-[11px] font-mono text-[#8C8C90]/50">
                            Press Enter to send · Shift+Enter for a new line
                        </span>
                    </div>
                </div>
            </section>

            <!-- ===================================================================== -->
            <!-- COLUMN B (EMPTY STATE): NULL SELECTION SURFACE                        -->
            <!-- ===================================================================== -->
            <section
                v-else
                class="hidden md:flex flex-1 h-full min-h-0 flex-col items-center justify-center text-center p-8 bg-[#17181B]"
            >
                <div
                    class="w-16 h-16 rounded-[14px] bg-[#0E0F11] border border-[rgba(242,239,234,0.08)] flex items-center justify-center text-[#8C8C90] mb-4 shadow-lg"
                >
                    <SquarePen class="h-7 w-7 text-[#7A9BFF]" />
                </div>
                <h3 class="text-[17px] font-semibold text-[#F2EFEA]">
                    Select a conversation
                </h3>
                <p class="text-[13px] text-[#8C8C90] max-w-sm mt-1.5 leading-relaxed">
                    Choose from existing creator dialogues on the left to review production notes, early script drafts, and behind-the-scenes updates.
                </p>
                <button
                    type="button"
                    @click="showNewChatModal = true"
                    class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1B44E8] text-white text-xs font-semibold shadow-[0_2px_12px_rgba(27,68,232,0.3)] hover:bg-[#1537C2] transition-colors cursor-pointer"
                >
                    <SquarePen class="h-3.5 w-3.5" />
                    <span>Mulai Obrolan Baru</span>
                </button>
            </section>
        </main>

        <!-- ========================================================================= -->
        <!-- 3. NEW CHAT / SELECT CREATOR MODAL                                        -->
        <!-- ========================================================================= -->
        <div
            v-if="showNewChatModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-[rgba(242,239,234,0.12)] bg-[#17181B] p-6 shadow-2xl relative"
            >
                <div class="flex items-center justify-between border-b border-[rgba(242,239,234,0.08)] pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-white">
                            Mulai Obrolan Baru
                        </h3>
                        <p class="text-xs text-[#8C8C90] mt-0.5">
                            Pilih sineas atau kreator untuk berdiskusi
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showNewChatModal = false"
                        class="text-[#8C8C90] hover:text-white cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-4 max-h-80 overflow-y-auto space-y-1 custom-scroll">
                    <button
                        v-for="c in conversations"
                        :key="c.id"
                        type="button"
                        @click="startConversationWith(c)"
                        class="w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-white/[0.05] transition-colors text-left cursor-pointer group"
                    >
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs ring-1"
                            :class="[
                                c.tier === 'VIP'
                                    ? 'bg-[#D4B67A]/20 text-[#D4B67A] ring-[#D4B67A]/40'
                                    : c.tier === 'PRO'
                                    ? 'bg-[#7A9BFF]/20 text-[#7A9BFF] ring-[#7A9BFF]/40'
                                    : 'bg-[#8C8C90]/20 text-[#8C8C90] ring-[#8C8C90]/30',
                            ]"
                        >
                            {{ c.initials }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <p class="text-xs font-semibold text-[#F2EFEA] group-hover:text-white truncate">
                                    {{ c.name }}
                                </p>
                                <span
                                    class="px-1 py-[1px] rounded text-[8px] font-mono font-bold"
                                    :class="[
                                        c.tier === 'VIP'
                                            ? 'bg-[#D4B67A]/20 text-[#D4B67A]'
                                            : c.tier === 'PRO'
                                            ? 'bg-[#7A9BFF]/20 text-[#7A9BFF]'
                                            : 'bg-[#8C8C90]/20 text-[#8C8C90]',
                                    ]"
                                >
                                    {{ c.tier }}
                                </span>

                                <span
                                    v-if="!canChatWithCreator(c.tier)"
                                    class="ml-auto inline-flex items-center gap-1 text-[9px] text-[#8C8C90] bg-white/[0.04] px-1.5 py-0.5 rounded"
                                >
                                    <Lock class="w-2.5 h-2.5" />
                                    <span>Butuh {{ c.tier }}</span>
                                </span>
                            </div>
                            <p class="text-[11px] text-[#8C8C90] truncate">
                                {{ c.bio }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 4. KASTA SYSTEM MANAGEMENT & UPGRADE MODAL                                -->
        <!-- ========================================================================= -->
        <div
            v-if="isKastaModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto"
        >
            <div
                class="w-full max-w-xl rounded-2xl border border-[rgba(242,239,234,0.14)] bg-[#17181B] p-6 shadow-2xl relative my-8"
            >
                <div class="flex items-center justify-between border-b border-[rgba(242,239,234,0.08)] pb-4">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[rgba(27,68,232,0.18)] text-[#7A9BFF]"
                        >
                            <Crown class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-white">
                                Sistem Kasta Penonton WATOON
                            </h3>
                            <p class="text-xs text-[#8C8C90]">
                                Kasta akun Anda saat ini: <span class="font-bold text-[#F2EFEA]">{{ currentUserTier }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isKastaModalOpen = false"
                        class="text-[#8C8C90] hover:text-white cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Hierarchy Info Banner -->
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
                            :disabled="currentUserTier === 'FREE' || isUpgrading"
                            class="mt-4 w-full py-2 rounded-lg text-xs font-medium transition-all cursor-pointer disabled:opacity-40 disabled:cursor-default"
                            :class="[
                                currentUserTier === 'FREE'
                                    ? 'bg-white/10 text-white'
                                    : 'bg-white/[0.07] text-[#8C8C90] hover:text-white hover:bg-white/[0.12]'
                            ]"
                        >
                            {{ currentUserTier === 'FREE' ? 'Kasta Anda Saat Ini' : 'Pilih Free' }}
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
                            :disabled="currentUserTier === 'PRO' || isUpgrading"
                            class="mt-4 w-full py-2 rounded-lg text-xs font-medium transition-all cursor-pointer disabled:opacity-40 disabled:cursor-default"
                            :class="[
                                currentUserTier === 'PRO'
                                    ? 'bg-[#1B44E8] text-white'
                                    : 'bg-[#1B44E8] text-white hover:bg-[#1537C2]'
                            ]"
                        >
                            {{ currentUserTier === 'PRO' ? 'Kasta Anda Saat Ini' : 'Aktifkan Pro' }}
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
                                    <span>Prioritas balasan & behind-the-scenes</span>
                                </li>
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="switchUserTier('VIP')"
                            :disabled="currentUserTier === 'VIP' || isUpgrading"
                            class="mt-4 w-full py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer disabled:opacity-40 disabled:cursor-default"
                            :class="[
                                currentUserTier === 'VIP'
                                    ? 'bg-[#D4B67A] text-[#0E0F11]'
                                    : 'bg-gradient-to-r from-[#D4B67A] to-[#F3DEB0] text-[#0E0F11] hover:opacity-95'
                            ]"
                        >
                            {{ currentUserTier === 'VIP' ? 'Kasta Anda Saat Ini' : 'Aktifkan VIP' }}
                        </button>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <button
                        type="button"
                        @click="isKastaModalOpen = false"
                        class="text-xs text-[#8C8C90] hover:text-white cursor-pointer"
                    >
                        Tutup Jendela Kasta
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 5. FLOATING TOAST FEEDBACK                                               -->
        <!-- ========================================================================= -->
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
                    type="button"
                    @click="showToast = false"
                    class="text-[#8C8C90] hover:text-white cursor-pointer ml-1"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
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
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: rgba(242, 239, 234, 0.12);
    border-radius: 2px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(242, 239, 234, 0.25);
}
</style>
