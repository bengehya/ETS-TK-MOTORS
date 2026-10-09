<script setup lang="ts">
import EmptyState from '@/Components/Dashboard/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    suggestion: {
        available: boolean;
        automatic: boolean;
        period_days: number;
        rate_percent: number;
        revenue: string | null;
        daily_average: string | null;
        suggested_amount: string | null;
        message: string;
    };
}>();
</script>

<template>
    <Head title="Épargne suggérée" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Épargne suggérée</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <EmptyState v-if="!suggestion.available" :message="suggestion.message" />
                    <div v-else>
                        <p class="text-sm text-gray-600">
                            Moyenne journalière du chiffre d’affaires sur {{ suggestion.period_days }} jours :
                            <span class="font-medium text-brand-navy">{{ suggestion.daily_average }}</span>
                        </p>
                        <p class="mt-4 font-display text-2xl uppercase tracking-[0.06em] text-brand-navy">
                            Épargne suggérée : {{ suggestion.suggested_amount }}
                        </p>
                        <p class="mt-2 text-sm text-gray-600">
                            Soit {{ suggestion.rate_percent }} % de cette moyenne. Le chiffre d’affaires de la période est {{ suggestion.revenue }}.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
