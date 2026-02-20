<script setup lang="ts">
import { WifiOff, CheckCircle } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

const { message, type, dismiss } = useToast();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-3"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-3"
        >
            <div
                v-if="message"
                class="fixed bottom-24 md:bottom-6 left-1/2 -translate-x-1/2 z-50 w-[calc(100%-2rem)] max-w-sm cursor-pointer"
                @click="dismiss"
            >
                <!-- Offline saved -->
                <div
                    v-if="type === 'offline'"
                    class="flex items-center gap-3 px-4 py-3.5 rounded-2xl bg-amber-50 dark:bg-[#1f1a0e] border border-amber-200 dark:border-amber-500/25 shadow-xl shadow-amber-500/10 backdrop-blur-md"
                >
                    <div class="w-8 h-8 rounded-xl bg-amber-500/15 flex items-center justify-center flex-shrink-0">
                        <WifiOff class="w-4 h-4 text-amber-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Saved offline</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Will sync automatically when back online</p>
                    </div>
                </div>

                <!-- Synced -->
                <div
                    v-else
                    class="flex items-center gap-3 px-4 py-3.5 rounded-2xl bg-emerald-50 dark:bg-[#0d1f14] border border-emerald-200 dark:border-emerald-500/25 shadow-xl shadow-emerald-500/10 backdrop-blur-md"
                >
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/15 flex items-center justify-center flex-shrink-0">
                        <CheckCircle class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ message }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">All caught up</p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
