<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const organization = page.props.organization;
const brand = page.props.brand;
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-display text-2xl uppercase tracking-[0.12em] text-brand-navy">
                    Tableau de bord
                </h2>
                <p class="mt-1 text-sm italic text-brand-gold">{{ brand.slogan }}</p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">
                            Utilisateur connecté
                        </h3>
                        <p class="mt-3 text-xl font-semibold text-brand-navy">{{ user?.name }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ user?.email }}</p>
                    </section>

                    <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">
                            Rôle
                        </h3>
                        <p class="mt-3 text-xl font-semibold text-brand-navy">{{ user?.role_label }}</p>
                        <p class="mt-1 font-mono text-sm text-gray-600">{{ user?.role }}</p>
                    </section>

                    <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">
                            Organisation
                        </h3>
                        <p class="mt-3 text-xl font-semibold text-brand-navy">
                            {{ organization?.name }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">{{ brand.city }}</p>
                    </section>
                </div>

                <section class="mt-6 rounded-xl border border-brand-navy/10 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">
                        Fondation
                    </h3>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
                        L’authentification, l’organisation et les rôles sont en place.
                        Les modules métier (stock, ventes, caisse, rapports) seront ajoutés
                        progressivement. Aucune donnée commerciale n’est affichée à ce stade.
                    </p>
                    <p class="mt-4 text-xs text-gray-500">
                        Session sécurisée : expiration après 5 minutes d’inactivité.
                    </p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
