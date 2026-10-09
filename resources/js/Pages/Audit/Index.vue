<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    logs: {
        data: {
            id: number;
            action: string;
            result: string | null;
            actor_role: string | null;
            correlation_id: string | null;
            reason: string | null;
            created_at_label: string | null;
            subject_type: string | null;
            subject_id: number | null;
            user: { name: string; photo_url: string | null } | null;
            old_values: Record<string, unknown> | null;
            new_values: Record<string, unknown> | null;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    actions: string[];
    filters: { action: string; date_from: string; date_to: string };
}>();

const form = useForm({
    action: props.filters.action,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const submit = () => form.get(route('audit.index'), { preserveState: true });

const resultLabel = (result: string | null): string => {
    if (result === 'success') {
        return 'Réussi';
    }

    if (result === 'failure') {
        return 'Échec';
    }

    if (result === 'denied') {
        return 'Refusé';
    }

    return '—';
};

const roleLabel = (role: string | null): string => {
    if (role === 'BOSS_PRINCIPAL') {
        return 'Patron principal';
    }

    if (role === 'BOSS_SECONDAIRE') {
        return 'Patron secondaire';
    }

    if (role === 'EMPLOYE') {
        return 'Employé';
    }

    return '—';
};

const contextLines = (values: Record<string, unknown> | null): string[] => {
    if (!values) {
        return [];
    }

    return Object.entries(values)
        .filter((entry) => ['string', 'number', 'boolean'].includes(typeof entry[1]) || entry[1] === null)
        .slice(0, 8)
        .map(([key, value]) => `${key} : ${value ?? '—'}`);
};
</script>

<template>
    <Head title="Audit" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Audit</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <form class="flex flex-wrap items-end gap-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Action" />
                        <select v-model="form.action" class="mt-1 block rounded-md border-gray-300 text-sm">
                            <option value="">Toutes</option>
                            <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Du" />
                        <TextInput v-model="form.date_from" type="date" class="mt-1 block" />
                    </div>
                    <div>
                        <InputLabel value="Au" />
                        <TextInput v-model="form.date_to" type="date" class="mt-1 block" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Filtrer</PrimaryButton>
                </form>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <p v-if="logs.data.length === 0" class="p-6 text-sm text-gray-600">Aucun événement d’audit.</p>
                    <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-brand-cream text-left text-brand-navy">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Utilisateur</th>
                                <th class="px-4 py-3">Rôle</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3">Résultat</th>
                                <th class="px-4 py-3">Sujet</th>
                                <th class="px-4 py-3">Motif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="border-t border-gray-100 align-top">
                                <td class="px-4 py-3">{{ log.created_at_label }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <UserAvatar v-if="log.user" :name="log.user.name" :photo-url="log.user.photo_url" size="sm" decorative />
                                        {{ log.user?.name ?? 'Compte supprimé' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ roleLabel(log.actor_role) }}</td>
                                <td class="px-4 py-3">{{ log.action }}</td>
                                <td class="px-4 py-3">{{ resultLabel(log.result) }}</td>
                                <td class="px-4 py-3">{{ log.subject_type ?? '—' }} <template v-if="log.subject_id">#{{ log.subject_id }}</template></td>
                                <td class="px-4 py-3">
                                    <p>{{ log.reason ?? '—' }}</p>
                                    <p v-if="log.correlation_id" class="text-xs text-gray-500">Corrélation {{ log.correlation_id }}</p>
                                    <p v-for="line in contextLines(log.new_values)" :key="line" class="text-xs text-gray-600">{{ line }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <PaginationLinks :links="logs.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
