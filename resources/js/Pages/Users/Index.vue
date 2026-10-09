<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import UserIdentity from '@/Components/UserIdentity.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

type InvitationRow = {
    id: number;
    name: string;
    email: string;
    civility_label: string;
    role_label: string;
    status: string;
    status_label: string;
    created_at: string | null;
    expires_at: string | null;
    can_revoke: boolean;
    inviter: { name: string; photo_url?: string | null } | null;
};

defineProps<{
    invitations: InvitationRow[];
    activationCode: string | null;
    activationCodeTtlDays: number;
    emailDeliveryAvailable: boolean;
}>();

const revoke = (id: number) => {
    router.delete(route('users.invitations.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Utilisateurs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Utilisateurs</h2>
                <Link :href="route('users.invitations.create')">
                    <PrimaryButton type="button">Inviter un utilisateur</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <section v-if="activationCode" class="rounded-xl border border-brand-gold bg-brand-cream p-5 text-sm text-brand-navy">
                    <p class="font-semibold">Code d’activation à transmettre</p>
                    <p class="mt-1 text-gray-700">
                        Communiquez ce code à la personne invitée.
                        Il expire dans {{ activationCodeTtlDays }} jours, ne sert qu’une fois et ne sera plus affiché.
                    </p>
                    <p class="mt-3 rounded-md bg-white px-3 py-2 text-center font-mono text-2xl tracking-[0.4em] text-brand-navy">{{ activationCode }}</p>
                </section>

                <div class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-brand-cream/60 text-left text-xs uppercase tracking-wide text-brand-navy">
                                <tr>
                                    <th class="px-4 py-3">Personne</th>
                                    <th class="px-4 py-3">E-mail</th>
                                    <th class="px-4 py-3">Civilité</th>
                                    <th class="px-4 py-3">Rôle</th>
                                    <th class="px-4 py-3">Statut</th>
                                    <th class="px-4 py-3">Invité par</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="invitations.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Aucune invitation.</td>
                                </tr>
                                <tr v-for="invitation in invitations" :key="invitation.id">
                                    <td class="px-4 py-3 font-medium text-brand-navy">{{ invitation.name }}</td>
                                    <td class="px-4 py-3">{{ invitation.email }}</td>
                                    <td class="px-4 py-3">{{ invitation.civility_label }}</td>
                                    <td class="px-4 py-3">{{ invitation.role_label }}</td>
                                    <td class="px-4 py-3">
                                        {{ invitation.status_label }}
                                        <span v-if="invitation.expires_at && invitation.status === 'pending'" class="block text-xs text-gray-500">
                                            jusqu’au {{ invitation.expires_at }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><UserIdentity :user="invitation.inviter" /></td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            v-if="invitation.can_revoke"
                                            type="button"
                                            class="text-xs font-semibold uppercase tracking-widest text-brand-navy underline"
                                            @click="revoke(invitation.id)"
                                        >
                                            Révoquer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
