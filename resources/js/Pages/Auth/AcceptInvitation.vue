<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    codeTtlDays: number;
}>();

const form = useForm({
    code: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('invitations.accept.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Activer le compte" />

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <p class="text-sm text-gray-600">
                    Saisissez le code à 5 chiffres remis par votre patron. Il est valable {{ codeTtlDays }} jours et ne sert qu’une fois.
                    Choisissez ensuite votre mot de passe : le code ne le remplace pas.
                </p>
            </div>

            <div>
                <InputLabel for="code" value="Code d’invitation" />
                <TextInput
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    maxlength="5"
                    pattern="[0-9]{5}"
                    class="mt-1 block w-full tracking-[0.4em]"
                    required
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div>
                <InputLabel for="password" value="Mot de passe" />
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-between">
                <Link :href="route('login')" class="text-sm text-brand-navy underline">Retour à la connexion</Link>
                <PrimaryButton :disabled="form.processing">Activer le compte</PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
