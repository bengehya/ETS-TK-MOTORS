<script setup lang="ts">
import ArrivalTable from '@/Components/ArrivalTable.vue';

defineProps<{
    title: string;
    arrivals: {
        data: Array<{
            id: number;
            quantity: number;
            supplier_reference: string | null;
            status: string;
            status_label: string;
            created_at: string;
            product: { id: number; code: string; name: string } | null;
            location: { name: string } | null;
            recorder: { name: string } | null;
        }>;
        links: { url: string | null; label: string; active: boolean }[];
        meta: { total: number };
    };
    filters: { q: string; status: string };
    canCreate: boolean;
    canApprove: boolean;
}>();
</script>

<template>
    <ArrivalTable
        :title="title"
        route-name="arrivals.index"
        :arrivals="arrivals"
        :filters="filters"
        :can-create="canCreate"
        :can-approve="canApprove"
        :status-options="[
            { value: 'pending', label: 'En attente' },
            { value: 'validated', label: 'Validé' },
            { value: 'rejected', label: 'Rejeté' },
        ]"
    />
</template>
