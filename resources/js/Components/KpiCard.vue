<script setup lang="ts">
defineProps<{
    label: string;
    amount: number;
    currency?: string;
    variant?: 'primary' | 'success' | 'danger' | 'warning';
    icon?: object;
}>();
</script>

<template>
    <div
        class="rounded-2xl shadow-lg p-5 text-white relative overflow-hidden backdrop-blur-sm"
        :class="{
            'bg-gradient-to-br from-violet-600/90 to-purple-700/90 shadow-violet-500/25 border border-white/20': variant === 'primary' || !variant,
            'bg-gradient-to-br from-emerald-500/90 to-teal-600/90 shadow-emerald-500/20 border border-white/20': variant === 'success',
            'bg-gradient-to-br from-red-500/90 to-rose-600/90 shadow-red-500/20 border border-white/20': variant === 'danger',
            'bg-gradient-to-br from-amber-500/90 to-orange-600/90 shadow-amber-500/20 border border-white/20': variant === 'warning',
        }"
    >
        <!-- Shine overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/15 to-transparent pointer-events-none" />
        <!-- Decorative circles -->
        <div class="absolute -right-4 -top-4 w-24 h-24 rounded-full bg-white/10" />
        <div class="absolute -right-2 top-8 w-16 h-16 rounded-full bg-white/[0.07]" />

        <div class="relative">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-white/80">{{ label }}</span>
                <component v-if="icon" :is="icon" class="w-5 h-5 text-white/60" />
            </div>
            <div class="text-xl sm:text-2xl font-bold tracking-tight">
                {{ currency ?? '৳' }}{{ Number(amount).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </div>
        </div>
    </div>
</template>
