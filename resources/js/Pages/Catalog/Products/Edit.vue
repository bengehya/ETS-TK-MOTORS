<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductFormFields from '@/Components/ProductFormFields.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    product: {
        id: number;
        code: string;
        barcode: string | null;
        name: string;
        category: string;
        description: string | null;
        sale_price: string;
    };
    categories: string[];
    canUpdatePrice: boolean;
}>();

const form = useForm({
    code: props.product.code,
    barcode: props.product.barcode ?? '',
    name: props.product.name,
    category: props.product.category,
    description: props.product.description ?? '',
    sale_price: props.product.sale_price,
    _method: 'put',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('products.update', props.product.id));
};
</script>

<template>
    <Head title="Modifier l’article" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Modifier l’article</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <ProductFormFields :form="form" :categories="categories" :can-update-price="canUpdatePrice" />

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <Link :href="route('products.show', product.id)" class="text-sm text-brand-navy underline">Annuler</Link>
                        <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
