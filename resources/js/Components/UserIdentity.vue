<script setup lang="ts">
import UserAvatar from '@/Components/UserAvatar.vue';

defineProps<{
    user: {
        name: string;
        photo_url?: string | null;
    } | null;
}>();
</script>

<template>
    <span v-if="user" class="inline-flex items-center gap-2 align-middle">
        <UserAvatar :name="user.name" :photo-url="user.photo_url" size="sm" decorative />
        <span>{{ user.name }}</span>
    </span>
    <span v-else>—</span>
</template>
