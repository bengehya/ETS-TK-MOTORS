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
    customerRequest: {
        id: number;
        customer_name: string | null;
        quantity: number | null;
        priority_label: string;
        frequency: number;
        status: string;
        status_label: string;
        notes: string | null;
        closure_note: string | null;
        requested_at_label: string | null;
        closed_at_label: string | null;
        product: { name: string; code: string } | null;
        recorder: { name: string; photo_url: string | null } | null;
        closer: { name: string; photo_url: string | null } | null;
    };
}>();

const fulfillForm = useForm({ note: '' });
const cancelForm = useForm({ note: '' });

const fulfill = () => fulfillForm.post(route('requests.fulfill', props.customerRequest.id));
const cancel = () => cancelForm.post(route('requests.cancel', props.customerRequest.id));
</script>

<template>
    <Head title="Demande" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Demande</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <div class="rounded-xl border border-brand-gold/40 bg-white p-6 text-sm shadow-sm">
                    <p class="font-medium text-brand-navy">{{ customerRequest.product?.name }} ({{ customerRequest.product?.code }})</p>
                    <p class="mt-2">Client : {{ customerRequest.customer_name ?? 'Non renseigné' }}</p>
                    <p class="mt-2">Quantité : {{ customerRequest.quantity ?? 'Non renseignée' }}</p>
                    <p class="mt-2">Priorité : {{ customerRequest.priority_label }} · Fréquence : {{ customerRequest.frequency }}</p>
                    <p class="mt-2">Statut : {{ customerRequest.status_label }}</p>
                    <p class="mt-2">Demandée le {{ customerRequest.requested_at_label }}</p>
                    <p v-if="customerRequest.notes" class="mt-2">Notes : {{ customerRequest.notes }}</p>
                    <p v-if="customerRequest.closure_note" class="mt-2">Clôture : {{ customerRequest.closure_note }} ({{ customerRequest.closed_at_label }})</p>
                    <p class="mt-3 inline-flex items-center gap-2">
                        <UserAvatar v-if="customerRequest.recorder" :name="customerRequest.recorder.name" :photo-url="customerRequest.recorder.photo_url" size="sm" decorative />
                        {{ customerRequest.recorder?.name }}
                    </p>
                </div>

                <div v-if="customerRequest.status === 'open'" class="grid gap-4 md:grid-cols-2">
                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="fulfill">
                        <InputLabel value="Note de satisfaction (optionnelle)" />
                        <TextInput v-model="fulfillForm.note" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="fulfillForm.errors.note" />
                        <PrimaryButton class="mt-4" :disabled="fulfillForm.processing">Marquer satisfaite</PrimaryButton>
                    </form>
                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="cancel">
                        <InputLabel value="Motif d’annulation" />
                        <TextInput v-model="cancelForm.note" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="cancelForm.errors.note" />
                        <PrimaryButton class="mt-4" :disabled="cancelForm.processing">Annuler la demande</PrimaryButton>
                    </form>
                </div>

                <Link :href="route('requests.index')" class="text-sm text-brand-navy underline">Retour aux demandes</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
