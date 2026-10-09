<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Match = {
    id: number;
    code: string;
    barcode: string | null;
    name: string;
    sale_price: string;
    boutique_quantity: number;
    purchase_price?: string | null;
};

type Line = {
    uid: string;
    query: string;
    product_id: string;
    quantity: string;
    product: Match | null;
    matches: Match[];
};

const props = defineProps<{
    filters: { q: string };
    matches: Match[];
    includeFinance: boolean;
}>();

const blankLine = (): Line => ({
    uid: crypto.randomUUID(),
    query: '',
    product_id: '',
    quantity: '1',
    product: null,
    matches: [],
});

const lines = ref<Line[]>([blankLine()]);
const currency = ref<'USD' | 'CDF'>('USD');
const amountReceived = ref('');
const clientToken = crypto.randomUUID();

const form = useForm({
    lines: [] as { product_id: number; quantity: number }[],
    currency: 'USD',
    amount_received: '',
    client_token: clientToken,
});

const cents = (value: string): number => {
    const [whole, fraction = ''] = value.split('.');
    const minor = (fraction + '00').slice(0, 2);

    return (Number(whole) * 100) + Number(minor);
};

const formatCents = (value: number): string => {
    const sign = value < 0 ? '-' : '';
    const absolute = Math.abs(value);
    const whole = Math.floor(absolute / 100);
    const minor = String(absolute % 100).padStart(2, '0');

    return `${sign}${whole}.${minor}`;
};

const subtotalCents = (line: Line): number => {
    if (!line.product) {
        return 0;
    }

    const quantity = Number(line.quantity);

    if (!Number.isInteger(quantity) || quantity < 1) {
        return 0;
    }

    return cents(line.product.sale_price) * quantity;
};

const totalCents = computed(() => lines.value.reduce((sum, line) => sum + subtotalCents(line), 0));
const totalLabel = computed(() => formatCents(totalCents.value));
const receivedCents = computed(() => (amountReceived.value.trim() === '' ? null : cents(amountReceived.value)));
const differenceLabel = computed(() => {
    if (receivedCents.value === null) {
        return null;
    }

    const difference = receivedCents.value - totalCents.value;

    if (difference < 0) {
        return { label: 'Montant restant dû', amount: formatCents(difference) };
    }

    return { label: 'Monnaie à rendre', amount: formatCents(difference) };
});

const searchLine = (line: Line) => {
    router.get(route('sales.search'), { q: line.query }, {
        preserveState: true,
        preserveScroll: true,
        only: ['matches', 'filters'],
        onSuccess: (page) => {
            line.matches = (page.props.matches as Match[]) ?? [];
        },
    });
};

const choose = (line: Line, match: Match) => {
    line.product = match;
    line.product_id = String(match.id);
    line.query = match.name;
    line.matches = [];
};

const addLine = () => {
    lines.value.push(blankLine());
};

const removeLine = (uid: string) => {
    lines.value = lines.value.filter((line) => line.uid !== uid);

    if (lines.value.length === 0) {
        lines.value.push(blankLine());
    }
};

const fieldError = (key: string): string | undefined => (form.errors as Record<string, string | undefined>)[key];

const submit = () => {
    if (form.processing) {
        return;
    }

    form.transform(() => ({
        lines: lines.value
            .filter((line) => line.product_id !== '')
            .map((line) => ({
                product_id: Number(line.product_id),
                quantity: Number(line.quantity),
            })),
        currency: currency.value,
        amount_received: amountReceived.value,
        client_token: clientToken,
    })).post(route('sales.store'));
};
</script>

<template>
    <Head title="Nouvelle vente" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Nouvelle vente</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    La vente sort le stock de la boutique uniquement. Les prix enregistrés sont ceux du catalogue.
                    L’aperçu ci-dessous est indicatif : le total, la monnaie et la caisse sont calculés par le serveur.
                </p>

                <form class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div v-for="(line, index) in lines" :key="line.uid" class="space-y-3 rounded-lg border border-gray-200 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-brand-navy">Article {{ index + 1 }}</p>
                            <button type="button" class="text-sm text-red-700 underline" @click="removeLine(line.uid)">Retirer</button>
                        </div>
                        <div>
                            <InputLabel :for="`q-${line.uid}`" value="Nom, code ou code-barres" />
                            <div class="mt-1 flex gap-3">
                                <TextInput :id="`q-${line.uid}`" v-model="line.query" class="block w-full" @keydown.enter.prevent="searchLine(line)" />
                                <SecondaryButton type="button" @click="searchLine(line)">Rechercher</SecondaryButton>
                            </div>
                        </div>
                        <div v-if="line.matches.length > 0" class="space-y-2">
                            <button
                                v-for="match in line.matches"
                                :key="match.id"
                                type="button"
                                class="block w-full rounded-md border border-gray-200 p-3 text-start"
                                @click="choose(line, match)"
                            >
                                <span class="block font-medium text-brand-navy">{{ match.name }}</span>
                                <span class="block text-sm text-gray-600">
                                    {{ match.code }}
                                    <template v-if="match.barcode"> · {{ match.barcode }}</template>
                                    · {{ match.sale_price }} {{ currency }}
                                    · Boutique {{ match.boutique_quantity }}
                                    <template v-if="includeFinance && match.purchase_price"> · Achat {{ match.purchase_price }}</template>
                                </span>
                            </button>
                        </div>
                        <p v-if="line.product" class="text-sm text-brand-navy">
                            Sélection : {{ line.product.name }} · prix unitaire {{ line.product.sale_price }} {{ currency }}
                            · boutique {{ line.product.boutique_quantity }}
                        </p>
                        <div>
                            <InputLabel :for="`qty-${line.uid}`" value="Quantité" />
                            <TextInput :id="`qty-${line.uid}`" v-model="line.quantity" type="number" min="1" class="mt-1 block w-full" />
                        </div>
                        <p class="text-sm text-gray-600">Sous-total aperçu : {{ formatCents(subtotalCents(line)) }} {{ currency }}</p>
                        <InputError :message="fieldError(`lines.${index}.product_id`)" />
                        <InputError :message="fieldError(`lines.${index}.quantity`)" />
                    </div>
                    <InputError :message="fieldError('lines')" />
                    <InputError :message="fieldError('product_id')" />
                    <InputError :message="fieldError('quantity')" />

                    <SecondaryButton type="button" @click="addLine">+ Ajouter un article</SecondaryButton>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="currency" value="Devise de paiement" />
                            <select id="currency" v-model="currency" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="USD">USD</option>
                                <option value="CDF">CDF</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="amount_received" value="Montant reçu" />
                            <TextInput id="amount_received" v-model="amountReceived" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.amount_received" />
                        </div>
                    </div>

                    <div class="rounded-md bg-brand-cream p-4 text-sm text-brand-navy">
                        <p>Total général aperçu : {{ totalLabel }} {{ currency }}</p>
                        <p v-if="differenceLabel">{{ differenceLabel.label }} : {{ differenceLabel.amount }} {{ currency }}</p>
                        <p class="mt-1 text-xs text-gray-600">La caisse enregistrera le total de la vente, pas le montant rendu au client.</p>
                    </div>
                    <InputError :message="form.errors.client_token" />

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="route('sales.index')" class="text-sm text-brand-navy underline">Retour</Link>
                        <PrimaryButton :disabled="form.processing">Enregistrer la vente</PrimaryButton>
                    </div>
                </form>
                <p v-if="props.filters.q" class="sr-only">{{ props.matches.length }}</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
