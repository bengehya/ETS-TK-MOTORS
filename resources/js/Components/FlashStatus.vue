<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const status = computed(() => usePage().props.flash?.status);
</script>

<template>
    <div v-if="status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ status }}
    </div>
</template>
