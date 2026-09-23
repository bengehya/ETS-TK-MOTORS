<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        size?: 'sm' | 'md' | 'lg';
        label?: string | null;
    }>(),
    {
        size: 'md',
        label: null,
    },
);

const frameClass = computed(() => {
    if (props.size === 'sm') {
        return 'h-16 w-16 border-[3px] sm:h-20 sm:w-20';
    }

    if (props.size === 'lg') {
        return 'h-28 w-28 border-4 sm:h-36 sm:w-36';
    }

    return 'h-20 w-20 border-4 sm:h-24 sm:w-24';
});
</script>

<template>
    <div class="flex flex-col items-center justify-center" role="status" aria-live="polite">
        <span
            class="tk-loader-spin flex overflow-hidden rounded-full border-brand-gold bg-white shadow-lg"
            :class="frameClass"
        >
            <ApplicationLogo />
        </span>
        <span v-if="label" class="mt-4 text-[11px] font-medium uppercase tracking-[0.28em] text-brand-navy/45">
            {{ label }}
        </span>
        <span class="sr-only">Chargement</span>
    </div>
</template>

<style scoped>
.tk-loader-spin {
    animation: tk-loader-rotate 1.8s linear infinite;
}

@keyframes tk-loader-rotate {
    to {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .tk-loader-spin {
        animation: none;
    }
}
</style>
