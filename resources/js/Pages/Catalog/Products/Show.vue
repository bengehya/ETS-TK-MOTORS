<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type LocationStock = {
    id: number;
    quantity: number;
    location: { id: number; type: string; name: string; is_sellable: boolean };
};

const props = defineProps<{
    product: {
        id: number;
        code: string;
        barcode: string | null;
        name: string;
        category: string;
        description: string | null;
        sale_price: string;
        is_active: boolean;
        stocks: {
            boutique: LocationStock | null;
            depot: LocationStock | null;
            sellable_quantity: number;
            depot_quantity: number;
        };
    };
    canManage: boolean;
    canMutateStock: boolean;
    canUpdatePrice: boolean;
}>();

const receipt = useForm({
    location_id: String(props.product.stocks.depot?.location.id ?? props.product.stocks.boutique?.location.id ?? ''),
    quantity: '1',
    notes: '',
});

const transfer = useForm({
    quantity: '1',
    notes: '',
});

const adjustment = useForm({
    location_id: String(props.product.stocks.boutique?.location.id ?? ''),
    direction: 'decrease',
    quantity: '1',
    reason: '',
});

const submitReceipt = () => {
    if (receipt.processing) {
        return;
    }

    receipt.post(route('products.stock.receive', props.product.id), { preserveScroll: true });
};

const submitTransfer = () => {
    if (transfer.processing) {
        return;
    }

    transfer.post(route('products.stock.transfer', props.product.id), { preserveScroll: true });
};

const submitAdjustment = () => {
    if (adjustment.processing) {
        return;
    }

    adjustment.post(route('products.stock.adjust', props.product.id), { preserveScroll: true });
};

const statusBusy = ref(false);

const toggleActive = () => {
    if (statusBusy.value) {
        return;
    }

    statusBusy.value = true;
    const namedRoute = props.product.is_active ? 'products.deactivate' : 'products.activate';
    router.post(route(namedRoute, props.product.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            statusBusy.value = false;
        },
    });
};
</script>

<template>
    <Head :title="product.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">{{ product.name }}</h2>
                    <p class="mt-1 font-mono text-sm text-brand-gold">{{ product.code }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="canManage" :href="route('products.edit', product.id)">
                        <PrimaryButton type="button">Modifier</PrimaryButton>
                    </Link>
                    <button
                        v-if="canManage"
                        type="button"
                        class="rounded-md border border-brand-navy px-4 py-2 text-xs font-semibold uppercase tracking-widest text-brand-navy disabled:pointer-events-none disabled:opacity-50"
                        :disabled="statusBusy"
                        @click="toggleActive"
                    >
                        {{ product.is_active ? 'Désactiver' : 'Réactiver' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <div class="grid gap-6 lg:grid-cols-3">
                    <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm lg:col-span-2">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">Identification</h3>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-gray-500">Catégorie</dt>
                                <dd class="font-medium text-brand-navy">{{ product.category }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Prix de vente</dt>
                                <dd class="font-medium text-brand-navy">{{ product.sale_price }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Code-barres / QR</dt>
                                <dd class="font-medium text-brand-navy">{{ product.barcode || 'Non renseigné' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">État</dt>
                                <dd class="font-medium text-brand-navy">{{ product.is_active ? 'Actif' : 'Désactivé' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-gray-500">Description</dt>
                                <dd class="text-brand-navy">{{ product.description || '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="space-y-4">
                        <div class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">Boutique</h3>
                            <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ product.stocks.sellable_quantity }}</p>
                            <p class="mt-1 text-xs text-gray-500">Disponible à la vente</p>
                        </div>
                        <div class="rounded-xl border border-brand-navy/20 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Dépôt</h3>
                            <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ product.stocks.depot_quantity }}</p>
                            <p class="mt-1 text-xs text-gray-500">Non disponible à la vente en boutique</p>
                        </div>
                    </section>
                </div>

                <section v-if="canMutateStock && product.is_active" class="grid gap-6 lg:grid-cols-3">
                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submitReceipt">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Entrée de stock</h3>
                        <div class="mt-4 space-y-3">
                            <div>
                                <InputLabel value="Emplacement" />
                                <select v-model="receipt.location_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                    <option v-if="product.stocks.boutique" :value="String(product.stocks.boutique.location.id)">Boutique</option>
                                    <option v-if="product.stocks.depot" :value="String(product.stocks.depot.location.id)">Dépôt</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Quantité" />
                                <TextInput v-model="receipt.quantity" type="number" min="1" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="receipt.errors.quantity" />
                            </div>
                            <div>
                                <InputLabel value="Note" />
                                <TextInput v-model="receipt.notes" type="text" class="mt-1 block w-full" />
                            </div>
                            <PrimaryButton :disabled="receipt.processing">Enregistrer l’entrée</PrimaryButton>
                        </div>
                    </form>

                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submitTransfer">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Transfert dépôt → boutique</h3>
                        <p class="mt-2 text-xs text-gray-500">Le stock dépôt n’est jamais vendable tant qu’il n’a pas été transféré.</p>
                        <div class="mt-4 space-y-3">
                            <div>
                                <InputLabel value="Quantité" />
                                <TextInput v-model="transfer.quantity" type="number" min="1" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="transfer.errors.quantity" />
                            </div>
                            <div>
                                <InputLabel value="Note" />
                                <TextInput v-model="transfer.notes" type="text" class="mt-1 block w-full" />
                            </div>
                            <PrimaryButton :disabled="transfer.processing">Transférer</PrimaryButton>
                        </div>
                    </form>

                    <form class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submitAdjustment">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Ajustement exceptionnel</h3>
                        <div class="mt-4 space-y-3">
                            <div>
                                <InputLabel value="Emplacement" />
                                <select v-model="adjustment.location_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                    <option v-if="product.stocks.boutique" :value="String(product.stocks.boutique.location.id)">Boutique</option>
                                    <option v-if="product.stocks.depot" :value="String(product.stocks.depot.location.id)">Dépôt</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Sens" />
                                <select v-model="adjustment.direction" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                                    <option value="increase">Augmenter</option>
                                    <option value="decrease">Diminuer</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Quantité" />
                                <TextInput v-model="adjustment.quantity" type="number" min="1" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="adjustment.errors.quantity" />
                            </div>
                            <div>
                                <InputLabel value="Motif obligatoire" />
                                <TextInput v-model="adjustment.reason" type="text" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="adjustment.errors.reason" />
                            </div>
                            <PrimaryButton :disabled="adjustment.processing">Ajuster</PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
