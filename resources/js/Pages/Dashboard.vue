<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    catalog: {
        active_products: number;
        boutique_quantity: number;
        depot_quantity: number;
    };
    canViewCatalog: boolean;
    arrivals: {
        pending: number;
    };
    canRecordArrivals: boolean;
    canValidateArrivals: boolean;
}>();

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

                <div v-if="canRecordArrivals" class="mt-6 grid gap-6 md:grid-cols-2">
                    <Link :href="route('arrivals.pending')" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">
                            {{ canValidateArrivals ? 'Arrivages en attente' : 'Mes arrivages en attente' }}
                        </h3>
                        <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ arrivals.pending }}</p>
                    </Link>
                    <Link :href="route('arrivals.create')" class="rounded-xl border border-brand-navy/20 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Nouvel arrivage</h3>
                        <p class="mt-3 text-sm text-gray-600">Enregistrer une marchandise. Le stock n’augmente qu’après validation d’un patron.</p>
                    </Link>
                </div>

                <div v-if="canViewCatalog" class="mt-6 grid gap-6 md:grid-cols-3">
                    <Link :href="route('products.index')" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">Articles actifs</h3>
                        <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ catalog.active_products }}</p>
                    </Link>
                    <Link :href="route('stocks.boutique')" class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">Stock boutique</h3>
                        <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ catalog.boutique_quantity }}</p>
                        <p class="mt-1 text-xs text-gray-500">Disponible à la vente</p>
                    </Link>
                    <Link :href="route('stocks.depot')" class="rounded-xl border border-brand-navy/20 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Stock dépôt</h3>
                        <p class="mt-3 text-3xl font-semibold text-brand-navy">{{ catalog.depot_quantity }}</p>
                        <p class="mt-1 text-xs text-gray-500">Non disponible à la vente</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
