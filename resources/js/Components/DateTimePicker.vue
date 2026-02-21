<script setup lang="ts">
import { ChevronDown, ChevronLeft, ChevronRight, ChevronUp, CalendarClock } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

// ── Parse / emit ─────────────────────────────────────────────────────────────

const parse = (val: string) => {
    const [datePart, timePart] = (val || '').split('T');
    const [y, m, d] = (datePart || '').split('-').map(Number);
    const [h, min] = (timePart || '00:00').split(':').map(Number);
    const date = (y && m && d) ? new Date(y, m - 1, d) : new Date();
    return { date, hours: isNaN(h) ? new Date().getHours() : h, minutes: isNaN(min) ? 0 : min };
};

const { date: iDate, hours: iH, minutes: iM } = parse(props.modelValue);
const selectedDate = ref<Date>(iDate);
const selectedHour = ref(iH);
const selectedMinute = ref(iM);
const viewYear = ref(iDate.getFullYear());
const viewMonth = ref(iDate.getMonth());

watch(() => props.modelValue, (val) => {
    const { date, hours, minutes } = parse(val);
    selectedDate.value = date;
    selectedHour.value = hours;
    selectedMinute.value = minutes;
    viewYear.value = date.getFullYear();
    viewMonth.value = date.getMonth();
});

const emitValue = () => {
    const y = selectedDate.value.getFullYear();
    const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0');
    const d = String(selectedDate.value.getDate()).padStart(2, '0');
    const h = String(selectedHour.value).padStart(2, '0');
    const min = String(selectedMinute.value).padStart(2, '0');
    emit('update:modelValue', `${y}-${m}-${d}T${h}:${min}`);
};

// ── Display ───────────────────────────────────────────────────────────────────

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS   = ['Su','Mo','Tu','We','Th','Fr','Sa'];

const displayValue = computed(() => {
    const h = selectedHour.value;
    const min = String(selectedMinute.value).padStart(2, '0');
    const period = h >= 12 ? 'PM' : 'AM';
    const h12 = String(h % 12 || 12).padStart(2, '0');
    const d = selectedDate.value;
    return `${MONTHS[d.getMonth()].slice(0, 3)} ${d.getDate()}, ${d.getFullYear()}  ${h12}:${min} ${period}`;
});

// ── Calendar ──────────────────────────────────────────────────────────────────

const calendarDays = computed(() => {
    const firstDay = new Date(viewYear.value, viewMonth.value, 1).getDay();
    const total    = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    const cells: (number | null)[] = Array(firstDay).fill(null);
    for (let i = 1; i <= total; i++) cells.push(i);
    return cells;
});

const isSelected = (day: number) =>
    selectedDate.value.getFullYear() === viewYear.value &&
    selectedDate.value.getMonth() === viewMonth.value &&
    selectedDate.value.getDate() === day;

const isToday = (day: number) => {
    const t = new Date();
    return t.getFullYear() === viewYear.value && t.getMonth() === viewMonth.value && t.getDate() === day;
};

const prevMonth = () => { if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value--; } else viewMonth.value--; };
const nextMonth = () => { if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++; } else viewMonth.value++; };

const selectDay = (day: number) => {
    selectedDate.value = new Date(viewYear.value, viewMonth.value, day);
    emitValue();
    closePicker();
};

// ── Time ──────────────────────────────────────────────────────────────────────

const clampHour   = (v: number) => Math.max(0, Math.min(23, v));
const clampMinute = (v: number) => Math.max(0, Math.min(59, v));

const incHour   = () => { selectedHour.value   = (selectedHour.value   + 1)  % 24; emitValue(); };
const decHour   = () => { selectedHour.value   = (selectedHour.value   + 23) % 24; emitValue(); };
const incMinute = () => { selectedMinute.value = (selectedMinute.value + 5)  % 60; emitValue(); };
const decMinute = () => { selectedMinute.value = (selectedMinute.value + 55) % 60; emitValue(); };

const onHourInput = (e: Event) => {
    selectedHour.value = clampHour(Number((e.target as HTMLInputElement).value));
    emitValue();
};
const onMinuteInput = (e: Event) => {
    selectedMinute.value = clampMinute(Number((e.target as HTMLInputElement).value));
    emitValue();
};

// ── Popover positioning (teleported to body) ──────────────────────────────────

const open      = ref(false);
const trigger   = ref<HTMLElement | null>(null);
const pickerPos = ref({ top: 0, left: 0, width: 0 });

const reposition = () => {
    if (!trigger.value) return;
    const r = trigger.value.getBoundingClientRect();
    const pickerH = 360;
    const below = r.bottom + 8 + pickerH < window.innerHeight;
    pickerPos.value = {
        top:   below ? r.bottom + 8 + window.scrollY : r.top - pickerH - 8 + window.scrollY,
        left:  r.left + window.scrollX,
        width: r.width,
    };
};

const openPicker = async () => {
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        reposition();
        document.addEventListener('click', onOutside, true);
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);
    }
};

const closePicker = () => {
    open.value = false;
    document.removeEventListener('click', onOutside, true);
    window.removeEventListener('scroll', reposition, true);
    window.removeEventListener('resize', reposition);
};

const onOutside = (e: MouseEvent) => {
    const picker = document.getElementById('dtp-popover');
    if (trigger.value?.contains(e.target as Node)) return;
    if (picker?.contains(e.target as Node)) return;
    closePicker();
};

onBeforeUnmount(closePicker);
</script>

<template>
    <!-- Trigger -->
    <div
        ref="trigger"
        class="input flex items-center gap-2.5 cursor-pointer select-none"
        @click="openPicker"
    >
        <CalendarClock class="w-4 h-4 text-gray-400 dark:text-gray-500 flex-shrink-0" />
        <span class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ displayValue }}</span>
    </div>

    <!-- Picker (teleported to body to escape modal overflow) -->
    <Teleport to="body">
        <div
            v-if="open"
            id="dtp-popover"
            class="fixed z-[200] rounded-2xl bg-white/90 dark:bg-[#1A1A2E]/95 backdrop-blur-2xl border border-white/60 dark:border-white/10 shadow-2xl shadow-black/30 p-4"
            :style="{ top: pickerPos.top + 'px', left: pickerPos.left + 'px', width: pickerPos.width + 'px', minWidth: '280px' }"
        >
            <!-- Time picker -->
            <div class="flex items-center justify-center gap-2 mb-3">
                <!-- Hour -->
                <div class="flex flex-col items-center gap-0.5">
                    <button type="button" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-all" @click="incHour">
                        <ChevronUp class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                    </button>
                    <input
                        type="number" min="0" max="23"
                        class="w-12 text-center text-base font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg py-1.5 focus:outline-none focus:ring-2 focus:ring-coin-primary/40 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                        :value="String(selectedHour).padStart(2, '0')"
                        @change="onHourInput"
                    />
                    <button type="button" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-all" @click="decHour">
                        <ChevronDown class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                    </button>
                </div>

                <span class="text-xl font-bold text-gray-300 dark:text-gray-600 pb-0.5">:</span>

                <!-- Minute -->
                <div class="flex flex-col items-center gap-0.5">
                    <button type="button" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-all" @click="incMinute">
                        <ChevronUp class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                    </button>
                    <input
                        type="number" min="0" max="59"
                        class="w-12 text-center text-base font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg py-1.5 focus:outline-none focus:ring-2 focus:ring-coin-primary/40 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                        :value="String(selectedMinute).padStart(2, '0')"
                        @change="onMinuteInput"
                    />
                    <button type="button" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-all" @click="decMinute">
                        <ChevronDown class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                    </button>
                </div>
            </div>

            <!-- Divider -->
            <div class="mb-3 border-t border-gray-100 dark:border-white/[0.07]" />

            <!-- Month navigation -->
            <div class="flex items-center justify-between mb-3">
                <button type="button" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-all" @click="prevMonth">
                    <ChevronLeft class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                </button>
                <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ MONTHS[viewMonth] }} {{ viewYear }}</span>
                <button type="button" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10 transition-all" @click="nextMonth">
                    <ChevronRight class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                </button>
            </div>

            <!-- Day headers -->
            <div class="grid grid-cols-7 mb-1">
                <div v-for="d in DAYS" :key="d" class="text-center text-[11px] font-medium text-gray-400 dark:text-gray-500 py-0.5">{{ d }}</div>
            </div>

            <!-- Day cells -->
            <div class="grid grid-cols-7 gap-y-0.5">
                <div v-for="(day, i) in calendarDays" :key="i" class="flex items-center justify-center">
                    <button
                        v-if="day"
                        type="button"
                        class="w-8 h-8 rounded-full text-sm transition-all"
                        :class="isSelected(day)
                            ? 'bg-coin-primary text-white font-semibold shadow-sm'
                            : isToday(day)
                                ? 'text-coin-primary font-semibold hover:bg-coin-primary/10'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10'"
                        @click="selectDay(day)"
                    >{{ day }}</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
