<script setup lang="ts">
import EmptyState from '@/Components/Dashboard/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

type Match = { id: number; code: string; barcode: string | null; name: string };

const props = defineProps<{
    filters: { q: string };
    matches: Match[];
}>();

const search = useForm({ q: props.filters.q });
const form = useForm({
    product_id: props.matches.length === 1 ? String(props.matches[0].id) : '',
    customer_name: '',
    quantity: '',
    urgent: false,
    notes: '',
});

watch(() => props.matches, (matches) => {
    if (matches.length === 1) {
        form.product_id = String(matches[0].id);
    }
});

const runSearch = () => search.get(route('requests.create'), { preserveState: true });

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('requests.store'));
};
</script>

<template>
    <Head title="Nouvelle demande" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvelle demande</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    Enregistrez une demande lorsqu’un article n’est pas disponible. Les demandes répétées augmentent la fréquence et peuvent passer en priorité urgente.
                </p>
                <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="runSearch">
                    <InputLabel value="Nom, code ou code-barres" />
                    <div class="mt-1 flex gap-3">
                        <TextInput v-model="search.q" class="block w-full" autofocus />
                        <PrimaryButton :disabled="search.processing">Rechercher</PrimaryButton>
                    </div>
                </form>

                <EmptyState v-if="filters.q !== '' && matches.length === 0" message="Aucun article ne correspond à cette recherche." />

                <form v-if="matches.length > 0" class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <label v-for="match in matches" :key="match.id" class="flex gap-3 rounded-md border border-gray-200 p-3">
                        <input v-model="form.product_id" type="radio" :value="String(match.id)" />
                        <span>{{ match.code }} — {{ match.name }}<template v-if="match.barcode"> · {{ match.barcode }}</template></span>
                    </label>
                    <InputError :message="form.errors.product_id" />
                    <div>
                        <InputLabel value="Nom du client (optionnel)" />
                        <TextInput v-model="form.customer_name" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.customer_name" />
                    </div>
                    <div>
                        <InputLabel value="Quantité si elle est connue" />
                        <TextInput v-model="form.quantity" type="number" min="1" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.quantity" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-brand-navy">
                        <input v-model="form.urgent" type="checkbox" />
                        Demande urgente
                    </label>
                    <div>
                        <InputLabel value="Notes" />
                        <TextInput v-model="form.notes" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.notes" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('requests.index')" class="text-sm text-brand-navy underline">Retour</Link>
                        <PrimaryButton :disabled="form.processing || form.product_id === ''">Enregistrer la demande</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
