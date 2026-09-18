<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type ProductRow = {
    id: number;
    code: string;
    name: string;
    category: string;
    sale_price: string;
    is_active: boolean;
    stocks: {
        sellable_quantity: number;
        depot_quantity: number;
    };
};

const props = defineProps<{
    products: {
        data: ProductRow[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
    };
    filters: { q: string; category: string; status: string };
    categories: string[];
    canManage: boolean;
}>();

const q = ref(props.filters.q);
const category = ref(props.filters.category);
const status = ref(props.filters.status);

const search = () => {
    router.get(route('products.index'), {
        q: q.value || undefined,
        category: category.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Articles" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Articles</h2>
                <Link v-if="canManage" :href="route('products.create')">
                    <PrimaryButton type="button">Nouvel article</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <form class="grid gap-3 rounded-xl border border-brand-gold/40 bg-white p-4 sm:grid-cols-4" @submit.prevent="search">
                    <TextInput v-model="q" type="search" placeholder="Nom, code ou code-barres" class="sm:col-span-2" />
                    <select v-model="category" class="rounded-md border-gray-300 text-sm">
                        <option value="">Toutes les catégories</option>
                        <option v-for="item in categories" :key="item" :value="item">{{ item }}</option>
                    </select>
                    <div class="flex gap-2">
                        <select v-model="status" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Tous les états</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Désactivés</option>
                        </select>
                        <PrimaryButton>Filtrer</PrimaryButton>
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
                                    <th class="px-4 py-3">Boutique</th>
                                    <th class="px-4 py-3">Dépôt</th>
                                    <th class="px-4 py-3">État</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="products.data.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucun article trouvé.</td>
                                </tr>
                                <tr v-for="product in products.data" :key="product.id" class="hover:bg-brand-cream/40">
                                    <td class="px-4 py-3 font-mono text-xs">
                                        <Link :href="route('products.show', product.id)" class="text-brand-navy underline">
                                            {{ product.code }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-brand-navy">{{ product.name }}</td>
                                    <td class="px-4 py-3">{{ product.category }}</td>
                                    <td class="px-4 py-3">{{ product.sale_price }}</td>
                                    <td class="px-4 py-3">{{ product.stocks.sellable_quantity }}</td>
                                    <td class="px-4 py-3">{{ product.stocks.depot_quantity }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="product.is_active ? 'text-green-700' : 'text-gray-500'">
                                            {{ product.is_active ? 'Actif' : 'Désactivé' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3">
                        <PaginationLinks :links="products.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
