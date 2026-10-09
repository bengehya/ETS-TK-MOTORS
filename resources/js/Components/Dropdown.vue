<script setup lang="ts">
import { computed, inject, onMounted, onUnmounted, ref, type Ref } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right';
        width?: '48';
        contentClasses?: string;
        menuId?: string;
    }>(),
    {
        align: 'right',
        width: '48',
        contentClasses: 'py-1 bg-white',
    },
);

type NavMenus = {
    open: Ref<string | null>;
    toggle: (id: string) => void;
    close: () => void;
};

const menus = inject<NavMenus | null>('navMenus', null);

const closeOnEscape = (e: KeyboardEvent) => {
    if (isOpen.value && e.key === 'Escape') {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        48: 'w-48',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});

const localOpen = ref(false);
const isOpen = computed(() => (props.menuId && menus ? menus.open.value === props.menuId : localOpen.value));

const toggle = () => {
    if (props.menuId && menus) {
        menus.toggle(props.menuId);

        return;
    }

    localOpen.value = !localOpen.value;
};

const close = () => {
    if (props.menuId && menus) {
        menus.close();

        return;
    }

    localOpen.value = false;
};
</script>

<template>
    <div class="relative">
        <div class="touch-manipulation" @click="toggle">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="isOpen"
            class="fixed inset-0 z-40"
            @click="close"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="isOpen"
                class="absolute z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
                @click="close"
            >
                <div
                    class="rounded-md ring-1 ring-black ring-opacity-5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
