<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules?: string;
}>();

const showPassword = ref(false);
const selectedRole = ref<'viewer' | 'film' | 'novel'>('viewer');
</script>

<template>
    <div>
        <Head title="Create Your Account — FlixRead" />

        <!-- Segmented Toggle -->
        <div
            class="relative mb-6 flex items-center gap-8 border-b border-black/[0.1] pb-2.5"
        >
            <Link
                :href="login()"
                class="relative cursor-pointer text-sm font-medium text-[#6A6A6E] transition-colors hover:text-[#0E0F11]"
            >
                Sign in
            </Link>
            <span
                class="relative cursor-default text-sm font-semibold text-[#0E0F11] transition-colors"
            >
                Register
                <!-- 2px Cobalt Sliding Hairline Indicator -->
                <span
                    class="absolute right-0 -bottom-[11px] left-0 h-[2px] bg-[#1B44E8]"
                ></span>
            </span>
        </div>

        <!-- Dynamic Header Block -->
        <div class="mb-6 space-y-1">
            <div
                class="text-[11px] font-semibold tracking-[0.14em] text-[#1B44E8] uppercase"
            >
                GET STARTED
            </div>
            <h1 class="text-3xl font-medium tracking-tight text-[#0E0F11]">
                Create your account.
            </h1>
            <p class="text-xs leading-relaxed text-[#6A6A6E]">
                One account for films and serialized novels.
            </p>
        </div>

        <!-- REGISTER FORM -->
        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <!-- Name Input -->
            <div class="space-y-1">
                <label
                    class="block text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                    for="name"
                >
                    Full Name (Nama Lengkap)
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    required
                    v-focus
                    autocomplete="name"
                    placeholder="Elena Rostova"
                    class="w-full border-0 border-b border-black/[0.12] bg-transparent py-1.5 text-sm text-[#0E0F11] placeholder-[#6A6A6E]/40 transition-colors focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                />
                <InputError :message="errors.name" />
            </div>

            <!-- Email Input -->
            <div class="space-y-1">
                <label
                    class="block text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                    for="email"
                >
                    Email
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="elena@cinematheque.org"
                    class="w-full border-0 border-b border-black/[0.12] bg-transparent py-1.5 text-sm text-[#0E0F11] placeholder-[#6A6A6E]/40 transition-colors focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Password Input with Toggle -->
            <div class="space-y-1">
                <label
                    class="block text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                    for="password"
                >
                    Password
                </label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                        class="w-full border-0 border-b border-black/[0.12] bg-transparent py-1.5 pr-8 text-sm text-[#0E0F11] placeholder-[#6A6A6E]/40 transition-colors focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                    />
                    <button
                        aria-label="Toggle password visibility"
                        class="absolute top-2 right-1 cursor-pointer text-[#6A6A6E] transition-colors hover:text-[#0E0F11]"
                        @click="showPassword = !showPassword"
                        type="button"
                    >
                        <svg
                            v-if="!showPassword"
                            fill="none"
                            height="15"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            viewBox="0 0 24 24"
                            width="15"
                        >
                            <path
                                d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"
                            ></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg
                            v-else
                            fill="none"
                            height="15"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.7"
                            viewBox="0 0 24 24"
                            width="15"
                        >
                            <path
                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"
                            ></path>
                            <line x1="1" x2="23" y1="1" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Confirm Password Input -->
            <div class="space-y-1">
                <label
                    class="block text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                    for="password_confirmation"
                >
                    Confirm Password
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="Re-enter password"
                    class="w-full border-0 border-b border-black/[0.12] bg-transparent py-1.5 text-sm text-[#0E0F11] placeholder-[#6A6A6E]/40 transition-colors focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Role Selection (10px rounded chips) -->
            <div class="space-y-1.5 pt-1">
                <label
                    class="block text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                >
                    Select Primary Role (Pilih Peran)
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        @click="selectedRole = 'viewer'"
                        class="cursor-pointer rounded-[10px] border px-2 py-1.5 text-center text-xs font-medium transition-all"
                        :class="
                            selectedRole === 'viewer'
                                ? 'border-[#1B44E8] bg-white font-semibold text-[#1B44E8] shadow-xs'
                                : 'border-black/[0.1] bg-white text-[#6A6A6E] hover:text-[#0E0F11]'
                        "
                    >
                        Penonton
                    </button>
                    <button
                        type="button"
                        @click="selectedRole = 'film'"
                        class="cursor-pointer rounded-[10px] border px-2 py-1.5 text-center text-xs font-medium transition-all"
                        :class="
                            selectedRole === 'film'
                                ? 'border-[#1B44E8] bg-white font-semibold text-[#1B44E8] shadow-xs'
                                : 'border-black/[0.1] bg-white text-[#6A6A6E] hover:text-[#0E0F11]'
                        "
                    >
                        Film Creator
                    </button>
                    <button
                        type="button"
                        @click="selectedRole = 'novel'"
                        class="cursor-pointer rounded-[10px] border px-2 py-1.5 text-center text-xs font-medium transition-all"
                        :class="
                            selectedRole === 'novel'
                                ? 'border-[#1B44E8] bg-white font-semibold text-[#1B44E8] shadow-xs'
                                : 'border-black/[0.1] bg-white text-[#6A6A6E] hover:text-[#0E0F11]'
                        "
                    >
                        Novel Creator
                    </button>
                </div>
                <div
                    v-if="selectedRole === 'novel'"
                    class="pt-0.5 text-[11px] leading-tight text-[#1B44E8]"
                >
                    ✦ Novels are coming soon — you'll join the private creator
                    waitlist.
                </div>
            </div>

            <!-- Terms and Privacy Checkbox -->
            <div class="flex items-start gap-2 pt-1 text-xs">
                <input
                    id="reg-terms"
                    type="checkbox"
                    required
                    class="mt-0.5 h-[15px] w-[15px] cursor-pointer rounded-[3px] border-black/[0.25] text-[#1B44E8] focus:ring-[#1B44E8] focus:ring-offset-0"
                />
                <label
                    class="text-[12px] leading-tight text-[#6A6A6E] select-none"
                    for="reg-terms"
                >
                    I agree to the
                    <a
                        class="text-[#0E0F11] underline hover:text-[#1B44E8]"
                        href="#"
                        >Terms of Service</a
                    >
                    and
                    <a
                        class="text-[#0E0F11] underline hover:text-[#1B44E8]"
                        href="#"
                        >Privacy Policy</a
                    >
                </label>
            </div>

            <!-- Submit Button -->
            <button
                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-[10px] bg-[#0E0F11] px-4 py-3 text-sm font-medium text-[#FAFAF7] shadow-sm transition-colors duration-150 ease-out hover:bg-[#1B44E8] hover:shadow focus:outline-none disabled:cursor-not-allowed disabled:opacity-75"
                type="submit"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" class="h-4 w-4 text-white" />
                <span>Create account</span>
            </button>

            <!-- Social Divider -->
            <div class="flex items-center gap-3 pt-0.5">
                <div class="h-[1px] flex-1 bg-black/[0.1]"></div>
                <span
                    class="text-[10px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                    >or continue with</span
                >
                <div class="h-[1px] flex-1 bg-black/[0.1]"></div>
            </div>

            <!-- Social Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <button
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border border-black/[0.1] bg-white px-3 py-2 text-xs font-medium text-[#0E0F11] transition-colors hover:border-[#0E0F11]"
                    type="button"
                >
                    <svg
                        fill="currentColor"
                        height="14"
                        viewBox="0 0 24 24"
                        width="14"
                    >
                        <path
                            d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"
                        ></path>
                    </svg>
                    Google
                </button>
                <button
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border border-black/[0.1] bg-white px-3 py-2 text-xs font-medium text-[#0E0F11] transition-colors hover:border-[#0E0F11]"
                    type="button"
                >
                    <svg
                        fill="currentColor"
                        height="14"
                        viewBox="0 0 24 24"
                        width="14"
                    >
                        <path
                            d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 0.92-2.85-.9.04-1.99.6-2.63 1.35-.57.66-.99 1.72-.86 2.73 1 .08 1.95-.48 2.57-1.23"
                        ></path>
                    </svg>
                    Apple
                </button>
            </div>

            <!-- Footer prompt -->
            <div class="pt-1 text-center text-xs text-[#6A6A6E]">
                Already have an account?
                <Link
                    :href="login()"
                    class="ml-1 font-medium text-[#1B44E8] hover:underline"
                >
                    Sign in
                </Link>
            </div>
        </Form>

        <!-- Trust Footnote -->
        <div
            class="pt-6 text-center text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E]/70 uppercase"
        >
            Secured by Midtrans · Terms · Privacy
        </div>
    </div>
</template>
