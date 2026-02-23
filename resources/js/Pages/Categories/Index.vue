<script setup lang="ts">
import AppModal from '@/Components/AppModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type CategoryType = 'expense' | 'income' | 'saving' | 'loan';

type Category = {
    id: number; name: string; type: CategoryType; color: string; icon: string;
    budget: number;
    loan_amount: number | null; emi_amount: number | null;
    monthly_amount: number | null; target_amount: number | null;
};

const props = defineProps<{ categories: Category[] }>();

const activeTab = ref<CategoryType>('expense');
const showAdd = ref(false);
const editTarget = ref<Category | null>(null);
const confirmingDelete = ref(false);

const addForm = useForm({
    name: '', type: 'expense' as CategoryType, color: '#7C3AED', icon: 'circle',
    budget_amount: '',
    loan_amount: '', emi_amount: '',
    monthly_amount: '', target_amount: '',
});

const editForm = useForm({
    name: '', color: '#7C3AED', icon: 'circle',
    budget_amount: '' as string | number,
    loan_amount: '' as string | number,
    emi_amount: '' as string | number,
    monthly_amount: '' as string | number,
    target_amount: '' as string | number,
});

const listed = computed(() => props.categories.filter(c => c.type === activeTab.value));

const presetColors = [
    '#F97316', '#F59E0B', '#EAB308', '#84CC16', '#22C55E',
    '#10B981', '#14B8A6', '#06B6D4', '#0EA5E9', '#3B82F6',
    '#6366F1', '#8B5CF6', '#A855F7', '#D946EF', '#EC4899',
    '#7C3AED', '#0D9488', '#059669', '#EA580C', '#CA8A04',
    '#65A30D', '#0284C7', '#4F46E5', '#7E22CE', '#64748B',
    '#475569', '#0891B2', '#2563EB', '#9333EA', '#C026D3',
];

const fmt = (v: number) => '৳' + new Intl.NumberFormat('en', { minimumFractionDigits: 2 }).format(v);
const randomColor = () => presetColors[Math.floor(Math.random() * presetColors.length)];

const tabConfig: Record<CategoryType, { label: string; color: string }> = {
    expense: { label: 'Expense', color: 'bg-red-500' },
    income: { label: 'Income', color: 'bg-emerald-500' },
    saving: { label: 'Saving', color: 'bg-blue-500' },
    loan: { label: 'Loan', color: 'bg-orange-500' },
};

const openEdit = (cat: Category) => {
    editTarget.value = cat;
    editForm.name = cat.name;
    editForm.color = cat.color;
    editForm.icon = cat.icon;
    editForm.budget_amount = cat.budget;
    editForm.loan_amount = cat.loan_amount ?? '';
    editForm.emi_amount = cat.emi_amount ?? '';
    editForm.monthly_amount = cat.monthly_amount ?? '';
    editForm.target_amount = cat.target_amount ?? '';
    confirmingDelete.value = false;
};

const saveNew = () => {
    addForm.post('/categories', {
        onSuccess: () => { showAdd.value = false; addForm.reset(); addForm.color = '#7C3AED'; },
    });
};

const saveEdit = () => {
    editForm.patch(`/categories/${editTarget.value!.id}`, {
        onSuccess: () => { editTarget.value = null; },
    });
};

const confirmDelete = () => {
    if (!editTarget.value) return;
    router.delete(`/categories/${editTarget.value.id}`, {
        onSuccess: () => { editTarget.value = null; confirmingDelete.value = false; },
    });
};

const cardSubtitle = (cat: Category) => {
    if (cat.type === 'expense') return `${fmt(cat.budget)} / mo`;
    if (cat.type === 'income') return cat.monthly_amount ? `${fmt(cat.monthly_amount)} / mo` : '';
    if (cat.type === 'loan') return `EMI ${fmt(cat.emi_amount ?? 0)} · Total ${fmt(cat.loan_amount ?? 0)}`;
    if (cat.type === 'saving') {
        const base = `${fmt(cat.monthly_amount ?? 0)} / mo`;
        return cat.target_amount ? `${base} · Goal ${fmt(cat.target_amount)}` : base;
    }
    return '';
};
</script>

<template>
    <Head title="Categories" />
    <AppLayout>
        <div class="space-y-5">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Categories</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Organise how you label money</p>
                </div>
                <button class="btn-primary text-sm flex items-center gap-1.5" @click="showAdd = true; addForm.type = activeTab; addForm.color = randomColor()">
                    <Plus class="w-4 h-4" /> <span class="hidden sm:inline">New Category</span><span class="sm:hidden">Add</span>
                </button>
            </div>


            <div class="flex gap-1 p-1 bg-gray-100 dark:bg-white/5 rounded-xl w-fit">
                <button
                    v-for="tab in (['expense', 'income', 'saving', 'loan'] as const)"
                    :key="tab"
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all capitalize"
                    :class="activeTab === tab
                        ? 'bg-white dark:bg-coin-dark-card text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    @click="activeTab = tab"
                >
                    {{ tabConfig[tab].label }}
                    <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                        :class="activeTab === tab ? 'bg-coin-primary/10 text-coin-primary' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400'"
                    >
                        {{ categories.filter(c => c.type === tab).length }}
                    </span>
                </button>
            </div>


            <div v-if="listed.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div
                    v-for="cat in listed"
                    :key="cat.id"
                    class="card card-hoverable flex items-center gap-4"
                    @click="openEdit(cat)"
                >
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-lg"
                        :style="{ backgroundColor: cat.color, boxShadow: `0 4px 14px ${cat.color}40` }"
                    >
                        {{ cat.name[0].toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white truncate">{{ cat.name }}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ cardSubtitle(cat) }}</div>
                    </div>
                </div>
            </div>


            <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-4">
                    <Plus class="w-7 h-7 text-gray-400" />
                </div>
                <p class="font-medium text-gray-600 dark:text-gray-400">No {{ tabConfig[activeTab].label.toLowerCase() }} categories</p>
                <p class="text-sm text-gray-400 dark:text-gray-600 mt-1">Add one to start tracking</p>
                <button class="btn-primary mt-4 text-sm" @click="showAdd = true; addForm.type = activeTab; addForm.color = randomColor()">
                    Add category
                </button>
            </div>
        </div>


        <AppModal v-if="showAdd" title="New Category" @close="showAdd = false">
            <form class="space-y-5" @submit.prevent="saveNew">

                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg flex-shrink-0"
                        :style="{ backgroundColor: addForm.color, boxShadow: `0 4px 14px ${addForm.color}40` }"
                    >
                        {{ addForm.name ? addForm.name[0].toUpperCase() : '?' }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ addForm.name || 'Category name' }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ addForm.type }}</div>
                    </div>
                </div>


                <div class="grid grid-cols-4 rounded-xl overflow-hidden border border-gray-200 dark:border-white/10">
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="addForm.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="addForm.type = 'expense'"
                    >Expense</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="addForm.type === 'income' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="addForm.type = 'income'"
                    >Income</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="addForm.type === 'saving' ? 'bg-blue-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="addForm.type = 'saving'"
                    >Saving</button>
                    <button type="button"
                        class="py-2 text-xs font-medium transition-all"
                        :class="addForm.type === 'loan' ? 'bg-orange-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                        @click="addForm.type = 'loan'"
                    >Loan</button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input v-model="addForm.name" type="text" required class="input" placeholder="e.g. Groceries" />
                    <p v-if="addForm.errors.name" class="mt-1 text-xs text-red-500">{{ addForm.errors.name }}</p>
                </div>


                <div v-if="addForm.type === 'expense'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Budget</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                        <input v-model="addForm.budget_amount" type="number" step="0.01" min="0" required placeholder="0.00" class="input pl-7" />
                    </div>
                    <p v-if="addForm.errors.budget_amount" class="mt-1 text-xs text-red-500">{{ addForm.errors.budget_amount }}</p>
                </div>


                <div v-if="addForm.type === 'income'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Monthly Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                        <input v-model="addForm.monthly_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                    </div>
                    <p v-if="addForm.errors.monthly_amount" class="mt-1 text-xs text-red-500">{{ addForm.errors.monthly_amount }}</p>
                </div>


                <template v-if="addForm.type === 'loan'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Loan Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="addForm.loan_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="addForm.errors.loan_amount" class="mt-1 text-xs text-red-500">{{ addForm.errors.loan_amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly EMI</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="addForm.emi_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="addForm.errors.emi_amount" class="mt-1 text-xs text-red-500">{{ addForm.errors.emi_amount }}</p>
                    </div>
                </template>


                <template v-if="addForm.type === 'saving'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Contribution</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="addForm.monthly_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="addForm.errors.monthly_amount" class="mt-1 text-xs text-red-500">{{ addForm.errors.monthly_amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Amount <span class="text-gray-400">(optional)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="addForm.target_amount" type="number" step="0.01" min="0" placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="addForm.errors.target_amount" class="mt-1 text-xs text-red-500">{{ addForm.errors.target_amount }}</p>
                    </div>
                </template>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="c in presetColors"
                            :key="c"
                            type="button"
                            class="w-9 h-9 rounded-lg transition-all"
                            :style="{ backgroundColor: c }"
                            :class="addForm.color === c ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-coin-dark-card ring-gray-600' : 'hover:scale-110'"
                            @click="addForm.color = c"
                        />
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-2.5" :disabled="addForm.processing">
                    {{ addForm.processing ? 'Adding…' : 'Add Category' }}
                </button>
            </form>
        </AppModal>


        <AppModal v-if="editTarget" :title="`Edit ${editTarget.name}`" @close="editTarget = null">
            <form class="space-y-5" @submit.prevent="saveEdit">
                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg flex-shrink-0"
                        :style="{ backgroundColor: editForm.color, boxShadow: `0 4px 14px ${editForm.color}40` }"
                    >
                        {{ editForm.name ? editForm.name[0].toUpperCase() : '?' }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ editForm.name || '—' }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ editTarget.type }}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input v-model="editForm.name" type="text" required class="input" />
                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-500">{{ editForm.errors.name }}</p>
                </div>


                <div v-if="editTarget.type === 'expense'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Budget</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                        <input v-model="editForm.budget_amount" type="number" step="0.01" min="0" required placeholder="0.00" class="input pl-7" />
                    </div>
                    <p v-if="editForm.errors.budget_amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.budget_amount }}</p>
                </div>


                <div v-if="editTarget.type === 'income'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Monthly Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                        <input v-model="editForm.monthly_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                    </div>
                    <p v-if="editForm.errors.monthly_amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.monthly_amount }}</p>
                </div>


                <template v-if="editTarget.type === 'loan'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Loan Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="editForm.loan_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="editForm.errors.loan_amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.loan_amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly EMI</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="editForm.emi_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="editForm.errors.emi_amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.emi_amount }}</p>
                    </div>
                </template>


                <template v-if="editTarget.type === 'saving'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Contribution</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="editForm.monthly_amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="editForm.errors.monthly_amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.monthly_amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Amount <span class="text-gray-400">(optional)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-violet-400 select-none pointer-events-none">৳</span>
                            <input v-model="editForm.target_amount" type="number" step="0.01" min="0" placeholder="0.00" class="input pl-7" />
                        </div>
                        <p v-if="editForm.errors.target_amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.target_amount }}</p>
                    </div>
                </template>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="c in presetColors"
                            :key="c"
                            type="button"
                            class="w-9 h-9 rounded-lg transition-all"
                            :style="{ backgroundColor: c }"
                            :class="editForm.color === c ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-coin-dark-card ring-gray-600' : 'hover:scale-110'"
                            @click="editForm.color = c"
                        />
                    </div>
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
                            Delete Category
                        </button>
                    </div>
                    <div v-else class="space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Permanently delete <span class="font-semibold text-gray-900 dark:text-white">{{ editTarget!.name }}</span> and all its transactions?
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
