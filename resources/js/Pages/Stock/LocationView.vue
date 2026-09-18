<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Row = {
    inventory: { quantity: number; location: { id: number; name: string; is_sellable: boolean } };
    product: { id: number; code: string; name: string; category: string; sale_price: string; is_active: boolean };
};

const props = defineProps<{
    location: { id: number; type: string; name: string; is_sellable: boolean };
    rows: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { total: number };
    };
    filters: { q: string; category: string; status: string };
    categories: string[];
    canMutateStock: boolean;
    totalQuantity: number;
}>();

const q = ref(props.filters.q);
const category = ref(props.filters.category);
const status = ref(props.filters.status);

const routeName = computed(() => (props.location.type === 'BOUTIQUE' ? 'stocks.boutique' : 'stocks.depot'));

const search = () => {
    router.get(route(routeName.value), {
        q: q.value || undefined,
        category: category.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="location.name" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">{{ location.name }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ location.is_sellable ? 'Stock disponible à la vente.' : 'Stock non disponible à la vente en boutique.' }}
                    Total : {{ totalQuantity }}
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <p v-if="canMutateStock" class="text-sm text-gray-600">
                    Pour une entrée, un transfert dépôt → boutique ou un ajustement, ouvrez la fiche de l’article.
                </p>

                <form class="grid gap-3 rounded-xl border border-brand-gold/40 bg-white p-4 sm:grid-cols-4" @submit.prevent="search">
                    <TextInput v-model="q" type="search" placeholder="Nom ou code" class="sm:col-span-2" />
                    <select v-model="category" class="rounded-md border-gray-300 text-sm">
                        <option value="">Toutes les catégories</option>
                        <option v-for="item in categories" :key="item" :value="item">{{ item }}</option>
                    </select>
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
                                    <th class="px-4 py-3">Catégorie</th>
                                    <th class="px-4 py-3">Prix</th>
                                    <th class="px-4 py-3">Quantité</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="rows.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun article dans cet emplacement.</td>
                                </tr>
                                <tr v-for="row in rows.data" :key="row.product.id">
                                    <td class="px-4 py-3 font-mono text-xs">
                                        <Link :href="route('products.show', row.product.id)" class="text-brand-navy underline">{{ row.product.code }}</Link>
                                    </td>
                                    <td class="px-4 py-3">{{ row.product.name }}</td>
                                    <td class="px-4 py-3">{{ row.product.category }}</td>
                                    <td class="px-4 py-3">{{ row.product.sale_price }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ row.inventory.quantity }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3">
                        <PaginationLinks :links="rows.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
