<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import FlashStatus from '@/Components/FlashStatus.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import UserIdentity from '@/Components/UserIdentity.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

type MemberRow = {
    id: number;
    name: string;
    email: string;
    role_label: string;
    status: 'active' | 'suspended';
    status_label: string;
    can_suspend: boolean;
    can_reactivate: boolean;
};

defineProps<{
    members: MemberRow[];
    invitations: InvitationRow[];
    activationCode: string | null;
    activationCodeTtlDays: number;
    emailDeliveryAvailable: boolean;
}>();

const page = usePage();
const memberError = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;

    return errors?.member ?? null;
});

const confirming = ref<null | { id: number; name: string; mode: 'suspend' | 'reactivate' }>(null);

const revoke = (id: number) => {
    router.delete(route('users.invitations.destroy', id), { preserveScroll: true });
};

const ask = (member: MemberRow, mode: 'suspend' | 'reactivate') => {
    confirming.value = { id: member.id, name: member.name, mode };
};

const closeConfirm = () => {
    confirming.value = null;
};

const confirmChange = () => {
    if (!confirming.value) {
        return;
    }

    const current = confirming.value;
    const options = { preserveScroll: true, onFinish: closeConfirm };

    if (current.mode === 'suspend') {
        router.post(route('users.suspend', current.id), {}, options);

        return;
    }

    router.delete(route('users.reactivate', current.id), options);
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

                <p v-if="memberError" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ memberError }}
                </p>

                <section v-if="activationCode" class="rounded-xl border border-brand-gold bg-brand-cream p-5 text-sm text-brand-navy">
                    <p class="font-semibold">Code d’activation à transmettre</p>
                    <p class="mt-1 text-gray-700">
                        Communiquez ce code à la personne invitée.
                        Il expire dans {{ activationCodeTtlDays }} jours, ne sert qu’une fois et ne sera plus affiché.
                    </p>
                    <p class="mt-3 rounded-md bg-white px-3 py-2 text-center font-mono text-2xl tracking-[0.4em] text-brand-navy">{{ activationCode }}</p>
                </section>

                <section class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <h3 class="border-b border-brand-gold/30 bg-brand-cream/60 px-4 py-3 font-display text-sm uppercase tracking-[0.12em] text-brand-navy">
                        Comptes
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-brand-navy">
                                <tr>
                                    <th class="px-4 py-3">Personne</th>
                                    <th class="px-4 py-3">E-mail</th>
                                    <th class="px-4 py-3">Rôle</th>
                                    <th class="px-4 py-3">Statut</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="member in members" :key="member.id">
                                    <td class="px-4 py-3 font-medium text-brand-navy">{{ member.name }}</td>
                                    <td class="px-4 py-3">{{ member.email }}</td>
                                    <td class="px-4 py-3">{{ member.role_label }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="member.status === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                        >
                                            {{ member.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <DangerButton v-if="member.can_suspend" type="button" @click="ask(member, 'suspend')">
                                            Suspendre
                                        </DangerButton>
                                        <SecondaryButton v-if="member.can_reactivate" type="button" @click="ask(member, 'reactivate')">
                                            Réactiver
                                        </SecondaryButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-brand-gold/40 bg-white shadow-sm">
                    <h3 class="border-b border-brand-gold/30 bg-brand-cream/60 px-4 py-3 font-display text-sm uppercase tracking-[0.12em] text-brand-navy">
                        Invitations
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-brand-navy">
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
                </section>
            </div>
        </div>

        <Modal :show="confirming !== null" max-width="md" @close="closeConfirm">
            <div class="p-6">
                <h2 class="text-lg font-medium text-brand-navy">
                    {{ confirming?.mode === 'reactivate' ? 'Réactiver ce compte ?' : 'Suspendre ce compte ?' }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    <template v-if="confirming?.mode === 'suspend'">
                        {{ confirming.name }} ne pourra plus se connecter.
                    </template>
                    <template v-else>
                        {{ confirming?.name }} pourra à nouveau se connecter.
                    </template>
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeConfirm">Annuler</SecondaryButton>
                    <DangerButton v-if="confirming?.mode === 'suspend'" type="button" @click="confirmChange">
                        Suspendre
                    </DangerButton>
                    <PrimaryButton v-else type="button" @click="confirmChange">Réactiver</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
