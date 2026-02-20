<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { ChevronDown, X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    modelValue: string;
    options: Array<{ value: string; label: string }>;
    placeholder?: string;
    allLabel?: string;
    required?: boolean;
}>(), {
    placeholder: 'Select…',
    allLabel: '',
    required: false,
});

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const open = ref(false);
const search = ref('');
const container = ref<HTMLElement | null>(null);

const selected = computed(() => props.options.find(o => o.value === props.modelValue) ?? null);

const filtered = computed(() => {
    const q = search.value.toLowerCase();
    return q ? props.options.filter(o => o.label.toLowerCase().includes(q)) : props.options;
});

const displayLabel = computed(() => {
    if (props.modelValue === '' && props.allLabel) return props.allLabel;
    return selected.value?.label ?? '';
});

const toggle = () => {
    open.value = !open.value;
    if (open.value) search.value = '';
};

const select = (value: string) => {
    emit('update:modelValue', value);
    open.value = false;
    search.value = '';
};

const clear = (e: Event) => {
    e.stopPropagation();
    emit('update:modelValue', '');
    open.value = false;
    search.value = '';
};

const onOutsideClick = (e: MouseEvent) => {
    if (container.value && !container.value.contains(e.target as Node)) {
        open.value = false;
        search.value = '';
    }
};

onMounted(() => document.addEventListener('mousedown', onOutsideClick));
onBeforeUnmount(() => document.removeEventListener('mousedown', onOutsideClick));

watch(() => props.modelValue, () => { search.value = ''; });
</script>

<template>
    <div ref="container" class="relative">
        <!-- Trigger -->
        <button
            type="button"
            class="input flex items-center justify-between gap-2 text-left w-full"
            :class="{ 'ring-2 ring-coin-primary/40': open }"
            @click="toggle"
        >
            <span :class="displayLabel ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">
                {{ displayLabel || placeholder }}
            </span>
            <span class="flex items-center gap-1 flex-shrink-0">
                <X
                    v-if="modelValue && !required"
                    class="w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    @click="clear"
                />
                <ChevronDown class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" />
            </span>
        </button>

        <!-- Dropdown -->
        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-coin-dark-card shadow-xl overflow-hidden"
        >
            <!-- Search -->
            <div class="p-2 border-b border-gray-100 dark:border-white/5">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search…"
                    class="w-full px-3 py-1.5 text-sm rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:ring-2 focus:ring-coin-primary/40"
                    autofocus
                />
            </div>

            <!-- Options -->
            <ul class="max-h-52 overflow-y-auto py-1">
                <!-- All option -->
                <li
                    v-if="allLabel"
                    class="px-3 py-2 text-sm cursor-pointer transition-colors"
                    :class="modelValue === ''
                        ? 'bg-coin-primary/10 text-coin-primary font-medium'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
                    @click="select('')"
                >
                    {{ allLabel }}
                </li>

                <li
                    v-for="opt in filtered"
                    :key="opt.value"
                    class="px-3 py-2 text-sm cursor-pointer transition-colors"
                    :class="modelValue === opt.value
                        ? 'bg-coin-primary/10 text-coin-primary font-medium'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
                    @click="select(opt.value)"
                >
                    {{ opt.label }}
                </li>

                <li v-if="filtered.length === 0" class="px-3 py-3 text-sm text-center text-gray-400 dark:text-gray-500">
                    No categories found
                </li>
            </ul>
        </div>
    </div>
</template>
