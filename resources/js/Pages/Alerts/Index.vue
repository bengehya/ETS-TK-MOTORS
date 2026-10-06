<script setup lang="ts">
import EmptyState from '@/Components/Dashboard/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    alerts: { type: string; title: string; message: string; href: string }[];
}>();
</script>

<template>
    <Head title="Alertes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Alertes</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <EmptyState v-if="alerts.length === 0" message="Aucune alerte pour le moment." />
                <ul v-else class="space-y-3">
                    <li v-for="(alert, index) in alerts" :key="`${alert.type}-${index}`" class="rounded-xl border border-brand-gold/40 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-widest text-brand-gold">{{ alert.title }}</p>
                        <p class="mt-1 text-sm text-brand-navy">{{ alert.message }}</p>
                        <Link :href="alert.href" class="mt-2 inline-block text-sm text-brand-navy underline">Ouvrir</Link>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
