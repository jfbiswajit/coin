<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import DatePicker from '@/Components/DatePicker.vue';

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

const props = defineProps<{
    categories: Array<{ id: number; name: string; type: string; color: string; icon: string }>;
}>();

const form = useForm({
    uuid: generateUUID(),
    category_id: '',
    amount: '',
    type: 'expense',
    transacted_at: new Date().toISOString().split('T')[0],
    title: '',
});

const submit = () => {
    form.post('/transactions', {
        onSuccess: () => router.visit('/transactions'),
    });
};
</script>

<template>
    <Head title="Add Transaction" />
    <AppLayout>
        <div class="max-w-lg mx-auto space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Add Transaction</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Record a new entry</p>
                </div>
            </div>

            <form class="card space-y-4" @submit.prevent="submit">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200 dark:border-white/10">
                        <button
                            type="button"
                            class="flex-1 py-2.5 text-sm font-medium transition-all"
                            :class="form.type === 'expense' ? 'bg-red-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                            @click="form.type = 'expense'"
                        >Expense</button>
                        <button
                            type="button"
                            class="flex-1 py-2.5 text-sm font-medium transition-all"
                            :class="form.type === 'income' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-white/5 text-gray-600 dark:text-gray-400'"
                            @click="form.type = 'income'"
                        >Income</button>
                    </div>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                        placeholder="0.00"
                        class="input text-lg font-semibold"
                    />
                    <p v-if="form.errors.amount" class="text-red-500 text-xs mt-1">{{ form.errors.amount }}</p>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select v-model="form.category_id" required class="input">
                        <option value="" disabled>Select category</option>
                        <option
                            v-for="cat in categories.filter(c => c.type === form.type)"
                            :key="cat.id"
                            :value="String(cat.id)"
                        >{{ cat.name }}</option>
                    </select>
                    <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">{{ form.errors.category_id }}</p>
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                    <DatePicker v-model="form.transacted_at" />
                </div>


                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                    <input v-model="form.title" type="text" required placeholder="What was this for?" class="input" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <button
                    type="submit"
                    class="btn-primary w-full py-3 text-base"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : 'Save Transaction' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
