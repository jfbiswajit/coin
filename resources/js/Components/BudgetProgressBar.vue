<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    name: string;
    color: string;
    budget: number | null;
    spent: number;
}>();

const pct = computed(() => {
    if (!props.budget || props.budget === 0) return 0;
    return Math.min(100, (props.spent / props.budget) * 100);
});

const overBudget = computed(() => props.budget !== null && props.spent > props.budget);

const fmt = (v: number) => `৳${new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(v)}`;
</script>

<template>
    <div class="space-y-1.5 flex-1">
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: color }" />
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ name }}</span>
            </div>
            <div class="text-right text-xs">
                <span :class="overBudget ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-400'">
                    {{ fmt(spent) }}
                </span>
                <span class="text-gray-400 dark:text-gray-500"> / {{ budget !== null ? fmt(budget) : '—' }}</span>
            </div>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
            <div
                class="h-full rounded-full transition-all duration-500"
                :class="overBudget ? 'bg-red-500' : ''"
                :style="{ width: `${pct}%`, backgroundColor: overBudget ? undefined : color }"
            />
        </div>
    </div>
</template>
