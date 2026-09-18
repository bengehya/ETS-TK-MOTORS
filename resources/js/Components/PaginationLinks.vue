<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    links: PaginatorLink[];
}>();
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-md px-3 py-1 text-sm"
                :class="link.active ? 'bg-brand-navy text-white' : 'text-brand-navy hover:bg-brand-cream'"
                preserve-scroll
            >
                <span v-html="link.label" />
            </Link>
            <span
                v-else
                class="rounded-md px-3 py-1 text-sm text-gray-400"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
