<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

defineProps<{
    name: string;
    company: string;
    slogan: string;
}>();

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Maintenance" />

    <div class="flex min-h-screen items-center justify-center bg-brand-cream px-4 py-10">
        <div class="w-full max-w-md rounded-xl border border-brand-gold/40 bg-white p-6 text-center text-brand-navy shadow-sm sm:p-8">
            <img src="/images/logo.jpg" alt="" class="mx-auto h-16 w-16 rounded-full object-cover" />
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-brand-gold">{{ company }}</p>
            <h1 class="mt-2 font-display text-2xl uppercase tracking-[0.08em]">{{ name }}</h1>
            <p class="mt-4 text-sm text-gray-700">
                L’application est momentanément indisponible pour maintenance.
            </p>
            <p class="mt-2 text-sm text-gray-600">{{ slogan }}</p>
            <button
                type="button"
                class="mt-6 inline-flex items-center rounded-md border border-transparent bg-brand-navy px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-brand-navy-deep"
                @click="logout"
            >
                Déconnexion
            </button>
        </div>
    </div>
</template>
