<script setup lang="ts">
import { CategoryScale, Chart as ChartJS, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const props = defineProps<{
    labels: string[];
    balanceData: number[];
}>();

const data = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: 'Balance',
            data: props.balanceData,
            borderColor: '#7C3AED',
            backgroundColor: 'rgba(124,58,237,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#7C3AED',
            pointRadius: 4,
            tension: 0.4,
            fill: true,
        },
    ],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
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
        <Line :data="data" :options="options" />
    </div>
</template>
