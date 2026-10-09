<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
        fee_amount: string;
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
        fee_amount?: string;
        rate?: string;
        effective_at_label?: string | null;
        balances_before?: { USD: string; CDF: string };
        balances_after?: { USD: string; CDF: string };
    } | null;
    filters: { amount: string; source_currency: string };
}>();

const confirmed = computed(() => usePage().props.flash?.exchange ?? null);
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
                    <div v-if="confirmed" class="mt-3 rounded-md bg-brand-cream p-3">
                        <p>Change {{ confirmed.reference }} confirmé.</p>
                        <p>Avant : {{ confirmed.before.USD }} USD · {{ confirmed.before.CDF }} CDF</p>
                        <p>Après : {{ confirmed.after.USD }} USD · {{ confirmed.after.CDF }} CDF</p>
                        <p>Frais : {{ confirmed.fee_amount }}</p>
                    </div>
                    <p v-if="rate" class="mt-2">Taux en vigueur : {{ rate.cdf_per_usd }} CDF pour 1 USD, depuis le {{ rate.effective_at_label }}.</p>
                    <p v-else class="mt-2">Aucun taux n’est défini.</p>
                </section>

                <form class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="saveRate">
                    <InputLabel for="cdf_per_usd" value="Nouveau taux : CDF pour 1 USD" />
                    <TextInput id="cdf_per_usd" v-model="rateForm.cdf_per_usd" type="text" inputmode="decimal" autocomplete="off" class="block w-full" required />
                    <InputError :message="rateForm.errors.cdf_per_usd" />
                    <PrimaryButton :disabled="rateForm.processing">Enregistrer le taux</PrimaryButton>
                </form>

                <form class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="loadPreview">
                    <InputLabel value="Aperçu" />
                    <select v-model="previewCurrency" class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="USD">USD vers CDF</option>
                        <option value="CDF">CDF vers USD</option>
                    </select>
                    <TextInput v-model="previewAmount" type="text" inputmode="decimal" autocomplete="off" class="block w-full" />
                    <SecondaryButton type="submit">Calculer l’aperçu</SecondaryButton>
                    <div v-if="preview" class="text-sm text-brand-navy">
                        <p v-if="preview.balances_before">Avant : {{ preview.balances_before.USD }} USD · {{ preview.balances_before.CDF }} CDF</p>
                        <p v-if="!preview.available">{{ preview.message }}</p>
                        <template v-else>
                            <p>Après : {{ preview.balances_after?.USD }} USD · {{ preview.balances_after?.CDF }} CDF</p>
                            <p>
                                {{ preview.source_amount }} {{ preview.source_currency }} → {{ preview.destination_amount }} {{ preview.destination_currency }}
                                au taux {{ preview.rate }} du {{ preview.effective_at_label }}. Frais {{ preview.fee_amount }}.
                            </p>
                        </template>
                    </div>
                </form>

                <form class="space-y-3 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="convert">
                    <InputLabel value="Confirmer la conversion" />
                    <select v-model="exchangeForm.source_currency" class="block w-full rounded-md border-gray-300 text-sm">
                        <option value="USD">Depuis USD</option>
                        <option value="CDF">Depuis CDF</option>
                    </select>
                    <TextInput v-model="exchangeForm.amount" type="text" inputmode="decimal" autocomplete="off" class="block w-full" required />
                    <InputError :message="exchangeForm.errors.amount" />
                    <PrimaryButton :disabled="exchangeForm.processing">Confirmer le change</PrimaryButton>
                </form>

                <section>
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Historique des changes</h3>
                    <p v-if="exchanges.length === 0" class="mt-2 text-sm text-gray-600">Aucun change.</p>
                    <ul v-else class="mt-3 space-y-2 text-sm">
                        <li v-for="item in exchanges" :key="item.id">
                            {{ item.reference }} · {{ item.source_amount }} {{ item.source_currency }} → {{ item.destination_amount }} {{ item.destination_currency }}
                            · taux {{ item.rate }} · frais {{ item.fee_amount }} · {{ item.occurred_at_label }} · {{ item.creator?.name }}
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
