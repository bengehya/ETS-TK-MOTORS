<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    message: string;
}>();
</script>

<template>
    <Head title="Opération interrompue" />

    <div class="flex min-h-screen items-center justify-center bg-brand-cream px-4">
        <div class="w-full max-w-md rounded-xl border border-brand-gold/40 bg-white p-6 text-brand-navy shadow-sm">
            <h1 class="font-display text-xl uppercase tracking-[0.08em]">{{ message }}</h1>
            <Link :href="route('dashboard')" class="mt-4 inline-block text-sm underline">Retour</Link>
        </div>
    </div>
</template>
