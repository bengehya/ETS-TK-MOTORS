<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

type Declaration = {
    id: number;
    note: string | null;
    counted_usd: string | null;
    counted_cdf: string | null;
    book_usd: string;
    book_cdf: string;
    gap_usd: string | null;
    gap_cdf: string | null;
    reference_rate: string | null;
    rate_effective_at_label: string | null;
    indicative_usd: string | null;
    status_label: string;
    correction_reason: string | null;
    created_at_label: string | null;
    author: { name: string } | null;
};

const props = defineProps<{
    balances: { USD: string; CDF: string };
    rate: { cdf_per_usd: string; effective_at_label: string | null } | null;
    declarations: Declaration[];
    canValidate: boolean;
    canAdjust: boolean;
}>();

const form = useForm({
    note: '',
    counted_usd: '',
    counted_cdf: '',
});

const correction = useForm({
    note: '',
    counted_usd: '',
    counted_cdf: '',
    correction_reason: '',
    declaration_id: '',
});

const adjustment = useForm({
    currency: 'USD',
    direction: 'outflow',
    amount: '',
    reason: '',
});

const validateDeclaration = (id: number) => {
    useForm({}).post(route('cash.counts.validate', id));
};

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('cash.counts.store'));
};

const correct = (id: number) => {
    if (correction.processing) {
        return;
    }

    correction.declaration_id = String(id);
    correction.post(route('cash.counts.correct', id));
};
</script>

<template>
    <Head title="Notes et comptage de caisse" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Notes et comptage de caisse</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">Solde comptable USD {{ balances.USD }} · Solde comptable CDF {{ balances.CDF }}</p>
                    <p v-if="rate" class="mt-2 text-sm text-gray-600">
                        Taux enregistré : {{ rate.cdf_per_usd }} CDF pour 1 USD, depuis le {{ rate.effective_at_label }}.
                        L’équivalent indiqué ne modifie aucun solde.
                    </p>
                    <p class="mt-2 text-sm text-gray-600">Une note ou un comptage ne change pas la caisse comptable.</p>
                </section>

                <form class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel for="note" value="Note" />
                        <textarea id="note" v-model="form.note" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="3" />
                        <InputError class="mt-2" :message="form.errors.note" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="counted_usd" value="Comptage physique USD" />
                            <TextInput id="counted_usd" v-model="form.counted_usd" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="counted_cdf" value="Comptage physique CDF" />
                            <TextInput id="counted_cdf" v-model="form.counted_cdf" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <PrimaryButton :disabled="form.processing">Enregistrer la déclaration</PrimaryButton>
                </form>

                <section class="space-y-4">
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Historique</h3>
                    <p v-if="declarations.length === 0" class="text-sm text-gray-600">Aucune déclaration.</p>
                    <article v-for="declaration in declarations" :key="declaration.id" class="rounded-xl border border-brand-gold/40 bg-white p-4 text-sm shadow-sm">
                        <p class="font-medium text-brand-navy">{{ declaration.created_at_label }} · {{ declaration.author?.name }} · {{ declaration.status_label }}</p>
                        <p v-if="declaration.note" class="mt-2">{{ declaration.note }}</p>
                        <p class="mt-2">Comptage physique USD {{ declaration.counted_usd ?? '—' }} · CDF {{ declaration.counted_cdf ?? '—' }}</p>
                        <p>Solde comptable USD {{ declaration.book_usd }} · CDF {{ declaration.book_cdf }}</p>
                        <p>Écart USD {{ declaration.gap_usd ?? '—' }} · CDF {{ declaration.gap_cdf ?? '—' }}</p>
                        <p v-if="declaration.indicative_usd">
                            Équivalent indicatif {{ declaration.indicative_usd }} USD au taux {{ declaration.reference_rate }} du {{ declaration.rate_effective_at_label }}.
                        </p>
                        <p v-if="declaration.correction_reason" class="mt-2">Motif de correction : {{ declaration.correction_reason }}</p>
                        <div v-if="canValidate" class="mt-3 flex flex-wrap gap-3">
                            <button type="button" class="text-brand-navy underline" @click="validateDeclaration(declaration.id)">Valider</button>
                        </div>
                        <form v-if="canValidate" class="mt-3 space-y-2" @submit.prevent="correct(declaration.id)">
                            <InputLabel value="Corriger cette déclaration" />
                            <TextInput v-model="correction.counted_usd" type="number" min="0" step="0.01" placeholder="USD" class="block w-full" />
                            <TextInput v-model="correction.counted_cdf" type="number" min="0" step="0.01" placeholder="CDF" class="block w-full" />
                            <TextInput v-model="correction.note" placeholder="Note" class="block w-full" />
                            <TextInput v-model="correction.correction_reason" placeholder="Motif de correction" class="block w-full" required />
                            <InputError :message="correction.errors.correction_reason" />
                            <PrimaryButton :disabled="correction.processing">Enregistrer la correction</PrimaryButton>
                        </form>
                    </article>
                </section>

                <form v-if="canAdjust" class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="adjustment.post(route('cash.adjust'))">
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Correction comptable distincte</h3>
                    <p class="text-sm text-gray-600">Cette opération crée une écriture de caisse motivée. Elle est réservée au patron.</p>
                    <select v-model="adjustment.currency" class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                    <select v-model="adjustment.direction" class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="inflow">Entrée</option>
                        <option value="outflow">Sortie</option>
                    </select>
                    <TextInput v-model="adjustment.amount" type="number" min="0.01" step="0.01" class="block w-full" />
                    <TextInput v-model="adjustment.reason" class="block w-full" placeholder="Motif" required />
                    <InputError :message="adjustment.errors.amount" />
                    <PrimaryButton :disabled="adjustment.processing">Enregistrer la correction comptable</PrimaryButton>
                </form>
                <p class="sr-only">{{ props.balances.USD }}</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
