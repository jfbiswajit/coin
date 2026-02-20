<script setup lang="ts">
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
    items: Array<{ label: string; color: string; total: number }>;
}>();

const data = computed(() => ({
    labels: props.items.map((i) => i.label),
    datasets: [{
        data: props.items.map((i) => i.total),
        backgroundColor: props.items.map((i) => i.color),
        borderWidth: 0,
        hoverOffset: 8,
    }],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: { position: 'bottom' as const, labels: { padding: 16, usePointStyle: true, font: { size: 12 } } },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ৳${ctx.parsed.toLocaleString('en', { minimumFractionDigits: 2 })}`,
            },
        },
    },
};
</script>

<template>
    <div class="relative h-48 sm:h-64">
        <Doughnut v-if="items.length" :data="data" :options="options" />
        <div v-else class="flex items-center justify-center h-full text-gray-400 dark:text-gray-600 text-sm">
            No expense data
        </div>
    </div>
</template>
