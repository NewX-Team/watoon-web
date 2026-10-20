<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Film, Feather } from '@lucide/vue';
import { dashboard } from '@/routes';

defineProps<{
    creatorType?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard Kreator Novel',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <div
        class="flex h-full flex-1 flex-col items-center justify-center p-6 sm:p-10 text-center"
    >
        <Head title="Dashboard Kreator Novel" />

        <div
            class="w-full max-w-xl rounded-2xl border border-sidebar-border/70 bg-card/60 backdrop-blur-sm p-8 sm:p-12 shadow-sm dark:border-sidebar-border"
        >
            <!-- Badge / Icon Header -->
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                <BookOpen class="h-8 w-8" />
            </div>

            <div class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/25 bg-amber-500/10 px-3 py-1 text-[11px] font-semibold tracking-wider text-amber-600 dark:text-amber-400 uppercase mb-4">
                <Feather class="h-3.5 w-3.5" />
                <span>Kreator Novel</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-foreground">
                ini dashboard kreator novel
            </h1>

            <p class="mt-2 text-sm text-muted-foreground">
                Role: Kreator Novel (Sastra, Serial Naskah &amp; Cerita)
            </p>

            <p class="mt-4 text-xs leading-relaxed text-muted-foreground/80 max-w-md mx-auto">
                Ruang kerja penerbitan novel serial, manajemen bab berbayar, pembaca setia, dan draf naskah cerita WATOON.
            </p>

            <!-- Quick Switcher to Film Creator Dashboard -->
            <div class="mt-8 border-t border-sidebar-border/60 pt-6">
                <p class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground/70 mb-3">
                    Beralih Tampilan Kreator
                </p>
                <div class="inline-flex items-center rounded-xl border border-sidebar-border bg-muted/40 p-1">
                    <Link
                        :href="dashboard.url({ query: { type: 'film' } })"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-background/50 transition-colors"
                    >
                        <Film class="h-3.5 w-3.5" />
                        Lihat Kreator Film
                    </Link>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-lg bg-background px-3 py-1.5 text-xs font-semibold text-foreground shadow-xs cursor-default"
                    >
                        <BookOpen class="h-3.5 w-3.5 text-amber-500" />
                        Kreator Novel (Aktif)
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
