<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    href?: string;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'section'"
        :href="href"
        class="rounded-xl border border-brand-gold/40 bg-white p-6 shadow-sm"
    >
        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-gold">
            {{ title }}
        </h3>
        <div class="mt-3">
            <slot />
        </div>
    </component>
</template>
