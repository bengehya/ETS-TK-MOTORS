<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    products: { id: number; code: string; name: string }[];
    locations: { id: number; name: string; type: string }[];
}>();

const form = useForm({
    product_id: props.products[0] ? String(props.products[0].id) : '',
    location_id: props.locations[0] ? String(props.locations[0].id) : '',
    quantity: '1',
    supplier_reference: '',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('arrivals.store'));
};
</script>

<template>
    <Head title="Nouvel arrivage" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvel arrivage</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-600">
                    L’enregistrement ne change pas le stock. Un patron doit valider l’arrivage pour augmenter l’emplacement choisi.
                </p>

                <form v-if="products.length > 0" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Article" />
                            <select v-model="form.product_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                <option v-for="product in products" :key="product.id" :value="String(product.id)">
                                    {{ product.code }} — {{ product.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.product_id" />
                        </div>
                        <div>
                            <InputLabel value="Emplacement" />
                            <select v-model="form.location_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                <option v-for="location in locations" :key="location.id" :value="String(location.id)">
                                    {{ location.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.location_id" />
                        </div>
                        <div>
                            <InputLabel value="Quantité" />
                            <TextInput v-model="form.quantity" type="number" min="1" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.quantity" />
                        </div>
                        <div>
                            <InputLabel value="Référence / fournisseur (optionnel)" />
                            <TextInput v-model="form.supplier_reference" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.supplier_reference" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <Link :href="route('arrivals.index')" class="text-sm text-brand-navy underline">Annuler</Link>
                        <PrimaryButton :disabled="form.processing">Enregistrer l’arrivage</PrimaryButton>
                    </div>
                </form>

                <div v-else class="rounded-xl border border-brand-gold/40 bg-white p-6 text-sm text-gray-600">
                    Aucun article actif n’est disponible. Un patron doit d’abord créer un article.
                    <div class="mt-3">
                        <Link :href="route('arrivals.index')" class="text-brand-navy underline">Retour aux arrivages</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
