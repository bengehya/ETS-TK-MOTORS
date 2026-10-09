<script setup lang="ts">
import FlashStatus from '@/Components/FlashStatus.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    enabled: boolean;
    started_at: string | null;
    started_by: { name: string } | null;
}>();

const confirming = ref(false);

const activate = () => {
    router.post(route('maintenance.store'), {}, {
        preserveScroll: true,
        onFinish: () => {
            confirming.value = false;
        },
    });
};

const deactivate = () => {
    router.delete(route('maintenance.destroy'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Mode maintenance" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Mode maintenance</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
                <FlashStatus />

                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <p
                        class="inline-flex rounded-full px-3 py-1 text-sm font-semibold"
                        :class="enabled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                    >
                        {{ enabled ? 'Mode maintenance actif' : 'Mode maintenance inactif' }}
                    </p>

                    <p v-if="enabled" class="mt-4 text-sm text-gray-700">
                        Les employés ne peuvent plus utiliser l’application.
                        <span v-if="started_at">Activé le {{ started_at }}<span v-if="started_by"> par {{ started_by.name }}</span>.</span>
                    </p>
                    <p v-else class="mt-4 text-sm text-gray-700">
                        L’application est ouverte aux employés.
                    </p>

                    <div class="mt-6">
                        <PrimaryButton v-if="!enabled" type="button" @click="confirming = true">
                            Activer
                        </PrimaryButton>
                        <SecondaryButton v-else type="button" @click="deactivate">
                            Désactiver
                        </SecondaryButton>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-brand-navy">Activer le mode maintenance ?</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Les employés ne pourront plus utiliser l’application tant qu’il est actif.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="confirming = false">Annuler</SecondaryButton>
                    <PrimaryButton type="button" @click="activate">Activer</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
