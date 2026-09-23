<script setup lang="ts">
import TkMotorsLoader from '@/Components/TkMotorsLoader.vue';
import { useAppLoader } from '@/composables/useAppLoader';

const { visible } = useAppLoader();
</script>

<template>
    <Teleport to="body">
        <Transition name="tk-loader-fade">
            <div
                v-if="visible"
                class="fixed inset-0 z-[80] flex cursor-wait items-center justify-center bg-brand-cream/80 select-none"
                aria-busy="true"
                aria-modal="true"
            >
                <TkMotorsLoader size="lg" label="Chargement..." />
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.tk-loader-fade-enter-active,
.tk-loader-fade-leave-active {
    transition: opacity 220ms ease;
}

.tk-loader-fade-enter-from,
.tk-loader-fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .tk-loader-fade-enter-active,
    .tk-loader-fade-leave-active {
        transition: none;
    }
}
</style>
