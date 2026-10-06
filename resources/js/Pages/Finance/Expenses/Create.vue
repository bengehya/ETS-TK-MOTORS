<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    amount: '',
    reason: '',
    spent_on: new Date().toISOString().slice(0, 10),
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('expenses.store'));
};
</script>

<template>
    <Head title="Nouvelle dépense" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvelle dépense</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-600">
                    L’enregistrement ne débite pas la caisse. Le débit a lieu seulement après validation, si le solde est suffisant.
                </p>
                <form class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel for="amount" value="Montant" />
                        <TextInput id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel for="reason" value="Motif" />
                        <TextInput id="reason" v-model="form.reason" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.reason" />
                    </div>
                    <div>
                        <InputLabel for="spent_on" value="Date" />
                        <TextInput id="spent_on" v-model="form.spent_on" type="date" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.spent_on" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('expenses.index')" class="text-sm text-brand-navy underline">Retour</Link>
                        <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
