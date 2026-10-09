<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Dashboard/EmptyState.vue';
import PeriodTabs from '@/Components/Dashboard/PeriodTabs.vue';
import SalesChart from '@/Components/Dashboard/SalesChart.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
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
    welcome: string;
    profile: {
        name: string;
        photo_url: string | null;
    };
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
        low_stock: {
            threshold_defined: boolean;
            count: number;
            message: string;
            items: { id: number; code: string; name: string; quantity: number; threshold: number }[];
        };
        exhausted: { count: number; items: { id: number | null; code: string | null; name: string | null; quantity: number }[] };
    } | null;
    arrivals: {
        pending: number;
        validated: ArrivalRow[];
        rejected: ArrivalRow[];
    } | null;
    finance?: {
        sales: {
            available: boolean;
            today_count: number;
            today_quantity: number;
            today_amount: string;
            period_count: number;
            period_quantity: number;
            period_amount: string;
        };
        profit: { available: boolean; amount: string; label?: string; by_currency?: { USD?: { gross_profit: string }; CDF?: { gross_profit: string } } };
        cash: { available: boolean; amount: string; usd?: string; cdf?: string };
        expenses: { available: boolean; total: string; recent: { id: number; reference: string; amount: string; reason: string }[] };
        top_sold: { id: number | null; code: string | null; name: string | null; quantity_sold: number; amount: string | null }[];
        least_sold: { id: number | null; code: string | null; name: string | null; quantity_sold: number; amount: string | null }[];
        chart: { label: string; empty: boolean; points: { key: string; label: string; quantity: number; count: number; amount?: string }[] };
    };
}>();

const page = usePage();
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
                <section class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <UserAvatar :name="profile.name" :photo-url="profile.photo_url" size="lg" />
                        <div>
                            <p class="font-display text-2xl uppercase tracking-[0.06em] text-brand-navy">
                                {{ welcome }}
                            </p>
                            <p class="mt-2 text-sm italic text-brand-gold">{{ brand.slogan }}</p>
                        </div>
                    </div>
                </section>

                <template v-if="canViewFinance && finance">
                    <section class="space-y-4">
                        <h3 class="font-display text-lg uppercase tracking-[0.12em] text-brand-navy">Pilotage</h3>
                        <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                            <StatCard title="Ventes du jour" :href="route('sales.index')">
                                <p class="text-3xl font-semibold text-brand-navy">{{ finance.sales.today_amount }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ finance.sales.today_quantity }} article(s) · {{ finance.sales.today_count }} vente(s)
                                </p>
                            </StatCard>
                            <StatCard title="Ventes de la période" :href="route('sales.index')">
                                <p class="text-3xl font-semibold text-brand-navy">{{ finance.sales.period_amount }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ finance.sales.period_quantity }} article(s)</p>
                            </StatCard>
                            <StatCard title="Bénéfice brut" :href="route('sales.index')">
                                <p class="text-3xl font-semibold text-brand-navy">{{ finance.profit.amount }} USD</p>
                                <p v-if="finance.profit.by_currency" class="mt-1 text-sm text-gray-600">CDF {{ finance.profit.by_currency.CDF?.gross_profit ?? '0.00' }}</p>
                            </StatCard>
                            <StatCard title="Caisse" :href="route('cash.index')">
                                <p class="text-3xl font-semibold text-brand-navy">{{ finance.cash.usd ?? finance.cash.amount }} USD</p>
                                <p class="mt-1 text-sm text-gray-600">{{ finance.cash.cdf ?? '0.00' }} CDF</p>
                            </StatCard>
                            <StatCard title="Dépenses validées" :href="route('expenses.index')">
                                <p class="text-3xl font-semibold text-brand-navy">{{ finance.expenses.total }}</p>
                                <EmptyState v-if="finance.expenses.recent.length === 0" class="mt-3" message="Aucune dépense validée sur la période." />
                                <ul v-else class="mt-3 space-y-1 text-sm text-brand-navy">
                                    <li v-for="expense in finance.expenses.recent" :key="expense.id">
                                        {{ expense.reference }} · {{ expense.amount }}
                                    </li>
                                </ul>
                            </StatCard>
                            <StatCard v-if="stock" title="Stock faible" :href="route('alerts.index')">
                                <p class="text-3xl font-semibold text-brand-navy">{{ stock.low_stock.count }}</p>
                                <p class="mt-2 text-sm text-gray-600">{{ stock.low_stock.message }}</p>
                            </StatCard>
                        </div>
                        <p>
                            <Link :href="route('savings.show')" class="text-sm font-medium text-brand-navy underline hover:text-brand-gold">
                                Voir l’épargne suggérée
                            </Link>
                        </p>
                    </section>

                    <section class="grid gap-6 lg:grid-cols-2">
                        <StatCard title="Articles les plus vendus">
                            <EmptyState v-if="finance.top_sold.length === 0" message="Aucune vente enregistrée." />
                            <ul v-else class="space-y-2 text-sm text-brand-navy">
                                <li v-for="item in finance.top_sold" :key="String(item.id ?? item.code)">
                                    <span class="font-semibold">{{ item.name }}</span>
                                    <span class="text-gray-500"> · {{ item.code }} · {{ item.quantity_sold }} vendu(s) · {{ item.amount }}</span>
                                </li>
                            </ul>
                        </StatCard>
                        <StatCard title="Articles les moins vendus">
                            <EmptyState v-if="finance.least_sold.length === 0" message="Aucune vente enregistrée." />
                            <ul v-else class="space-y-2 text-sm text-brand-navy">
                                <li v-for="item in finance.least_sold" :key="String(item.id ?? item.code)">
                                    <span class="font-semibold">{{ item.name }}</span>
                                    <span class="text-gray-500"> · {{ item.code }} · {{ item.quantity_sold }} vendu(s) · {{ item.amount }}</span>
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
                            <p class="mt-2 text-sm text-gray-600">{{ stock.low_stock.message }}</p>
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
                            <p class="text-sm text-gray-600">Enregistrer une marchandise.</p>
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
