<script setup lang="ts">
import LocationView from '@/Pages/Stock/LocationView.vue';

defineProps<{
    location: { id: number; type: string; name: string; is_sellable: boolean };
    rows: {
        data: Array<{
            inventory: { quantity: number; location: { id: number; name: string; is_sellable: boolean } };
            product: { id: number; code: string; name: string; category: string; sale_price: string; is_active: boolean };
        }>;
        links: { url: string | null; label: string; active: boolean }[];
        meta: { total: number };
    };
    filters: { q: string; category: string; status: string };
    categories: string[];
    canMutateStock: boolean;
    totalQuantity: number;
}>();
</script>

<template>
    <LocationView
        :location="location"
        :rows="rows"
        :filters="filters"
        :categories="categories"
        :can-mutate-stock="canMutateStock"
        :total-quantity="totalQuantity"
    />
</template>
