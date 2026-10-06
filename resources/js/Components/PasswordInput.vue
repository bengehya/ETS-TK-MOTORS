<script setup lang="ts">
import { computed, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ required: true });

const visible = ref(false);
const input = ref<HTMLInputElement | null>(null);
const attrs = useAttrs();

const toggleLabel = computed(() => (visible.value ? 'Masquer le mot de passe' : 'Afficher le mot de passe'));
const inputId = computed(() => (typeof attrs.id === 'string' ? attrs.id : undefined));
const passthrough = computed(() => {
    const { class: _class, style: _style, type: _type, ...rest } = attrs;

    return rest;
});

const toggle = () => {
    visible.value = !visible.value;
};

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="relative" :class="attrs.class">
        <input
            ref="input"
            v-model="model"
            v-bind="passthrough"
            :type="visible ? 'text' : 'password'"
            class="block w-full rounded-md border-gray-300 pe-12 shadow-sm focus:border-brand-gold focus:ring-brand-gold"
        />
        <button
            type="button"
            class="absolute inset-y-0 end-0 flex items-center rounded-e-md px-3 text-brand-navy hover:text-brand-gold focus:outline-none focus:ring-2 focus:ring-brand-gold"
            :aria-label="toggleLabel"
            :aria-pressed="visible"
            :aria-controls="inputId"
            @click="toggle"
        >
            <svg v-if="!visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 6.5 12 6.5 21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z" />
                <circle cx="12" cy="12" r="2.5" />
            </svg>
            <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.6 6.7A10.7 10.7 0 0 1 12 6.5C18 6.5 21.5 12 21.5 12a18.4 18.4 0 0 1-3.2 3.6" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.1 8.2C3.9 9.7 2.5 12 2.5 12S6 17.5 12 17.5c1.2 0 2.3-.2 3.3-.6" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 9.9a2.5 2.5 0 0 0 3.2 3.2" />
            </svg>
        </button>
    </div>
</template>
