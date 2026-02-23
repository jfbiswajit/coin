<script setup lang="ts">
import AppModal from '@/Components/AppModal.vue';
import DatePicker from '@/Components/DatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';

const generateUUID = (): string => {
    if (typeof crypto.randomUUID === 'function') return crypto.randomUUID();
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    return [...bytes].map((b, i) =>
        ([4, 6, 8, 10].includes(i) ? '-' : '') + b.toString(16).padStart(2, '0')
    ).join('');
};
import { queueOfflineTransaction, syncOfflineQueue } from '@/offline';
import { useToast } from '@/composables/useToast';

type TxType = 'expense' | 'income' | 'saving' | 'loan';

type Transaction = {
    id: number; amount: number; type: TxType; title: string; transacted_at: string;
    category: { id: number; name: string; color: string; icon: string };
};

const props = defineProps<{
    transactions: {
        data: Transaction[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
    };
    categories: Array<{ id: number; name: string; type: TxType; color: string }>;
    filters: { month: number; year: number; type?: string; category_id?: string };
    typeCounts: { expense: number; income: number; saving: number; loan: number };
}>();

const monthYear = ref(`${props.filters.year}-${String(props.filters.month).padStart(2, '0')}`);

const displayMonthYear = computed(() => {
    const [year, month] = monthYear.value.split('-');
    return new Date(Number(year), Number(month) - 1, 1)
        .toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const shiftMonth = (delta: number) => {
    const [year, month] = monthYear.value.split('-').map(Number);
    const d = new Date(year, month - 1 + delta, 1);
    monthYear.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
};

const activeTab = ref<TxType>((props.filters.type as TxType) ?? 'expense');
const categoryId = ref(props.filters.category_id ?? '');

const applyFilters = () => {
    const [year, month] = monthYear.value.split('-');
    router.get('/transactions', {
        month: Number(month),
        year: Number(year),
        type: activeTab.value,
        ...(categoryId.value ? { category_id: categoryId.value } : {}),
    }, { preserveScroll: true, replace: true });
};

watch([monthYear, activeTab, categoryId], applyFilters);

const showAdd = ref(false);
const editTarget = ref<Transaction | null>(null);
const confirmingDelete = ref(false);
const isOffline = ref(!navigator.onLine);
const { show: showToast } = useToast();

onMounted(() => {
    window.addEventListener('online', () => { isOffline.value = false; syncOfflineQueue(); });
    window.addEventListener('offline', () => { isOffline.value = true; });
});

const todayDate = () => new Date().toISOString().slice(0, 10);

const form = useForm({
    uuid: generateUUID(),
    category_id: '',
    amount: '',
    type: 'expense' as TxType,
    transacted_at: todayDate(),
    title: '',
});

const editForm = useForm({
    category_id: '',
    amount: '' as string | number,
    type: 'expense' as TxType,
    transacted_at: '',
    title: '',
});

const groupedTransactions = computed(() => {
    const groups: Record<string, Transaction[]> = {};
    for (const t of props.transactions.data) {
        const dateKey = t.transacted_at.substring(0, 10);
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(t);
    }
    return Object.entries(groups).sort(([a], [b]) => b.localeCompare(a));
});

const formatTransactedAt = (dt: string) => {
    const d = new Date(dt + 'T00:00:00');
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    const isSameDay = (a: Date, b: Date) =>
        a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    if (isSameDay(d, today)) return 'Today';
    if (isSameDay(d, yesterday)) return 'Yesterday';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const formatDateHeader = (dateStr: string) => {
    const d = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    const isSameDay = (a: Date, b: Date) =>
        a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    if (isSameDay(d, today)) return 'Today';
    if (isSameDay(d, yesterday)) return 'Yesterday';
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const amountColor = (type: TxType) => {
    if (type === 'income') return 'text-emerald-600 dark:text-emerald-400';
    if (type === 'saving') return 'text-blue-600 dark:text-blue-400';
    if (type === 'loan') return 'text-orange-500 dark:text-orange-400';
    return 'text-red-500 dark:text-red-400';
};

const amountPrefix = (type: TxType) => type === 'income' ? '+' : '-';

const tabConfig: Record<TxType, { label: string }> = {
    expense: { label: 'Expense' },
    income: { label: 'Income' },
    saving: { label: 'Saving' },
    loan: { label: 'Loan' },
};

const openAdd = () => {
    form.uuid = generateUUID();
    form.category_id = '';
    form.amount = '';
    form.type = activeTab.value;
    form.transacted_at = todayDate();
    form.title = '';
    form.clearErrors();
    showAdd.value = true;
};

const openEdit = (t: Transaction) => {
    editTarget.value = t;
    editForm.category_id = String(t.category.id);
    editForm.amount = t.amount;
    editForm.type = t.type;
    editForm.transacted_at = t.transacted_at;
    editForm.title = t.title;
    confirmingDelete.value = false;
};

const submit = async () => {
    if (!navigator.onLine) {
        await queueOfflineTransaction({
            uuid: form.uuid,
            category_id: Number(form.category_id),
            amount: Number(form.amount),
            type: form.type,
            title: form.title,
            transacted_at: form.transacted_at,
        });
        showAdd.value = false;
        showToast('Saved offline');
        return;
    }

    form.post('/transactions', {
        onSuccess: () => { showAdd.value = false; },
    });
};

const saveEdit = () => {
    editForm.patch(`/transactions/${editTarget.value!.id}`, {
        onSuccess: () => { editTarget.value = null; },
    });
};

const confirmDelete = () => {
    if (!editTarget.value) return;
    router.delete(`/transactions/${editTarget.value.id}`, {
        onSuccess: () => { editTarget.value = null; confirmingDelete.value = false; },
    });
};
</script>

<template>
    <Head title="Transactions" />
    <AppLayout>
        <div class="space-y-5">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Transactions</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your income & expenses</p>
                </div>
                <div class="flex items-center gap-1 bg-white/60 dark:bg-white/[0.05] rounded-xl border border-white/60 dark:border-white/10 px-1 py-1">
                    <button class="p-1.5 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all" @click="shiftMonth(-1)">
                        <ChevronLeft class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                    </button>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[110px] sm:min-w-[130px] text-center select-none">
                        {{ displayMonthYear }}
                    </span>
                    <button class="p-1.5 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all" @click="shiftMonth(1)">
                        <ChevronRight class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                    </button>
                </div>
            </div>


            <div class="flex items-center justify-between gap-3">
                <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-xl overflow-x-auto">
                    <button
                        v-for="(cfg, tab) in tabConfig"
                        :key="tab"
                        class="px-3 sm:px-4 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition-all whitespace-nowrap"
                        :class="activeTab === tab
                            ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        @click="activeTab = (tab as TxType); categoryId = ''"
                    >
                        {{ cfg.label }}
                        <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full"
                            :class="activeTab === tab ? 'bg-coin-primary/10 text-coin-primary' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400'"
                        >{{ typeCounts[tab as TxType] }}</span>
                    </button>
                </div>
                <button class="btn-primary text-sm flex items-center gap-1.5 flex-shrink-0" @click="openAdd">
                    <Plus class="w-4 h-4" /> <span class="hidden sm:inline">New Transaction</span><span class="sm:hidden">Add</span>
                </button>
            </div>


            <SearchableSelect
                v-model="categoryId"
                :options="categories.filter(c => c.type === activeTab).map(c => ({ value: String(c.id), label: c.name }))"
                all-label="All categories"
                placeholder="All categories"
            />


            <div v-if="transactions.data.length" class="space-y-4">
                <div v-for="[date, items] in groupedTransactions" :key="date" class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-bold tracking-widest uppercase text-gray-400 dark:text-gray-500 whitespace-nowrap">
                            {{ formatDateHeader(date) }}
                        </span>
                        <div class="flex-1 h-px bg-gray-200 dark:bg-white/5"></div>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="t in items"
                            :key="t.id"
                            class="card card-hoverable flex items-center gap-3 sm:gap-4"
                            @click="openEdit(t)"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold text-base sm:text-lg shadow-lg"
                                :style="{ backgroundColor: t.category.color, boxShadow: `0 4px 14px ${t.category.color}40` }"
                            >
                                {{ t.category.name[0].toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 dark:text-white truncate">{{ t.title }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ backgroundColor: t.category.color }" />
                                    <span class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ t.category.name }} · {{ formatTransactedAt(t.transacted_at) }}</span>
                                </div>
                            </div>
                            <div class="font-semibold text-sm flex-shrink-0" :class="amountColor(t.type)">
                                {{ amountPrefix(t.type) }}৳{{ new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(t.amount) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="card text-center py-10 text-gray-400 dark:text-gray-600 text-sm">
                No transactions found for this period.
            </div>


            <div v-if="transactions.last_page > 1" class="flex justify-center gap-2 flex-wrap">
                <button
                    v-for="link in transactions.links"
                    :key="link.label"
                    :disabled="!link.url"
                    class="px-3 py-2 rounded-lg text-sm transition-all disabled:opacity-40"
                    :class="link.active
                        ? 'bg-coin-primary text-white'
                        : 'bg-white dark:bg-coin-dark-card text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5'"
                    v-html="link.label"
                    @click="link.url && router.visit(link.url)"
                />
            </div>
        </div>


        <AppModal v-if="showAdd" title="Add Transaction" @close="showAdd = false">
            <form class="space-y-5" @submit.prevent="submit">
                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg flex-shrink-0 bg-coin-primary"
                        style="box-shadow: 0 4px 14px rgba(124,58,237,0.4)">
                        {{ form.title ? form.title[0].toUpperCase() : '?' }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ form.title || 'Transaction title' }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ form.type }}</div>
                    </div>
                </div>


                <div class="grid grid-cols-4 rounded-xl overflow-hidden border border-gray-200 dark:border-white/10">
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="form.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="form.type = 'expense'; form.category_id = ''"
                    >Expense</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="form.type === 'income' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="form.type = 'income'; form.category_id = ''"
                    >Income</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="form.type === 'saving' ? 'bg-blue-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="form.type = 'saving'; form.category_id = ''"
                    >Saving</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="form.type === 'loan' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="form.type = 'loan'; form.category_id = ''"
                    >Loan</button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input v-model="form.title" type="text" required placeholder="e.g. Grocery run" class="input" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                        <input v-model="form.amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                    </div>
                    <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <SearchableSelect
                        v-model="form.category_id"
                        :options="categories.filter(c => c.type === form.type).map(c => ({ value: String(c.id), label: c.name }))"
                        placeholder="Select category"
                        required
                    />
                    <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                    <DatePicker v-model="form.transacted_at" />
                    <p v-if="form.errors.transacted_at" class="mt-1 text-xs text-red-500">{{ form.errors.transacted_at }}</p>
                </div>

                <button type="submit" class="btn-primary w-full py-2.5" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : isOffline ? 'Save Offline' : 'Save Transaction' }}
                </button>
            </form>
        </AppModal>


        <AppModal v-if="editTarget" title="Edit Transaction" @close="editTarget = null">
            <form class="space-y-5" @submit.prevent="saveEdit">
                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg flex-shrink-0 bg-coin-primary"
                        style="box-shadow: 0 4px 14px rgba(124,58,237,0.4)">
                        {{ editForm.title ? editForm.title[0].toUpperCase() : '?' }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ editForm.title || 'Transaction title' }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ editForm.type }}</div>
                    </div>
                </div>


                <div class="grid grid-cols-4 rounded-xl overflow-hidden border border-gray-200 dark:border-white/10">
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="editForm.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="editForm.type = 'expense'; editForm.category_id = ''"
                    >Expense</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="editForm.type === 'income' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="editForm.type = 'income'; editForm.category_id = ''"
                    >Income</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="editForm.type === 'saving' ? 'bg-blue-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="editForm.type = 'saving'; editForm.category_id = ''"
                    >Saving</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="editForm.type === 'loan' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="editForm.type = 'loan'; editForm.category_id = ''"
                    >Loan</button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input v-model="editForm.title" type="text" required placeholder="e.g. Grocery run" class="input" />
                    <p v-if="editForm.errors.title" class="mt-1 text-xs text-red-500">{{ editForm.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                        <input v-model="editForm.amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                    </div>
                    <p v-if="editForm.errors.amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.amount }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <SearchableSelect
                        v-model="editForm.category_id"
                        :options="categories.filter(c => c.type === editForm.type).map(c => ({ value: String(c.id), label: c.name }))"
                        placeholder="Select category"
                        required
                    />
                    <p v-if="editForm.errors.category_id" class="mt-1 text-xs text-red-500">{{ editForm.errors.category_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                    <DatePicker v-model="editForm.transacted_at" />
                    <p v-if="editForm.errors.transacted_at" class="mt-1 text-xs text-red-500">{{ editForm.errors.transacted_at }}</p>
                </div>

                <button type="submit" class="btn-primary w-full py-2.5" :disabled="editForm.processing">
                    {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                </button>

                <div class="border-t border-gray-100 dark:border-white/5 pt-4">
                    <div v-if="!confirmingDelete">
                        <button
                            type="button"
                            class="w-full py-2.5 rounded-xl text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all"
                            @click="confirmingDelete = true"
                        >
                            Delete Transaction
                        </button>
                    </div>
                    <div v-else class="space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Permanently delete this transaction? This cannot be undone.
                        </p>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-all"
                                @click="confirmingDelete = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium bg-red-500 hover:bg-red-600 text-white shadow-md shadow-red-500/30 transition-all active:scale-[0.98]"
                                @click="confirmDelete"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </AppModal>
    </AppLayout>
</template>
