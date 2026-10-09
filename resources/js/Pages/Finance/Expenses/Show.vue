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
    expense: {
        id: number;
        reference: string;
        amount: string;
        reason: string;
        spent_on_label: string | null;
        status: string;
        status_label: string;
        decision_note: string | null;
        decided_at_label: string | null;
        creator: { name: string; photo_url: string | null } | null;
        decider: { name: string; photo_url: string | null } | null;
    };
}>();

const validateForm = useForm({
    expense: '',
});
const refuseForm = useForm({ decision_note: '' });

const validateExpense = () => {
    if (validateForm.processing) {
        return;
    }

    validateForm.post(route('expenses.validate', props.expense.id));
};

const refuse = () => {
    if (refuseForm.processing) {
        return;
    }

    refuseForm.post(route('expenses.refuse', props.expense.id));
};
</script>

<template>
    <Head :title="expense.reference" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">{{ expense.reference }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <div class="rounded-xl border border-brand-gold/40 bg-white p-6 text-sm shadow-sm">
                    <p><span class="text-gray-500">Montant :</span> <span class="font-medium text-brand-navy">{{ expense.amount }}</span></p>
                    <p class="mt-2"><span class="text-gray-500">Motif :</span> {{ expense.reason }}</p>
                    <p class="mt-2"><span class="text-gray-500">Date :</span> {{ expense.spent_on_label }}</p>
                    <p class="mt-2"><span class="text-gray-500">Statut :</span> {{ expense.status_label }}</p>
                    <p v-if="expense.decision_note" class="mt-2"><span class="text-gray-500">Décision :</span> {{ expense.decision_note }}</p>
                    <p v-if="expense.decided_at_label" class="mt-2"><span class="text-gray-500">Traitée le :</span> {{ expense.decided_at_label }}</p>
                    <p class="mt-3 inline-flex items-center gap-2">
                        <UserAvatar v-if="expense.creator" :name="expense.creator.name" :photo-url="expense.creator.photo_url" size="sm" decorative />
                        Enregistrée par {{ expense.creator?.name }}
                    </p>
                    <p v-if="expense.decider" class="mt-2 inline-flex items-center gap-2">
                        <UserAvatar :name="expense.decider.name" :photo-url="expense.decider.photo_url" size="sm" decorative />
                        Décision de {{ expense.decider.name }}
                    </p>
                </div>

                <div v-if="expense.status === 'pending'" class="space-y-4">
                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="validateExpense">
                        <p class="text-sm text-gray-600">La validation débite la caisse si le solde couvre le montant. Sinon la dépense est refusée et l’historique est conservé.</p>
                        <InputError class="mt-2" :message="validateForm.errors.expense" />
                        <div class="mt-4 flex justify-end">
                            <PrimaryButton :disabled="validateForm.processing">Valider la dépense</PrimaryButton>
                        </div>
                    </form>
                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="refuse">
                        <InputLabel for="decision_note" value="Refuser avec un motif" />
                        <TextInput id="decision_note" v-model="refuseForm.decision_note" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="refuseForm.errors.decision_note" />
                        <div class="mt-4 flex justify-end">
                            <PrimaryButton :disabled="refuseForm.processing">Refuser</PrimaryButton>
                        </div>
                    </form>
                </div>

                <Link :href="route('expenses.index')" class="text-sm text-brand-navy underline">Retour aux dépenses</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
