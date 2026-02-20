<script setup lang="ts">
import { useColorMode } from '@vueuse/core';
import { Moon, Sun } from 'lucide-vue-next';

const mode = useColorMode();
</script>

<template>
    <div class="min-h-screen flex bg-[#f0edff] dark:bg-coin-dark-bg relative overflow-hidden">
        <!-- Ambient blobs -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full bg-violet-400/20 dark:bg-violet-600/15 blur-3xl" />
            <div class="absolute -bottom-32 right-0 w-[400px] h-[400px] rounded-full bg-purple-400/15 dark:bg-purple-600/10 blur-3xl" />
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] rounded-full bg-indigo-300/10 dark:bg-indigo-600/8 blur-3xl" />
        </div>

        <!-- Left panel — branding (hidden on mobile) -->
        <div class="hidden lg:flex lg:w-[45%] bg-gradient-to-br from-violet-600 via-purple-700 to-indigo-800 flex-col justify-between p-12 relative overflow-hidden">
            <div class="absolute -top-20 -left-20 w-72 h-72 rounded-full bg-white/10 blur-2xl" />
            <div class="absolute top-1/3 -right-10 w-48 h-48 rounded-full bg-white/5 blur-xl" />
            <div class="absolute -bottom-16 left-20 w-96 h-96 rounded-full bg-white/5 blur-2xl" />

            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                        <span class="text-white font-bold text-lg">₵</span>
                    </div>
                    <span class="text-white font-bold text-2xl tracking-tight">Coin</span>
                </div>
            </div>

            <div class="relative space-y-5">
                <h1 class="text-4xl font-bold text-white leading-tight">
                    Take control of<br/>your finances.
                </h1>
                <p class="text-white/70 text-lg leading-relaxed max-w-sm">
                    Track income, set budgets, and understand where your money goes — beautifully.
                </p>
                <div class="flex items-center gap-6 pt-2">
                    <div class="text-center">
                        <div class="text-white font-bold text-2xl">14+</div>
                        <div class="text-white/60 text-sm">Categories</div>
                    </div>
                    <div class="w-px h-10 bg-white/20" />
                    <div class="text-center">
                        <div class="text-white font-bold text-2xl">PWA</div>
                        <div class="text-white/60 text-sm">Works offline</div>
                    </div>
                    <div class="w-px h-10 bg-white/20" />
                    <div class="text-center">
                        <div class="text-white font-bold text-2xl">∞</div>
                        <div class="text-white/60 text-sm">Transactions</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel — form -->
        <div class="flex-1 flex flex-col relative z-10">
            <!-- Top bar -->
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2 lg:hidden">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center shadow-md shadow-violet-500/30">
                        <span class="text-white font-bold text-sm">₵</span>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">Coin</span>
                </div>
                <div class="hidden lg:block" />
                <button
                    class="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-white/10 backdrop-blur-sm transition-all"
                    @click="mode = mode === 'dark' ? 'light' : 'dark'"
                >
                    <Sun v-if="mode === 'dark'" class="w-5 h-5" />
                    <Moon v-else class="w-5 h-5" />
                </button>
            </div>

            <!-- Centered form -->
            <div class="flex-1 flex items-center justify-center px-6 py-8">
                <div class="w-full max-w-md bg-white/60 dark:bg-white/[0.04] backdrop-blur-2xl border border-white/80 dark:border-white/[0.08] rounded-3xl shadow-xl shadow-black/5 dark:shadow-black/30 p-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
