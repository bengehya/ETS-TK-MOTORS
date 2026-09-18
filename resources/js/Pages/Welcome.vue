<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const brand = usePage().props.brand;
</script>

<template>
    <Head title="Accueil" />

    <div class="min-h-screen bg-brand-cream">
        <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
            <header class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-14 w-14 overflow-hidden rounded-full border-2 border-brand-navy bg-white">
                        <ApplicationLogo />
                    </span>
                    <div>
                        <p class="font-display text-xl uppercase tracking-[0.18em] text-brand-navy">
                            {{ brand.name }}
                        </p>
                        <p class="text-sm italic text-brand-gold">{{ brand.slogan }}</p>
                    </div>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-3 text-sm font-medium">
                    <Link
                        :href="route('login')"
                        class="rounded-md border border-brand-navy px-4 py-2 text-brand-navy transition hover:bg-brand-navy hover:text-white"
                    >
                        Connexion
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md bg-brand-navy px-4 py-2 text-white transition hover:bg-brand-navy-deep"
                    >
                        Premier compte
                    </Link>
                </nav>
            </header>

            <main class="flex flex-1 flex-col items-center justify-center py-16 text-center">
                <span class="mb-6 flex h-40 w-40 overflow-hidden rounded-full border-4 border-brand-gold bg-white shadow-lg sm:h-52 sm:w-52">
                    <ApplicationLogo />
                </span>
                <h1 class="font-display text-4xl uppercase tracking-[0.2em] text-brand-navy sm:text-5xl">
                    {{ brand.name }}
                </h1>
                <p class="mt-3 text-lg font-medium text-brand-navy/80">
                    {{ brand.company }}
                </p>
                <p class="mt-2 text-xl italic text-brand-gold">{{ brand.slogan }}</p>
                <p class="mt-6 max-w-xl text-sm text-brand-navy/70">
                    Application métier de gestion — Kinshasa. Connexion réservée aux membres de l’organisation.
                </p>
            </main>

            <footer class="border-t border-brand-gold/40 py-4 text-center text-xs text-brand-navy/70">
                {{ brand.company }} · {{ brand.city }} · Qualité · Service · Confiance
            </footer>
        </div>
    </div>
</template>
