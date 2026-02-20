<script setup lang="ts">
import BarChart from '@/Components/BarChart.vue';
import DonutChart from '@/Components/DonutChart.vue';
import KpiCard from '@/Components/KpiCard.vue';
import TransactionItem from '@/Components/TransactionItem.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight, TrendingDown, TrendingUp, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: { totalIncome: number; totalExpense: number; balance: number; savingsRate: number | null };
    expenseByCategory: Array<{ label: string; color: string; total: number }>;
    last6Months: Array<{ label: string; income: number; expense: number }>;
    recentTransactions: Array<{
        id: number; amount: number; type: string; date: string; note: string | null;
        category: { name: string; color: string; icon: string };
    }>;
    month: number;
    year: number;
}>();

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const monthLabel = computed(() => `${monthNames[props.month - 1]} ${props.year}`);

function navigate(delta: number) {
    let m = props.month + delta;
    let y = props.year;
    if (m < 1) { m = 12; y--; }
    if (m > 12) { m = 1; y++; }
    router.get('/dashboard', { month: m, year: y }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ monthLabel }}</p>
                </div>
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-white/5 rounded-full px-1 py-1">
                    <button @click="navigate(-1)" class="p-1.5 rounded-full hover:bg-white/10 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200 min-w-[110px] sm:min-w-[120px] text-center">{{ monthLabel }}</span>
                    <button @click="navigate(1)" class="p-1.5 rounded-full hover:bg-white/10 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <KpiCard label="Income" :amount="stats.totalIncome" :icon="TrendingUp" variant="success" />
                <KpiCard label="Expenses" :amount="stats.totalExpense" :icon="TrendingDown" variant="danger" />
                <KpiCard label="Balance" :amount="stats.balance" :icon="Wallet" variant="primary" />
            </div>

            <div v-if="stats.savingsRate !== null" class="card py-3 px-5 flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Savings rate this month</span>
                <span
                    class="text-sm font-bold"
                    :class="stats.savingsRate >= 20 ? 'text-emerald-600 dark:text-emerald-400' : stats.savingsRate >= 0 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500'"
                >
                    {{ stats.savingsRate }}%
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="card">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-4">6-Month Trend</h2>
                    <BarChart
                        :labels="last6Months.map(m => m.label)"
                        :income-data="last6Months.map(m => m.income)"
                        :expense-data="last6Months.map(m => m.expense)"
                    />
                </div>
                <div class="card">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Expenses by Category</h2>
                    <DonutChart :items="expenseByCategory" />
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Recent Transactions</h2>
                    <Link href="/transactions" class="text-coin-primary text-sm font-medium flex items-center gap-1 hover:underline">
                        View all <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>
                <div v-if="recentTransactions.length" class="divide-y divide-gray-100 dark:divide-white/5">
                    <TransactionItem v-for="t in recentTransactions" :key="t.id" :transaction="t" />
                </div>
                <div v-else class="text-center py-8 text-gray-400 dark:text-gray-600 text-sm">
                    No transactions yet. Head to Transactions to add one.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
