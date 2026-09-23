<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Dashboard/EmptyState.vue';
import PeriodTabs from '@/Components/Dashboard/PeriodTabs.vue';
import SalesChart from '@/Components/Dashboard/SalesChart.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import UnavailableModule from '@/Components/Dashboard/UnavailableModule.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

type ArrivalRow = {
    id: number;
    quantity: number;
    status_label: string;
    created_at: string | null;
    product: { name: string; code: string } | null;
    location: { name: string } | null;
};

defineProps<{
    periode: string;
    periodes: { value: string; label: string }[];
    canViewCatalog: boolean;
    canRecordArrivals: boolean;
    canValidateArrivals: boolean;
    canViewFinance: boolean;
    stock: {
        boutique_quantity: number;
        depot_quantity: number;
        active_products: number;
        low_stock: { threshold_defined: boolean; message: string };
        exhausted: { count: number; items: { id: number | null; code: string | null; name: string | null; quantity: number }[] };
    } | null;
    arrivals: {
        pending: number;
        validated: ArrivalRow[];
        rejected: ArrivalRow[];
    } | null;
    finance?: {
        sales: { available: boolean; reason: string; today_count: number; today_quantity: number; today_amount: number | null };
        profit: { available: boolean; reason: string; amount: number | null };
        cash: { available: boolean; reason: string; amount: number | null };
        expenses: { available: boolean; reason: string; total: number | null; recent: unknown[] };
        top_sold: { id: number | null; code: string | null; name: string | null; quantity_sold: number; amount: number | null }[];
        least_sold: { id: number | null; code: string | null; name: string | null; quantity_sold: number; amount: number | null }[];
        chart: { label: string; empty: boolean; points: { key: string; label: string; quantity: number; count: number }[] };
    };
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
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <StatCard title="Utilisateur connecté">
                        <p class="text-xl font-semibold text-brand-navy">{{ user?.name }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ user?.email }}</p>
                    </StatCard>
                    <StatCard title="Rôle">
                        <p class="text-xl font-semibold text-brand-navy">{{ user?.role_label }}</p>
                        <p class="mt-1 font-mono text-sm text-gray-600">{{ user?.role }}</p>
                    </StatCard>
                    <StatCard title="Organisation">
                        <p class="text-xl font-semibold text-brand-navy">{{ organization?.name }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ brand.city }}</p>
                    </StatCard>
                </div>

                <template v-if="canViewFinance && finance">
                    <section class="space-y-4">
                        <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Pilotage</h3>
                        <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                            <UnavailableModule title="Ventes du jour" :reason="finance.sales.reason" />
                            <UnavailableModule title="Bénéfice du jour" :reason="finance.profit.reason" />
                            <UnavailableModule title="Montant en caisse" :reason="finance.cash.reason" />
                            <UnavailableModule title="Dépenses" :reason="finance.expenses.reason" />
                            <StatCard v-if="stock" title="Stock faible" :href="route('stocks.boutique')">
                                <EmptyState :message="stock.low_stock.message" />
                                <p class="mt-3 text-sm text-brand-navy">
                                    Articles épuisés en boutique : <span class="font-semibold">{{ stock.exhausted.count }}</span>
                                </p>
                            </StatCard>
                        </div>
                    </section>

                    <section class="grid gap-6 lg:grid-cols-2">
                        <StatCard title="Articles les plus vendus">
                            <EmptyState v-if="finance.top_sold.length === 0" message="Aucune vente enregistrée." />
                            <ul v-else class="space-y-2 text-sm text-brand-navy">
                                <li v-for="item in finance.top_sold" :key="String(item.id ?? item.code)">
                                    <span class="font-semibold">{{ item.name }}</span>
                                    <span class="text-gray-500"> · {{ item.code }} · {{ item.quantity_sold }} vendu(s)</span>
                                </li>
                            </ul>
                        </StatCard>
                        <StatCard title="Articles les moins vendus">
                            <EmptyState v-if="finance.least_sold.length === 0" message="Aucune vente enregistrée." />
                            <ul v-else class="space-y-2 text-sm text-brand-navy">
                                <li v-for="item in finance.least_sold" :key="String(item.id ?? item.code)">
                                    <span class="font-semibold">{{ item.name }}</span>
                                    <span class="text-gray-500"> · {{ item.code }} · {{ item.quantity_sold }} vendu(s)</span>
                                </li>
                            </ul>
                        </StatCard>
                    </section>

                    <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">
                                Graphique des ventes
                            </h3>
                            <PeriodTabs :periode="periode" :periodes="periodes" />
                        </div>
                        <SalesChart class="mt-4" :label="finance.chart.label" :empty="finance.chart.empty" :points="finance.chart.points" />
                    </section>
                </template>

                <section v-if="stock && canViewCatalog" class="space-y-4">
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Stock</h3>
                    <div class="grid gap-6 md:grid-cols-3">
                        <StatCard title="Articles actifs" :href="route('products.index')">
                            <p class="text-3xl font-semibold text-brand-navy">{{ stock.active_products }}</p>
                        </StatCard>
                        <StatCard title="Stock boutique" :href="route('stocks.boutique')">
                            <p class="text-3xl font-semibold text-brand-navy">{{ stock.boutique_quantity }}</p>
                            <p class="mt-1 text-xs text-gray-500">Disponible à la vente</p>
                        </StatCard>
                        <StatCard title="Stock dépôt" :href="route('stocks.depot')">
                            <p class="text-3xl font-semibold text-brand-navy">{{ stock.depot_quantity }}</p>
                            <p class="mt-1 text-xs text-gray-500">Non disponible à la vente</p>
                        </StatCard>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <Link :href="route('stocks.boutique')" class="font-medium text-brand-navy underline hover:text-brand-gold">Voir la Boutique</Link>
                        <Link :href="route('stocks.depot')" class="font-medium text-brand-navy underline hover:text-brand-gold">Voir le Dépôt</Link>
                        <Link :href="route('stocks.movements')" class="font-medium text-brand-navy underline hover:text-brand-gold">Voir les mouvements</Link>
                    </div>
                    <StatCard v-if="stock.exhausted.count > 0" title="Articles épuisés en boutique">
                        <ul class="space-y-2 text-sm text-brand-navy">
                            <li v-for="item in stock.exhausted.items" :key="String(item.id ?? item.code)">
                                {{ item.name }} <span class="text-gray-500">({{ item.code }})</span>
                            </li>
                        </ul>
                    </StatCard>
                </section>

                <section v-if="arrivals && canRecordArrivals" class="space-y-4">
                    <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Approvisionnements</h3>
                    <div class="grid gap-6 md:grid-cols-2">
                        <StatCard title="Arrivages en attente" :href="route('arrivals.pending')">
                            <p class="text-3xl font-semibold text-brand-navy">{{ arrivals.pending }}</p>
                        </StatCard>
                        <StatCard title="Nouvel arrivage" :href="route('arrivals.create')">
                            <p class="text-sm text-gray-600">Enregistrer une marchandise. Le stock n’augmente qu’après validation d’un patron.</p>
                        </StatCard>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <StatCard title="Derniers arrivages validés">
                            <EmptyState v-if="arrivals.validated.length === 0" message="Aucune donnée" />
                            <ul v-else class="space-y-2 text-sm text-brand-navy">
                                <li v-for="arrival in arrivals.validated" :key="arrival.id">
                                    <Link :href="route('arrivals.show', arrival.id)" class="hover:text-brand-gold">
                                        {{ arrival.product?.name }} · {{ arrival.quantity }} · {{ arrival.created_at }}
                                    </Link>
                                </li>
                            </ul>
                        </StatCard>
                        <StatCard title="Derniers arrivages rejetés">
                            <EmptyState v-if="arrivals.rejected.length === 0" message="Aucune donnée" />
                            <ul v-else class="space-y-2 text-sm text-brand-navy">
                                <li v-for="arrival in arrivals.rejected" :key="arrival.id">
                                    <Link :href="route('arrivals.show', arrival.id)" class="hover:text-brand-gold">
                                        {{ arrival.product?.name }} · {{ arrival.quantity }} · {{ arrival.created_at }}
                                    </Link>
                                </li>
                            </ul>
                        </StatCard>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <Link :href="route('arrivals.index')" class="font-medium text-brand-navy underline hover:text-brand-gold">Tous les arrivages</Link>
                        <Link :href="route('arrivals.pending')" class="font-medium text-brand-navy underline hover:text-brand-gold">En attente</Link>
                        <Link :href="route('arrivals.history')" class="font-medium text-brand-navy underline hover:text-brand-gold">Historique</Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
