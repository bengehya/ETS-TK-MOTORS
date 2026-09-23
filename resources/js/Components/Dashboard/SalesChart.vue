<script setup lang="ts">
import EmptyState from '@/Components/Dashboard/EmptyState.vue';

const props = defineProps<{
    label: string;
    empty: boolean;
    points: { key: string; label: string; quantity: number; count: number }[];
}>();

const maxQuantity = () => Math.max(...props.points.map((point) => point.quantity), 1);
</script>

<template>
    <div>
        <p class="text-sm text-gray-600">{{ label }}</p>
        <EmptyState v-if="empty" class="mt-4" message="Aucune vente enregistrée." />
        <div v-else class="mt-4 flex h-48 items-end gap-1">
            <div
                v-for="point in points"
                :key="point.key"
                class="flex min-w-0 flex-1 flex-col items-center justify-end"
            >
                <div
                    class="w-full rounded-t bg-brand-navy"
                    :style="{ height: `${Math.max(4, (point.quantity / maxQuantity()) * 100)}%` }"
                    :title="`${point.label} : ${point.quantity}`"
                />
                <span class="mt-1 truncate text-[10px] text-gray-500">{{ point.label }}</span>
            </div>
        </div>
    </div>
</template>
