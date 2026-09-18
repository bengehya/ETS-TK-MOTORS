<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    arrival: {
        id: number;
        quantity: number;
        supplier_reference: string | null;
        status: string;
        status_label: string;
        created_at: string;
        validated_at: string | null;
        rejected_at: string | null;
        rejection_reason: string | null;
        product: { id: number; code: string; name: string } | null;
        location: { id: number; name: string; type: string } | null;
        recorder: { id: number; name: string } | null;
        validator: { id: number; name: string } | null;
        rejector: { id: number; name: string } | null;
    };
    canApprove: boolean;
}>();

const confirmingApprove = ref(false);
const confirmingReject = ref(false);

const approveForm = useForm({
    arrival: '',
});
const rejectForm = useForm({
    rejection_reason: '',
});

const submitApprove = () => {
    approveForm.post(route('arrivals.approve', props.arrival.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingApprove.value = false;
        },
    });
};

const submitReject = () => {
    rejectForm.post(route('arrivals.reject', props.arrival.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingReject.value = false;
        },
    });
};
</script>

<template>
    <Head title="Arrivage" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Arrivage</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ arrival.product?.code }} — {{ arrival.product?.name }}</p>
                </div>
                <Link :href="route('arrivals.index')" class="text-sm text-brand-navy underline">Tous les arrivages</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <InputError :message="approveForm.errors.arrival" />

                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">Détail</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">Statut</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.status_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Quantité</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.quantity }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Emplacement</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.location?.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Référence / fournisseur</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.supplier_reference || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Enregistré par</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.recorder?.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Date d’enregistrement</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.created_at }}</dd>
                        </div>
                        <div v-if="arrival.validator">
                            <dt class="text-gray-500">Validé par</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.validator.name }}</dd>
                        </div>
                        <div v-if="arrival.validated_at">
                            <dt class="text-gray-500">Date de validation</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.validated_at }}</dd>
                        </div>
                        <div v-if="arrival.rejector">
                            <dt class="text-gray-500">Rejeté par</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.rejector.name }}</dd>
                        </div>
                        <div v-if="arrival.rejected_at">
                            <dt class="text-gray-500">Date de rejet</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.rejected_at }}</dd>
                        </div>
                        <div v-if="arrival.rejection_reason" class="sm:col-span-2">
                            <dt class="text-gray-500">Motif de rejet</dt>
                            <dd class="font-medium text-brand-navy">{{ arrival.rejection_reason }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="canApprove" class="flex flex-wrap gap-3">
                    <PrimaryButton type="button" @click="confirmingApprove = true">Valider l’arrivage</PrimaryButton>
                    <DangerButton type="button" @click="confirmingReject = true">Rejeter l’arrivage</DangerButton>
                </section>
            </div>
        </div>

        <Modal :show="confirmingApprove" max-width="md" @close="confirmingApprove = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-brand-navy">Confirmer la validation</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Le stock de {{ arrival.location?.name }} sera augmenté de {{ arrival.quantity }} pour
                    {{ arrival.product?.name }}. Cette action est définitive.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingApprove = false">Annuler</SecondaryButton>
                    <PrimaryButton :disabled="approveForm.processing" @click="submitApprove">Confirmer la validation</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingReject" max-width="md" @close="confirmingReject = false">
            <form class="p-6" @submit.prevent="submitReject">
                <h3 class="text-lg font-semibold text-brand-navy">Confirmer le rejet</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Le stock ne sera pas modifié. L’arrivage restera dans l’historique.
                </p>
                <div class="mt-4">
                    <InputLabel value="Motif obligatoire" />
                    <TextInput v-model="rejectForm.rejection_reason" type="text" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="rejectForm.errors.rejection_reason" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="confirmingReject = false">Annuler</SecondaryButton>
                    <DangerButton :disabled="rejectForm.processing">Confirmer le rejet</DangerButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
