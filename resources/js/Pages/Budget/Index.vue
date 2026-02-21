<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));

const props = defineProps<{
    budgets: Array<{
        category_id: number;
        name: string;
        color: string;
        icon: string;
        budget: number | null;
        spent: number;
    }>;
    month: number;
    year: number;
}>();

const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

const totalBudget = computed(() => props.budgets.reduce((s, b) => s + (b.budget ?? 0), 0));
const totalSpent  = computed(() => props.budgets.reduce((s, b) => s + b.spent, 0));
const remaining   = computed(() => totalBudget.value - totalSpent.value);
const overallPct  = computed(() => totalBudget.value > 0 ? Math.min(100, (totalSpent.value / totalBudget.value) * 100) : 0);

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


const itemPct = (item: typeof props.budgets[0]) =>
    item.budget ? Math.min(100, (item.spent / item.budget) * 100) : 0;

const isOver = (item: typeof props.budgets[0]) =>
    item.budget !== null && item.spent > item.budget;

const search = ref('');
const filteredBudgets = computed(() =>
    search.value.trim()
        ? props.budgets.filter(b => b.name.toLowerCase().includes(search.value.trim().toLowerCase()))
        : props.budgets
);

const goToTransactions = (item: typeof props.budgets[0]) => {
    router.get('/transactions', {
        month: props.month,
        year: props.year,
        type: 'expense',
        category_id: String(item.category_id),
    });
};
</script>

<template>
    <Head title="Budget" />
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
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

            <!-- Summary card -->
            <div class="card space-y-4">
                <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total Budget</p>
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
                <div>
                    <div class="flex items-center justify-between text-xs text-gray-400 dark:text-gray-500 mb-1.5">
                        <span>Overall spending</span>
                        <span>{{ Math.round(overallPct) }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-700"
                            :class="overallPct >= 100 ? 'bg-red-500' : overallPct >= 75 ? 'bg-amber-500' : 'bg-coin-primary'"
                            :style="{ width: ready ? `${overallPct}%` : '0%' }"
                        />
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search categories…"
                    class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl bg-white/60 dark:bg-white/[0.05] border border-white/60 dark:border-white/10 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-coin-primary/40"
                />
            </div>

            <!-- Budget grid -->
            <div v-if="filteredBudgets.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="item in filteredBudgets"
                    :key="item.category_id"
                    class="card card-hoverable flex flex-col gap-3"
                    @click="goToTransactions(item)"
                >
                    <!-- Card header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                :style="{ backgroundColor: item.color, boxShadow: `0 4px 12px ${item.color}50` }"
                            >
                                {{ item.name[0].toUpperCase() }}
                            </div>
                            <span class="font-semibold text-sm text-gray-800 dark:text-white">{{ item.name }}</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: ready ? `${itemPct(item)}%` : '0%', backgroundColor: isOver(item) ? '#ef4444' : item.color }"
                        />
                    </div>

                    <!-- Amounts row -->
                    <div class="flex items-center justify-between">
                        <div class="text-xs">
                            <span :class="isOver(item) ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-400'">
                                {{ fmt(item.spent) }}
                            </span>
                            <span v-if="item.budget !== null" class="text-gray-400 dark:text-gray-500">
                                &nbsp;/ {{ fmt(item.budget) }}
                            </span>
                        </div>

                        <span
                            v-if="item.budget !== null"
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

                    <!-- Available row -->
                    <div v-if="item.budget !== null" class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-white/5">
                        <span class="text-xs text-gray-400 dark:text-gray-500">Available</span>
                        <span
                            class="text-xs font-semibold"
                            :class="isOver(item) ? 'text-red-500' : 'text-emerald-500'"
                        >
                            {{ fmt(item.budget - item.spent) }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="card text-center py-12 text-sm text-gray-400 dark:text-gray-600">
                {{ search.trim() ? 'No categories match your search.' : 'Add expense categories to set budgets.' }}
            </div>
        </div>

    </AppLayout>
</template>
