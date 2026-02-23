<script setup lang="ts">
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const showCalendar = ref(false);
const containerRef = ref<HTMLElement | null>(null);

const relativeDate = (daysAgo: number) => {
    const d = new Date();
    d.setDate(d.getDate() - daysAgo);
    return d.toISOString().slice(0, 10);
};

const isRelativeDay = (daysAgo: number) => props.modelValue === relativeDate(daysAgo);

const formattedDate = computed(() => {
    if (!props.modelValue) return '';
    const [y, m, d] = props.modelValue.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
});

// Calendar state: viewing month
const viewDate = ref(new Date());

const viewYear = computed(() => viewDate.value.getFullYear());
const viewMonth = computed(() => viewDate.value.getMonth());

const monthLabel = computed(() =>
    viewDate.value.toLocaleDateString('en', { month: 'long', year: 'numeric' }),
);

const shiftMonth = (delta: number) => {
    const d = new Date(viewYear.value, viewMonth.value + delta, 1);
    viewDate.value = d;
};

const calendarDays = computed(() => {
    const firstDay = new Date(viewYear.value, viewMonth.value, 1).getDay();
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    const daysInPrev = new Date(viewYear.value, viewMonth.value, 0).getDate();

    const cells: Array<{ day: number; date: string; currentMonth: boolean }> = [];

    // Previous month fill
    for (let i = firstDay - 1; i >= 0; i--) {
        const d = daysInPrev - i;
        const dt = new Date(viewYear.value, viewMonth.value - 1, d);
        cells.push({ day: d, date: fmt(dt), currentMonth: false });
    }

    // Current month
    for (let d = 1; d <= daysInMonth; d++) {
        const dt = new Date(viewYear.value, viewMonth.value, d);
        cells.push({ day: d, date: fmt(dt), currentMonth: true });
    }

    // Next month fill to 42 cells (6 rows)
    const remaining = 42 - cells.length;
    for (let d = 1; d <= remaining; d++) {
        const dt = new Date(viewYear.value, viewMonth.value + 1, d);
        cells.push({ day: d, date: fmt(dt), currentMonth: false });
    }

    return cells;
});

const todayStr = new Date().toISOString().slice(0, 10);

function fmt(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function selectDay(date: string) {
    emit('update:modelValue', date);
    showCalendar.value = false;
}

function toggleCalendar() {
    if (!showCalendar.value) {
        // Sync view to selected date
        if (props.modelValue) {
            const [y, m] = props.modelValue.split('-').map(Number);
            viewDate.value = new Date(y, m - 1, 1);
        }
    }
    showCalendar.value = !showCalendar.value;
}

// Click outside
function onClickOutside(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        showCalendar.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<template>
    <div ref="containerRef" class="relative rounded-2xl bg-white/30 dark:bg-white/[0.03] p-3 border border-white/40 dark:border-white/[0.06] space-y-3">
        <!-- Quick-select chips -->
        <div class="flex gap-2">
            <button
                v-for="(label, i) in ['Today', 'Yesterday', '2 days ago']"
                :key="i"
                type="button"
                class="flex-1 py-2.5 rounded-xl text-xs font-medium transition-all border"
                :class="isRelativeDay(i)
                    ? 'bg-transparent border-coin-primary ring-2 ring-coin-primary text-coin-primary dark:text-coin-primary'
                    : 'bg-white/60 dark:bg-white/5 border-white/60 dark:border-white/10 text-gray-600 dark:text-gray-400 hover:border-coin-primary/50'"
                @click="emit('update:modelValue', relativeDate(i))"
            >{{ label }}</button>
        </div>

        <!-- Trigger row -->
        <div class="flex items-center gap-2.5 cursor-pointer group" @click="toggleCalendar">
            <CalendarDays class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-coin-primary transition-colors shrink-0" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-coin-primary transition-colors">{{ formattedDate }}</span>
        </div>

        <!-- Calendar dropdown -->
        <div
            v-if="showCalendar"
            class="absolute left-0 right-0 bottom-full mb-2 z-50 bg-white/80 dark:bg-coin-dark-card/80 backdrop-blur-2xl border border-white/60 dark:border-white/[0.08] rounded-2xl shadow-2xl shadow-black/20 p-4"
        >
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <button type="button" class="p-1.5 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all" @click="shiftMonth(-1)">
                    <ChevronLeft class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </button>
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 select-none">{{ monthLabel }}</span>
                <button type="button" class="p-1.5 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all" @click="shiftMonth(1)">
                    <ChevronRight class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </button>
            </div>

            <!-- Day-of-week labels -->
            <div class="grid grid-cols-7 mb-1">
                <div v-for="d in ['S','M','T','W','T','F','S']" :key="d" class="text-center text-[10px] font-semibold text-gray-400 dark:text-gray-500 py-1">{{ d }}</div>
            </div>

            <!-- Day grid -->
            <div class="grid grid-cols-7">
                <button
                    v-for="(cell, idx) in calendarDays"
                    :key="idx"
                    type="button"
                    class="aspect-square flex items-center justify-center text-xs rounded-xl transition-all"
                    :class="[
                        cell.date === modelValue
                            ? 'bg-coin-primary text-white ring-2 ring-coin-primary font-bold'
                            : cell.date === todayStr && cell.currentMonth
                                ? 'ring-1 ring-coin-primary/50 text-gray-800 dark:text-gray-200 font-medium hover:bg-coin-primary/10'
                                : cell.currentMonth
                                    ? 'text-gray-800 dark:text-gray-200 hover:bg-coin-primary/10'
                                    : 'text-gray-300 dark:text-gray-600 hover:bg-white/10',
                    ]"
                    @click="selectDay(cell.date)"
                >{{ cell.day }}</button>
            </div>
        </div>
    </div>
</template>
