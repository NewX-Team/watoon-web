<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard Admin',
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
        <Head title="Dashboard Admin" />
        <div
            class="rounded-2xl border border-sidebar-border/70 p-12 dark:border-sidebar-border"
        >
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                ini dashboard admin
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Role: Administrator
            </p>
        </div>
    </div>
</template>
