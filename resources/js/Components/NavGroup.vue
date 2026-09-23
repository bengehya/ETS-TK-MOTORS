<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

defineProps<{
    label: string;
    active?: boolean;
}>();

const open = ref(false);

const closeOnEscape = (event: KeyboardEvent) => {
    if (open.value && event.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div class="relative inline-flex items-center">
        <button
            type="button"
            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
            :class="
                active
                    ? 'border-brand-gold text-white'
                    : 'border-transparent text-brand-cream/80 hover:border-brand-gold/40 hover:text-white'
            "
            @click="open = !open"
        >
            {{ label }}
            <svg class="-me-0.5 ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path
                    fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div v-show="open" class="fixed inset-0 z-40" @click="open = false" />

        <div
            v-show="open"
            class="absolute left-0 top-full z-50 mt-2 w-56 rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5"
            @click="open = false"
        >
            <slot />
        </div>
    </div>
</template>
