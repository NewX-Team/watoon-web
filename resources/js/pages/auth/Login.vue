<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const showPassword = ref(false);
</script>

<template>
    <div>
        <Head title="Sign In — FlixRead" />

        <!-- Segmented Toggle -->
        <div
            class="relative mb-8 flex items-center gap-8 border-b border-black/[0.1] pb-2.5"
        >
            <span
                class="relative cursor-default text-sm font-semibold text-[#0E0F11] transition-colors"
            >
                Sign in
                <!-- 2px Cobalt Sliding Hairline Indicator -->
                <span
                    class="absolute right-0 -bottom-[11px] left-0 h-[2px] bg-[#1B44E8]"
                ></span>
            </span>
            <Link
                :href="register()"
                class="relative cursor-pointer text-sm font-medium text-[#6A6A6E] transition-colors hover:text-[#0E0F11]"
            >
                Register
            </Link>
        </div>

        <!-- Dynamic Header Block -->
        <div class="mb-6 space-y-1">
            <div
                class="text-[11px] font-semibold tracking-[0.14em] text-[#1B44E8] uppercase"
            >
                WELCOME
            </div>
            <h1 class="text-3xl font-medium tracking-tight text-[#0E0F11]">
                Welcome back.
            </h1>
            <p class="text-xs leading-relaxed text-[#6A6A6E]">
                Sign in to continue watching and reading.
            </p>
        </div>

        <!-- Status Message -->
        <div
            v-if="status"
            class="mb-4 rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-center text-xs font-medium text-emerald-700"
        >
            {{ status }}
        </div>

        <!-- SIGN IN FORM -->
        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
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
                    v-focus
                    autocomplete="email"
                    placeholder="name@domain.com"
                    class="w-full border-0 border-b border-black/[0.12] bg-transparent py-2 text-sm text-[#0E0F11] placeholder-[#6A6A6E]/40 transition-colors focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Password Input with Toggle -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label
                        class="block text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E] uppercase"
                        for="password"
                    >
                        Password
                    </label>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                        class="w-full border-0 border-b border-black/[0.12] bg-transparent py-2 pr-8 text-sm text-[#0E0F11] placeholder-[#6A6A6E]/40 transition-colors focus:border-[#1B44E8] focus:ring-0 focus:outline-none"
                    />
                    <button
                        aria-label="Toggle password visibility"
                        class="absolute top-2.5 right-1 cursor-pointer text-[#6A6A6E] transition-colors hover:text-[#0E0F11]"
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

            <!-- Options: Remember Me & Forgot Password -->
            <div class="flex items-center justify-between pt-1 text-xs">
                <label
                    class="flex cursor-pointer items-center gap-2 text-[#0E0F11] select-none"
                    for="remember"
                >
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        class="h-[15px] w-[15px] cursor-pointer rounded-[3px] border-black/[0.25] text-[#1B44E8] focus:ring-[#1B44E8] focus:ring-offset-0"
                    />
                    <span>Remember me</span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-[#6A6A6E] transition-colors hover:text-[#0E0F11]"
                >
                    Forgot password?
                </Link>
            </div>

            <!-- Submit Button -->
            <button
                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-[10px] bg-[#0E0F11] px-4 py-3 text-sm font-medium text-[#FAFAF7] shadow-sm transition-colors duration-150 ease-out hover:bg-[#1B44E8] hover:shadow focus:outline-none disabled:cursor-not-allowed disabled:opacity-75"
                type="submit"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="h-4 w-4 text-white" />
                <span>Sign in</span>
            </button>

            <!-- Social Divider -->
            <div class="flex items-center gap-3 pt-1">
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
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border border-black/[0.1] bg-white px-3 py-2.5 text-xs font-medium text-[#0E0F11] transition-colors hover:border-[#0E0F11]"
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
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-[10px] border border-black/[0.1] bg-white px-3 py-2.5 text-xs font-medium text-[#0E0F11] transition-colors hover:border-[#0E0F11]"
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
            <div class="pt-2 text-center text-xs text-[#6A6A6E]">
                New to FlixRead?
                <Link
                    :href="register()"
                    class="ml-1 font-medium text-[#1B44E8] hover:underline"
                >
                    Create an account
                </Link>
            </div>
        </Form>

        <!-- Trust Footnote -->
        <div
            class="pt-8 text-center text-[11px] font-medium tracking-[0.12em] text-[#6A6A6E]/70 uppercase"
        >
            Secured by Midtrans · Terms · Privacy
        </div>
    </div>
</template>
