<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    balances: { USD: string; CDF: string };
    rate: { id: number; cdf_per_usd: string; effective_at_label: string | null; creator: { name: string } | null } | null;
    rates: { id: number; cdf_per_usd: string; effective_at_label: string | null; creator: { name: string } | null }[];
    exchanges: {
        id: number;
        reference: string;
        source_currency: string;
        source_amount: string;
        destination_currency: string;
        destination_amount: string;
        rate: string;
        occurred_at_label: string | null;
        creator: { name: string } | null;
    }[];
    preview: {
        available: boolean;
        message?: string;
        source_currency?: string;
        source_amount?: string;
        destination_currency?: string;
        destination_amount?: string;
        rate?: string;
        effective_at_label?: string | null;
    } | null;
    filters: { amount: string; source_currency: string };
}>();

const rateForm = useForm({ cdf_per_usd: '' });
const exchangeForm = useForm({ source_currency: 'USD', amount: '' });
const previewAmount = ref('');
const previewCurrency = ref('USD');

const loadPreview = () => {
    router.get(route('cash.exchange'), {
        amount: previewAmount.value,
        source_currency: previewCurrency.value,
    }, { preserveState: true, preserveScroll: true });
};

const saveRate = () => {
    if (rateForm.processing) {
        return;
    }

    rateForm.post(route('cash.rates.store'));
};

const convert = () => {
    if (exchangeForm.processing) {
        return;
    }

    exchangeForm.post(route('cash.exchange.store'));
};
</script>

<template>
    <Head title="Change" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Change</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 text-sm shadow-sm">
                    <p>Solde USD {{ balances.USD }} · Solde CDF {{ balances.CDF }}</p>
                    <p class="mt-2">Un change transfère de la valeur entre les deux caisses. Il n’est pas un bénéfice commercial.</p>
                    <p v-if="rate" class="mt-2">Taux en vigueur : {{ rate.cdf_per_usd }} CDF pour 1 USD, depuis le {{ rate.effective_at_label }}.</p>
                    <p v-else class="mt-2">Aucun taux n’est défini.</p>
                </section>

                <form class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="saveRate">
                    <InputLabel for="cdf_per_usd" value="Nouveau taux : CDF pour 1 USD" />
                    <TextInput id="cdf_per_usd" v-model="rateForm.cdf_per_usd" type="number" min="0.0001" step="0.0001" class="block w-full" required />
                    <InputError :message="rateForm.errors.cdf_per_usd" />
                    <PrimaryButton :disabled="rateForm.processing">Enregistrer le taux</PrimaryButton>
                </form>

                <form class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="loadPreview">
                    <InputLabel value="Aperçu" />
                    <select v-model="previewCurrency" class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="USD">USD vers CDF</option>
                        <option value="CDF">CDF vers USD</option>
                    </select>
                    <TextInput v-model="previewAmount" type="number" min="0.01" step="0.01" class="block w-full" />
                    <SecondaryButton type="submit">Calculer l’aperçu</SecondaryButton>
                    <div v-if="preview" class="text-sm text-brand-navy">
                        <p v-if="!preview.available">{{ preview.message }}</p>
                        <p v-else>
                            {{ preview.source_amount }} {{ preview.source_currency }} deviennent {{ preview.destination_amount }} {{ preview.destination_currency }}
                            au taux {{ preview.rate }} du {{ preview.effective_at_label }}.
                        </p>
                    </div>
                </form>

                <form class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="convert">
                    <InputLabel value="Confirmer la conversion" />
                    <select v-model="exchangeForm.source_currency" class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="USD">Depuis USD</option>
                        <option value="CDF">Depuis CDF</option>
                    </select>
                    <TextInput v-model="exchangeForm.amount" type="number" min="0.01" step="0.01" class="block w-full" required />
                    <InputError :message="exchangeForm.errors.amount" />
                    <PrimaryButton :disabled="exchangeForm.processing">Confirmer le change</PrimaryButton>
                </form>

                <section>
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Historique des changes</h3>
                    <p v-if="exchanges.length === 0" class="mt-2 text-sm text-gray-600">Aucun change.</p>
                    <ul v-else class="mt-3 space-y-2 text-sm">
                        <li v-for="item in exchanges" :key="item.id">
                            {{ item.reference }} · {{ item.source_amount }} {{ item.source_currency }} → {{ item.destination_amount }} {{ item.destination_currency }}
                            · taux {{ item.rate }} · {{ item.occurred_at_label }} · {{ item.creator?.name }}
                        </li>
                    </ul>
                </section>
                <section>
                    <h3 class="mt-4 font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Historique des taux</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="item in rates" :key="item.id">{{ item.cdf_per_usd }} CDF / USD · {{ item.effective_at_label }} · {{ item.creator?.name }}</li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
