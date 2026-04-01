<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BarElement, CategoryScale, Chart as ChartJS, LinearScale, Tooltip } from 'chart.js';
import { ArrowRight, Landmark, PiggyBank, Wallet } from 'lucide-vue-next';
import { onMounted, ref, computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip);

type TxType = 'income' | 'expense' | 'loan' | 'saving';

const props = defineProps<{
    balance: number;
    cashInHand: number;
    totalCreditExpense: number;
    loanOutstanding: number;
    totalSaved: number;
    incomeThisMonth: number;
    spentThisMonth: number;
    moneyNeeded: number;
    monthLabel: string;
    recent: Array<{ id: number; amount: number; type: TxType; title: string; transacted_at: string; category: { name: string; color: string } }>;
    dailyExpense: number[];
}>();

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

const fmt = (v: number) => '৳' + new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(Math.abs(v));

const spentPct = computed(() =>
    props.incomeThisMonth > 0
        ? Math.min(100, (props.spentThisMonth / props.incomeThisMonth) * 100)
        : props.spentThisMonth > 0 ? 100 : 0
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


            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                <Link href="/budget" class="card !p-5 border-l-[3px] border-l-violet-500 block hover:opacity-80 transition-opacity">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><Wallet class="w-3.5 h-3.5" />Money Needed</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">{{ monthLabel }}</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-violet-500">{{ fmt(moneyNeeded) }}</p>
                    <p class="text-[11px] mt-1" :class="shortfall <= 0 ? 'text-emerald-500' : 'text-amber-500'">
                        <template v-if="shortfall <= 0">Fully covered</template>
                        <template v-else>{{ fmt(shortfall) }} shortfall</template>
                    </p>
                </Link>

                <Link href="/budget?tab=saving" class="card !p-5 border-l-[3px] border-l-blue-500 block hover:opacity-80 transition-opacity">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><PiggyBank class="w-3.5 h-3.5" />Total Saved</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">All time</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-blue-500">{{ fmt(totalSaved) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="savingsLoanCoverage !== null">{{ savingsLoanCoverage }}% of loan covered</template>
                        <template v-else>Keep saving!</template>
                    </p>
                </Link>

                <Link href="/budget?tab=loan" class="card !p-5 border-l-[3px] border-l-orange-500 block hover:opacity-80 transition-opacity">
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


            <div class="grid lg:grid-cols-5 gap-4">


                <div class="card !p-0 lg:col-span-3 overflow-hidden">
                    <!-- Balance section -->
                    <div class="p-5 pb-4">
                        <div class="flex items-start justify-between">
                            <div class="space-y-0.5">
                                <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Net Balance</p>
                                <p class="text-3xl font-black tracking-tight" :class="balance >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-500'">
                                    {{ balance < 0 ? '−' : '' }}{{ fmt(balance) }}
                                </p>
                            </div>
                        </div>

                        <!-- Cash in hand pill -->
                        <div v-if="totalCreditExpense > 0"
                             class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 cursor-pointer hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-colors"
                             @click="router.get('/transactions', { type: 'expense', is_credit: 1 })">
                            <span class="text-[9px] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Cash in Hand</span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">{{ cashInHand < 0 ? '−' : '' }}{{ fmt(cashInHand) }}</span>
                            <span class="text-[9px] text-amber-500 dark:text-amber-400">{{ fmt(totalCreditExpense) }} on credit →</span>
                        </div>

                        <!-- Balance bar: balance vs this month's expenses -->
                        <div class="mt-4 space-y-1.5">
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden flex gap-0.5">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-700 ease-out"
                                    :style="{ width: ready ? `${Math.min(100, (balance / (balance + spentThisMonth)) * 100)}%` : '0%' }" />
                                <div class="h-full rounded-full bg-red-400 transition-all duration-700 ease-out"
                                    :style="{ width: ready ? `${Math.min(100, (spentThisMonth / (balance + spentThisMonth)) * 100)}%` : '0%' }" />
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500">
                                <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>Balance {{ fmt(balance) }}</span>
                                <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-red-400"></span>Spent {{ fmt(spentThisMonth) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Divider with month label -->
                    <div class="flex items-center gap-3 px-5">
                        <div class="h-px flex-1 bg-gray-100 dark:bg-white/10" />
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ monthLabel }}</span>
                        <div class="h-px flex-1 bg-gray-100 dark:bg-white/10" />
                    </div>

                    <!-- This month section -->
                    <div class="p-5 pt-4 space-y-4">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-500/10 p-3 space-y-0.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Income</p>
                                <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 truncate">{{ fmt(incomeThisMonth) }}</p>
                            </div>
                            <div class="rounded-xl p-3 space-y-0.5"
                                :class="spentPct <= 60 ? 'bg-emerald-50 dark:bg-emerald-500/10' : spentPct <= 85 ? 'bg-amber-50 dark:bg-amber-500/10' : 'bg-red-50 dark:bg-red-500/10'">
                                <p class="text-[10px] font-semibold uppercase tracking-wider truncate"
                                    :class="spentPct <= 60 ? 'text-emerald-600 dark:text-emerald-400' : spentPct <= 85 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500'">Expense</p>
                                <p class="text-base sm:text-lg font-black truncate"
                                    :class="spentPct <= 60 ? 'text-emerald-600 dark:text-emerald-400' : spentPct <= 85 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500'">
                                    {{ fmt(spentThisMonth) }}
                                </p>
                            </div>
                            <div class="rounded-xl p-3 space-y-0.5"
                                :class="inHand >= 0 ? 'bg-gray-50 dark:bg-white/5' : 'bg-red-50 dark:bg-red-500/10'">
                                <p class="text-[10px] font-semibold uppercase tracking-wider"
                                    :class="inHand >= 0 ? 'text-gray-400 dark:text-gray-500' : 'text-red-500'">
                                    {{ inHand >= 0 ? 'Saved' : 'Over' }}
                                </p>
                                <p class="text-base sm:text-lg font-black truncate" :class="inHand >= 0 ? 'text-gray-700 dark:text-gray-200' : 'text-red-500'">
                                    {{ inHand < 0 ? '−' : '' }}{{ fmt(Math.abs(inHand)) }}
                                </p>
                            </div>
                        </div>

                    </div>
                </div>


                <div class="card lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Recent</p>
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
