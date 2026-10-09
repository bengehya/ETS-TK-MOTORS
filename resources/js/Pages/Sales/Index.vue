<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type SaleRow = {
    id: number;
    reference: string;
    quantity: number;
    unit_sale_price: string;
    line_total: string;
    status_label: string;
    sold_at_label: string | null;
    profit?: string;
    product: { name: string; code: string } | null;
    seller: { name: string; photo_url: string | null } | null;
};

const props = defineProps<{
    sales: { data: SaleRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { q: string; seller_id: string; product_id: string; status: string; date_from: string; date_to: string };
    statuses: { value: string; label: string }[];
    sellers: { id: number; name: string }[];
    canCancel: boolean;
    includeFinance: boolean;
}>();

const form = useForm({
    q: props.filters.q,
    seller_id: props.filters.seller_id,
    status: props.filters.status,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const submit = () => {
    form.get(route('sales.index'), { preserveState: true });
};
</script>

<template>
    <Head title="Ventes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Ventes</h2>
                <Link :href="route('sales.create')" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">
                    Nouvelle vente
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <form class="grid gap-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm md:grid-cols-5" @submit.prevent="submit">
                    <div class="md:col-span-2">
                        <InputLabel value="Référence, article, code ou code-barres" />
                        <TextInput v-model="form.q" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Vendeur" />
                        <select v-model="form.seller_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option v-for="seller in sellers" :key="seller.id" :value="String(seller.id)">{{ seller.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Statut" />
                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Du" />
                        <TextInput v-model="form.date_from" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Au" />
                        <TextInput v-model="form.date_to" type="date" class="mt-1 block w-full" />
                    </div>
                    <div class="flex items-end">
                        <PrimaryButton :disabled="form.processing">Filtrer</PrimaryButton>
                    </div>
                </form>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <p v-if="sales.data.length === 0" class="p-6 text-sm text-gray-600">Aucune vente enregistrée.</p>
                    <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-brand-cream text-left text-brand-navy">
                            <tr>
                                <th class="px-4 py-3">Référence</th>
                                <th class="px-4 py-3">Article</th>
                                <th class="px-4 py-3">Qté</th>
                                <th class="px-4 py-3">Prix</th>
                                <th class="px-4 py-3">Total</th>
                                <th v-if="includeFinance" class="px-4 py-3">Bénéfice brut</th>
                                <th class="px-4 py-3">Vendeur</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sale in sales.data" :key="sale.id">
                                <td class="px-4 py-3">
                                    <Link :href="route('sales.show', sale.id)" class="font-medium text-brand-navy underline">{{ sale.reference }}</Link>
                                </td>
                                <td class="px-4 py-3">{{ sale.product?.name }} <span class="text-gray-500">{{ sale.product?.code }}</span></td>
                                <td class="px-4 py-3">{{ sale.quantity }}</td>
                                <td class="px-4 py-3">{{ sale.unit_sale_price }}</td>
                                <td class="px-4 py-3">{{ sale.line_total }}</td>
                                <td v-if="includeFinance" class="px-4 py-3">{{ sale.profit }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <UserAvatar v-if="sale.seller" :name="sale.seller.name" :photo-url="sale.seller.photo_url" size="sm" decorative />
                                        {{ sale.seller?.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ sale.sold_at_label }}</td>
                                <td class="px-4 py-3">{{ sale.status_label }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationLinks :links="sales.links" />
                <p v-if="canCancel" class="text-xs text-gray-500">L’annulation d’une vente se fait depuis son détail, avec un motif.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
