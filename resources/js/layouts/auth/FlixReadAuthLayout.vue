<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { home, login, register } from '@/routes';

const mobileMenuOpen = ref(false);

const navItems = [
    { id: 'features', label: 'Film' },
    { id: 'novel', label: 'Novel', badge: 'SOON' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'faq', label: 'FAQ' },
];
</script>

<template>
    <div
        class="flex min-h-screen animate-in flex-col justify-between bg-[#FAFAF7] font-sans text-[#0E0F11] antialiased duration-300 fade-in selection:bg-[#dee0ff] selection:text-[#00115a]"
    >
        <Head>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link
                rel="preconnect"
                href="https://fonts.gstatic.com"
                crossorigin=""
            />
            <link
                href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap"
                rel="stylesheet"
            />
        </Head>

        <!-- FLOATING TOP HEADER -->
        <header
            class="fixed inset-x-4 top-[16px] z-50 mx-auto max-w-[1200px] rounded-[16px] border border-[#0E0F11]/[0.08] bg-[#FAFAF7]/85 shadow-[0_1px_3px_rgba(0,0,0,0.03)] backdrop-blur-md transition-all duration-300 md:inset-x-8 md:top-[20px]"
            id="floating-header"
        >
            <div class="flex h-16 items-center justify-between px-6">
                <!-- Left: WATOON wordmark with monoline film+book icon matching Welcome -->
                <Link
                    class="group flex items-center gap-2.5 text-[#0E0F11] transition-all hover:opacity-85"
                    :href="home.url()"
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
                </Link>

                <!-- Center: Primary Nav Links with Animated Underline and proper URLs -->
                <nav
                    class="hidden items-center gap-8 text-[14px] font-medium md:flex"
                >
                    <Link
                        v-for="item in navItems"
                        :key="item.id"
                        :href="home.url() + '#' + item.id"
                        class="group relative inline-flex cursor-pointer items-center gap-1.5 py-1 text-[#6A6A6E] transition-colors hover:text-[#0E0F11]"
                    >
                        <span>{{ item.label }}</span>
                        <span
                            v-if="item.badge"
                            class="rounded-[4px] bg-[#1B44E8]/10 px-1.5 py-0.5 text-[9px] font-semibold tracking-widest text-[#1B44E8] uppercase transition-colors duration-200 group-hover:bg-[#1B44E8] group-hover:text-white"
                        >
                            {{ item.badge }}
                        </span>
                        <!-- Animated hover underline -->
                        <span
                            class="absolute right-0 -bottom-[6px] left-0 h-[2px] origin-center scale-x-0 rounded-full bg-[#1B44E8] opacity-0 transition-all duration-300 group-hover:scale-x-100 group-hover:opacity-100"
                        ></span>
                    </Link>
                </nav>

                <!-- Right: Account Actions -->
                <div class="flex items-center gap-4 sm:gap-5">
                    <Link
                        :href="login()"
                        class="hidden text-[14px] font-medium transition-colors sm:inline-block"
                        :class="
                            $page.component === 'auth/Login'
                                ? 'font-semibold text-[#1B44E8]'
                                : 'text-[#0E0F11] hover:text-[#1B44E8]'
                        "
                    >
                        Sign in
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-[10px] px-[18px] py-[9px] text-[14px] font-medium shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md active:translate-y-0"
                        :class="
                            $page.component === 'auth/Register'
                                ? 'bg-[#1B44E8] text-white shadow-md'
                                : 'bg-[#0E0F11] text-[#FAFAF7] hover:bg-[#1B44E8]'
                        "
                    >
                        Get started
                    </Link>

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

            <!-- Mobile Dropdown Nav -->
            <div
                v-if="mobileMenuOpen"
                class="flex animate-in flex-col gap-3 border-t border-black/[0.06] px-6 pt-2 pb-6 duration-200 fade-in slide-in-from-top-2 md:hidden"
            >
                <Link
                    v-for="item in navItems"
                    :key="item.id"
                    :href="home.url() + '#' + item.id"
                    class="flex items-center justify-between py-1.5 text-left text-[14px] font-medium text-[#6A6A6E] transition-colors hover:text-[#1B44E8]"
                    @click="mobileMenuOpen = false"
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.badge"
                        class="rounded-[3px] bg-[#1B44E8]/10 px-1.5 py-0.5 text-[9px] font-semibold tracking-widest text-[#1B44E8] uppercase"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
                <div
                    class="flex items-center justify-between border-t border-black/[0.06] pt-3"
                >
                    <Link
                        :href="login()"
                        class="text-[14px] font-medium"
                        :class="
                            $page.component === 'auth/Login'
                                ? 'font-semibold text-[#1B44E8]'
                                : 'text-[#0E0F11]'
                        "
                        >Sign in</Link
                    >
                    <Link
                        :href="register()"
                        class="text-[14px] font-medium"
                        :class="
                            $page.component === 'auth/Register'
                                ? 'font-semibold text-[#1B44E8]'
                                : 'text-[#1B44E8]'
                        "
                        >Register →</Link
                    >
                </div>
            </div>
        </header>

        <!-- MAIN WRAPPER: items-start guarantees Left Frame top is ALWAYS static -->
        <main
            class="flex w-full flex-1 flex-col justify-start px-4 pt-24 pb-12 md:px-8 md:pt-28"
        >
            <div
                class="mx-auto grid w-full max-w-[1200px] grid-cols-1 items-start gap-8 md:grid-cols-12 lg:gap-12"
            >
                <!-- LEFT PANEL: Persistent, strictly fixed at top-28, never jumps/shifts -->
                <div
                    class="relative hidden h-[620px] flex-col justify-between overflow-hidden rounded-[20px] border border-black/[0.08] bg-[#0E0F11] p-10 shadow-[0_12px_36px_rgba(14,15,17,0.08)] select-none md:sticky md:top-28 md:col-span-6 md:flex lg:col-span-6"
                >
                    <img
                        alt="Quiet film still reading by warm lamp"
                        class="pointer-events-none absolute inset-0 h-full w-full object-cover object-center opacity-80"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1UzI6je7Hx9Iylyxic_BSFaelmZTkDaThlz8iHc_qtCgqFawhWuCa63XhEXV-y98rtO3K9LuRh8cS9h6hQbd7hRL1_NTTWFdI2Mu1yqPlDCK-YD0Xhv17rbR_94QCiPkVMgJyWog2bOahmflr0ttQFNy2Bgg_MppHFWzsR-1WwDXuu1B-yXFqf7qQiMPEHS8Z5eQQ7sBpn_at_8w52sShbB8KL9-uBOS5_fo7MgSYic9CE-KvUnp4RRMHM"
                    />
                    <div
                        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#0E0F11]/95 via-[#0E0F11]/45 to-[#0E0F11]/30"
                    ></div>

                    <!-- Top: Monoline tag -->
                    <div
                        class="relative z-10 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-[4px] border border-white/15 bg-white/10 px-3 py-1 font-mono text-[10px] tracking-[0.16em] text-white/80 uppercase backdrop-blur-md"
                            >
                                Watoon Original
                            </span>
                        </div>
                        <div class="font-mono text-[11px] text-white/50">
                            EST. 2024
                        </div>
                    </div>

                    <!-- Bottom Area: Pull-Quote & Indicator -->
                    <div class="relative z-10 space-y-4">
                        <div class="space-y-2">
                            <p
                                class="font-serif text-[28px] leading-snug font-normal tracking-tight text-white lg:text-[32px]"
                            >
                                “Films tonight. A chapter before bed.”
                            </p>
                            <p
                                class="text-xs font-normal tracking-wide text-white/70"
                            >
                                One calm subscription uniting cinema-grade
                                streaming and serialized literature.
                            </p>
                        </div>
                        <div
                            class="flex items-center justify-between border-t border-white/15 pt-2"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-[2px] w-6 rounded-full bg-white"
                                ></div>
                                <div
                                    class="h-[2px] w-6 rounded-full bg-white/30"
                                ></div>
                                <div
                                    class="h-[2px] w-6 rounded-full bg-white/30"
                                ></div>
                            </div>
                            <span
                                class="font-mono text-[10px] tracking-widest text-white/50 uppercase"
                                >Story Vol. 01</span
                            >
                        </div>
                    </div>
                </div>

                <!-- RIGHT PANEL: Dedicated Authentication Form Slot (Only this frame changes/extends) -->
                <div
                    class="flex w-full flex-col justify-start px-2 py-2 sm:px-6 md:col-span-6 lg:col-span-6"
                >
                    <div
                        class="mx-auto w-full max-w-[420px] animate-in transition-all duration-300 fade-in"
                    >
                        <slot />
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
