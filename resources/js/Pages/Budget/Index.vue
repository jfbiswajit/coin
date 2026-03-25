<script setup lang="ts">
import CategoryCreateModal from '@/Components/CategoryCreateModal.vue';
import CategoryEditModal, { type CategoryForEdit } from '@/Components/CategoryEditModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import { MoreVertical, Plus } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type ExpenseItem = {
    category_id: number; name: string; color: string; icon: string;
    budget: number | null; spent: number;
};
type LoanItem = {
    category_id: number; name: string; color: string; icon: string;
    loan_amount: number; emi_amount: number;
    paid_this_month: number; total_paid: number; remaining: number; is_settled: boolean;
};
type IncomeItem = {
    category_id: number; name: string; color: string; icon: string;
    monthly_amount: number; earned_this_month: number;
};
type SavingItem = {
    category_id: number; name: string; color: string; icon: string;
    monthly_amount: number; target_amount: number | null;
    saved_this_month: number; total_saved: number; is_withdrawn: boolean;
};

type WithdrawState = { item: SavingItem; incomeCategoryId: number | null };

const props = defineProps<{
    expenses: ExpenseItem[];
    loans: LoanItem[];
    savings: SavingItem[];
    incomes: IncomeItem[];
    month: number;
    year: number;
}>();

const ready = ref(false);
onMounted(() => {
    setTimeout(() => { ready.value = true; }, 50);
    document.addEventListener('click', () => { openMenu.value = null; });
});

const validTabs = ['expense', 'loan', 'saving', 'income'] as const;
type Tab = typeof validTabs[number];
const urlTab = new URLSearchParams(window.location.search).get('tab');
const activeSection = ref<Tab>(validTabs.includes(urlTab as Tab) ? (urlTab as Tab) : 'expense');

const setTab = (tab: Tab) => {
    activeSection.value = tab;
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    window.history.replaceState(window.history.state, '', `?${params.toString()}`);
};

useKeyboardShortcuts({
    e: () => setTab('expense'),
    i: () => setTab('income'),
    s: () => setTab('saving'),
    l: () => setTab('loan'),
});

const fmt = (v: number) => `৳${new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(v)}`;

const itemPct = (item: ExpenseItem) =>
    item.budget ? Math.min(100, (item.spent / item.budget) * 100) : 0;
const isOver = (item: ExpenseItem) => !!item.budget && item.spent > item.budget;

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
const totalExpectedIncome = computed(() => props.incomes.reduce((s, i) => s + i.monthly_amount, 0));
const incomePct = (item: IncomeItem) =>
    item.monthly_amount > 0 ? Math.min(100, (item.earned_this_month / item.monthly_amount) * 100) : 100;
const isIncomeOver = (item: IncomeItem) => item.monthly_amount > 0 && item.earned_this_month >= item.monthly_amount;

const activeLoansForTotal = computed(() => props.loans.filter(l => !l.is_settled));
const totalLoanAmount = computed(() => activeLoansForTotal.value.reduce((s, l) => s + l.loan_amount, 0));
const totalLoanPaid = computed(() => activeLoansForTotal.value.reduce((s, l) => s + l.total_paid, 0));
const totalLoanRemaining = computed(() => activeLoansForTotal.value.reduce((s, l) => s + l.remaining, 0));

const isGoalMet = (item: SavingItem) => !!item.target_amount && item.total_saved >= item.target_amount;
const isCompleted = (item: SavingItem) => item.is_withdrawn || isGoalMet(item);
const totalSavedAllTime = computed(() => props.savings.reduce((s, sv) => s + sv.total_saved, 0));
const totalSavingTarget = computed(() => props.savings.reduce((s, sv) => s + (sv.target_amount ?? 0), 0));

const showCompletedLoans = ref(false);
const activeLoans = computed(() => props.loans.filter(l => l.remaining > 0 && !l.is_settled));
const completedLoans = computed(() => props.loans.filter(l => l.remaining === 0 || l.is_settled));
const visibleLoans = computed(() => showCompletedLoans.value ? props.loans : activeLoans.value);

const showCompletedSavings = ref(false);
const activeSavings = computed(() => props.savings.filter(s => !isCompleted(s)));
const completedSavings = computed(() => props.savings.filter(s => isCompleted(s)));
const visibleSavings = computed(() => showCompletedSavings.value ? props.savings : activeSavings.value);

const goToTransactions = (categoryId: number, type: string) => {
    router.get('/transactions', {
        month: props.month,
        year: props.year,
        type,
        category_id: String(categoryId),
    });
};

const openMenu = ref<number | null>(null);
const toggleMenu = (id: number, e: Event) => { e.stopPropagation(); openMenu.value = openMenu.value === id ? null : id; };

const withdrawState = ref<WithdrawState | null>(null);
const settleConfirm = ref<LoanItem | null>(null);

const submitSettle = () => {
    if (!settleConfirm.value) return;
    router.post(
        `/loans/${settleConfirm.value.category_id}/settle`,
        {},
        { onSuccess: () => { settleConfirm.value = null; } }
    );
};

const openWithdraw = (item: SavingItem, e: Event) => {
    e.stopPropagation();
    withdrawState.value = { item, incomeCategoryId: null };
};

const submitWithdraw = () => {
    if (!withdrawState.value || !withdrawState.value.incomeCategoryId) return;
    router.post(
        `/savings/${withdrawState.value.item.category_id}/withdraw`,
        { income_category_id: withdrawState.value.incomeCategoryId },
        { onSuccess: () => { withdrawState.value = null; } }
    );
};

const showCreateModal = ref(false);
const editCategory = ref<CategoryForEdit | null>(null);

const openEditFromBudget = (item: ExpenseItem | IncomeItem | LoanItem | SavingItem, type: Tab) => {
    editCategory.value = {
        id: item.category_id,
        name: item.name,
        type: type as CategoryForEdit['type'],
        color: item.color,
        icon: item.icon,
        budget_amount: 'budget' in item ? (item.budget ?? '') : '',
        loan_amount: 'loan_amount' in item ? item.loan_amount : null,
        emi_amount: 'emi_amount' in item ? item.emi_amount : null,
        monthly_amount: 'monthly_amount' in item ? item.monthly_amount : null,
        target_amount: 'target_amount' in item ? item.target_amount : null,
    };
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
                <div class="flex items-center gap-2">
                <button class="btn-primary text-sm flex items-center gap-1.5" @click="showCreateModal = true">
                    <Plus class="w-4 h-4" /> <span class="hidden sm:inline">New Category</span><span class="sm:hidden">Add</span>
                </button>
                </div>
            </div>


            <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-xl w-fit">
                <button
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all"
                    :class="activeSection === 'expense'
                        ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    @click="setTab('expense')"
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
                    @click="setTab('income')"
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
                    @click="setTab('saving')"
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
                    @click="setTab('loan')"
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
                                {{ item.budget ? fmt(item.budget - item.spent) : fmt(item.spent) }}
                            </span>
                            <div class="relative shrink-0">
                                <button
                                    class="p-1 rounded-lg hover:bg-white/10 transition-colors text-gray-400 dark:text-gray-500"
                                    @click.stop="toggleMenu(item.category_id, $event)"
                                >
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                                <div v-if="openMenu === item.category_id" class="absolute right-0 top-7 z-20 bg-white dark:bg-coin-dark-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg py-1 min-w-[120px]">
                                    <button
                                        class="w-full text-left px-3 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        @click.stop="openEditFromBudget(item, 'expense'); openMenu = null"
                                    >Edit</button>
                                </div>
                            </div>
                        </div>

                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="!item.budget ? 'bg-red-500' : isOver(item) ? 'bg-red-500' : itemPct(item) > 75 ? 'bg-amber-400' : 'bg-emerald-500'"
                                :style="{ width: ready ? (item.budget ? `${itemPct(item)}%` : '100%') : '0%' }"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs">
                                <span :class="isOver(item) ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-400'">
                                    {{ fmt(item.spent) }}
                                </span>
                                <span v-if="item.budget" class="text-gray-400 dark:text-gray-500"> / {{ fmt(item.budget) }}</span>
                            </div>
                            <span
                                class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                :class="!item.budget
                                    ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                    : isOver(item)
                                        ? 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400'
                                        : itemPct(item) > 75
                                            ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                            : 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'"
                            >
                                {{ item.budget ? `${Math.round(itemPct(item))}%` : 'No budget' }}
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
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total Loan</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                                {{ fmt(totalLoanAmount) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total Paid</p>
                            <p class="text-sm sm:text-lg font-bold text-emerald-500 truncate">
                                {{ fmt(totalLoanPaid) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Outstanding</p>
                            <p class="text-sm sm:text-lg font-bold text-orange-500 truncate">
                                {{ fmt(totalLoanRemaining) }}
                            </p>
                        </div>
                    </div>
                </div>

                <template v-if="loans.length">
                <div v-if="visibleLoans.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in visibleLoans"
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
                            <span class="text-sm font-bold shrink-0" :class="item.remaining === 0 || item.is_settled ? 'text-emerald-500' : 'text-orange-500'">{{ fmt(item.remaining) }}</span>
                            <div class="relative shrink-0">
                                <button
                                    class="p-1 rounded-lg hover:bg-white/10 transition-colors text-gray-400 dark:text-gray-500"
                                    @click.stop="toggleMenu(item.category_id, $event)"
                                >
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                                <div v-if="openMenu === item.category_id" class="absolute right-0 top-7 z-20 bg-white dark:bg-coin-dark-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg py-1 min-w-[120px]">
                                    <button
                                        class="w-full text-left px-3 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        @click.stop="openEditFromBudget(item, 'loan'); openMenu = null"
                                    >Edit</button>
                                    <button
                                        v-if="item.remaining > 0 && !item.is_settled"
                                        class="w-full text-left px-3 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        @click.stop="settleConfirm = item; openMenu = null"
                                    >Settle</button>
                                </div>
                            </div>
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
                            <span v-if="item.is_settled" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-500/15 text-violet-600 dark:text-violet-400">
                                Settled
                            </span>
                            <span v-else-if="item.remaining === 0" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
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
                <div v-else class="card text-center py-8 text-sm text-gray-400 dark:text-gray-600">
                    All loans paid off.
                </div>
                <button
                    v-if="completedLoans.length"
                    class="w-full text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors py-2"
                    @click="showCompletedLoans = !showCompletedLoans"
                >
                    {{ showCompletedLoans ? 'Hide completed' : `Show completed (${completedLoans.length})` }}
                </button>
                </template>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add loan categories to track repayments.
                </div>
            </template>


            <template v-if="activeSection === 'saving'">

                <div class="card">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Target</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                                {{ fmt(totalSavingTarget) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total Saved</p>
                            <p class="text-sm sm:text-lg font-bold text-emerald-500 truncate">
                                {{ fmt(totalSavedAllTime) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ totalSavedAllTime >= totalSavingTarget ? 'Extra' : 'Remaining' }}</p>
                            <p class="text-sm sm:text-lg font-bold truncate"
                                :class="totalSavedAllTime >= totalSavingTarget ? 'text-emerald-500' : 'text-blue-500'">
                                {{ fmt(Math.abs(totalSavingTarget - totalSavedAllTime)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <template v-if="savings.length">
                <div v-if="visibleSavings.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in visibleSavings"
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
                            <span class="text-sm font-bold shrink-0" :class="!item.target_amount || isCompleted(item) ? 'text-emerald-500' : 'text-blue-500'">{{ fmt(item.total_saved) }}</span>
                            <div class="relative shrink-0">
                                <button
                                    class="p-1 rounded-lg hover:bg-white/10 transition-colors text-gray-400 dark:text-gray-500"
                                    @click.stop="toggleMenu(item.category_id, $event)"
                                >
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                                <div v-if="openMenu === item.category_id" class="absolute right-0 top-7 z-20 bg-white dark:bg-coin-dark-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg py-1 min-w-[120px]">
                                    <button
                                        class="w-full text-left px-3 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        @click.stop="openEditFromBudget(item, 'saving'); openMenu = null"
                                    >Edit</button>
                                    <button
                                        v-if="item.total_saved > 0 && !item.is_withdrawn"
                                        class="w-full text-left px-3 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        @click.stop="openWithdraw(item, $event); openMenu = null"
                                    >Withdraw</button>
                                </div>
                            </div>
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
                                <span v-if="item.is_withdrawn" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-500/15 text-violet-600 dark:text-violet-400">
                                    Withdrawn
                                </span>
                                <span v-else-if="isGoalMet(item)" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
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
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" :style="{ width: ready ? '100%' : '0%' }" />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ fmt(item.total_saved) }}</span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">No target</span>
                            </div>
                        </template>

                        <div class="space-y-1.5 pt-2 border-t border-gray-100 dark:border-white/5">
                            <div v-if="item.monthly_amount > 0" class="flex justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Monthly target</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ fmt(item.monthly_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Paid this month</span>
                                <span class="font-medium" :class="item.saved_this_month >= item.monthly_amount ? 'text-emerald-500' : 'text-blue-500'">
                                    {{ fmt(item.saved_this_month) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="card text-center py-8 text-sm text-gray-400 dark:text-gray-600">
                    All saving goals achieved.
                </div>
                <button
                    v-if="completedSavings.length"
                    class="w-full text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors py-2"
                    @click="showCompletedSavings = !showCompletedSavings"
                >
                    {{ showCompletedSavings ? 'Hide completed' : `Show completed (${completedSavings.length})` }}
                </button>
                </template>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add saving categories to track your goals.
                </div>
            </template>


            <template v-if="activeSection === 'income'">

                <div class="card space-y-4">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Expected</p>
                            <p class="text-sm sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ fmt(totalExpectedIncome) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Earned</p>
                            <p class="text-sm sm:text-lg font-bold text-emerald-500 truncate">{{ fmt(totalIncome) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ totalIncome >= totalExpectedIncome ? 'Extra' : 'Remaining' }}</p>
                            <p class="text-sm sm:text-lg font-bold truncate" :class="totalIncome >= totalExpectedIncome ? 'text-emerald-500' : 'text-orange-500'">
                                {{ fmt(Math.abs(totalExpectedIncome - totalIncome)) }}
                            </p>
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
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-sm text-gray-800 dark:text-white">{{ item.name }}</span>
                            </div>
                            <span class="text-sm font-bold shrink-0" :class="item.monthly_amount === 0 || isIncomeOver(item) ? 'text-emerald-500' : 'text-orange-500'">
                                {{ item.monthly_amount === 0 ? fmt(item.earned_this_month) : fmt(Math.abs(item.monthly_amount - item.earned_this_month)) }}
                            </span>
                            <div class="relative shrink-0">
                                <button
                                    class="p-1 rounded-lg hover:bg-white/10 transition-colors text-gray-400 dark:text-gray-500"
                                    @click.stop="toggleMenu(item.category_id, $event)"
                                >
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                                <div v-if="openMenu === item.category_id" class="absolute right-0 top-7 z-20 bg-white dark:bg-coin-dark-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg py-1 min-w-[120px]">
                                    <button
                                        class="w-full text-left px-3 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                        @click.stop="openEditFromBudget(item, 'income'); openMenu = null"
                                    >Edit</button>
                                </div>
                            </div>
                        </div>

                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="item.monthly_amount === 0 || isIncomeOver(item) ? 'bg-emerald-500' : incomePct(item) >= 75 ? 'bg-amber-400' : 'bg-blue-500'"
                                :style="{ width: ready ? `${incomePct(item)}%` : '0%' }"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs">
                                <span :class="isIncomeOver(item) ? 'text-emerald-500 font-semibold' : 'text-gray-600 dark:text-gray-400'">
                                    {{ fmt(item.earned_this_month) }}
                                </span>
                                <span v-if="item.monthly_amount > 0" class="text-gray-400 dark:text-gray-500"> / {{ fmt(item.monthly_amount) }}</span>
                            </div>
                            <span
                                class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                :class="item.monthly_amount === 0 || isIncomeOver(item)
                                    ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                    : incomePct(item) >= 75
                                        ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                        : 'bg-blue-100 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400'"
                            >
                                {{ item.monthly_amount === 0 ? 'No target' : `${Math.round(incomePct(item))}%` }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                    Add income categories to track earnings.
                </div>
            </template>
        </div>

        <CategoryCreateModal :show="showCreateModal" :default-type="activeSection" @close="showCreateModal = false" />
        <CategoryEditModal :category="editCategory" @close="editCategory = null" />

        <!-- Settle Loan Modal -->
        <div v-if="settleConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="settleConfirm = null">
            <div class="bg-white dark:bg-coin-dark-card rounded-2xl p-6 w-full max-w-sm space-y-4 shadow-xl">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Settle Loan</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Mark <span class="font-semibold text-gray-800 dark:text-white">{{ settleConfirm.name }}</span> as settled?
                    The remaining <span class="font-semibold text-gray-800 dark:text-white">{{ fmt(settleConfirm.remaining) }}</span> will be written off with no transaction recorded.
                </p>
                <div class="flex gap-2 pt-1">
                    <button
                        class="flex-1 py-2 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors"
                        @click="settleConfirm = null"
                    >Cancel</button>
                    <button
                        class="flex-1 py-2 rounded-xl text-sm font-medium text-white bg-coin-primary hover:bg-coin-primary/90 transition-colors"
                        @click="submitSettle"
                    >Confirm</button>
                </div>
            </div>
        </div>

        <!-- Withdraw Modal -->
        <div v-if="withdrawState" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="withdrawState = null">
            <div class="bg-white dark:bg-coin-dark-card rounded-2xl p-6 w-full max-w-sm space-y-4 shadow-xl">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Withdraw Savings</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Withdraw <span class="font-semibold text-gray-800 dark:text-white">{{ fmt(withdrawState.item.total_saved) }}</span>
                    from <span class="font-semibold text-gray-800 dark:text-white">{{ withdrawState.item.name }}</span> as income.
                </p>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Income Category</label>
                    <select
                        v-model="withdrawState.incomeCategoryId"
                        class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-sm text-gray-800 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-coin-primary"
                    >
                        <option :value="null" disabled>Select a category</option>
                        <option v-for="cat in incomes" :key="cat.category_id" :value="cat.category_id">{{ cat.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-1">
                    <button
                        class="flex-1 py-2 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors"
                        @click="withdrawState = null"
                    >Cancel</button>
                    <button
                        class="flex-1 py-2 rounded-xl text-sm font-medium text-white bg-coin-primary hover:bg-coin-primary/90 transition-colors disabled:opacity-50"
                        :disabled="!withdrawState.incomeCategoryId"
                        @click="submitWithdraw"
                    >Confirm</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
