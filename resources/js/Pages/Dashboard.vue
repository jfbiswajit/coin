<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
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


            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-700 p-6 sm:p-8">
                <!-- Decorative circles -->
                <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/[0.07]" />
                <div class="absolute -bottom-12 -left-8 w-32 h-32 rounded-full bg-white/[0.05]" />

                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-white/60">Current Balance</p>
                        <span class="bg-black/30 backdrop-blur-md border border-white/10 text-white text-sm font-semibold px-5 py-2 rounded-full shadow-lg">
                            {{ monthLabel }}
                        </span>
                    </div>
                    <p class="text-4xl sm:text-5xl font-black text-white tracking-tight">
                        {{ balance < 0 ? '−' : '' }}{{ fmt(balance) }}
                    </p>
                    <div v-if="totalCreditExpense > 0"
                         class="mt-4 inline-flex items-center gap-3 px-4 py-2.5 rounded-full bg-black/30 backdrop-blur-md border border-white/10 cursor-pointer hover:bg-black/40 transition-all shadow-lg"
                         @click="router.get('/transactions', { type: 'expense', is_credit: 1 })">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_6px_2px_rgba(52,211,153,0.5)]" />
                            <span class="text-xs font-semibold uppercase tracking-wider text-white/50">Cash in Hand</span>
                        </div>
                        <span class="text-sm font-bold text-white">{{ cashInHand < 0 ? '−' : '' }}{{ fmt(cashInHand) }}</span>
                        <span class="text-[10px] text-amber-400/90">{{ fmt(totalCreditExpense) }} on credit →</span>
                    </div>
                </div>
            </div>


            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                <div class="card !p-5 border-l-[3px] border-l-violet-500">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><Wallet class="w-3.5 h-3.5" />Money Needed</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">This month</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-violet-500">{{ fmt(moneyNeeded) }}</p>
                    <p class="text-[11px] mt-1" :class="shortfall <= 0 ? 'text-emerald-500' : 'text-amber-500'">
                        <template v-if="shortfall <= 0">Fully covered</template>
                        <template v-else>{{ fmt(shortfall) }} shortfall</template>
                    </p>
                </div>

                <div class="card !p-5 border-l-[3px] border-l-blue-500">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><PiggyBank class="w-3.5 h-3.5" />Total Saved</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">All time</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-blue-500">{{ fmt(totalSaved) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="savingsLoanCoverage !== null">{{ savingsLoanCoverage }}% of loan covered</template>
                        <template v-else>Keep saving!</template>
                    </p>
                </div>

                <div class="card !p-5 border-l-[3px] border-l-orange-500">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5"><Landmark class="w-3.5 h-3.5" />Loan Outstanding</p>
                        <span class="text-[9px] font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider">All time</span>
                    </div>
                    <p class="text-2xl font-black tracking-tight text-orange-500">{{ fmt(loanOutstanding) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="loanToBalanceRatio !== null">{{ loanToBalanceRatio }}x your balance</template>
                        <template v-else>No loans</template>
                    </p>
                </div>
            </div>


            <div class="grid lg:grid-cols-5 gap-4">


                <div class="card space-y-5 lg:col-span-3">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Income vs Expense</p>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Earned</p>
                            <p class="text-xl sm:text-2xl font-black text-emerald-500 truncate">{{ fmt(incomeThisMonth) }}</p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500">This month</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Spent</p>
                            <p class="text-xl sm:text-2xl font-black truncate"
                                :class="spentPct <= 60 ? 'text-emerald-600 dark:text-emerald-400' : spentPct <= 85 ? 'text-amber-500' : 'text-red-500'">
                                {{ fmt(spentThisMonth) }}
                            </p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ Math.round(spentPct) }}% of income</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">In Hand</p>
                            <p class="text-xl sm:text-2xl font-black truncate" :class="inHand >= 0 ? 'text-emerald-500' : 'text-red-500'">
                                {{ fmt(Math.abs(inHand)) }}
                            </p>
                            <p class="text-[11px]" :class="inHand >= 0 ? 'text-emerald-500/70' : 'text-red-500/70'">
                                {{ inHand >= 0 ? 'Remaining' : 'Overspent' }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="h-3 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 ease-out"
                                :class="spentPct <= 60 ? 'bg-emerald-500' : spentPct <= 85 ? 'bg-amber-400' : 'bg-red-400'"
                                :style="{ width: ready ? `${spentPct}%` : '0%' }" />
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
                            <span>0%</span>
                            <span class="font-medium" :class="spentPct <= 60 ? 'text-emerald-500' : spentPct <= 85 ? 'text-amber-500' : 'text-red-500'">
                                {{ Math.round(spentPct) }}% spent
                            </span>
                            <span>100%</span>
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
                <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Daily Expenses</p>
                <div class="relative h-48 sm:h-64" style="cursor: pointer;">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
