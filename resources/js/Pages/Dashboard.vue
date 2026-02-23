<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { onMounted, ref, computed } from 'vue';

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
}>();

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

const circumference = 2 * Math.PI * 54;
const dashOffset = computed(() => circumference - (circumference * spentPct.value) / 100);

const txColor: Record<TxType, string> = {
    income: 'text-emerald-500',
    expense: 'text-red-500',
    loan: 'text-orange-500',
    saving: 'text-blue-500',
};

const txDot: Record<TxType, string> = {
    income: 'bg-emerald-500',
    expense: 'bg-red-500',
    loan: 'bg-orange-500',
    saving: 'bg-blue-500',
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

            <!-- ── 1. HERO BALANCE CARD ─────────────────────────────────── -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-purple-700 p-6 sm:p-8">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-white/60 mb-2">Current Balance</p>
                <p class="text-4xl sm:text-5xl font-black text-white tracking-tight">
                    {{ balance < 0 ? '−' : '' }}{{ fmt(balance) }}
                </p>
                <span class="absolute bottom-4 right-4 bg-white/15 text-white text-xs font-medium px-3 py-1 rounded-full">
                    {{ monthLabel }}
                </span>
            </div>

            <!-- ── 2. THREE STAT CARDS ──────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                <div class="card !p-5 border-l-[3px] border-l-orange-500">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Loan Outstanding</p>
                    <p class="text-2xl font-black tracking-tight text-orange-500">{{ fmt(loanOutstanding) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="loanToBalanceRatio !== null">{{ loanToBalanceRatio }}x your balance</template>
                        <template v-else>&nbsp;</template>
                    </p>
                </div>

                <div class="card !p-5 border-l-[3px] border-l-blue-500">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Total Saved</p>
                    <p class="text-2xl font-black tracking-tight text-blue-500">{{ fmt(totalSaved) }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        <template v-if="savingsLoanCoverage !== null">{{ savingsLoanCoverage }}% of loan covered</template>
                        <template v-else>Keep saving!</template>
                    </p>
                </div>

                <div class="card !p-5 border-l-[3px] border-l-violet-500">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Money Needed</p>
                    <p class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ fmt(moneyNeeded) }}</p>
                    <p class="text-[11px] mt-1" :class="shortfall <= 0 ? 'text-emerald-500' : 'text-amber-500'">
                        <template v-if="shortfall <= 0">Fully covered</template>
                        <template v-else>{{ fmt(shortfall) }} shortfall</template>
                    </p>
                </div>
            </div>

            <!-- ── 3. INCOME + RECENT TRANSACTIONS ──────────────────────── -->
            <div class="grid lg:grid-cols-5 gap-4">

                <!-- Income vs Expense -->
                <div class="card space-y-4 lg:col-span-3">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Income vs Expense · {{ monthLabel }}</p>

                    <div class="flex items-center gap-6">
                        <!-- SVG Ring -->
                        <div class="relative shrink-0">
                            <svg width="120" height="120" viewBox="0 0 120 120" class="-rotate-90">
                                <circle cx="60" cy="60" r="54" fill="none" stroke-width="8"
                                    class="stroke-gray-100 dark:stroke-white/10" />
                                <circle cx="60" cy="60" r="54" fill="none" stroke-width="8"
                                    stroke-linecap="round"
                                    :class="spentPct <= 60 ? 'stroke-emerald-500' : spentPct <= 85 ? 'stroke-amber-400' : 'stroke-red-400'"
                                    :stroke-dasharray="circumference"
                                    :stroke-dashoffset="ready ? dashOffset : circumference"
                                    style="transition: stroke-dashoffset 0.8s ease-out" />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900 dark:text-white">
                                {{ Math.round(spentPct) }}%
                            </span>
                        </div>

                        <div class="flex-1 min-w-0 space-y-3">
                            <div>
                                <p class="text-3xl font-black" :class="spentPct <= 60 ? 'text-emerald-600 dark:text-emerald-400' : spentPct <= 85 ? 'text-amber-500' : 'text-red-500'">{{ fmt(spentThisMonth) }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">spent of {{ fmt(incomeThisMonth) }} earned</p>
                            </div>
                            <!-- Progress bar -->
                            <div class="h-2.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 ease-out"
                                    :class="spentPct <= 60 ? 'bg-emerald-500' : spentPct <= 85 ? 'bg-amber-400' : 'bg-red-400'"
                                    :style="{ width: ready ? `${spentPct}%` : '0%' }" />
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">{{ Math.round(spentPct) }}% spent</span>
                                <span v-if="inHand >= 0" class="text-emerald-500 font-medium">{{ fmt(inHand) }} in hand</span>
                                <span v-else class="text-red-500 font-medium">{{ fmt(inHand) }} overspent</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Recent</h2>
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
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ t.title }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ t.category.name }} · {{ formatDate(t.transacted_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full" :class="txDot[t.type]" />
                                <span class="text-sm font-bold" :class="txColor[t.type]">
                                    {{ t.type === 'income' ? '+' : '−' }}{{ fmt(t.amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">No transactions yet this month.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
