<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const brand = usePage().props.brand;

let timer: ReturnType<typeof setTimeout> | null = null;

const skipWait = () => {
    if (timer !== null) {
        clearTimeout(timer);
        timer = null;
    }
};

const goToLogin = () => {
    skipWait();
    router.visit(route('login'), { replace: true });
};

onMounted(() => {
    timer = setTimeout(goToLogin, 2500);
});

onUnmounted(() => {
    if (timer !== null) {
        clearTimeout(timer);
    }
});
</script>

<template>
    <Head title="TK MOTORS" />

    <div class="min-h-screen bg-brand-cream">
        <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
            <main class="flex flex-1 flex-col items-center justify-center py-12 text-center">
                <div class="splash-fade flex flex-col items-center">
                    <span class="mb-6 flex h-40 w-40 overflow-hidden rounded-full border-4 border-brand-gold bg-white shadow-lg sm:h-52 sm:w-52">
                        <ApplicationLogo />
                    </span>
                    <h1 class="font-display text-4xl uppercase tracking-[0.2em] text-brand-navy sm:text-5xl">
                        {{ brand.name }}
                    </h1>
                    <p class="mt-3 text-lg font-medium text-brand-navy/80">
                        {{ brand.company }}
                    </p>
                    <p class="mt-2 text-xl italic text-brand-gold">{{ brand.slogan }}</p>
                </div>

                <div v-if="canLogin" class="splash-fade-late mt-10 flex flex-col items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="rounded-md border border-brand-navy bg-brand-navy px-8 py-3 text-sm font-semibold uppercase tracking-widest text-white transition hover:bg-brand-navy-deep"
                        @click="skipWait"
                    >
                        Connexion
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="text-sm font-medium text-brand-navy underline hover:text-brand-gold"
                        @click="skipWait"
                    >
                        Premier compte
                    </Link>
                </div>
            </main>

            <footer class="border-t border-brand-gold/40 py-4 text-center text-xs text-brand-navy/70">
                {{ brand.company }} · {{ brand.city }} · Qualité · Service · Confiance
            </footer>
        </div>
    </div>
</template>

<style scoped>
.splash-fade {
    animation: splash-in 700ms ease-out both;
}

.splash-fade-late {
    animation: splash-in 700ms ease-out 250ms both;
}

@keyframes splash-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .splash-fade,
    .splash-fade-late {
        animation: none;
    }
}
</style>
