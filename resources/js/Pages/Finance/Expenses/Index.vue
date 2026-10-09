<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    expenses: {
        data: { id: number; reference: string; amount: string; reason: string; spent_on_label: string | null; status_label: string; creator: { name: string } | null }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}>();

const form = useForm({ q: props.filters.q, status: props.filters.status });

const submit = () => form.get(route('expenses.index'), { preserveState: true });
</script>

<template>
    <Head title="Dépenses" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Dépenses</h2>
                <Link :href="route('expenses.create')" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">Nouvelle dépense</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <form class="flex flex-wrap items-end gap-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Référence ou motif" />
                        <TextInput v-model="form.q" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Statut" />
                        <select v-model="form.status" class="mt-1 block rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </div>
                    <PrimaryButton :disabled="form.processing">Filtrer</PrimaryButton>
                </form>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <p v-if="expenses.data.length === 0" class="p-6 text-sm text-gray-600">Aucune dépense enregistrée.</p>
                    <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-brand-cream text-left text-brand-navy">
                            <tr>
                                <th class="px-4 py-3">Référence</th>
                                <th class="px-4 py-3">Motif</th>
                                <th class="px-4 py-3">Montant</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Statut</th>
                                <th class="px-4 py-3">Auteur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="expense in expenses.data" :key="expense.id">
                                <td class="px-4 py-3">
                                    <Link :href="route('expenses.show', expense.id)" class="font-medium text-brand-navy underline">{{ expense.reference }}</Link>
                                </td>
                                <td class="px-4 py-3">{{ expense.reason }}</td>
                                <td class="px-4 py-3">{{ expense.amount }}</td>
                                <td class="px-4 py-3">{{ expense.spent_on_label }}</td>
                                <td class="px-4 py-3">{{ expense.status_label }}</td>
                                <td class="px-4 py-3">{{ expense.creator?.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationLinks :links="expenses.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
