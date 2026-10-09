<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    roles: { value: string; label: string }[];
    civilities: { value: string; label: string }[];
}>();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    civility: '',
    role: '',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('users.invitations.store'));
};
</script>

<template>
    <Head title="Inviter un utilisateur" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">Inviter un utilisateur</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-4 rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <InputLabel for="first_name" value="Prénom" />
                        <TextInput id="first_name" v-model="form.first_name" type="text" class="mt-1 block w-full" required autocomplete="given-name" />
                        <InputError class="mt-2" :message="form.errors.first_name" />
                    </div>

                    <div>
                        <InputLabel for="last_name" value="Nom" />
                        <TextInput id="last_name" v-model="form.last_name" type="text" class="mt-1 block w-full" required autocomplete="family-name" />
                        <InputError class="mt-2" :message="form.errors.last_name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="E-mail" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autocomplete="off" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="civility" value="Civilité" />
                        <select id="civility" v-model="form.civility" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                            <option value="" disabled>Choisir</option>
                            <option v-for="civility in civilities" :key="civility.value" :value="civility.value">{{ civility.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.civility" />
                    </div>

                    <div>
                        <InputLabel for="role" value="Rôle" />
                        <select id="role" v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
                            <option value="" disabled>Choisir</option>
                            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="route('users.invitations.index')" class="text-sm text-brand-navy underline">Annuler</Link>
                        <PrimaryButton :disabled="form.processing">Créer l’invitation</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
