<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Line = {
    id: number;
    quantity: number;
    unit_sale_price: string;
    line_total: string;
    product: { name: string; code: string } | null;
};

defineProps<{
    sale: {
        id: number;
        reference: string;
        currency: string;
        line_total: string;
        amount_received: string;
        change_given: string;
        sold_at_label: string | null;
        lines: Line[];
        location: { name: string } | null;
        seller: { name: string } | null;
        product: { name: string; code: string } | null;
        quantity: number;
        unit_sale_price: string;
    };
    company: {
        name: string;
        slogan: string;
        city: string;
        logo: string;
    };
}>();

const printInvoice = () => window.print();
</script>

<template>
    <Head :title="`Facture ${sale.reference}`" />

    <div class="min-h-screen bg-white text-brand-navy">
        <div class="no-print flex items-center justify-end gap-3 border-b border-brand-gold/40 px-4 py-3">
            <button type="button" class="rounded-md bg-brand-navy px-4 py-2 text-sm text-white" @click="printInvoice">Imprimer la facture</button>
            <Link :href="route('sales.show', sale.id)" class="text-sm underline">Retour</Link>
        </div>

        <article class="mx-auto max-w-3xl px-6 py-8">
            <header class="flex items-center gap-4 border-b border-brand-gold pb-4">
                <img :src="company.logo" alt="Logo ETS TK MOTORS" class="h-16 w-16 rounded-full border border-brand-gold object-cover" />
                <div>
                    <h1 class="font-display text-2xl uppercase tracking-[0.14em]">{{ company.name }}</h1>
                    <p class="text-sm italic text-brand-gold">{{ company.slogan }}</p>
                    <p class="text-sm">{{ company.city }}</p>
                </div>
            </header>

            <h2 class="mt-6 font-display text-xl uppercase tracking-[0.12em]">Facture {{ sale.reference }}</h2>
            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500">Date et heure</dt><dd>{{ sale.sold_at_label }}</dd></div>
                <div><dt class="text-gray-500">Vendeur</dt><dd>{{ sale.seller?.name }}</dd></div>
                <div><dt class="text-gray-500">Lieu de vente</dt><dd>{{ sale.location?.name }}</dd></div>
                <div><dt class="text-gray-500">Devise</dt><dd>{{ sale.currency }}</dd></div>
            </dl>

            <table class="mt-6 w-full text-sm">
                <thead>
                    <tr class="border-b border-brand-navy text-left">
                        <th class="py-2">Article</th>
                        <th class="py-2">Qté</th>
                        <th class="py-2">Prix unitaire</th>
                        <th class="py-2">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in sale.lines" :key="line.id" class="border-b border-gray-200">
                        <td class="py-2">{{ line.product?.name }} <span class="text-gray-500">{{ line.product?.code }}</span></td>
                        <td class="py-2">{{ line.quantity }}</td>
                        <td class="py-2">{{ line.unit_sale_price }} {{ sale.currency }}</td>
                        <td class="py-2">{{ line.line_total }} {{ sale.currency }}</td>
                    </tr>
                    <tr v-if="sale.lines.length === 0">
                        <td class="py-2">{{ sale.product?.name }}</td>
                        <td class="py-2">{{ sale.quantity }}</td>
                        <td class="py-2">{{ sale.unit_sale_price }} {{ sale.currency }}</td>
                        <td class="py-2">{{ sale.line_total }} {{ sale.currency }}</td>
                    </tr>
                </tbody>
            </table>

            <dl class="mt-6 space-y-1 text-sm">
                <div class="flex justify-between"><dt>Total</dt><dd>{{ sale.line_total }} {{ sale.currency }}</dd></div>
                <div class="flex justify-between"><dt>Montant reçu</dt><dd>{{ sale.amount_received }} {{ sale.currency }}</dd></div>
                <div class="flex justify-between"><dt>Monnaie rendue</dt><dd>{{ sale.change_given }} {{ sale.currency }}</dd></div>
            </dl>
        </article>
    </div>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
