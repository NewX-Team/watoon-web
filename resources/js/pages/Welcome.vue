<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { dashboard, login, register } from '@/routes';

const billingCycle = ref<'monthly' | 'annual'>('monthly');
const mobileMenuOpen = ref(false);
const waitlistEmail = ref('');
const waitlistSubmitted = ref(false);
const isScrolled = ref(false);

// Interactive Navbar scrollspy
const activeSection = ref('features');

const navItems = [
    { id: 'features', label: 'Film' },
    { id: 'novel', label: 'Novel', badge: 'SOON' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'faq', label: 'FAQ' },
];

const scrollToSection = (sectionId: string, event?: Event) => {
    if (event) event.preventDefault();
    const element = document.getElementById(sectionId);
    if (element) {
        const navOffset = 90;
        const elementPosition =
            element.getBoundingClientRect().top + window.scrollY;
        window.scrollTo({
            top: Math.max(0, elementPosition - navOffset),
            behavior: 'smooth',
        });
        activeSection.value = sectionId;
        if (typeof window !== 'undefined' && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + sectionId);
        }
    }
    mobileMenuOpen.value = false;
};

const scrollToTop = (event?: Event) => {
    if (event) event.preventDefault();
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
    activeSection.value = 'features';
    if (typeof window !== 'undefined' && window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname);
    }
    mobileMenuOpen.value = false;
};

// Thematic FAQ Data & State
const openFaqId = ref<number | null>(1);

interface FaqItem {
    id: number;
    question: string;
    answer: string;
    highlight?: string;
}

const faqs = ref<FaqItem[]>([
    {
        id: 1,
        question: 'What is Watoon?',
        answer: 'Watoon is an integrated cultural platform uniting cinema-grade independent streaming and serialized literature in one calm, subscription-based ecosystem. We combine pristine 4K video masters and distraction-free prose so you can experience deep storytelling in both visual and written forms.',
        highlight: 'Unified Cultural Platform',
    },
    {
        id: 2,
        question: 'Can I use one account for film and novel?',
        answer: 'Yes, absolutely. Your single Watoon identity handles film watchlists, reading bookmarks, private reading notes, and creator discourse channels seamlessly across web, iOS, Android, and TV apps with real-time cloud synchronization.',
        highlight: 'One Unified Account',
    },
    {
        id: 3,
        question: 'How does reading novels work alongside evening cinema?',
        answer: 'Novels are serialized chapter-by-chapter with custom Newsreader serif typography, gentle paper contrast tones, and zero algorithmic interruptions. You can watch an independent film in the evening and seamlessly transition to continuing your current chapter before bed on the same device without losing your rhythm.',
        highlight: 'Distraction-Free Reading',
    },
    {
        id: 4,
        question: "What's the difference between Pro and VIP?",
        answer: 'Pro includes unconstrained 4K HDR master streaming, offline download caching for travel, and verified creator Q&A channels. VIP Patron expands your tier to encompass complete serialized novel rights, direct feedback lines to our editorial team, private rough cut screenings, and physical volume print privileges.',
        highlight: 'Clear Tier Value',
    },
    {
        id: 5,
        question: 'When will novels be available?',
        answer: 'Our inaugural serialized literature slate commences rolling release in Autumn 2025. Pro and VIP members receive immediate enrollment upon catalog debut, while waitlist members receive prioritized early access tokens for preview chapters.',
        highlight: 'Autumn 2025 Release',
    },
    {
        id: 6,
        question: 'Can I download films and chapters for offline access?',
        answer: 'Yes. Pro and VIP members can cache both complete film masters and book chapters to mobile or tablet storage for travel and flights. Offline progress syncs automatically once you reconnect.',
        highlight: 'Offline Travel Mode',
    },
    {
        id: 7,
        question: 'Can I cancel or change plans anytime?',
        answer: 'Without exception. You can pause, adjust, or cancel your subscription with a single click in your account settings; access remains completely active until the end of the billing period with zero cancellation fees.',
        highlight: 'Instant 1-Click Cancellation',
    },
]);

const toggleFaq = (id: number) => {
    openFaqId.value = openFaqId.value === id ? null : id;
};

const handleWaitlist = () => {
    if (waitlistEmail.value.trim()) {
        waitlistSubmitted.value = true;
    }
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 40;

    // Detect active section on scroll
    const scrollPosition = window.scrollY + 220;
    const sectionIds = ['faq', 'pricing', 'novel', 'features'];

    for (const id of sectionIds) {
        const el = document.getElementById(id);
        if (el) {
            const top = el.offsetTop;
            if (scrollPosition >= top) {
                activeSection.value = id;
                return;
            }
        }
    }
    activeSection.value = 'features';
};

let observer: IntersectionObserver | null = null;

const handleInitialHash = () => {
    const rawHash = window.location.hash.replace('#', '');
    if (!rawHash) {
        handleScroll();
        return;
    }

    activeSection.value = rawHash;

    // Smooth scroll down to target section
    setTimeout(() => {
        scrollToSection(rawHash);
    }, 80);

    // Safety micro-correction after images and custom fonts calculate layout
    setTimeout(() => {
        const el = document.getElementById(rawHash);
        if (el) {
            const navOffset = 90;
            const targetPos =
                el.getBoundingClientRect().top + window.scrollY - navOffset;
            if (Math.abs(window.scrollY - targetPos) > 10) {
                window.scrollTo({
                    top: Math.max(0, targetPos),
                    behavior: 'smooth',
                });
            }
        }
    }, 300);
};

const onHashChange = () => {
    const rawHash = window.location.hash.replace('#', '');
    if (rawHash) {
        scrollToSection(rawHash);
    } else {
        scrollToTop();
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('hashchange', onHashChange);
    handleInitialHash();

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                } else {
                    const rect = entry.boundingClientRect;
                    if (rect.top > window.innerHeight || rect.bottom < 0) {
                        entry.target.classList.remove('is-revealed');
                    }
                }
            });
        },
        {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px',
        },
    );

    document.querySelectorAll('.scroll-reveal').forEach((el) => {
        observer?.observe(el);
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('hashchange', onHashChange);
    observer?.disconnect();
});
</script>

<template>
    <div
        class="min-h-screen animate-in overflow-x-hidden bg-[#FAFAF7] font-sans text-[#0E0F11] antialiased duration-300 fade-in selection:bg-[#dee0ff] selection:text-[#00115a]"
    >
        <Head title="Watoon — Films tonight. Chapters before bed.">
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link
                rel="preconnect"
                href="https://fonts.gstatic.com"
                crossorigin=""
            />
            <link
                href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400;1,6..72,500&display=swap"
                rel="stylesheet"
            />
            <link
                href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
                rel="stylesheet"
            />
        </Head>

        <!-- 1. FLOATING INTERACTIVE NAVBAR WITH DYNAMIC SCROLLSPY -->
        <header
            class="fixed inset-x-4 top-[16px] z-50 mx-auto max-w-[1200px] rounded-[16px] transition-all duration-300 md:inset-x-8 md:top-[20px]"
            :class="[
                isScrolled
                    ? 'border border-[#0E0F11]/[0.12] bg-[#FAFAF7]/92 shadow-[0_8px_30px_rgba(0,0,0,0.06)] backdrop-blur-xl'
                    : 'border border-[#0E0F11]/[0.08] bg-[#FAFAF7]/80 shadow-[0_1px_3px_rgba(0,0,0,0.03)] backdrop-blur-md',
            ]"
            id="floating-header"
        >
            <div class="flex h-16 items-center justify-between px-6">
                <!-- Left: WATOON wordmark with monoline film+book icon -->
                <a
                    class="group flex cursor-pointer items-center gap-2.5 text-[#0E0F11] transition-all hover:opacity-85"
                    href="#"
                    @click="scrollToTop($event)"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-black/[0.06] bg-black/[0.04] transition-colors group-hover:border-[#1B44E8]/20 group-hover:bg-[#1B44E8]/10"
                    >
                        <svg
                            class="h-5 w-5 text-[#0E0F11] transition-colors group-hover:text-[#1B44E8]"
                            fill="none"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.75"
                            viewBox="0 0 24 24"
                        >
                            <rect
                                height="18"
                                rx="2.5"
                                ry="2.5"
                                width="18"
                                x="3"
                                y="3"
                            ></rect>
                            <line x1="3" x2="21" y1="8" y2="8"></line>
                            <line x1="3" x2="21" y1="16" y2="16"></line>
                            <line x1="8" x2="8" y1="3" y2="8"></line>
                            <line x1="16" x2="16" y1="3" y2="8"></line>
                            <line x1="8" x2="8" y1="16" y2="21"></line>
                            <line x1="16" x2="16" y1="16" y2="21"></line>
                        </svg>
                    </div>
                    <span
                        class="text-[17px] font-semibold tracking-[-0.01em] text-[#0E0F11]"
                        >WATOON</span
                    >
                </a>

                <!-- Center: Interactive Nav Links with Animated Active Indicator -->
                <nav
                    class="hidden items-center gap-8 text-[14px] font-medium md:flex"
                >
                    <button
                        v-for="item in navItems"
                        :key="item.id"
                        @click="scrollToSection(item.id, $event)"
                        class="relative inline-flex cursor-pointer items-center gap-1.5 py-1 transition-colors"
                        :class="
                            activeSection === item.id
                                ? 'font-semibold text-[#0E0F11]'
                                : 'text-[#6A6A6E] hover:text-[#1B44E8]'
                        "
                    >
                        <span>{{ item.label }}</span>
                        <span
                            v-if="item.badge"
                            class="rounded-[4px] px-1.5 py-0.5 text-[9px] font-semibold tracking-widest uppercase transition-colors duration-200"
                            :class="
                                activeSection === item.id
                                    ? 'bg-[#1B44E8] text-white'
                                    : 'bg-[#1B44E8]/10 text-[#1B44E8]'
                            "
                        >
                            {{ item.badge }}
                        </span>

                        <!-- Smoothly animating underline indicator -->
                        <span
                            class="absolute right-0 -bottom-[6px] left-0 h-[2px] origin-center rounded-full bg-[#1B44E8] transition-all duration-300"
                            :class="
                                activeSection === item.id
                                    ? 'scale-x-100 opacity-100'
                                    : 'scale-x-0 opacity-0'
                            "
                        ></span>
                    </button>
                </nav>

                <!-- Right: Account Actions -->
                <div class="flex items-center gap-4 sm:gap-5">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            class="rounded-[10px] bg-[#0E0F11] px-[18px] py-[9px] text-[14px] font-medium text-[#FAFAF7] shadow-sm transition-all hover:-translate-y-0.5 hover:bg-[#1B44E8] hover:shadow-md active:translate-y-0"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="hidden text-[14px] font-medium text-[#0E0F11] transition-colors hover:text-[#1B44E8] sm:inline-block"
                        >
                            Sign in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-[10px] bg-[#0E0F11] px-[18px] py-[9px] text-[14px] font-medium text-[#FAFAF7] shadow-sm transition-all hover:-translate-y-0.5 hover:bg-[#1B44E8] hover:shadow-md active:translate-y-0"
                        >
                            Get started
                        </Link>
                    </template>

                    <!-- Mobile Menu Button -->
                    <button
                        aria-label="Toggle Menu"
                        class="flex h-9 w-9 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg p-1 text-[#0E0F11] transition-colors hover:bg-black/5 md:hidden"
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <span
                            class="h-[1.8px] w-5 rounded-full bg-[#0E0F11] transition-transform duration-200"
                            :class="{
                                'translate-y-[4px] rotate-45': mobileMenuOpen,
                            }"
                        ></span>
                        <span
                            class="h-[1.8px] w-5 rounded-full bg-[#0E0F11] transition-transform duration-200"
                            :class="{
                                '-translate-y-[4px] -rotate-45': mobileMenuOpen,
                            }"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- Mobile Dropdown Nav with Active Indicator -->
            <div
                v-if="mobileMenuOpen"
                class="flex animate-in flex-col gap-3 border-t border-black/[0.06] px-6 pt-2 pb-6 duration-200 fade-in slide-in-from-top-2 md:hidden"
            >
                <button
                    v-for="item in navItems"
                    :key="item.id"
                    @click="scrollToSection(item.id, $event)"
                    class="flex items-center justify-between py-1.5 text-left text-[14px] font-medium transition-colors"
                    :class="
                        activeSection === item.id
                            ? 'border-l-2 border-[#1B44E8] pl-2 font-semibold text-[#1B44E8]'
                            : 'text-[#6A6A6E]'
                    "
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.badge"
                        class="rounded-[3px] bg-[#1B44E8]/10 px-1.5 py-0.5 text-[9px] font-semibold tracking-widest text-[#1B44E8] uppercase"
                    >
                        {{ item.badge }}
                    </span>
                </button>
                <div
                    class="flex items-center justify-between border-t border-black/[0.06] pt-3"
                    v-if="!$page.props.auth.user"
                >
                    <Link
                        :href="login()"
                        class="text-[14px] font-medium text-[#0E0F11]"
                        >Sign in</Link
                    >
                    <Link
                        :href="register()"
                        class="text-[14px] font-medium text-[#1B44E8]"
                        >Register →</Link
                    >
                </div>
            </div>
        </header>

        <main>
            <!-- 2. HERO SECTION -->
            <section
                class="mx-auto max-w-[1200px] px-6 pt-36 pb-16 md:px-8 md:pt-44"
            >
                <div
                    class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-10"
                >
                    <!-- Left Column (7 cols) -->
                    <div
                        class="scroll-reveal flex flex-col justify-center lg:col-span-7"
                    >
                        <div class="mb-5 inline-flex items-center gap-2">
                            <span
                                class="rounded-[6px] border border-black/[0.08] bg-black/[0.03] px-3 py-1.5 text-xs font-medium tracking-[0.14em] text-[#6A6A6E] uppercase backdrop-blur-sm"
                            >
                                STREAM · READ · ONE PLACE
                            </span>
                        </div>
                        <h1
                            class="mb-6 text-[44px] leading-[1.06] font-semibold tracking-[-0.03em] text-[#0E0F11] sm:text-[54px] lg:text-[62px]"
                        >
                            Films tonight.<br />
                            <span
                                class="font-serif font-normal text-[#1B44E8] italic"
                                >Chapters</span
                            >
                            before bed.
                        </h1>
                        <p
                            class="mb-8 max-w-xl text-lg leading-relaxed font-light text-[#6A6A6E]"
                        >
                            A single, distraction-free subscription bringing
                            cinema-grade streaming and serialized literature
                            together.
                        </p>
                        <div class="mb-5 flex flex-wrap items-center gap-4">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="dashboard()"
                                class="inline-flex items-center justify-center rounded-[12px] bg-[#0E0F11] px-7 py-3.5 text-[14px] font-medium text-white shadow-[0_4px_16px_rgba(14,15,17,0.12)] transition-all hover:-translate-y-0.5 hover:bg-[#1B44E8] hover:shadow-[0_8px_24px_rgba(27,68,232,0.25)]"
                            >
                                Go to Dashboard
                            </Link>
                            <Link
                                v-else
                                :href="register()"
                                class="inline-flex items-center justify-center rounded-[12px] bg-[#0E0F11] px-7 py-3.5 text-[14px] font-medium text-white shadow-[0_4px_16px_rgba(14,15,17,0.12)] transition-all hover:-translate-y-0.5 hover:bg-[#1B44E8] hover:shadow-[0_8px_24px_rgba(27,68,232,0.25)]"
                            >
                                Start free
                            </Link>
                            <a
                                class="inline-flex cursor-pointer items-center gap-2 rounded-[12px] px-4 py-3.5 text-[14px] font-medium text-[#0E0F11] transition-colors hover:bg-black/[0.03] hover:text-[#1B44E8]"
                                href="#pricing"
                                @click="scrollToSection('pricing', $event)"
                            >
                                <span>See pricing</span>
                                <span
                                    class="text-[16px] leading-none transition-transform group-hover:translate-x-1"
                                    >→</span
                                >
                            </a>
                        </div>
                        <div
                            class="flex items-center gap-2.5 text-[13px] text-[#6A6A6E]"
                        >
                            <span
                                class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#1B44E8]"
                            ></span>
                            <span>No card required · Cancel anytime</span>
                        </div>
                    </div>

                    <!-- Right Column (5 cols) -->
                    <div
                        class="scroll-reveal scroll-delay-2 relative flex w-full justify-center lg:col-span-5 lg:justify-end"
                    >
                        <div
                            class="relative flex w-full max-w-[460px] flex-col items-center justify-center gap-4 sm:flex-row sm:items-end sm:gap-0"
                        >
                            <!-- Desktop/Laptop Reader Preview Frame -->
                            <div
                                class="z-10 w-full rounded-[12px] border border-black/[0.08] bg-white p-4 shadow-[0_8px_30px_rgba(14,15,17,0.08)] transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl sm:mr-[-40px] sm:w-[280px]"
                            >
                                <!-- Window controls & tag -->
                                <div
                                    class="mb-3 flex items-center justify-between border-b border-black/[0.06] pb-2.5"
                                >
                                    <div class="flex gap-1.5">
                                        <div
                                            class="h-2 w-2 rounded-full bg-black/20"
                                        ></div>
                                        <div
                                            class="h-2 w-2 rounded-full bg-black/20"
                                        ></div>
                                        <div
                                            class="h-2 w-2 rounded-full bg-black/20"
                                        ></div>
                                    </div>
                                    <span
                                        class="font-mono text-[10px] tracking-wider text-[#6A6A6E] uppercase"
                                        >Solitude &amp; Focus</span
                                    >
                                </div>
                                <div
                                    class="rounded-[8px] border border-black/[0.04] bg-[#FAFAF7] p-3.5"
                                >
                                    <div
                                        class="mb-1 font-mono text-[10px] tracking-widest text-[#6A6A6E] uppercase"
                                    >
                                        Chapter 14
                                    </div>
                                    <h4
                                        class="mb-2 font-serif text-[16px] leading-tight font-medium text-[#0E0F11]"
                                    >
                                        The North Shore
                                    </h4>
                                    <p
                                        class="mb-3 line-clamp-3 font-serif text-[12px] leading-relaxed text-[#6A6A6E] italic"
                                    >
                                        The mist came in off the estuary before
                                        midnight, settling over the slipway like
                                        poured cream.
                                    </p>
                                    <div
                                        class="space-y-1 border-t border-black/[0.05] pt-1"
                                    >
                                        <div
                                            class="flex justify-between font-mono text-[10px] text-[#6A6A6E]"
                                        >
                                            <span>PAGE 184 / 240</span>
                                            <span>76%</span>
                                        </div>
                                        <div
                                            class="h-1.5 w-full overflow-hidden rounded-full bg-black/[0.08]"
                                        >
                                            <div
                                                class="h-full w-[76%] rounded-full bg-[#0E0F11]"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sleek Upright Mobile Cinema Screen Preview -->
                            <div
                                class="z-20 w-[220px] shrink-0 rounded-[16px] border border-black/[0.12] bg-[#0E0F11] p-2.5 text-[#FAFAF7] shadow-[0_20px_48px_rgba(14,15,17,0.22)] transition-transform duration-300 hover:-translate-y-1 hover:shadow-2xl"
                            >
                                <div
                                    class="relative flex aspect-[9/15] flex-col justify-between overflow-hidden rounded-[12px] border border-white/10 bg-[#16171A] p-3.5"
                                >
                                    <div
                                        class="flex items-center justify-between font-mono text-[10px] text-white/60"
                                    >
                                        <span class="tracking-wider"
                                            >1080P MASTER</span
                                        >
                                        <span
                                            class="material-symbols-outlined text-[15px] text-white/80"
                                            >cast</span
                                        >
                                    </div>
                                    <!-- Center cinematic icon & title -->
                                    <div
                                        class="my-auto flex flex-col items-center justify-center text-center"
                                    >
                                        <div
                                            class="mb-2.5 flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 transition-transform hover:scale-110"
                                        >
                                            <span
                                                class="material-symbols-outlined text-[22px] text-white"
                                                >play_arrow</span
                                            >
                                        </div>
                                        <span
                                            class="text-[12px] font-medium tracking-wide text-white"
                                            >After the Rain</span
                                        >
                                        <span class="text-[10px] text-white/50"
                                            >Dir. K. Tanaka · 2024</span
                                        >
                                    </div>
                                    <!-- Scrubber bar with cobalt accent -->
                                    <div class="space-y-1.5">
                                        <div
                                            class="relative flex h-1.5 w-full items-center rounded-full bg-white/20"
                                        >
                                            <div
                                                class="h-full w-[45%] rounded-full bg-[#1B44E8]"
                                            ></div>
                                            <div
                                                class="absolute left-[45%] h-2.5 w-2.5 -translate-x-1/2 rounded-full border-2 border-white bg-[#1B44E8] shadow-sm"
                                            ></div>
                                        </div>
                                        <div
                                            class="flex justify-between font-mono text-[9px] text-white/50"
                                        >
                                            <span>42:18</span>
                                            <span>1:38:00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom platform strip -->
                <div
                    class="scroll-reveal scroll-delay-3 mt-20 flex flex-wrap items-center justify-between gap-4 border-t border-black/[0.08] pt-6"
                >
                    <span
                        class="font-mono text-[11px] tracking-[0.14em] text-[#6A6A6E] uppercase"
                    >
                        AVAILABILITY
                    </span>
                    <div
                        class="flex flex-wrap items-center gap-6 font-mono text-xs tracking-[0.14em] text-[#6A6A6E] sm:gap-8"
                    >
                        <span class="transition-colors hover:text-[#0E0F11]"
                            >WEB</span
                        >
                        <span>·</span>
                        <span class="transition-colors hover:text-[#0E0F11]"
                            >IOS</span
                        >
                        <span>·</span>
                        <span class="transition-colors hover:text-[#0E0F11]"
                            >ANDROID</span
                        >
                        <span>·</span>
                        <span class="transition-colors hover:text-[#0E0F11]"
                            >TV</span
                        >
                        <span>·</span>
                        <span class="transition-colors hover:text-[#0E0F11]"
                            >TABLET</span
                        >
                    </div>
                    <div class="flex items-center gap-2 text-xs text-[#6A6A6E]">
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                        ></span>
                        <span>Synced across all screens</span>
                    </div>
                </div>
            </section>

            <!-- 3. WHAT YOU GET (EDITORIAL INDEX) -->
            <section
                class="mx-auto max-w-[1200px] border-t border-black/[0.08] px-6 py-24 md:px-8"
                id="features"
            >
                <div class="scroll-reveal mb-14">
                    <span
                        class="mb-2 block text-xs font-medium tracking-[0.14em] text-[#6A6A6E] uppercase"
                    >
                        WHAT YOU GET
                    </span>
                    <h2
                        class="text-[32px] font-semibold tracking-tight text-[#0E0F11] md:text-[38px]"
                    >
                        Built for watching and reading.
                    </h2>
                </div>

                <!-- Clean 4-column grid with hairline border -->
                <div
                    class="grid grid-cols-1 gap-8 border-t border-black/[0.08] pt-8 md:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- Block 01 -->
                    <div
                        class="scroll-reveal scroll-delay-1 flex flex-col rounded-xl p-5 transition-all duration-300 hover:bg-black/[0.02]"
                    >
                        <div
                            class="mb-3 font-mono text-xs font-semibold text-[#1B44E8]"
                        >
                            01
                        </div>
                        <h3
                            class="mb-2 text-[20px] font-semibold tracking-tight text-[#0E0F11]"
                        >
                            Cinematic streaming
                        </h3>
                        <p class="text-[14px] leading-relaxed text-[#6A6A6E]">
                            High-definition video mastered at cinematic
                            bitrates. Offline caching for travel and zero
                            buffering interruptions.
                        </p>
                    </div>

                    <!-- Block 02 -->
                    <div
                        class="scroll-reveal scroll-delay-2 flex flex-col rounded-xl p-5 transition-all duration-300 hover:bg-black/[0.02]"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <span
                                class="font-mono text-xs font-semibold text-[#1B44E8]"
                                >02</span
                            >
                            <span
                                class="rounded-[4px] border border-black/[0.08] bg-black/[0.04] px-2 py-0.5 font-mono text-[9px] font-medium tracking-widest text-[#0E0F11] uppercase"
                            >
                                COMING SOON
                            </span>
                        </div>
                        <h3
                            class="mb-2 text-[20px] font-semibold tracking-tight text-[#0E0F11]"
                        >
                            A reading space
                        </h3>
                        <p class="text-[14px] leading-relaxed text-[#6A6A6E]">
                            Serialized long-form novels engineered for sustained
                            focus. Custom serif typography and distraction-free
                            page rhythms.
                        </p>
                    </div>

                    <!-- Block 03 -->
                    <div
                        class="scroll-reveal scroll-delay-3 flex flex-col rounded-xl p-5 transition-all duration-300 hover:bg-black/[0.02]"
                    >
                        <div
                            class="mb-3 font-mono text-xs font-semibold text-[#1B44E8]"
                        >
                            03
                        </div>
                        <h3
                            class="mb-2 text-[20px] font-semibold tracking-tight text-[#0E0F11]"
                        >
                            Talk to creators
                        </h3>
                        <p class="text-[14px] leading-relaxed text-[#6A6A6E]">
                            Direct, moderated discourse channels connecting
                            verified filmmakers, authors, and dedicated patrons.
                        </p>
                    </div>

                    <!-- Block 04 -->
                    <div
                        class="scroll-reveal scroll-delay-4 flex flex-col rounded-xl p-5 transition-all duration-300 hover:bg-black/[0.02]"
                    >
                        <div
                            class="mb-3 font-mono text-xs font-semibold text-[#1B44E8]"
                        >
                            04
                        </div>
                        <h3
                            class="mb-2 text-[20px] font-semibold tracking-tight text-[#0E0F11]"
                        >
                            Fair pricing
                        </h3>
                        <p class="text-[14px] leading-relaxed text-[#6A6A6E]">
                            One transparent flat subscription. No split tiers
                            for reading versus watching, and no
                            micro-transaction paywalls.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 4. DARK PRODUCT MOMENT (FULL-WIDTH BREATH) -->
            <section
                class="relative overflow-hidden border-y border-white/10 bg-[#0E0F11] py-24 text-[#FAFAF7] lg:py-32"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(#1B44E8_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.03]"
                ></div>

                <div class="relative z-10 mx-auto max-w-[1200px] px-6 md:px-8">
                    <div
                        class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-16"
                    >
                        <!-- Left: Cinema & Novel Preview Cards side-by-side -->
                        <div class="scroll-reveal lg:col-span-6">
                            <div
                                class="grid grid-cols-2 gap-4 rounded-[16px] border border-white/10 bg-white/[0.03] p-4 shadow-[0_20px_50px_rgba(0,0,0,0.4)] backdrop-blur-sm sm:gap-6 sm:p-6"
                            >
                                <!-- Cinema Poster Preview -->
                                <div
                                    class="flex aspect-[3/4] flex-col justify-between rounded-[10px] border border-white/10 bg-[#16171A] p-4 transition-transform duration-300 hover:-translate-y-1"
                                >
                                    <div
                                        class="flex items-center justify-between font-mono text-[10px] text-white/50"
                                    >
                                        <span>FLIX · REEL</span>
                                        <span>4K HDR</span>
                                    </div>
                                    <div class="space-y-1">
                                        <div
                                            class="mb-2 h-0.5 w-6 bg-[#1B44E8]"
                                        ></div>
                                        <h4
                                            class="text-[15px] leading-tight font-medium text-white"
                                        >
                                            SOLITUDE BY DESIGN
                                        </h4>
                                        <p class="text-[11px] text-white/50">
                                            An architecture essay film
                                        </p>
                                    </div>
                                </div>
                                <!-- Novel Preview -->
                                <div
                                    class="flex aspect-[3/4] flex-col justify-between rounded-[10px] border border-white/20 bg-[#FAFAF7] p-4 text-[#0E0F11] shadow-lg transition-transform duration-300 hover:-translate-y-1"
                                >
                                    <div
                                        class="flex items-center justify-between font-mono text-[10px] text-[#6A6A6E]"
                                    >
                                        <span>SERIES VOL. 03</span>
                                        <span>14 MIN</span>
                                    </div>
                                    <div class="my-auto space-y-1.5">
                                        <div
                                            class="font-serif text-[11px] text-black/50 italic"
                                        >
                                            Excerpts from Chapter II
                                        </div>
                                        <p
                                            class="line-clamp-4 font-serif text-[12px] leading-snug text-[#0E0F11]"
                                        >
                                            The concrete walls absorbed the
                                            afternoon light without reflecting
                                            it back into the studio.
                                        </p>
                                    </div>
                                    <div
                                        class="flex justify-between border-t border-black/[0.08] pt-2 font-mono text-[9px] text-[#6A6A6E]"
                                    >
                                        <span>PROGRESS</span>
                                        <span>42%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Text & Ghost link -->
                        <div class="scroll-reveal scroll-delay-2 lg:col-span-6">
                            <span
                                class="mb-3 block text-xs font-medium tracking-[0.14em] text-white/50 uppercase"
                            >
                                ONE SUBSCRIPTION
                            </span>
                            <h2
                                class="mb-5 text-3xl leading-snug font-semibold tracking-tight text-[#FAFAF7] lg:text-4xl"
                            >
                                Films tonight. A chapter before bed.
                            </h2>
                            <p
                                class="mb-8 max-w-lg text-base leading-relaxed font-light text-white/70"
                            >
                                Switch effortlessly from an independent feature
                                film to the continuation of your serialized
                                story without leaving your flow or maintaining
                                duplicate subscriptions.
                            </p>
                            <div>
                                <a
                                    class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-white/25 px-5 py-3 text-[14px] font-medium text-[#FAFAF7] transition-all hover:translate-x-1 hover:border-white hover:bg-white/10"
                                    href="#features"
                                    @click="scrollToSection('features', $event)"
                                >
                                    <span>Explore the library</span>
                                    <span class="text-[15px] leading-none"
                                        >→</span
                                    >
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. NOVEL COMING SOON (SOFT REFINED PAPER SANCTUARY) -->
            <section
                class="relative overflow-hidden border-t border-b border-[#0E0F11]/10 bg-[#F4F0E6] py-28 lg:py-36"
                id="novel"
            >
                <!-- Warm ambient watermark elements -->
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(#C2A676_1px,transparent_1px)] [background-size:20px_20px] opacity-[0.2]"
                ></div>
                <div
                    class="pointer-events-none absolute -top-32 -right-32 h-96 w-96 rounded-full bg-[#E8DFC8]/40 blur-3xl"
                ></div>
                <div
                    class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-[#DFD3B7]/50 blur-3xl"
                ></div>

                <div class="relative z-10 mx-auto max-w-[1200px] px-6 md:px-8">
                    <!-- Centered Header & Subline with Luxury Archival Treatment -->
                    <div
                        class="scroll-reveal mx-auto mb-20 max-w-3xl text-center"
                    >
                        <!-- Luxury Archival Accession Badge -->
                        <div
                            class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-[#0E0F11]/[0.08] bg-white/80 px-4 py-1.5 shadow-[0_2px_10px_rgba(0,0,0,0.03)] backdrop-blur-md"
                        >
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#1B44E8] opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-[#1B44E8]"
                                ></span>
                            </span>
                            <span
                                class="font-mono text-[11px] font-medium tracking-[0.18em] text-[#4A4B50] uppercase"
                            >
                                NOVEL ARCHIVE · SERIALIZED EDITIONS
                            </span>
                            <span class="h-3 w-[1px] bg-black/15"></span>
                            <span
                                class="font-mono text-[10px] font-semibold tracking-wider text-[#1B44E8] uppercase"
                            >
                                COMING SOON
                            </span>
                        </div>

                        <!-- Grand Editorial Headline -->
                        <h2
                            class="mb-5 font-serif text-[42px] leading-[1.06] font-normal tracking-[-0.02em] text-[#0E0F11] sm:text-[54px] lg:text-[64px]"
                        >
                            A quieter kind of
                            <span
                                class="text-[#1B44E8] italic underline decoration-[#1B44E8]/25 underline-offset-8"
                                >story</span
                            >.
                        </h2>

                        <!-- Refined Literary Subtitle -->
                        <p
                            class="mx-auto mb-10 max-w-2xl font-serif text-[17px] leading-relaxed text-[#55565B] italic sm:text-[20px]"
                        >
                            Long-form serialized literature typeset for
                            sustained focus, delivered chapter by chapter
                            alongside your evening cinema.
                        </p>

                        <!-- Elevated Waitlist Card Envelope -->
                        <div class="mx-auto max-w-xl">
                            <div
                                class="rounded-[18px] border border-[#0E0F11]/12 bg-white/95 p-2 shadow-[0_16px_40px_rgba(14,15,17,0.06),0_1px_3px_rgba(0,0,0,0.04)] backdrop-blur-xl transition-all duration-300 hover:border-[#1B44E8]/40 sm:p-2.5"
                            >
                                <form
                                    v-if="!waitlistSubmitted"
                                    class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center"
                                    @submit.prevent="handleWaitlist"
                                >
                                    <div
                                        class="flex flex-grow items-center gap-3 px-3.5 py-2"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[20px] text-[#8C8C92]"
                                            >mail</span
                                        >
                                        <input
                                            v-model="waitlistEmail"
                                            class="w-full border-none bg-transparent p-0 text-[15px] text-[#0E0F11] placeholder-[#8C8C92] focus:ring-0 focus:outline-none"
                                            placeholder="Enter your email"
                                            required
                                            type="email"
                                        />
                                    </div>
                                    <button
                                        class="group inline-flex cursor-pointer items-center justify-center gap-2 rounded-[12px] bg-[#0E0F11] px-6 py-3.5 text-[14px] font-medium whitespace-nowrap text-white shadow-sm transition-all duration-200 hover:bg-[#1B44E8] hover:shadow-md active:scale-[0.98]"
                                        type="submit"
                                    >
                                        <span>Join the waitlist</span>
                                        <span
                                            class="text-[15px] transition-transform duration-200 group-hover:translate-x-1"
                                            >→</span
                                        >
                                    </button>
                                </form>
                                <div
                                    v-else
                                    class="flex animate-in items-center justify-center gap-2.5 rounded-[12px] bg-[#1B44E8]/[0.08] px-5 py-3 text-[14px] font-medium text-[#1B44E8] duration-300 fade-in"
                                >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                        >verified</span
                                    >
                                    <span
                                        >Invitation reserved. You are on the
                                        private edition priority list.</span
                                    >
                                </div>
                            </div>

                            <!-- Trust Accents: 3 Archival Tags -->
                            <div
                                class="mt-4 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 font-serif text-[12px] text-[#6A6A70]"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span class="text-[10px] text-[#1B44E8]"
                                        >✦</span
                                    >
                                    <span>Private preview access</span>
                                </span>
                                <span class="text-black/20">·</span>
                                <span class="flex items-center gap-1.5">
                                    <span class="text-[10px] text-[#1B44E8]"
                                        >✦</span
                                    >
                                    <span>Zero algorithmic feeds</span>
                                </span>
                                <span class="text-black/20">·</span>
                                <span class="flex items-center gap-1.5">
                                    <span class="text-[10px] text-[#1B44E8]"
                                        >✦</span
                                    >
                                    <span>Pure prose</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Book Covers Grid with Tactile Details & Ribbon Marker -->
                    <div
                        class="mx-auto grid max-w-[1060px] grid-cols-1 items-stretch gap-8 md:grid-cols-3"
                    >
                        <!-- Book 1: The Silent Current -->
                        <div
                            class="group scroll-reveal scroll-delay-1 relative flex flex-col justify-between overflow-hidden rounded-[12px] border border-[#0E0F11]/[0.1] bg-[#FAF7F0] p-8 shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_24px_48px_rgba(0,0,0,0.08)]"
                        >
                            <!-- Blue bookmark ribbon accent -->
                            <div
                                class="absolute -top-1 right-8 h-7 w-4 rounded-b-sm bg-[#1B44E8] shadow-sm transition-all duration-300 group-hover:h-9"
                            ></div>

                            <div>
                                <div
                                    class="mb-6 flex items-center justify-between border-b border-black/[0.08] pb-3"
                                >
                                    <span
                                        class="font-mono text-[10px] font-semibold tracking-wider text-[#0E0F11] uppercase"
                                        >SERIAL NO. 01</span
                                    >
                                    <span
                                        class="font-mono text-[10px] text-[#6A6A6E] uppercase"
                                        >Atmospheric Fiction</span
                                    >
                                </div>
                                <div class="py-2">
                                    <div
                                        class="mb-5 h-[2px] w-8 bg-[#0E0F11]"
                                    ></div>
                                    <h3
                                        class="mb-1 font-serif text-[26px] leading-tight font-medium text-[#0E0F11] transition-colors group-hover:text-[#1B44E8]"
                                    >
                                        The Silent Current
                                    </h3>
                                    <p
                                        class="mb-4 text-[13px] font-medium text-[#6A6A6E]"
                                    >
                                        M. Vandenberg
                                    </p>
                                    <p
                                        class="line-clamp-3 font-serif text-[13px] leading-relaxed text-black/70 italic"
                                    >
                                        "The estuary held neither light nor
                                        shadow until the lanterns on the quay
                                        began to gutter in the salt drift."
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-6 flex items-center justify-between border-t border-black/[0.08] pt-3 font-mono text-[10px] text-[#6A6A6E]"
                            >
                                <span>AUTUMN 2025</span>
                                <span class="font-medium text-[#0E0F11]"
                                    >380 PAGES</span
                                >
                            </div>
                        </div>

                        <!-- Book 2: Echoes of Kyoto -->
                        <div
                            class="group scroll-reveal scroll-delay-2 relative flex flex-col justify-between overflow-hidden rounded-[12px] border border-[#0E0F11]/[0.12] bg-[#EDE8DC] p-8 shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_24px_48px_rgba(0,0,0,0.08)]"
                        >
                            <!-- Amber bookmark ribbon accent -->
                            <div
                                class="absolute -top-1 right-8 h-7 w-4 rounded-b-sm bg-[#B87333] shadow-sm transition-all duration-300 group-hover:h-9"
                            ></div>

                            <div>
                                <div
                                    class="mb-6 flex items-center justify-between border-b border-black/[0.08] pb-3"
                                >
                                    <span
                                        class="font-mono text-[10px] font-semibold tracking-wider text-[#0E0F11] uppercase"
                                        >SERIAL NO. 02</span
                                    >
                                    <span
                                        class="font-mono text-[10px] text-[#6A6A6E] uppercase"
                                        >Travel &amp; Solitude</span
                                    >
                                </div>
                                <div class="py-2">
                                    <div
                                        class="mb-5 h-[2px] w-8 bg-[#0E0F11]"
                                    ></div>
                                    <h3
                                        class="mb-1 font-serif text-[26px] leading-tight font-medium text-[#0E0F11] transition-colors group-hover:text-[#1B44E8]"
                                    >
                                        Echoes of Kyoto
                                    </h3>
                                    <p
                                        class="mb-4 text-[13px] font-medium text-[#6A6A6E]"
                                    >
                                        Kenzo S.
                                    </p>
                                    <p
                                        class="line-clamp-3 font-serif text-[13px] leading-relaxed text-black/70 italic"
                                    >
                                        "The timber walls absorbed rain the way
                                        monastery bells retain prayer long after
                                        dusk."
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-6 flex items-center justify-between border-t border-black/[0.08] pt-3 font-mono text-[10px] text-[#6A6A6E]"
                            >
                                <span>AUTUMN 2025</span>
                                <span class="font-medium text-[#0E0F11]"
                                    >WEEKLY SERIAL</span
                                >
                            </div>
                        </div>

                        <!-- Book 3: Static Horizon -->
                        <div
                            class="group scroll-reveal scroll-delay-3 relative flex flex-col justify-between overflow-hidden rounded-[12px] border border-white/10 bg-[#141518] p-8 text-[#FAFAF7] shadow-[0_8px_24px_rgba(0,0,0,0.1)] transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_24px_48px_rgba(0,0,0,0.2)]"
                        >
                            <!-- White/silver bookmark ribbon accent -->
                            <div
                                class="absolute -top-1 right-8 h-7 w-4 rounded-b-sm bg-white/80 shadow-sm transition-all duration-300 group-hover:h-9"
                            ></div>

                            <div>
                                <div
                                    class="mb-6 flex items-center justify-between border-b border-white/10 pb-3"
                                >
                                    <span
                                        class="font-mono text-[10px] font-semibold tracking-wider text-white uppercase"
                                        >SERIAL NO. 03</span
                                    >
                                    <span
                                        class="font-mono text-[10px] text-white/50 uppercase"
                                        >Speculative Prose</span
                                    >
                                </div>
                                <div class="py-2">
                                    <div
                                        class="mb-5 h-[2px] w-8 bg-[#1B44E8]"
                                    ></div>
                                    <h3
                                        class="mb-1 font-serif text-[26px] leading-tight font-medium text-white transition-colors group-hover:text-[#6487FF]"
                                    >
                                        Static Horizon
                                    </h3>
                                    <p
                                        class="mb-4 text-[13px] font-medium text-white/60"
                                    >
                                        Elena Rostova
                                    </p>
                                    <p
                                        class="line-clamp-3 font-serif text-[13px] leading-relaxed text-white/70 italic"
                                    >
                                        "Beyond the broadcast relay tower, the
                                        tundra was silent except for the carrier
                                        waves in our headsets."
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-6 flex items-center justify-between border-t border-white/10 pt-3 font-mono text-[10px] text-white/60"
                            >
                                <span>WINTER 2025</span>
                                <span class="font-medium text-white"
                                    >COMPLETE ED.</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. PRICING (NO SURPRISES) -->
            <section
                class="mx-auto max-w-[1200px] px-6 py-28 md:px-8"
                id="pricing"
            >
                <div class="scroll-reveal mx-auto mb-16 max-w-2xl text-center">
                    <div class="mb-3 inline-flex items-center gap-2">
                        <span
                            class="rounded-[6px] border border-[#1B44E8]/20 bg-[#1B44E8]/[0.06] px-3 py-1.5 text-xs font-medium tracking-[0.14em] text-[#1B44E8] uppercase"
                        >
                            TRANSPARENT VALUE · NO MICROTRANSACTIONS
                        </span>
                    </div>
                    <h2
                        class="mb-3 text-[34px] font-semibold tracking-tight text-[#0E0F11] sm:text-[44px]"
                    >
                        Simple plans. No surprises.
                    </h2>
                    <p
                        class="mx-auto mb-8 max-w-lg text-[16px] leading-relaxed font-light text-[#6A6A6E]"
                    >
                        One calm membership uniting master-quality independent
                        cinema and serialized literature.
                    </p>

                    <!-- Monthly / Annual toggle -->
                    <div
                        class="inline-flex items-center rounded-full border border-black/[0.08] bg-black/[0.04] p-1.5 shadow-inner"
                    >
                        <button
                            class="cursor-pointer rounded-full px-5 py-2 text-xs font-medium transition-all duration-200"
                            :class="
                                billingCycle === 'monthly'
                                    ? 'bg-white text-[#0E0F11] shadow-sm'
                                    : 'text-[#6A6A6E] hover:text-[#0E0F11]'
                            "
                            type="button"
                            @click="billingCycle = 'monthly'"
                        >
                            Monthly billing
                        </button>
                        <button
                            class="flex cursor-pointer items-center gap-1.5 rounded-full px-5 py-2 text-xs font-medium transition-all duration-200"
                            :class="
                                billingCycle === 'annual'
                                    ? 'bg-white text-[#0E0F11] shadow-sm'
                                    : 'text-[#6A6A6E] hover:text-[#0E0F11]'
                            "
                            type="button"
                            @click="billingCycle = 'annual'"
                        >
                            <span>Annual billing</span>
                            <span
                                class="rounded-full bg-[#1B44E8] px-2 py-0.5 font-mono text-[9px] font-semibold text-white uppercase"
                            >
                                Save 20%
                            </span>
                        </button>
                    </div>
                </div>

                <!-- 3 Cards Architecture -->
                <div
                    class="mx-auto grid max-w-[1100px] grid-cols-1 items-stretch gap-8 lg:grid-cols-3"
                >
                    <!-- Card 1: Basic (Free) -->
                    <div
                        class="scroll-reveal scroll-delay-1 flex flex-col justify-between rounded-[16px] border border-black/[0.08] bg-white p-8 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <span
                                    class="font-mono text-xs font-semibold tracking-wider text-[#6A6A6E] uppercase"
                                    >BASIC TIER</span
                                >
                                <span
                                    class="rounded-[6px] bg-black/[0.04] px-2.5 py-1 font-mono text-[11px] text-[#6A6A6E]"
                                    >Casual</span
                                >
                            </div>
                            <div class="mb-2">
                                <span
                                    class="text-[38px] font-semibold tracking-tight text-[#0E0F11]"
                                    >Free</span
                                >
                            </div>
                            <div class="mb-6 text-[13px] text-[#6A6A6E]">
                                Forever free for curious cinephiles
                            </div>
                            <p
                                class="mb-6 border-b border-black/[0.06] pb-6 text-[13px] leading-relaxed text-[#6A6A6E]"
                            >
                                Curated cultural entry point with rotating
                                titles and sample chapters to sample the reading
                                experience.
                            </p>
                            <div
                                class="mb-4 text-[11px] font-semibold tracking-wider text-[#6A6A6E] uppercase"
                            >
                                Included in Basic
                            </div>
                            <ul
                                class="mb-8 space-y-3.5 text-[13px] text-[#0E0F11]"
                            >
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#0E0F11]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Rotating catalog of 30 curated
                                            films</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Refreshed monthly
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#0E0F11]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Standard definition (720p HD)</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Ideal for browser previewing
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#0E0F11]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Chapter 1 previews for all
                                            serials</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Sample the editorial catalog
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="border-t border-black/[0.06] pt-4">
                            <Link
                                :href="register()"
                                class="inline-flex w-full items-center justify-center rounded-[10px] border border-black/20 py-3.5 text-[14px] font-medium text-[#0E0F11] transition-colors hover:border-black"
                            >
                                Get started free
                            </Link>
                            <div
                                class="mt-2.5 text-center text-[11px] text-[#6A6A6E]"
                            >
                                No credit card required
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: PRO (Rp 80.000 / mo) — Clearly Highlighted -->
                    <div
                        class="scroll-reveal scroll-delay-2 relative flex flex-col justify-between rounded-[16px] border border-t-4 border-[#1B44E8]/30 border-t-[#1B44E8] bg-white p-8 shadow-[0_12px_36px_rgba(27,68,232,0.08)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_48px_rgba(27,68,232,0.14)]"
                    >
                        <!-- Cobalt Recommended Badge -->
                        <div
                            class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-[#1B44E8] px-3.5 py-1 font-mono text-[10px] font-semibold tracking-wider text-white uppercase shadow-sm"
                        >
                            RECOMMENDED
                        </div>
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <span
                                    class="font-mono text-xs font-semibold tracking-wider text-[#1B44E8] uppercase"
                                    >PRO TIER</span
                                >
                                <span
                                    class="rounded-[6px] bg-[#1B44E8]/[0.08] px-2.5 py-1 font-mono text-[11px] font-medium text-[#1B44E8]"
                                    >Full Catalog</span
                                >
                            </div>
                            <div class="mb-2 flex items-baseline gap-1">
                                <span
                                    class="text-[38px] font-semibold tracking-tight text-[#0E0F11]"
                                >
                                    {{
                                        billingCycle === 'annual'
                                            ? 'Rp 64.000'
                                            : 'Rp 80.000'
                                    }}
                                </span>
                                <span class="text-[13px] text-[#6A6A6E]"
                                    >/ month</span
                                >
                            </div>
                            <div
                                class="mb-6 text-[13px] font-medium text-[#1B44E8]"
                            >
                                {{
                                    billingCycle === 'annual'
                                        ? 'Billed annually (Save 20%) · 14-day trial'
                                        : 'Includes 14-day risk-free trial'
                                }}
                            </div>
                            <p
                                class="mb-6 border-b border-black/[0.06] pb-6 text-[13px] leading-relaxed text-[#0E0F11]"
                            >
                                Full cinematic fidelity, offline download
                                caching, unreleased chapters, and verified
                                creator access.
                            </p>
                            <div
                                class="mb-4 text-[11px] font-semibold tracking-wider text-[#1B44E8] uppercase"
                            >
                                Everything in Basic, plus
                            </div>
                            <ul
                                class="mb-8 space-y-3.5 text-[13px] text-[#0E0F11]"
                            >
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#1B44E8]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Complete 4K HDR master
                                            streaming</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Pristine master color grading
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#1B44E8]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Offline chapter &amp; video
                                            caching</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Save films and books for travel
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#1B44E8]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Private creator discourse
                                            channels</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Q&amp;A with directors and authors
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#1B44E8]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Zero ads or interruptions</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Pure immersion without distraction
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="border-t border-black/[0.06] pt-4">
                            <Link
                                :href="register()"
                                class="inline-flex w-full items-center justify-center rounded-[10px] bg-[#1B44E8] py-3.5 text-[14px] font-medium text-white shadow-[0_4px_16px_rgba(27,68,232,0.2)] transition-all hover:-translate-y-0.5 hover:bg-[#1638be] hover:shadow-[0_8px_24px_rgba(27,68,232,0.3)]"
                            >
                                Start Pro Trial
                            </Link>
                            <div
                                class="mt-2.5 text-center text-[11px] text-[#6A6A6E]"
                            >
                                Cancel anytime before day 14
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: VIP (Rp 150.000 / mo) -->
                    <div
                        class="scroll-reveal scroll-delay-3 flex flex-col justify-between rounded-[16px] border border-black/[0.08] bg-white p-8 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <span
                                    class="font-mono text-xs font-semibold tracking-wider text-[#6A6A6E] uppercase"
                                    >VIP TIER</span
                                >
                                <span
                                    class="rounded-[6px] bg-black/[0.06] px-2.5 py-1 font-mono text-[11px] font-medium text-[#0E0F11]"
                                    >Patron</span
                                >
                            </div>
                            <div class="mb-2 flex items-baseline gap-1">
                                <span
                                    class="text-[38px] font-semibold tracking-tight text-[#0E0F11]"
                                >
                                    {{
                                        billingCycle === 'annual'
                                            ? 'Rp 120.000'
                                            : 'Rp 150.000'
                                    }}
                                </span>
                                <span class="text-[13px] text-[#6A6A6E]"
                                    >/ month</span
                                >
                            </div>
                            <div class="mb-6 text-[13px] text-[#6A6A6E]">
                                {{
                                    billingCycle === 'annual'
                                        ? 'Billed annually (Save 20%)'
                                        : 'The premier cultural all-access pass'
                                }}
                            </div>
                            <p
                                class="mb-6 border-b border-black/[0.06] pb-6 text-[13px] leading-relaxed text-[#6A6A6E]"
                            >
                                For dedicated patrons seeking early archival
                                releases, private advisory with editors, and
                                physical edition privileges.
                            </p>
                            <div
                                class="mb-4 text-[11px] font-semibold tracking-wider text-[#6A6A6E] uppercase"
                            >
                                Everything in Pro, plus
                            </div>
                            <ul
                                class="mb-8 space-y-3.5 text-[13px] text-[#0E0F11]"
                            >
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#0E0F11]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >First-read access to every new
                                            volume</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Full novel slate unlocked early
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#0E0F11]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Early previews of rough cuts &amp;
                                            essays</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Attend private online screenings
                                        </p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="material-symbols-outlined mt-0.5 text-[16px] text-[#0E0F11]"
                                        >check</span
                                    >
                                    <div>
                                        <span class="font-medium"
                                            >Direct advisory chat with
                                            editors</span
                                        >
                                        <p class="text-[12px] text-[#6A6A6E]">
                                            Vote on future print editions
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="border-t border-black/[0.06] pt-4">
                            <Link
                                :href="register()"
                                class="inline-flex w-full items-center justify-center rounded-[10px] border border-black/20 py-3.5 text-[14px] font-medium text-[#0E0F11] transition-colors hover:border-black"
                            >
                                Choose VIP Patron
                            </Link>
                            <div
                                class="mt-2.5 text-center text-[11px] text-[#6A6A6E]"
                            >
                                Limited patron enrollment
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust row below -->
                <div
                    class="scroll-reveal mx-auto mt-16 max-w-xl border-t border-black/[0.08] pt-10 text-center"
                >
                    <p
                        class="font-mono text-xs tracking-wider text-[#6A6A6E] uppercase"
                    >
                        Secured by Midtrans · Cancel anytime · No hidden fees
                    </p>
                </div>
            </section>

            <!-- 7. FAQ (INTERACTIVE THEMED ACCORDION) -->
            <section
                class="mx-auto max-w-[780px] border-t border-black/[0.08] px-6 py-28"
                id="faq"
            >
                <div class="scroll-reveal mb-12 text-center sm:text-left">
                    <div class="mb-3 inline-flex items-center gap-2">
                        <span
                            class="rounded-[6px] border border-black/[0.08] bg-black/[0.04] px-3 py-1 text-xs font-medium tracking-[0.14em] text-[#6A6A6E] uppercase"
                        >
                            QUESTIONS &amp; ARCHIVE DETAILS
                        </span>
                    </div>
                    <h2
                        class="mb-2 text-[32px] font-semibold tracking-tight text-[#0E0F11] sm:text-[40px]"
                    >
                        Questions, answered.
                    </h2>
                    <p class="text-[15px] font-light text-[#6A6A6E]">
                        Everything you need to know about Watoon streaming,
                        serialized novels, and membership privileges.
                    </p>
                </div>

                <!-- Interactive Accordion with Smooth CSS Grid Animation -->
                <div
                    class="scroll-reveal divide-y divide-black/[0.08] border-t border-b border-black/[0.08]"
                >
                    <div
                        v-for="faq in faqs"
                        :key="faq.id"
                        class="py-4.5 transition-colors"
                        :class="
                            openFaqId === faq.id
                                ? '-mx-4 rounded-xl bg-black/[0.015] px-4'
                                : ''
                        "
                    >
                        <button
                            class="group flex w-full cursor-pointer items-center justify-between gap-4 py-2 text-left"
                            @click="toggleFaq(faq.id)"
                            type="button"
                            :aria-expanded="openFaqId === faq.id"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="h-1.5 w-1.5 rounded-full transition-all duration-300"
                                    :class="
                                        openFaqId === faq.id
                                            ? 'scale-125 bg-[#1B44E8]'
                                            : 'bg-black/20 group-hover:bg-black/40'
                                    "
                                ></span>
                                <span
                                    class="text-[17px] font-medium transition-colors"
                                    :class="
                                        openFaqId === faq.id
                                            ? 'font-semibold text-[#0E0F11]'
                                            : 'text-[#0E0F11] group-hover:text-[#1B44E8]'
                                    "
                                >
                                    {{ faq.question }}
                                </span>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    v-if="faq.highlight"
                                    class="hidden rounded px-2 py-0.5 font-mono text-[10px] tracking-wider uppercase transition-all sm:inline-block"
                                    :class="
                                        openFaqId === faq.id
                                            ? 'bg-[#1B44E8]/10 text-[#1B44E8]'
                                            : 'bg-black/[0.03] text-[#6A6A6E]'
                                    "
                                >
                                    {{ faq.highlight }}
                                </span>
                                <span
                                    class="material-symbols-outlined text-[#6A6A6E] transition-transform duration-300"
                                    :class="{
                                        'rotate-180 text-[#1B44E8]':
                                            openFaqId === faq.id,
                                    }"
                                >
                                    expand_more
                                </span>
                            </div>
                        </button>

                        <!-- Smooth CSS grid height expansion -->
                        <div
                            class="grid overflow-hidden transition-all duration-300 ease-out"
                            :class="
                                openFaqId === faq.id
                                    ? 'grid-rows-[1fr] pt-3 pb-2 opacity-100'
                                    : 'grid-rows-[0fr] pt-0 pb-0 opacity-0'
                            "
                        >
                            <div
                                class="ml-0.5 overflow-hidden border-l-2 border-[#1B44E8]/30 pl-4.5"
                            >
                                <p
                                    class="text-[14px] leading-relaxed font-light text-[#55565B]"
                                >
                                    {{ faq.answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 8. FINAL CTA -->
            <section
                class="mx-auto max-w-[1200px] border-t border-black/[0.08] px-6 py-28 text-center md:px-8"
            >
                <div class="scroll-reveal mx-auto max-w-xl">
                    <h2
                        class="mb-3 text-3xl font-semibold tracking-tight text-[#0E0F11] sm:text-4xl"
                    >
                        Start with Basic. Upgrade when you're ready.
                    </h2>
                    <p
                        class="mb-8 text-[15px] leading-relaxed font-light text-[#6A6A6E]"
                    >
                        Join thousands of dedicated readers and cinephiles
                        exploring deliberate media.
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-4"
                    >
                        <Link
                            :href="register()"
                            class="inline-flex items-center justify-center rounded-[12px] bg-[#0E0F11] px-7 py-3.5 text-[14px] font-medium text-[#FAFAF7] shadow-sm transition-all hover:-translate-y-0.5 hover:bg-[#1B44E8] hover:shadow-md"
                        >
                            Get started free
                        </Link>
                        <a
                            class="inline-flex cursor-pointer items-center justify-center rounded-[12px] border border-black/20 px-7 py-3.5 text-[14px] font-medium text-[#0E0F11] transition-all hover:border-black hover:bg-black/[0.02]"
                            href="#pricing"
                            @click="scrollToSection('pricing', $event)"
                        >
                            See all plans
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- 9. FOOTER -->
        <footer class="border-t border-black/[0.08] bg-white">
            <div class="mx-auto max-w-[1200px] px-6 py-16 md:px-8">
                <div
                    class="grid grid-cols-1 gap-10 border-b border-black/[0.08] pb-12 md:grid-cols-12"
                >
                    <!-- Brand & Manifesto (Left 5 cols) -->
                    <div class="space-y-4 md:col-span-5">
                        <div
                            class="text-[17px] font-semibold tracking-[-0.01em] text-[#0E0F11]"
                        >
                            WATOON
                        </div>
                        <p
                            class="max-w-sm text-[13px] leading-relaxed text-[#6A6A6E]"
                        >
                            Cinematic streaming and serialized literature,
                            curated in a single quiet space.
                        </p>
                        <div class="pt-2">
                            <span
                                class="mb-2 block font-mono text-[11px] tracking-wider text-[#6A6A6E] uppercase"
                            >
                                Enter as:
                            </span>
                            <div
                                class="flex flex-wrap gap-2.5 text-[13px] text-[#0E0F11]"
                            >
                                <a class="hover:underline" href="#">Viewer</a>
                                <span>·</span>
                                <a class="hover:underline" href="#"
                                    >Film Creator</a
                                >
                                <span>·</span>
                                <a class="hover:underline" href="#"
                                    >Novel Creator</a
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Links Columns (Right 7 cols) -->
                    <div
                        class="grid grid-cols-2 gap-6 text-[13px] sm:grid-cols-4 md:col-span-7"
                    >
                        <div>
                            <div class="mb-3 font-medium text-[#0E0F11]">
                                Product
                            </div>
                            <ul class="space-y-2 text-[#6A6A6E]">
                                <li>
                                    <a
                                        class="cursor-pointer transition-colors hover:text-[#0E0F11]"
                                        href="#features"
                                        @click="
                                            scrollToSection('features', $event)
                                        "
                                        >Streaming</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="cursor-pointer transition-colors hover:text-[#0E0F11]"
                                        href="#novel"
                                        @click="
                                            scrollToSection('novel', $event)
                                        "
                                        >Reader Mode</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="cursor-pointer transition-colors hover:text-[#0E0F11]"
                                        href="#pricing"
                                        @click="
                                            scrollToSection('pricing', $event)
                                        "
                                        >Pricing Plans</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Release Notes</a
                                    >
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="mb-3 font-medium text-[#0E0F11]">
                                Company
                            </div>
                            <ul class="space-y-2 text-[#6A6A6E]">
                                <li>
                                    <a
                                        class="cursor-pointer transition-colors hover:text-[#0E0F11]"
                                        href="#features"
                                        @click="
                                            scrollToSection('features', $event)
                                        "
                                        >Editorial Index</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Curators</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Careers</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Press</a
                                    >
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="mb-3 font-medium text-[#0E0F11]">
                                Support
                            </div>
                            <ul class="space-y-2 text-[#6A6A6E]">
                                <li>
                                    <a
                                        class="cursor-pointer transition-colors hover:text-[#0E0F11]"
                                        href="#faq"
                                        @click="scrollToSection('faq', $event)"
                                        >Help Center</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="cursor-pointer transition-colors hover:text-[#0E0F11]"
                                        href="#faq"
                                        @click="scrollToSection('faq', $event)"
                                        >Community</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Status</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Contact</a
                                    >
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="mb-3 font-medium text-[#0E0F11]">
                                Legal
                            </div>
                            <ul class="space-y-2 text-[#6A6A6E]">
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Privacy</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Terms</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Copyright IP</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="transition-colors hover:text-[#0E0F11]"
                                        href="#"
                                        >Cookies</a
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div
                    class="flex flex-col items-center justify-between gap-4 pt-8 text-[12px] text-[#6A6A6E] md:flex-row"
                >
                    <div>
                        © 2025 Watoon Inc. All rights reserved. Crafted for
                        critical readers and film enthusiasts.
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-4 font-mono text-xs">
                            <a
                                class="transition-colors hover:text-[#0E0F11]"
                                href="#"
                                >X</a
                            >
                            <a
                                class="transition-colors hover:text-[#0E0F11]"
                                href="#"
                                >GitHub</a
                            >
                            <a
                                class="transition-colors hover:text-[#0E0F11]"
                                href="#"
                                >Discord</a
                            >
                            <a
                                class="transition-colors hover:text-[#0E0F11]"
                                href="#"
                                >Instagram</a
                            >
                        </div>
                        <span class="text-black/20">|</span>
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]"
                                >language</span
                            >
                            <span>English (US)</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    font-size: 20px;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-block;
    white-space: nowrap;
    word-wrap: normal;
    direction: ltr;
    -webkit-font-smoothing: antialiased;
    vertical-align: middle;
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
    transition-delay: 100ms;
}
.scroll-delay-2 {
    transition-delay: 200ms;
}
.scroll-delay-3 {
    transition-delay: 300ms;
}
.scroll-delay-4 {
    transition-delay: 400ms;
}
</style>
