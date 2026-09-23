<script setup lang="ts">
defineProps<{
    message: string;
}>();
</script>

<template>
    <p class="text-sm text-gray-600">{{ message }}</p>
</template>
