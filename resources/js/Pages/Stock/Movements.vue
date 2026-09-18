<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type Movement = {
    id: number;
    type: string;
    type_label: string;
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    notes: string | null;
    created_at: string;
    product: { id: number; code: string; name: string } | null;
    location: { name: string; type: string } | null;
    user: { name: string } | null;
};

const props = defineProps<{
    movements: {
        data: Movement[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; type: string; location_id: number | string | null };
    types: { value: string; label: string }[];
    locations: { id: number; name: string }[];
}>();

const q = ref(props.filters.q);
const type = ref(props.filters.type);
const locationId = ref(props.filters.location_id ? String(props.filters.location_id) : '');

const search = () => {
    router.get(route('stocks.movements'), {
        q: q.value || undefined,
        type: type.value || undefined,
        location_id: locationId.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Mouvements de stock" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Mouvements</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <form class="grid gap-3 rounded-xl border border-brand-gold/40 bg-white p-4 lg:grid-cols-4" @submit.prevent="search">
                    <TextInput v-model="q" type="search" placeholder="Article (nom ou code)" />
                    <select v-model="type" class="rounded-md border-gray-300 text-sm">
                        <option value="">Tous les types</option>
                        <option v-for="item in types" :key="item.value" :value="item.value">{{ item.label }}</option>
                    </select>
                    <select v-model="locationId" class="rounded-md border-gray-300 text-sm">
                        <option value="">Tous les emplacements</option>
                        <option v-for="location in locations" :key="location.id" :value="String(location.id)">{{ location.name }}</option>
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
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Qté</th>
                                    <th class="px-4 py-3">Avant → après</th>
                                    <th class="px-4 py-3">Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="movements.data.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucun mouvement enregistré.</td>
                                </tr>
                                <tr v-for="movement in movements.data" :key="movement.id">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ movement.created_at }}</td>
                                    <td class="px-4 py-3">
                                        <Link v-if="movement.product" :href="route('products.show', movement.product.id)" class="text-brand-navy underline">
                                            {{ movement.product.code }} — {{ movement.product.name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">{{ movement.location?.name }}</td>
                                    <td class="px-4 py-3">{{ movement.type_label }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ movement.quantity }}</td>
                                    <td class="px-4 py-3">{{ movement.quantity_before }} → {{ movement.quantity_after }}</td>
                                    <td class="px-4 py-3">{{ movement.user?.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3">
                        <PaginationLinks :links="movements.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
