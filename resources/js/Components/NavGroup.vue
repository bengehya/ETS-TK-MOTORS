<script setup lang="ts">
import { computed, inject, onMounted, onUnmounted, ref, type Ref } from 'vue';

const props = defineProps<{
    label: string;
    menuId: string;
    active?: boolean;
}>();

type NavMenus = {
    open: Ref<string | null>;
    toggle: (id: string) => void;
    close: () => void;
};

const menus = inject<NavMenus | null>('navMenus', null);
const localOpen = ref(false);
const isOpen = computed(() => (menus ? menus.open.value === props.menuId : localOpen.value));

const toggle = () => {
    if (menus) {
        menus.toggle(props.menuId);

        return;
    }

    localOpen.value = !localOpen.value;
};

const close = () => {
    if (menus) {
        menus.close();

        return;
    }

    localOpen.value = false;
};

const closeOnEscape = (event: KeyboardEvent) => {
    if (isOpen.value && event.key === 'Escape') {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div class="relative inline-flex items-center">
        <button
            type="button"
            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none touch-manipulation"
            :class="
                active
                    ? 'border-brand-gold text-white'
                    : 'border-transparent text-brand-cream/80 hover:border-brand-gold/40 hover:text-white'
            "
            :aria-expanded="isOpen"
            @click="toggle"
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

        <div v-show="isOpen" class="fixed inset-0 z-40" @click="close" />

        <div
            v-show="isOpen"
            class="absolute left-0 top-full z-50 mt-2 max-h-[70vh] w-56 overflow-y-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5"
            @click="close"
        >
            <slot />
        </div>
    </div>
</template>
