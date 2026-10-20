<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Film, BookOpen } from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard Kreator',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <div
        class="flex h-full flex-1 flex-col items-center justify-center p-8 text-center"
    >
        <Head title="Dashboard Kreator" />
        <div
            class="max-w-md w-full rounded-2xl border border-sidebar-border/70 p-10 bg-card/60 backdrop-blur-sm dark:border-sidebar-border"
        >
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                Dashboard Kreator
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Pilih jenis dashboard kreator:
            </p>
            <div class="mt-6 flex flex-col gap-3">
                <Link
                    :href="dashboard.url({ query: { type: 'film' } })"
                    class="flex items-center justify-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-4 py-3 text-sm font-medium text-primary hover:bg-primary/15 transition-colors"
                >
                    <Film class="h-4 w-4" />
                    <span>Dashboard Kreator Film</span>
                </Link>
                <Link
                    :href="dashboard.url({ query: { type: 'novel' } })"
                    class="flex items-center justify-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-500/15 transition-colors"
                >
                    <BookOpen class="h-4 w-4" />
                    <span>Dashboard Kreator Novel</span>
                </Link>
            </div>
        </div>
    </div>
</template>
