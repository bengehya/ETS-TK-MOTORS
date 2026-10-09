<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    canRegister?: boolean;
    status?: string;
    error?: string;
}>();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <div v-if="error" class="mb-4 text-sm font-medium text-red-700">
            {{ error }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Adresse e-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-brand-navy underline hover:text-brand-gold focus:outline-none"
                >
                    Mot de passe oublié ?
                </Link>

                <PrimaryButton
                    class="ms-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Se connecter
                </PrimaryButton>
            </div>

            <p class="mt-6 text-center text-sm text-gray-600">
                Vous avez reçu un code d’invitation ?
                <Link :href="route('invitations.accept')" class="font-medium text-brand-navy underline hover:text-brand-gold">
                    Activer mon compte
                </Link>
            </p>

            <p v-if="canRegister" class="mt-6 text-center text-sm text-gray-600">
                Premier accès de l’organisation ?
                <Link :href="route('register')" class="font-medium text-brand-navy underline hover:text-brand-gold">
                    Créer le compte patron
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
