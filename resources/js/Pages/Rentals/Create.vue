<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    label: '',
    amount: '',
    started_on: new Date().toISOString().slice(0, 10),
    duration_months: '1',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('rentals.store'));
};
</script>

<template>
    <Head title="Nouvelle location" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvelle location</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-600">La date de fin est calculée à partir de la date de début et de la durée en mois.</p>
                <form class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Libellé" />
                        <TextInput v-model="form.label" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.label" />
                    </div>
                    <div>
                        <InputLabel value="Montant" />
                        <TextInput v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Date de début" />
                        <TextInput v-model="form.started_on" type="date" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.started_on" />
                    </div>
                    <div>
                        <InputLabel value="Durée (mois)" />
                        <TextInput v-model="form.duration_months" type="number" min="1" max="120" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.duration_months" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('rentals.index')" class="text-sm text-brand-navy underline">Retour</Link>
                        <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
