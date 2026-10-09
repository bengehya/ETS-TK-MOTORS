<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    requests: {
        data: {
            id: number;
            customer_name: string | null;
            quantity: number | null;
            priority_label: string;
            frequency: number;
            status_label: string;
            requested_at_label: string | null;
            designation: string | null;
            label: string | null;
            product: { name: string; code: string } | null;
            recorder: { name: string } | null;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; product_id: string; author_id: string; status: string; priority: string; date_from: string; date_to: string };
    statuses: { value: string; label: string }[];
    priorities: { value: string; label: string }[];
    canManage: boolean;
    authors: { id: number; name: string }[];
    suggestions: {
        id: number;
        label: string | null;
        request_count: number;
        total_quantity: number;
        observation_days: number;
        last_requested_at_label: string | null;
        boutique_quantity: number;
        depot_quantity: number;
        priority_label: string;
        status: string;
        status_label: string;
    }[];
    restock: { observation_days: number; repeat_threshold: number };
    suggestionStatuses: { value: string; label: string }[];
}>();

const form = useForm({
    q: props.filters.q,
    status: props.filters.status,
    priority: props.filters.priority,
    product_id: props.filters.product_id,
    author_id: props.filters.author_id,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const suggestionForm = useForm({
    status: 'reviewed',
    justification: '',
});

const submit = () => form.get(route('requests.index'), { preserveState: true });
</script>

<template>
    <Head title="Demandes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Demandes</h2>
                <Link :href="route('requests.create')" class="rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white">Nouvelle demande</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <form class="flex flex-wrap items-end gap-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Article, code, code-barres ou client" />
                        <TextInput v-model="form.q" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Statut" />
                        <select v-model="form.status" class="mt-1 block rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Priorité" />
                        <select v-model="form.priority" class="mt-1 block rounded-md border-gray-300 text-sm">
                            <option value="">Toutes</option>
                            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
                        </select>
                    </div>
                    <div v-if="canManage">
                        <InputLabel value="Auteur" />
                        <select v-model="form.author_id" class="mt-1 block rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option v-for="author in authors" :key="author.id" :value="String(author.id)">{{ author.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Du" />
                        <TextInput v-model="form.date_from" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Au" />
                        <TextInput v-model="form.date_to" type="date" class="mt-1 block w-full" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Filtrer</PrimaryButton>
                </form>

                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Suggestions de ravitaillement</h3>
                    <p v-if="suggestions.length === 0" class="mt-3 text-sm text-gray-600">Aucune suggestion visible.</p>
                    <article v-for="suggestion in suggestions" :key="suggestion.id" class="mt-4 border-t border-gray-100 pt-4 text-sm">
                        <p class="font-medium text-brand-navy">{{ suggestion.label }} · {{ suggestion.priority_label }} · {{ suggestion.status_label }}</p>
                        <p>{{ suggestion.request_count }} demande(s) · quantité {{ suggestion.total_quantity }} · période {{ suggestion.observation_days }} jours</p>
                        <p>Boutique {{ suggestion.boutique_quantity }} · Dépôt {{ suggestion.depot_quantity }} · dernière demande {{ suggestion.last_requested_at_label }}</p>
                        <form v-if="canManage" class="mt-2 flex flex-wrap items-end gap-2" @submit.prevent="suggestionForm.post(route('requests.suggestions.update', suggestion.id))">
                            <select v-model="suggestionForm.status" class="rounded-md border-gray-300 text-sm">
                                <option v-for="status in suggestionStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                            </select>
                            <TextInput v-model="suggestionForm.justification" placeholder="Justification" class="block" />
                            <PrimaryButton :disabled="suggestionForm.processing">Mettre à jour</PrimaryButton>
                        </form>
                    </article>
                </section>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <p v-if="requests.data.length === 0" class="p-6 text-sm text-gray-600">Aucune demande enregistrée.</p>
                    <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-brand-cream text-left text-brand-navy">
                            <tr>
                                <th class="px-4 py-3">Article</th>
                                <th class="px-4 py-3">Client</th>
                                <th class="px-4 py-3">Qté</th>
                                <th class="px-4 py-3">Priorité</th>
                                <th class="px-4 py-3">Fréquence</th>
                                <th class="px-4 py-3">Statut</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Auteur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in requests.data" :key="item.id" class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <Link :href="route('requests.show', item.id)" class="font-medium text-brand-navy underline">{{ item.label ?? item.product?.name ?? item.designation }}</Link>
                                </td>
                                <td class="px-4 py-3">{{ item.customer_name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ item.quantity ?? '—' }}</td>
                                <td class="px-4 py-3">{{ item.priority_label }}</td>
                                <td class="px-4 py-3">{{ item.frequency }}</td>
                                <td class="px-4 py-3">{{ item.status_label }}</td>
                                <td class="px-4 py-3">{{ item.requested_at_label }}</td>
                                <td class="px-4 py-3">{{ item.recorder?.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationLinks :links="requests.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
