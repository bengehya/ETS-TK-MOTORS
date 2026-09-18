<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductFormFields from '@/Components/ProductFormFields.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    categories: string[];
    canUpdatePrice: boolean;
}>();

const form = useForm({
    code: '',
    barcode: '',
    name: '',
    category: '',
    description: '',
    sale_price: '',
});

const submit = () => {
    form.post(route('products.store'));
};
</script>

<template>
    <Head title="Nouvel article" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvel article</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <ProductFormFields :form="form" :categories="categories" :can-update-price="canUpdatePrice" />

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <Link :href="route('products.index')" class="text-sm text-brand-navy underline">Annuler</Link>
                        <PrimaryButton :disabled="form.processing">Créer l’article</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
