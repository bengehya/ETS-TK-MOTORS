<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type ProductStock = {
    id: number;
    code: string;
    name: string;
    category: string;
    is_active: boolean;
    stocks: { sellable_quantity: number; depot_quantity: number };
};

const props = defineProps<{
    products: ProductStock[];
    filters: { q: string; status: string };
    totals: { boutique: number; depot: number };
    canMutateStock: boolean;
}>();

const q = ref(props.filters.q);
const status = ref(props.filters.status);

const search = () => {
    router.get(route('stocks.overview'), {
        q: q.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Stocks" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Stocks</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <div class="grid gap-4 sm:grid-cols-2">
                    <Link :href="route('stocks.boutique')" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">Boutique</h3>
                        <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ totals.boutique }}</p>
                        <p class="mt-1 text-xs text-gray-500">Disponible à la vente</p>
                    </Link>
                    <Link :href="route('stocks.depot')" class="rounded-xl border border-brand-navy/20 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Dépôt</h3>
                        <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ totals.depot }}</p>
                        <p class="mt-1 text-xs text-gray-500">Non disponible à la vente en boutique</p>
                    </Link>
                </div>

                <p v-if="canMutateStock" class="text-sm text-gray-600">
                    Le dépôt n’est jamais vendable en boutique. Un réassort se fait uniquement par transfert, depuis la fiche article.
                </p>

                <form class="grid gap-3 rounded-xl border border-brand-gold/40 bg-white p-4 sm:grid-cols-3" @submit.prevent="search">
                    <TextInput v-model="q" type="search" placeholder="Nom ou code" class="sm:col-span-2" />
                    <div class="flex gap-2">
                        <select v-model="status" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Désactivés</option>
                        </select>
                        <button type="submit" class="rounded-md bg-brand-navy px-4 text-xs font-semibold uppercase tracking-widest text-white">Filtrer</button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-brand-cream/60 text-left text-xs uppercase tracking-wide text-brand-navy">
                                <tr>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Article</th>
                                    <th class="px-4 py-3">Boutique</th>
                                    <th class="px-4 py-3">Dépôt</th>
                                    <th class="px-4 py-3">État</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="products.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun stock à afficher.</td>
                                </tr>
                                <tr v-for="product in products" :key="product.id">
                                    <td class="px-4 py-3 font-mono text-xs">
                                        <Link :href="route('products.show', product.id)" class="text-brand-navy underline">{{ product.code }}</Link>
                                    </td>
                                    <td class="px-4 py-3">{{ product.name }}</td>
                                    <td class="px-4 py-3">{{ product.stocks.sellable_quantity }}</td>
                                    <td class="px-4 py-3">{{ product.stocks.depot_quantity }}</td>
                                    <td class="px-4 py-3">{{ product.is_active ? 'Actif' : 'Désactivé' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
