<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

const brand = usePage().props.brand;
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-brand-cream px-4 py-8 sm:justify-center sm:pt-0">
        <div class="flex flex-col items-center text-center">
            <Link href="/" class="flex flex-col items-center">
                <span class="flex h-28 w-28 overflow-hidden rounded-full border-4 border-brand-navy bg-white shadow">
                    <ApplicationLogo />
                </span>
                <span class="mt-4 font-display text-3xl uppercase tracking-[0.2em] text-brand-navy">
                    {{ brand.name }}
                </span>
                <span class="mt-1 text-sm italic text-brand-gold">
                    {{ brand.slogan }}
                </span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden border border-brand-gold/40 bg-white px-6 py-6 shadow-md sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
