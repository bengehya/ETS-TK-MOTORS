<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    balance: string;
    balances: { USD: string; CDF: string };
    rate: { cdf_per_usd: string; effective_at_label: string | null } | null;
    declarations: {
        id: number;
        note: string | null;
        counted_usd: string | null;
        counted_cdf: string | null;
        book_usd: string;
        book_cdf: string;
        gap_usd: string | null;
        gap_cdf: string | null;
        status_label: string;
        created_at_label: string | null;
        author: { name: string } | null;
    }[];
    canValidate: boolean;
    canAdjust: boolean;
    entries: {
        data: {
            id: number;
            reference: string;
            direction_label: string;
            currency: string;
            amount: string;
            balance_after: string;
            label: string;
            occurred_at_label: string | null;
            user: { name: string; photo_url: string | null } | null;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();
</script>

<template>
    <Head title="Caisse" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Caisse</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm text-gray-500">Solde USD</p>
                            <p class="text-3xl font-semibold text-brand-navy">{{ balances.USD }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Solde CDF</p>
                            <p class="text-3xl font-semibold text-brand-navy">{{ balances.CDF }}</p>
                        </div>
                    </div>
                    <p v-if="rate" class="mt-2 text-sm text-gray-600">
                        Taux de référence : {{ rate.cdf_per_usd }} CDF pour 1 USD, en vigueur le {{ rate.effective_at_label }}.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm">
                        <Link :href="route('expenses.index')" class="text-brand-navy underline">Voir les dépenses</Link>
                        <Link :href="route('cash.exchange')" class="text-brand-navy underline">Change</Link>
                        <Link :href="route('cash.counts.index')" class="text-brand-navy underline">Notes et comptage</Link>
                    </div>
                </section>

                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Notes et comptage de caisse</h3>
                    <p v-if="declarations.length === 0" class="mt-4 text-sm text-gray-600">Aucune déclaration.</p>
                    <ul v-else class="mt-4 space-y-3 text-sm">
                        <li v-for="declaration in declarations" :key="declaration.id" class="rounded-md border border-gray-200 p-3">
                            <p class="font-medium text-brand-navy">{{ declaration.created_at_label }} · {{ declaration.author?.name }} · {{ declaration.status_label }}</p>
                            <p v-if="declaration.note">{{ declaration.note }}</p>
                            <p>Comptage USD {{ declaration.counted_usd ?? '—' }} · CDF {{ declaration.counted_cdf ?? '—' }}</p>
                            <p>Comptable USD {{ declaration.book_usd }} · CDF {{ declaration.book_cdf }}</p>
                            <p>Écart USD {{ declaration.gap_usd ?? '—' }} · CDF {{ declaration.gap_cdf ?? '—' }}</p>
                        </li>
                    </ul>
                </section>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <p v-if="entries.data.length === 0" class="p-6 text-sm text-gray-600">Aucun mouvement de caisse.</p>
                    <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-brand-cream text-left text-brand-navy">
                            <tr>
                                <th class="px-4 py-3">Référence</th>
                                <th class="px-4 py-3">Libellé</th>
                                <th class="px-4 py-3">Sens</th>
                                <th class="px-4 py-3">Montant</th>
                                <th class="px-4 py-3">Solde après</th>
                                <th class="px-4 py-3">Utilisateur</th>
                                <th class="px-4 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="entry in entries.data" :key="entry.id">
                                <td class="px-4 py-3">{{ entry.reference }}</td>
                                <td class="px-4 py-3">{{ entry.label }}</td>
                                <td class="px-4 py-3">{{ entry.direction_label }}</td>
                                <td class="px-4 py-3">{{ entry.amount }} {{ entry.currency }}</td>
                                <td class="px-4 py-3">{{ entry.balance_after }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <UserAvatar v-if="entry.user" :name="entry.user.name" :photo-url="entry.user.photo_url" size="sm" decorative />
                                        {{ entry.user?.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ entry.occurred_at_label }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationLinks :links="entries.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
