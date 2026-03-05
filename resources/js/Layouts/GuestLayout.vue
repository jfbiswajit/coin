<script setup lang="ts">
import { TrendingUp, Target, BarChart2, WifiOff } from 'lucide-vue-next';

const features = [
    {
        icon: TrendingUp,
        title: 'Track every transaction',
        desc: 'Log income & expenses across 14+ categories',
    },
    {
        icon: Target,
        title: 'Set monthly budgets',
        desc: 'Stay on target with per-category spending limits',
    },
    {
        icon: BarChart2,
        title: 'Visual reports',
        desc: 'Charts that show where your money actually goes',
    },
    {
        icon: WifiOff,
        title: 'Works offline',
        desc: 'PWA — add to home screen, use anywhere',
    },
];
</script>

<template>
    <div class="min-h-screen flex bg-coin-dark-bg relative overflow-hidden">

        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full bg-violet-600/15 blur-3xl" />
            <div class="absolute -bottom-32 right-0 w-[400px] h-[400px] rounded-full bg-purple-600/10 blur-3xl" />
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] rounded-full bg-indigo-600/8 blur-3xl" />
        </div>


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


            <div class="relative space-y-8">
                <div class="space-y-3">
                    <h1 class="text-4xl font-bold text-white leading-tight">
                        Take control of<br/>your finances.
                    </h1>
                    <p class="text-white/70 text-lg leading-relaxed max-w-sm">
                        Track income, set budgets, and understand where your money goes — beautifully.
                    </p>
                </div>


                <div class="space-y-4">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="flex items-start gap-4"
                    >
                        <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <component :is="f.icon" class="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <div class="text-white font-medium text-sm leading-tight">{{ f.title }}</div>
                            <div class="text-white/55 text-xs mt-0.5 leading-relaxed">{{ f.desc }}</div>
                        </div>
                    </div>
                </div>


                <div class="flex items-center gap-6 pt-2 border-t border-white/15">
                    <div class="text-center">
                        <div class="text-white font-bold text-xl">14+</div>
                        <div class="text-white/55 text-xs">Categories</div>
                    </div>
                    <div class="w-px h-8 bg-white/20" />
                    <div class="text-center">
                        <div class="text-white font-bold text-xl">Free</div>
                        <div class="text-white/55 text-xs">Always</div>
                    </div>
                    <div class="w-px h-8 bg-white/20" />
                    <div class="text-center">
                        <div class="text-white font-bold text-xl">∞</div>
                        <div class="text-white/55 text-xs">Transactions</div>
                    </div>
                </div>
            </div>
        </div>


        <div class="flex-1 flex flex-col relative z-10">

            <div class="flex items-center px-6 py-4 lg:hidden">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center shadow-md shadow-violet-500/30">
                        <span class="text-white font-bold text-sm">₵</span>
                    </div>
                    <span class="font-bold text-white">Coin</span>
                </div>
            </div>


            <div class="lg:hidden px-6 pb-2">
                <div class="rounded-2xl bg-gradient-to-br from-violet-600 via-purple-700 to-indigo-800 p-5 relative overflow-hidden">
                    <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full bg-white/10 blur-xl" />
                    <p class="text-white/80 text-sm font-medium mb-1 relative">Personal finance, simplified</p>
                    <h2 class="text-white text-xl font-bold leading-snug relative">
                        Track. Budget. Understand<br/>your money.
                    </h2>
                    <div class="flex gap-5 mt-4 relative">
                        <div v-for="f in features.slice(0, 3)" :key="f.title" class="flex items-center gap-1.5">
                            <component :is="f.icon" class="w-3.5 h-3.5 text-white/70 flex-shrink-0" />
                            <span class="text-white/70 text-xs leading-tight">{{ f.title.split(' ').slice(0,2).join(' ') }}</span>
                        </div>
                    </div>
                </div>
            </div>


            <div class="flex-1 flex items-center justify-center px-6 py-8">
                <div class="w-full max-w-md bg-white/[0.04] backdrop-blur-2xl border border-white/[0.08] rounded-3xl shadow-xl shadow-black/30 p-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
