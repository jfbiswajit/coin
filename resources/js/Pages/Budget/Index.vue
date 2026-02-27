<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type ExpenseItem = {
    category_id: number; name: string; color: string; icon: string;
    budget: number | null; spent: number;
};
type LoanItem = {
    category_id: number; name: string; color: string; icon: string;
    loan_amount: number; emi_amount: number;
    paid_this_month: number; total_paid: number; remaining: number;
};
type IncomeItem = {
    category_id: number; name: string; color: string; icon: string;
    earned_this_month: number;
};
type SavingItem = {
    category_id: number; name: string; color: string; icon: string;
    monthly_amount: number; target_amount: number | null;
    saved_this_month: number; total_saved: number;
};

const props = defineProps<{
    expenses: ExpenseItem[];
    loans: LoanItem[];
    savings: SavingItem[];
    incomes: IncomeItem[];
    month: number;
    year: number;
}>();

const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));

const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

const activeSection = ref<'expense' | 'loan' | 'saving' | 'income'>('expense');

const prevMonth = () => {
    let m = props.month - 1, y = props.year;
    if (m < 1) { m = 12; y--; }
    router.get('/budget', { month: m, year: y });
};
const nextMonth = () => {
    let m = props.month + 1, y = props.year;
    if (m > 12) { m = 1; y++; }
    router.get('/budget', { month: m, year: y });
};

const fmt = (v: number) => `৳${new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(v)}`;

const itemPct = (item: ExpenseItem) =>
    item.budget ? Math.min(100, (item.spent / item.budget) * 100) : 0;
const isOver = (item: ExpenseItem) => item.budget !== null && item.spent > item.budget;

const totalBudget = computed(() => props.expenses.reduce((s, b) => s + (b.budget ?? 0), 0));
const totalSpent = computed(() => props.expenses.reduce((s, b) => s + b.spent, 0));
const remaining = computed(() => totalBudget.value - totalSpent.value);

const loanPct = (item: LoanItem) =>
    item.loan_amount > 0 ? Math.min(100, (item.total_paid / item.loan_amount) * 100) : 0;

const savingPct = (item: SavingItem) =>
    item.target_amount && item.target_amount > 0
        ? Math.min(100, (item.total_saved / item.target_amount) * 100)
        : 0;

const totalIncome = computed(() => props.incomes.reduce((s, i) => s + i.earned_this_month, 0));
const incomePct = (item: IncomeItem) =>
    totalIncome.value > 0 ? Math.round((item.earned_this_month / totalIncome.value) * 100) : 0;

const totalEmi = computed(() => props.loans.filter(l => l.remaining > 0).reduce((s, l) => s + l.emi_amount, 0));
const totalLoanPaidThisMonth = computed(() => props.loans.reduce((s, l) => s + l.paid_this_month, 0));

const isGoalMet = (item: SavingItem) => !!item.target_amount && item.total_saved >= item.target_amount;
const monthlyPct = (item: SavingItem) =>
    item.monthly_amount > 0 ? Math.min(100, (item.saved_this_month / item.monthly_amount) * 100) : 0;

const goToTransactions = (categoryId: number, type: string) => {
    router.get('/transactions', {
        month: props.month,
        year: props.year,
        type,
        category_id: String(categoryId),
    });
};
</script>

<template>
    <Head title="Budget" />
    <AppLayout>
        <div class="space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Budget</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Track your spending goals</p>
                </div>
                <div class="flex items-center gap-1 bg-white/60 dark:bg-white/[0.05] rounded-xl border border-white/60 dark:border-white/10 px-1 py-1">
                    <button class="p-1.5 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all" @click="prevMonth">
                        <ChevronLeft class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                    </button>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[110px] sm:min-w-[130px] text-center">
                        {{ monthNames[month - 1] }} {{ year }}
                    </span>
                    <button class="p-1.5 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all" @click="nextMonth">
                        <ChevronRight class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                    </button>
                </div>
            </div>


            <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-xl w-fit">
                <button
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all"
                    :class="activeSection === 'expense'
                        ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    @click="activeSection = 'expense'"
                >
                    Expense
                    <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                        :class="activeSection === 'expense' ? 'bg-coin-primary/10 text-coin-primary' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400'"
                    >{{ expenses.length }}</span>
                </button>
                <button
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all"
                    :class="activeSection === 'income'
                        ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    @click="activeSection = 'income'"
                >
                    Income
                    <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                        :class="activeSection === 'income' ? 'bg-coin-primary/10 text-coin-primary' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400'"
                    >{{ incomes.length }}</span>
                </button>
                <button
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all"
                    :class="activeSection === 'saving'
                        ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    @click="activeSection = 'saving'"
                >
                    Saving
                    <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                        :class="activeSection === 'saving' ? 'bg-coin-primary/10 text-coin-primary' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400'"
                    >{{ savings.length }}</span>
                </button>
                <button
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all"
                    :class="activeSection === 'loan'
                        ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    @click="activeSection = 'loan'"
                >
                    Loan
                    <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                        :class="activeSection === 'loan' ? 'bg-coin-primary/10 text-coin-primary' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400'"
                    >{{ loans.length }}</span>
                </button>
            </div>


            <template v-if="activeSection === 'expense'">

                <div class="card space-y-4">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Budget</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ fmt(totalBudget) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Spent</p>
                            <p class="text-sm sm:text-lg font-bold text-red-500 truncate">{{ fmt(totalSpent) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Remaining</p>
                            <p class="text-sm sm:text-lg font-bold truncate" :class="remaining < 0 ? 'text-red-500' : 'text-emerald-500'">{{ fmt(remaining) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="expenses.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in expenses"
                        :key="item.category_id"
                        class="card card-hoverable flex flex-col gap-3"
                        @click="goToTransactions(item.category_id, 'expense')"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                :style="{ backgroundColor: item.color, boxShadow: `0 4px 12px ${item.color}50` }"
                            >
                                {{ item.name[0].toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-sm text-gray-800 dark:text-white">{{ item.name }}</span>
                            </div>
                            <span class="text-sm font-bold shrink-0" :class="isOver(item) ? 'text-red-500' : 'text-emerald-500'">
                                {{ fmt((item.budget ?? 0) - item.spent) }}
                            </span>
                        </div>

                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="isOver(item) ? 'bg-red-500' : itemPct(item) > 75 ? 'bg-amber-400' : 'bg-emerald-500'"
                                :style="{ width: ready ? `${itemPct(item)}%` : '0%' }"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs">
                                <span :class="isOver(item) ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-400'">
                                    {{ fmt(item.spent) }}
                                </span>
                                <span v-if="item.budget !== null" class="text-gray-400 dark:text-gray-500"> / {{ fmt(item.budget) }}</span>
                            </div>
                            <span
                                class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                :class="isOver(item)
                                    ? 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400'
                                    : itemPct(item) > 75
                                        ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                        : 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'"
                            >
                                {{ Math.round(itemPct(item)) }}%
                            </span>
                        </div>

                    </div>
                </div>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add expense categories to set budgets.
                </div>
            </template>


            <template v-if="activeSection === 'loan'">

                <div class="card">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">EMI This Month</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                                {{ fmt(totalEmi) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Paid This Month</p>
                            <p class="text-sm sm:text-lg font-bold text-emerald-500 truncate">
                                {{ fmt(totalLoanPaidThisMonth) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">
                                {{ totalLoanPaidThisMonth >= totalEmi ? 'Overpaid' : 'Due' }}
                            </p>
                            <p class="text-sm sm:text-lg font-bold truncate"
                                :class="totalLoanPaidThisMonth >= totalEmi ? 'text-emerald-500' : 'text-orange-500'">
                                {{ fmt(Math.abs(totalEmi - totalLoanPaidThisMonth)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="loans.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in loans"
                        :key="item.category_id"
                        class="card card-hoverable flex flex-col gap-3"
                        @click="goToTransactions(item.category_id, 'loan')"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                :style="{ backgroundColor: item.color, boxShadow: `0 4px 12px ${item.color}50` }"
                            >
                                {{ item.name[0].toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-sm text-gray-800 dark:text-white">{{ item.name }}</span>
                            </div>
                            <span class="text-sm font-bold shrink-0" :class="item.remaining === 0 ? 'text-emerald-500' : 'text-orange-500'">{{ fmt(item.remaining) }}</span>
                        </div>


                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="loanPct(item) >= 75 ? 'bg-emerald-500' : loanPct(item) >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                :style="{ width: ready ? `${loanPct(item)}%` : '0%' }"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ fmt(item.total_paid) }} / {{ fmt(item.loan_amount) }}
                            </span>
                            <span v-if="item.remaining === 0" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                Paid Off
                            </span>
                            <span v-else class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                :class="loanPct(item) >= 75
                                    ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                    : loanPct(item) >= 40
                                        ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                        : 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400'"
                            >
                                {{ Math.round(loanPct(item)) }}% paid
                            </span>
                        </div>

                        <div v-if="item.remaining > 0" class="space-y-1.5 pt-2 border-t border-gray-100 dark:border-white/5">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">EMI this month</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ fmt(item.emi_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Paid this month</span>
                                <span class="font-medium" :class="item.paid_this_month >= item.emi_amount ? 'text-emerald-500' : 'text-orange-500'">
                                    {{ fmt(item.paid_this_month) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add loan categories to track repayments.
                </div>
            </template>


            <template v-if="activeSection === 'saving'">

                <div class="card">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Monthly Target</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                                {{ fmt(savings.reduce((s, sv) => s + sv.monthly_amount, 0)) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Saved</p>
                            <p class="text-sm sm:text-lg font-bold text-emerald-500 truncate">
                                {{ fmt(savings.reduce((s, sv) => s + sv.saved_this_month, 0)) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">
                                {{ savings.reduce((s, sv) => s + sv.saved_this_month, 0) >= savings.reduce((s, sv) => s + sv.monthly_amount, 0) ? 'Excess' : 'Due' }}
                            </p>
                            <p class="text-sm sm:text-lg font-bold truncate"
                                :class="savings.reduce((s, sv) => s + sv.saved_this_month, 0) >= savings.reduce((s, sv) => s + sv.monthly_amount, 0) ? 'text-emerald-500' : 'text-blue-500'">
                                {{ fmt(Math.abs(savings.reduce((s, sv) => s + sv.monthly_amount, 0) - savings.reduce((s, sv) => s + sv.saved_this_month, 0))) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="savings.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in savings"
                        :key="item.category_id"
                        class="card card-hoverable flex flex-col gap-3"
                        @click="goToTransactions(item.category_id, 'saving')"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                :style="{ backgroundColor: item.color, boxShadow: `0 4px 12px ${item.color}50` }"
                            >
                                {{ item.name[0].toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-sm text-gray-800 dark:text-white">{{ item.name }}</span>
                            </div>
                            <span class="text-sm font-bold shrink-0" :class="item.saved_this_month >= item.monthly_amount ? 'text-emerald-500' : 'text-blue-500'">{{ fmt(item.saved_this_month) }}</span>
                        </div>


                        <template v-if="item.target_amount">
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="savingPct(item) >= 75 ? 'bg-emerald-500' : savingPct(item) >= 40 ? 'bg-amber-400' : 'bg-blue-500'"
                                    :style="{ width: ready ? `${savingPct(item)}%` : '0%' }"
                                />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ fmt(item.total_saved) }} / {{ fmt(item.target_amount) }}
                                </span>
                                <span v-if="isGoalMet(item)" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                    Goal Achieved
                                </span>
                                <span v-else class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="savingPct(item) >= 75 ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : savingPct(item) >= 40 ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400'">
                                    {{ Math.round(savingPct(item)) }}%
                                </span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500 bg-blue-500"
                                    :style="{ width: ready ? `${monthlyPct(item)}%` : '0%' }"
                                />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">This month</span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="monthlyPct(item) >= 100 ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-blue-100 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400'">
                                    {{ Math.round(monthlyPct(item)) }}%
                                </span>
                            </div>
                        </template>

                        <div class="space-y-1.5 pt-2 border-t border-gray-100 dark:border-white/5">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Monthly target</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ fmt(item.monthly_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Total balance</span>
                                <span class="font-medium" :class="isGoalMet(item) ? 'text-emerald-500' : 'text-blue-500'">
                                    {{ fmt(item.total_saved) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add saving categories to track your goals.
                </div>
            </template>


            <template v-if="activeSection === 'income'">

                <div class="card">
                    <div class="grid grid-cols-2 gap-2 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total Earned</p>
                            <p class="text-sm sm:text-lg font-bold text-emerald-500 truncate">{{ fmt(totalIncome) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Sources</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white">{{ incomes.length }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="incomes.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in incomes"
                        :key="item.category_id"
                        class="card card-hoverable flex flex-col gap-3"
                        @click="goToTransactions(item.category_id, 'income')"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                :style="{ backgroundColor: item.color, boxShadow: `0 4px 12px ${item.color}50` }"
                            >
                                {{ item.name[0].toUpperCase() }}
                            </div>
                            <span class="font-semibold text-sm text-gray-800 dark:text-white">{{ item.name }}</span>
                        </div>

                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{ width: ready ? `${incomePct(item)}%` : '0%', backgroundColor: item.color }"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-semibold">{{ fmt(item.earned_this_month) }}</span>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                {{ incomePct(item) }}% of total
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add income categories to track earnings.
                </div>
            </template>
        </div>
    </AppLayout>
</template>
