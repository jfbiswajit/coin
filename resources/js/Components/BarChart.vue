<script setup lang="ts">
import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    incomeData: number[];
    expenseData: number[];
}>();

const data = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: 'Income',
            data: props.incomeData,
            backgroundColor: '#10B981',
            borderRadius: 6,
            borderSkipped: false,
        },
        {
            label: 'Expense',
            data: props.expenseData,
            backgroundColor: '#EF4444',
            borderRadius: 6,
            borderSkipped: false,
        },
    ],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const, labels: { padding: 16, usePointStyle: true, font: { size: 12 } } },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ৳${ctx.parsed.y.toLocaleString('en', { minimumFractionDigits: 2 })}`,
            },
        },
    },
    scales: {
        x: { grid: { display: false } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: (v: any) => `৳${v}` } },
    },
};
</script>

<template>
    <div class="relative h-48 sm:h-64">
        <Bar :data="data" :options="options" />
    </div>
</template>
