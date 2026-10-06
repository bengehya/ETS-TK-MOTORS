<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user!;

const form = useForm<{ photo: File | null }>({
    photo: null,
});

const onFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.photo = input.files?.[0] ?? null;
};

const submit = () => {
    form.post(route('profile.photo.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const remove = () => {
    router.delete(route('profile.photo.destroy'), { preserveScroll: true });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-brand-navy">Photo de profil</h2>
            <p class="mt-1 text-sm text-gray-600">
                JPG, PNG ou WEBP, 2 Mo maximum. Cette photo apparaît à côté de votre nom dans les actions.
            </p>
        </header>

        <div class="mt-6 flex items-center gap-4">
            <UserAvatar :name="user.name" :photo-url="user.photo_url" size="lg" />
            <div>
                <p class="text-sm font-medium text-brand-navy">{{ user.name }}</p>
                <p v-if="!user.photo_url" class="text-xs text-gray-500">Aucune photo pour le moment.</p>
            </div>
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <div>
                <input
                    id="profile_photo"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-brand-navy file:mr-4 file:rounded-md file:border-0 file:bg-brand-navy file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white"
                    @change="onFile"
                />
                <InputError class="mt-2" :message="form.errors.photo" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <PrimaryButton :disabled="form.processing || !form.photo">Enregistrer la photo</PrimaryButton>
                <SecondaryButton v-if="user.photo_url" type="button" @click="remove">Supprimer</SecondaryButton>
            </div>
        </form>
    </section>
</template>
