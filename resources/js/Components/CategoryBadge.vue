<script setup lang="ts">
defineProps<{
    name: string;
    color: string;
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium text-white"
        :style="{ backgroundColor: color }"
    >
        {{ name }}
    </span>
</template>
