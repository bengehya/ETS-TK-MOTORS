<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export type ArrivalRow = {
    id: number;
    quantity: number;
    supplier_reference: string | null;
    status: string;
    status_label: string;
    created_at: string;
    product: { id: number; code: string; name: string } | null;
    location: { name: string } | null;
    recorder: { name: string } | null;
};

const props = defineProps<{
    title: string;
    routeName: string;
    arrivals: {
        data: ArrivalRow[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { total: number };
    };
    filters: { q: string; status: string };
    canCreate: boolean;
    canApprove: boolean;
    statusOptions?: { value: string; label: string }[];
}>();

const q = ref(props.filters.q);
const status = ref(props.filters.status);

const search = () => {
    router.get(route(props.routeName), {
        q: q.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">{{ title }}</h2>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('arrivals.pending')" class="text-sm font-medium text-brand-navy underline">En attente</Link>
                    <Link :href="route('arrivals.history')" class="text-sm font-medium text-brand-navy underline">Historique</Link>
                    <Link v-if="canCreate" :href="route('arrivals.create')">
                        <span class="inline-flex items-center rounded-md bg-brand-navy px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">Nouvel arrivage</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <form class="grid gap-3 rounded-xl border border-brand-gold/40 bg-white p-4 sm:grid-cols-4" @submit.prevent="search">
                    <TextInput v-model="q" type="search" placeholder="Article (nom ou code)" class="sm:col-span-2" />
                    <select v-if="statusOptions" v-model="status" class="rounded-md border-gray-300 text-sm">
                        <option value="">Tous les statuts</option>
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <button type="submit" class="rounded-md bg-brand-navy px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white">Filtrer</button>
                </form>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-brand-cream/60 text-left text-xs uppercase tracking-wide text-brand-navy">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Article</th>
                                    <th class="px-4 py-3">Emplacement</th>
                                    <th class="px-4 py-3">Qté</th>
                                    <th class="px-4 py-3">Statut</th>
                                    <th class="px-4 py-3">Enregistré par</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="arrivals.data.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun arrivage trouvé.</td>
                                </tr>
                                <tr v-for="arrival in arrivals.data" :key="arrival.id" class="hover:bg-brand-cream/40">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ arrival.created_at }}</td>
                                    <td class="px-4 py-3">
                                        <Link :href="route('arrivals.show', arrival.id)" class="text-brand-navy underline">
                                            {{ arrival.product?.code }} — {{ arrival.product?.name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">{{ arrival.location?.name }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ arrival.quantity }}</td>
                                    <td class="px-4 py-3">{{ arrival.status_label }}</td>
                                    <td class="px-4 py-3">{{ arrival.recorder?.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3">
                        <PaginationLinks :links="arrivals.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
