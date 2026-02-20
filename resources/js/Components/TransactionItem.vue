<script setup lang="ts">
const props = defineProps<{
    transaction: {
        id: number;
        amount: number;
        type: string;
        date: string;
        note: string | null;
        category: { name: string; color: string; icon: string };
    };
}>();

const fmt = (amount: number) =>
    new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);

const fmtDate = (dateStr: string) =>
    new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(new Date(dateStr));
</script>

<template>
    <div class="flex items-center gap-3 py-3 cursor-pointer">
        <div
            class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-white text-sm font-bold"
            :style="{ backgroundColor: transaction.category.color }"
        >
            {{ transaction.category.name[0] }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-900 dark:text-white text-sm truncate">
                {{ transaction.note || transaction.category.name }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ backgroundColor: transaction.category.color }"></span>
                <span>{{ transaction.category.name }}</span>
                <span>·</span>
                <span>{{ fmtDate(transaction.date) }}</span>
            </div>
        </div>
        <div
            class="font-semibold text-sm flex-shrink-0"
            :class="transaction.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'"
        >
            {{ transaction.type === 'income' ? '+' : '-' }}৳{{ fmt(transaction.amount) }}
        </div>
    </div>
</template>
