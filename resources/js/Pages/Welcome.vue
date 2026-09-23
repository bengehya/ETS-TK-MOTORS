<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

let timer: ReturnType<typeof setTimeout> | null = null;

const continueFromSplash = () => {
    const destination = usePage().props.auth.user ? route('dashboard') : route('login');

    router.visit(destination, { replace: true });
};

onMounted(() => {
    timer = setTimeout(continueFromSplash, 4000);
});

onUnmounted(() => {
    if (timer !== null) {
        clearTimeout(timer);
    }
});
</script>

<template>
    <Head title="TK MOTORS" />

    <div class="flex min-h-screen items-center justify-center bg-brand-cream">
        <span class="splash-fade flex h-40 w-40 overflow-hidden rounded-full border-4 border-brand-gold bg-white shadow-lg sm:h-52 sm:w-52">
            <ApplicationLogo />
        </span>
    </div>
</template>

<style scoped>
.splash-fade {
    animation: splash-in 700ms ease-out both;
}

@keyframes splash-in {
    from {
        opacity: 0;
        transform: scale(0.98);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .splash-fade {
        animation: none;
    }
}
</style>
