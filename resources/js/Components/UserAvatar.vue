<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    name: string;
    photoUrl?: string | null;
    size?: 'sm' | 'md' | 'lg';
    decorative?: boolean;
}>(), {
    photoUrl: null,
    size: 'md',
    decorative: false,
});

const initials = computed(() => {
    const parts = props.name.trim().split(/\s+/).filter(Boolean);
    const letters = parts.slice(0, 2).map((part) => part.charAt(0).toLocaleUpperCase('fr'));

    return letters.join('') || '?';
});

const sizeClass = computed(() => {
    if (props.size === 'sm') {
        return 'h-8 w-8 text-xs';
    }

    if (props.size === 'lg') {
        return 'h-16 w-16 text-lg';
    }

    return 'h-10 w-10 text-sm';
});
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-brand-gold bg-brand-navy font-semibold text-brand-cream"
        :class="sizeClass"
        :aria-hidden="decorative ? 'true' : undefined"
    >
        <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="decorative ? '' : `Photo de ${name}`"
            class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
    </span>
</template>
