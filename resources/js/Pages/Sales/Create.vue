<script setup lang="ts">
import EmptyState from '@/Components/Dashboard/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

type Match = {
    id: number;
    code: string;
    barcode: string | null;
    name: string;
    sale_price: string;
    boutique_quantity: number;
    purchase_price?: string | null;
};

const props = defineProps<{
    filters: { q: string };
    matches: Match[];
    includeFinance: boolean;
}>();

const search = useForm({ q: props.filters.q });
const form = useForm({
    product_id: props.matches.length === 1 ? String(props.matches[0].id) : '',
    quantity: '1',
});

const selected = computed(() => props.matches.find((match) => String(match.id) === form.product_id) ?? null);

watch(() => props.matches, (matches) => {
    if (matches.length === 1) {
        form.product_id = String(matches[0].id);
    }
});

const runSearch = () => {
    search.get(route('sales.search'), { preserveState: true });
};

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('sales.store'));
};
</script>

<template>
    <Head title="Nouvelle vente" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvelle vente</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    La vente sort le stock de la boutique uniquement. Le prix enregistré est le prix de vente du catalogue au moment de la vente.
                    Un lecteur de code-barres peut saisir directement dans le champ de recherche.
                </p>

                <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="runSearch">
                    <InputLabel for="q" value="Nom, code ou code-barres" />
                    <div class="mt-1 flex gap-3">
                        <TextInput id="q" v-model="search.q" class="block w-full" autofocus />
                        <PrimaryButton :disabled="search.processing">Rechercher</PrimaryButton>
                    </div>
                </form>

                <EmptyState v-if="filters.q !== '' && matches.length === 0" message="Aucun article ne correspond à cette recherche." />

                <form v-if="matches.length > 0" class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <fieldset class="space-y-3">
                        <legend class="text-sm font-medium text-brand-navy">Article</legend>
                        <label v-for="match in matches" :key="match.id" class="flex cursor-pointer gap-3 rounded-md border border-gray-200 p-3">
                            <input v-model="form.product_id" type="radio" :value="String(match.id)" class="mt-1" />
                            <span>
                                <span class="block font-medium text-brand-navy">{{ match.name }}</span>
                                <span class="block text-sm text-gray-600">
                                    {{ match.code }}
                                    <template v-if="match.barcode"> · {{ match.barcode }}</template>
                                    · Prix {{ match.sale_price }}
                                    · Boutique {{ match.boutique_quantity }}
                                    <template v-if="includeFinance && match.purchase_price"> · Achat {{ match.purchase_price }}</template>
                                </span>
                            </span>
                        </label>
                    </fieldset>
                    <InputError :message="form.errors.product_id" />

                    <div>
                        <InputLabel for="quantity" value="Quantité" />
                        <TextInput id="quantity" v-model="form.quantity" type="number" min="1" class="mt-1 block w-full" required />
                        <p v-if="selected" class="mt-1 text-xs text-gray-500">Stock boutique disponible : {{ selected.boutique_quantity }}</p>
                        <InputError class="mt-2" :message="form.errors.quantity" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="route('sales.index')" class="text-sm text-brand-navy underline">Retour</Link>
                        <PrimaryButton :disabled="form.processing || form.product_id === ''">Enregistrer la vente</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
