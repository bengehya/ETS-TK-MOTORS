<script setup lang="ts">
defineProps<{
    title: string;
    reason: string;
}>();
</script>

<template>
    <section class="rounded-xl border border-brand-navy/15 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-navy/70">
            {{ title }}
        </h3>
        <p class="mt-3 text-sm text-gray-600">{{ reason }}</p>
        <p class="mt-2 text-xs uppercase tracking-wide text-gray-400">Aucune donnée</p>
    </section>
</template>
