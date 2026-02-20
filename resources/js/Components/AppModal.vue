<script setup lang="ts">
import { X } from 'lucide-vue-next';

defineProps<{ title?: string }>();
const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-0 md:p-4"
            @click.self="emit('close')"
        >
            <div class="absolute inset-0 bg-black/40 backdrop-blur-md" @click="emit('close')" />
            <div class="relative w-full md:max-w-lg bg-white/80 dark:bg-coin-dark-card/80 backdrop-blur-2xl border border-white/60 dark:border-white/[0.08] rounded-t-3xl md:rounded-2xl shadow-2xl shadow-black/20 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-5 py-4 border-b border-white/60 dark:border-white/[0.06]">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
                    <button
                        class="p-1.5 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-white/10 transition-all"
                        @click="emit('close')"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-4 sm:p-5" style="padding-bottom: max(env(safe-area-inset-bottom), 20px)">
                    <slot />
                </div>
            </div>
        </div>
    </Teleport>
</template>
