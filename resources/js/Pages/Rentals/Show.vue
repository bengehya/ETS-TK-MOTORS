<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    rental: {
        id: number;
        label: string;
        amount: string;
        started_on_label: string | null;
        duration_months: number;
        ends_on_label: string | null;
        remaining_months: number;
        status: string;
        status_label: string;
        closure_reason: string | null;
        closed_at_label: string | null;
        recorder: { name: string; photo_url: string | null } | null;
        closer: { name: string; photo_url: string | null } | null;
    };
}>();

const form = useForm({ reason: '' });
const closeRental = () => form.post(route('rentals.close', props.rental.id));
</script>

<template>
    <Head :title="rental.label" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">{{ rental.label }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <div class="rounded-xl border border-brand-gold/40 bg-white p-6 text-sm shadow-sm">
                    <p>Montant : <span class="font-medium text-brand-navy">{{ rental.amount }}</span></p>
                    <p class="mt-2">Début : {{ rental.started_on_label }} · Durée : {{ rental.duration_months }} mois</p>
                    <p class="mt-2">Fin calculée : {{ rental.ends_on_label }}</p>
                    <p class="mt-2">Mois restants : {{ rental.remaining_months }}</p>
                    <p class="mt-2">Statut : {{ rental.status_label }}</p>
                    <p v-if="rental.closure_reason" class="mt-2">Clôture : {{ rental.closure_reason }} ({{ rental.closed_at_label }})</p>
                    <p class="mt-3 inline-flex items-center gap-2">
                        <UserAvatar v-if="rental.recorder" :name="rental.recorder.name" :photo-url="rental.recorder.photo_url" size="sm" decorative />
                        {{ rental.recorder?.name }}
                    </p>
                </div>

                <form v-if="rental.status !== 'closed'" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="closeRental">
                    <InputLabel value="Motif de clôture" />
                    <TextInput v-model="form.reason" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.reason" />
                    <div class="mt-4 flex justify-end">
                        <PrimaryButton :disabled="form.processing">Clôturer la location</PrimaryButton>
                    </div>
                </form>

                <Link :href="route('rentals.index')" class="text-sm text-brand-navy underline">Retour aux locations</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
