<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Person = { name: string; photo_url: string | null; civility_label: string | null } | null;

const props = defineProps<{
    sale: {
        id: number;
        reference: string;
        quantity: number;
        unit_sale_price: string;
        line_total: string;
        unit_purchase_cost?: string;
        cost_total?: string;
        profit?: string;
        status: string;
        status_label: string;
        sold_at_label: string | null;
        cancellation_reason: string | null;
        cancelled_at_label: string | null;
        product: { id: number; name: string; code: string; barcode: string | null } | null;
        location: { name: string } | null;
        seller: Person;
        canceller: Person;
    };
    canCancel: boolean;
    includeFinance: boolean;
}>();

const form = useForm({ reason: '' });

const cancel = () => {
    if (form.processing) {
        return;
    }

    form.post(route('sales.cancel', props.sale.id));
};
</script>

<template>
    <Head :title="sale.reference" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">{{ sale.reference }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <FlashStatus />
                <div class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">Article</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.product?.name }} ({{ sale.product?.code }})</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Quantité</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.quantity }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Prix de vente unitaire</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.unit_sale_price }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Total</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.line_total }}</dd>
                        </div>
                        <template v-if="includeFinance">
                            <div>
                                <dt class="text-gray-500">Coût d’achat unitaire</dt>
                                <dd class="font-medium text-brand-navy">{{ sale.unit_purchase_cost }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Coût total</dt>
                                <dd class="font-medium text-brand-navy">{{ sale.cost_total }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Résultat</dt>
                                <dd class="font-medium text-brand-navy">{{ sale.profit }}</dd>
                            </div>
                        </template>
                        <div>
                            <dt class="text-gray-500">Emplacement</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.location?.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Date et heure</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.sold_at_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Statut</dt>
                            <dd class="font-medium text-brand-navy">{{ sale.status_label }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Vendeur</dt>
                            <dd class="mt-1 inline-flex items-center gap-2 font-medium text-brand-navy">
                                <UserAvatar v-if="sale.seller" :name="sale.seller.name" :photo-url="sale.seller.photo_url" size="sm" decorative />
                                <span v-if="sale.seller?.civility_label">{{ sale.seller.civility_label }} </span>{{ sale.seller?.name }}
                            </dd>
                        </div>
                        <div v-if="sale.status === 'cancelled'" class="sm:col-span-2">
                            <dt class="text-gray-500">Annulation</dt>
                            <dd class="font-medium text-brand-navy">
                                {{ sale.cancelled_at_label }} — {{ sale.cancellation_reason }}
                                <span v-if="sale.canceller"> par {{ sale.canceller.name }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <form v-if="canCancel" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="cancel">
                    <InputLabel for="reason" value="Motif d’annulation" />
                    <TextInput id="reason" v-model="form.reason" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.reason" />
                    <div class="mt-4 flex justify-end">
                        <PrimaryButton :disabled="form.processing">Annuler la vente</PrimaryButton>
                    </div>
                </form>

                <Link :href="route('sales.index')" class="text-sm text-brand-navy underline">Retour aux ventes</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
