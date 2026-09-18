<script setup lang="ts">
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = page.props.auth.user;
const organization = page.props.organization;
const brand = page.props.brand;
const canSearch = computed(() => Boolean(user?.permissions.includes('search_products')));
const canRecordArrivals = computed(() => Boolean(user?.permissions.includes('record_stock_receipts')));
</script>

<template>
    <div class="min-h-screen bg-brand-cream">
        <nav class="border-b-4 border-brand-gold bg-brand-navy">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 justify-between">
                    <div class="flex min-w-0">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-3">
                                <span class="flex h-14 w-14 overflow-hidden rounded-full border-2 border-brand-gold bg-brand-cream">
                                    <ApplicationLogo />
                                </span>
                                <span class="hidden min-w-0 sm:block">
                                    <span class="block font-display text-lg uppercase tracking-[0.18em] text-white">
                                        {{ brand.name }}
                                    </span>
                                    <span class="block truncate text-xs text-brand-gold">
                                        {{ organization?.name ?? brand.company }}
                                    </span>
                                </span>
                            </Link>
                        </div>

                        <div class="hidden space-x-5 lg:-my-px lg:ms-8 lg:flex">
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                Tableau de bord
                            </NavLink>
                            <NavLink
                                v-if="canSearch"
                                :href="route('products.index')"
                                :active="route().current('products.*')"
                            >
                                Articles
                            </NavLink>
                            <NavLink
                                v-if="canRecordArrivals"
                                :href="route('arrivals.index')"
                                :active="route().current('arrivals.*')"
                            >
                                Arrivages
                            </NavLink>
                            <NavLink
                                v-if="canSearch"
                                :href="route('stocks.boutique')"
                                :active="route().current('stocks.boutique')"
                            >
                                Boutique
                            </NavLink>
                            <NavLink
                                v-if="canSearch"
                                :href="route('stocks.depot')"
                                :active="route().current('stocks.depot')"
                            >
                                Dépôt
                            </NavLink>
                            <NavLink
                                v-if="canSearch"
                                :href="route('stocks.overview')"
                                :active="route().current('stocks.overview')"
                            >
                                Stocks
                            </NavLink>
                            <NavLink
                                v-if="canSearch"
                                :href="route('stocks.movements')"
                                :active="route().current('stocks.movements')"
                            >
                                Mouvements
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden lg:ms-6 lg:flex lg:items-center">
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-brand-gold/40 bg-brand-navy-deep px-3 py-2 text-sm font-medium leading-4 text-brand-cream transition duration-150 ease-in-out hover:border-brand-gold hover:text-white focus:outline-none"
                                        >
                                            {{ user?.name }}
                                            <svg
                                                class="-me-0.5 ms-2 h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profil
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Déconnexion
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center lg:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-brand-cream transition duration-150 ease-in-out hover:bg-brand-navy-deep hover:text-white focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="bg-brand-navy-deep lg:hidden"
            >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Tableau de bord
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="canSearch"
                            :href="route('products.index')"
                            :active="route().current('products.*')"
                        >
                            Articles
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="canRecordArrivals"
                            :href="route('arrivals.index')"
                            :active="route().current('arrivals.*')"
                        >
                            Arrivages
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="canSearch"
                            :href="route('stocks.boutique')"
                            :active="route().current('stocks.boutique')"
                        >
                            Boutique
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="canSearch"
                            :href="route('stocks.depot')"
                            :active="route().current('stocks.depot')"
                        >
                            Dépôt
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="canSearch"
                            :href="route('stocks.overview')"
                            :active="route().current('stocks.overview')"
                        >
                            Stocks
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="canSearch"
                            :href="route('stocks.movements')"
                            :active="route().current('stocks.movements')"
                        >
                            Mouvements
                        </ResponsiveNavLink>
                    </div>

                <div class="border-t border-brand-gold/30 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-white">
                            {{ user?.name }}
                        </div>
                        <div class="text-sm font-medium text-brand-gold">
                            {{ user?.role_label }}
                        </div>
                        <div class="text-sm font-medium text-brand-cream/80">
                            {{ user?.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1 bg-white">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Profil
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            Déconnexion
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="border-t border-brand-gold/40 bg-brand-navy py-4 text-center text-xs text-brand-cream/80">
            <p>{{ brand.company }} · {{ brand.city }} · Qualité · Service · Confiance</p>
        </footer>
    </div>
</template>
