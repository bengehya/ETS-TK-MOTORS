<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    token: string;
    invalid: boolean;
    invitation: {
        name: string;
        email: string;
        civility_label: string;
        role_label: string;
    } | null;
}>();

const form = useForm({
    token: props.token,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (form.processing) {
        return;
    }

    form.post(route('invitations.accept.store', { token: props.token }), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Activer le compte" />

        <div v-if="invalid || !invitation" class="text-sm text-brand-navy">
            Cette invitation n’est plus valable.
        </div>

        <form v-else class="space-y-4" @submit.prevent="submit">
            <div>
                <p class="text-sm text-gray-600">Activation du compte pour</p>
                <p class="mt-1 font-semibold text-brand-navy">{{ invitation.civility_label }} {{ invitation.name }}</p>
                <p class="text-sm text-gray-600">{{ invitation.email }} · {{ invitation.role_label }}</p>
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
                <InputError class="mt-2" :message="form.errors.token" />
            </div>

            <div class="flex justify-end">
                <PrimaryButton :disabled="form.processing">Activer le compte</PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
