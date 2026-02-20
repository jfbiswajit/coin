<script setup lang="ts">
import BarChart from '@/Components/BarChart.vue';
import DonutChart from '@/Components/DonutChart.vue';
import LineChart from '@/Components/LineChart.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    byMonth: Array<{ income: number; expense: number; balance: number }>;
    topCategories: Array<{ label: string; color: string; total: number }>;
    year: number;
}>();

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const navigate = (delta: number) => router.get('/reports', { year: props.year + delta }, { preserveScroll: true });

const totalIncome = computed(() => props.byMonth.reduce((s, m) => s + m.income, 0));
const totalExpense = computed(() => props.byMonth.reduce((s, m) => s + m.expense, 0));
const netSavings = computed(() => totalIncome.value - totalExpense.value);
const savingsRate = computed(() =>
    totalIncome.value > 0 ? ((netSavings.value / totalIncome.value) * 100).toFixed(1) : null,
);

const fmt = (v: number) => `৳${v.toLocaleString('en', { minimumFractionDigits: 2 })}`;

const activeMonths = computed(() =>
    props.byMonth
        .map((m, i) => ({ ...m, label: monthNames[i] }))
        .filter((m) => m.income > 0 || m.expense > 0),
);

const bestMonth = computed(() => {
    if (!activeMonths.value.length) return null;
    return activeMonths.value.reduce((best, m) => (m.balance > best.balance ? m : best));
});

const worstMonth = computed(() => {
    if (!activeMonths.value.length) return null;
    return activeMonths.value.reduce((worst, m) => (m.balance < worst.balance ? m : worst));
});
</script>

<template>
    <Head title="Reports" />
    <AppLayout>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Reports</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your annual overview</p>
                </div>
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-white/5 rounded-full px-1 py-1">
                    <button @click="navigate(-1)" class="p-1.5 rounded-full hover:bg-white/10 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200 min-w-[40px] text-center">{{ year }}</span>
                    <button @click="navigate(1)" class="p-1.5 rounded-full hover:bg-white/10 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="card text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Income</div>
                    <div class="font-bold text-sm sm:text-base text-emerald-600 dark:text-emerald-400 truncate">{{ fmt(totalIncome) }}</div>
                </div>
                <div class="card text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Expense</div>
                    <div class="font-bold text-sm sm:text-base text-red-500 dark:text-red-400 truncate">{{ fmt(totalExpense) }}</div>
                </div>
                <div class="card text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Savings</div>
                    <div class="font-bold text-sm sm:text-base truncate" :class="netSavings >= 0 ? 'text-coin-primary' : 'text-red-500'">{{ fmt(netSavings) }}</div>
                </div>
                <div class="card text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Savings Rate</div>
                    <div
                        v-if="savingsRate !== null"
                        class="font-bold text-sm sm:text-base"
                        :class="Number(savingsRate) >= 20 ? 'text-emerald-600 dark:text-emerald-400' : Number(savingsRate) >= 0 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500'"
                    >
                        {{ savingsRate }}%
                    </div>
                    <div v-else class="font-bold text-sm sm:text-base text-gray-400">—</div>
                </div>
            </div>

            <div v-if="bestMonth && worstMonth && bestMonth.label !== worstMonth.label" class="grid grid-cols-2 gap-3">
                <div class="card py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold flex-shrink-0">↑</div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Best month</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ bestMonth.label }}</div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400">{{ fmt(bestMonth.balance) }} saved</div>
                    </div>
                </div>
                <div class="card py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center text-red-500 font-bold flex-shrink-0">↓</div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Worst month</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ worstMonth.label }}</div>
                        <div class="text-xs text-red-500">{{ fmt(worstMonth.balance) }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Monthly Income vs Expense</h2>
                <BarChart
                    :labels="monthNames"
                    :income-data="byMonth.map(m => m.income)"
                    :expense-data="byMonth.map(m => m.expense)"
                />
            </div>

            <div class="card">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Monthly Net Savings</h2>
                <LineChart
                    :labels="monthNames"
                    :balance-data="byMonth.map(m => m.balance)"
                />
            </div>

            <div v-if="topCategories.length" class="card">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Top Expense Categories</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <DonutChart :items="topCategories" />
                    <div class="space-y-2.5">
                        <div
                            v-for="cat in topCategories"
                            :key="cat.label"
                            class="flex items-center justify-between text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: cat.color }" />
                                <span class="text-gray-700 dark:text-gray-300">{{ cat.label }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ fmt(cat.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
