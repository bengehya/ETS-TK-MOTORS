<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    periode: string;
    periodes: { value: string; label: string }[];
}>();
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Link
            v-for="option in periodes"
            :key="option.value"
            :href="route('dashboard', { periode: option.value })"
            preserve-scroll
            class="rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-widest transition"
            :class="
                periode === option.value
                    ? 'border-brand-navy bg-brand-navy text-white'
                    : 'border-brand-navy/20 bg-white text-brand-navy hover:border-brand-gold'
            "
        >
            {{ option.label }}
        </Link>
    </div>
</template>
