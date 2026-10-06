<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    balance: string;
    entries: {
        data: {
            id: number;
            reference: string;
            direction_label: string;
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
                    <p class="text-sm text-gray-500">Solde</p>
                    <p class="text-3xl font-semibold text-brand-navy">{{ balance }}</p>
                    <p class="mt-2 text-sm text-gray-600">
                        Les ventes conclues alimentent la caisse. Une dépense validée ou l’annulation d’une vente la diminue.
                    </p>
                    <Link :href="route('expenses.index')" class="mt-3 inline-block text-sm text-brand-navy underline">Voir les dépenses</Link>
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
                                <td class="px-4 py-3">{{ entry.amount }}</td>
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
