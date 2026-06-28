<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, LinearScale, Tooltip, Legend } from 'chart.js';
import { ArrowRight, Landmark, PiggyBank, TrendingDown, Wallet } from 'lucide-vue-next';
import { onMounted, ref, computed } from 'vue';
import { fmtCurrency } from '@/constants/format';
import { Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

type TxType = 'income' | 'expense' | 'loan' | 'saving';

const props = defineProps<{
    balance: number;
    loanOutstanding: number;
    totalSaved: number;
    incomeThisMonth: number;
    spentThisMonth: number;
    moneyNeeded: number;
    monthLabel: string;
    recent: Array<{ id: number; amount: number; type: TxType; title: string; transacted_at: string; category: { name: string; color: string } }>;
    dailyExpense: number[];
    spendingByCategory: Array<{ name: string; color: string; total: number }>;
    totalBudget: number;
    totalIncomeBudget: number;
    expenseThisMonth: number;
}>();

const donutData = computed(() => ({
    labels: props.spendingByCategory.map(c => c.name),
    datasets: [{
        data: props.spendingByCategory.map(c => c.total),
        backgroundColor: props.spendingByCategory.map(c => c.color),
        borderWidth: 0,
        hoverOffset: 6,
    }],
}));

const donutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    animation: {
        animateRotate: true,
        animateScale: false,
        duration: 700,
        easing: 'easeOutQuart' as const,
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ৳${Number(ctx.raw).toLocaleString('en', { minimumFractionDigits: 2 })}`,
            },
        },
    },
};

const chartData = computed(() => ({
    labels: props.dailyExpense.map((_, i) => String(i + 1)),
    datasets: [{
        label: 'Expense',
        data: props.dailyExpense,
        backgroundColor: '#7C3AED',
        borderRadius: 4,
        borderSkipped: false,
    }],
}));

const handleBarClick = (_event: any, elements: any[]) => {
    if (!elements.length) return;
    const day = elements[0].index + 1;
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const dayStr = String(day).padStart(2, '0');
    router.get('/transactions', { date: `${year}-${month}-${dayStr}`, type: 'expense' });
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    onClick: handleBarClick,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                title: () => '',
                label: (ctx: any) => ` ৳${ctx.parsed.y.toLocaleString('en', { minimumFractionDigits: 2 })}`,
            },
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: (v: any) => `৳${v}` } },
    },
};

const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));

const fmt = (v: number) => fmtCurrency(Math.abs(v));

const spentPct = computed(() =>
    props.incomeThisMonth > 0
        ? Math.min(100, (props.spentThisMonth / props.incomeThisMonth) * 100)
        : props.spentThisMonth > 0 ? 100 : 0
);

const budgetPct = computed(() =>
    props.totalBudget > 0
        ? Math.min(100, (props.spentThisMonth / props.totalBudget) * 100)
        : props.spentThisMonth > 0 ? 100 : 0
);

const incomePct = computed(() =>
    props.totalIncomeBudget > 0
        ? Math.min(100, (props.incomeThisMonth / props.totalIncomeBudget) * 100)
        : 100
);

const inHand = computed(() => props.incomeThisMonth - props.spentThisMonth);

const shortfall = computed(() => props.moneyNeeded - props.incomeThisMonth);

const loanToBalanceRatio = computed(() =>
    props.balance > 0 ? (props.loanOutstanding / props.balance).toFixed(1) : null
);

const savingsLoanCoverage = computed(() =>
    props.loanOutstanding > 0
        ? Math.round((props.totalSaved / props.loanOutstanding) * 100)
        : null
);


const txColor: Record<TxType, string> = {
    income: 'text-emerald-500',
    expense: 'text-red-500',
    loan: 'text-orange-500',
    saving: 'text-blue-500',
};

const formatDate = (dt: string) => {
    const d = new Date(dt), today = new Date(), yest = new Date();
    yest.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return 'Today';
    if (d.toDateString() === yest.toDateString()) return 'Yesterday';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="space-y-4">


            <!-- Net Balance full width -->
            <div class="card !p-0 overflow-hidden">
                <div class="p-5 pb-4">
                    <!-- Top row: balance + month stats -->
                    <div class="flex items-start gap-6">
                        <!-- Balance -->
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Net Balance</p>
                            <p class="text-3xl font-black tracking-tight mt-0.5" :class="balance >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-500'">
                                {{ balance < 0 ? '−' : '' }}{{ fmt(balance) }}
                            </p>
                        </div>

                        <!-- Divider -->
                        <div class="w-px self-stretch bg-gray-100 dark:bg-white/10 shrink-0"></div>

                        <!-- Income this month -->
                        <div class="shrink-0 text-right">
                            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Income</p>
                            <p class="text-xl font-bold mt-0.5"
                                :class="totalIncomeBudget === 0 || incomePct >= 100 ? 'text-emerald-500' : 'text-orange-500'">
                                {{ fmt(incomeThisMonth) }}
                            </p>
                        </div>

                        <!-- Divider -->
                        <div class="w-px self-stretch bg-gray-100 dark:bg-white/10 shrink-0"></div>

                        <!-- Spent this month -->
                        <div class="shrink-0 text-right">
                            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Spent</p>
                            <p class="text-xl font-bold mt-0.5"
                                :class="budgetPct >= 100 ? 'text-red-500' : budgetPct >= 80 ? 'text-orange-500' : 'text-emerald-500'">
                                {{ fmt(spentThisMonth) }}
                            </p>
                        </div>
                    </div>

                    <!-- Budget vs Expense bar -->
                    <div v-if="totalBudget > 0" class="mt-4 space-y-1.5">
                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 ease-out"
                                :class="budgetPct >= 100 ? 'bg-red-500' : budgetPct >= 80 ? 'bg-amber-400' : 'bg-violet-500'"
                                :style="{ width: ready ? `${budgetPct}%` : '0%' }" />
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500">
                            <span>{{ Math.round(budgetPct) }}% of needed spent</span>
                            <span :class="spentThisMonth > totalBudget ? 'text-red-500' : ''">
                                {{ spentThisMonth > totalBudget ? fmt(spentThisMonth - totalBudget) + ' over budget' : fmt(totalBudget - spentThisMonth) + ' remaining' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                <Link href="/budget" class="card !p-5 !bg-white/[0.07] !backdrop-blur-2xl !border-white/[0.14] !shadow-lg border-l-[3px] border-l-violet-500 block hover:opacity-80 transition-opacity">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><Wallet class="w-3.5 h-3.5" />Monthly Budget</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">{{ monthLabel }}</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-violet-500">{{ fmt(moneyNeeded) }}</p>
                    <p class="text-[11px] mt-1" :class="shortfall <= 0 ? 'text-emerald-500' : 'text-amber-500'">
                        <template v-if="shortfall <= 0">Fully covered</template>
                        <template v-else>{{ fmt(shortfall) }} shortfall</template>
                    </p>
                </Link>

                <Link href="/budget?tab=saving" class="card !p-5 !bg-white/[0.07] !backdrop-blur-2xl !border-white/[0.14] !shadow-lg border-l-[3px] border-l-blue-500 block hover:opacity-80 transition-opacity">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><PiggyBank class="w-3.5 h-3.5" />Savings Balance</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">All time</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-blue-500">{{ fmt(totalSaved) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="savingsLoanCoverage !== null">{{ savingsLoanCoverage }}% of loan covered</template>
                        <template v-else>Keep saving!</template>
                    </p>
                </Link>

                <Link href="/budget?tab=loan" class="card !p-5 !bg-white/[0.07] !backdrop-blur-2xl !border-white/[0.14] !shadow-lg border-l-[3px] border-l-orange-500 block hover:opacity-80 transition-opacity">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><Landmark class="w-3.5 h-3.5" />Loan Outstanding</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">All time</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-orange-500">{{ fmt(loanOutstanding) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="loanToBalanceRatio !== null">{{ loanToBalanceRatio }}x your balance</template>
                        <template v-else>No loans</template>
                    </p>
                </Link>

            </div>


            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <!-- Spending by Category -->
                <div class="card">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Spending by Category</p>
                    <div v-if="spendingByCategory.length" class="flex gap-6 items-center">
                        <div class="relative shrink-0 w-32 h-32">
                            <Doughnut v-if="ready" :data="donutData" :options="donutOptions" />
                        </div>
                        <ul class="flex-1 space-y-2 min-w-0">
                            <li v-for="c in spendingByCategory" :key="c.name" class="flex items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: c.color }" />
                                    <span class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ c.name }}</span>
                                </div>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white shrink-0">{{ fmt(c.total) }}</span>
                            </li>
                        </ul>
                    </div>
                    <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">No expenses this month.</p>
                </div>

                <!-- Recent -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Recent Transactions</p>
                        <button class="text-xs text-coin-primary flex items-center gap-1 hover:underline" @click="router.get('/transactions')">
                            View all <ArrowRight class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="recent.length" class="space-y-3">
                        <div v-for="t in recent" :key="t.id" class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0"
                                :style="{ backgroundColor: t.category.color, boxShadow: `0 3px 10px ${t.category.color}40` }">
                                {{ t.category.name[0].toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ t.title }}</p>
                                    <span class="text-sm font-bold shrink-0" :class="txColor[t.type]">
                                        {{ t.type === 'income' ? '+' : '−' }}{{ fmt(t.amount) }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ t.category.name }} · {{ formatDate(t.transacted_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">No transactions yet this month.</p>
                </div>

            </div>

            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Daily Expenses</p>
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ monthLabel }}</p>
                </div>
                <div class="relative h-48 sm:h-64" style="cursor: pointer;">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
