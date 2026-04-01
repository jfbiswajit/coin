<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const open = ref(false);
const pickerRef = ref<HTMLElement>();
const svRef = ref<HTMLElement>();
const hueRef = ref<HTMLElement>();

const hue = ref(0);
const sat = ref(100);
const val = ref(100);
const hexInput = ref(props.modelValue);

watch(() => props.modelValue, (v) => { hexInput.value = v; });

function hexToHsv(hex: string) {
    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    let h = 0;
    if (d) {
        if (max === r) h = ((g - b) / d + 6) % 6;
        else if (max === g) h = (b - r) / d + 2;
        else h = (r - g) / d + 4;
        h *= 60;
    }
    return { h, s: max ? (d / max) * 100 : 0, v: max * 100 };
}

function hsvToHex(h: number, s: number, v: number) {
    s /= 100; v /= 100;
    const f = (n: number) => {
        const k = (n + h / 60) % 6;
        return v - v * s * Math.max(0, Math.min(k, 4 - k, 1));
    };
    return `#${[f(5), f(3), f(1)].map(x => Math.round(x * 255).toString(16).padStart(2, '0')).join('')}`;
}

function syncFromHex(hex: string) {
    if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        const hsv = hexToHsv(hex);
        hue.value = hsv.h;
        sat.value = hsv.s;
        val.value = hsv.v;
    }
}

// Only init h/s/v when opening — avoids circular update loop
watch(open, (v) => {
    if (v) { syncFromHex(props.modelValue); hexInput.value = props.modelValue; }
});

watch([hue, sat, val], () => {
    const hex = hsvToHex(hue.value, sat.value, val.value);
    hexInput.value = hex;
    emit('update:modelValue', hex);
});

function clamp(n: number, lo: number, hi: number) { return Math.max(lo, Math.min(hi, n)); }

function pickSV(e: MouseEvent | Touch) {
    const rect = svRef.value!.getBoundingClientRect();
    sat.value = clamp(((e.clientX - rect.left) / rect.width) * 100, 0, 100);
    val.value = clamp((1 - (e.clientY - rect.top) / rect.height) * 100, 0, 100);
}

function pickHue(e: MouseEvent | Touch) {
    const rect = hueRef.value!.getBoundingClientRect();
    hue.value = clamp(((e.clientX - rect.left) / rect.width) * 360, 0, 360);
}

let drag: 'sv' | 'hue' | null = null;
function onMouseMove(e: MouseEvent) {
    if (drag === 'sv') pickSV(e);
    else if (drag === 'hue') pickHue(e);
}
function onMouseUp() { drag = null; }

onMounted(() => { window.addEventListener('mousemove', onMouseMove); window.addEventListener('mouseup', onMouseUp); });
onUnmounted(() => { window.removeEventListener('mousemove', onMouseMove); window.removeEventListener('mouseup', onMouseUp); });

function onOutsideClick(e: MouseEvent) {
    if (!pickerRef.value?.contains(e.target as Node)) open.value = false;
}
watch(open, (v) => {
    if (v) setTimeout(() => document.addEventListener('click', onOutsideClick), 0);
    else document.removeEventListener('click', onOutsideClick);
});

const hueColor = computed(() => `hsl(${hue.value}, 100%, 50%)`);
const cursorStyle = computed(() => ({ left: `${sat.value}%`, top: `${100 - val.value}%` }));
const hueThumbStyle = computed(() => ({ left: `${(hue.value / 360) * 100}%`, backgroundColor: hueColor.value }));
</script>

<template>
    <div ref="pickerRef" class="relative">
        <!-- Trigger -->
        <button
            type="button"
            class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl border border-white/10 bg-white/[0.05] hover:border-white/20 transition-all"
            @click="open = !open"
        >
            <span
                class="w-7 h-7 rounded-lg flex-shrink-0"
                :style="{ backgroundColor: modelValue, boxShadow: `0 2px 8px ${modelValue}60` }"
            />
            <span class="text-sm font-mono text-gray-300 uppercase tracking-wider flex-1 text-left">{{ modelValue }}</span>
            <span class="text-xs text-gray-500">Tap to change</span>
        </button>

        <!-- Popover -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out origin-bottom"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in origin-bottom"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-2"
        >
            <div
                v-if="open"
                class="absolute z-50 bottom-full mb-2 left-0 right-0 rounded-2xl border border-white/10 bg-[#13131f] shadow-2xl p-4 space-y-3"
            >
                <!-- Saturation / Value area -->
                <div
                    ref="svRef"
                    class="relative h-44 rounded-xl overflow-hidden cursor-crosshair select-none"
                    @mousedown.prevent="drag = 'sv'; pickSV($event)"
                    @touchstart.prevent="(e: TouchEvent) => { drag = 'sv'; pickSV(e.touches[0]); }"
                    @touchmove.prevent="(e: TouchEvent) => pickSV(e.touches[0])"
                >
                    <div class="absolute inset-0" :style="{ backgroundColor: hueColor }" />
                    <div class="absolute inset-0" style="background: linear-gradient(to right, #fff, transparent)" />
                    <div class="absolute inset-0" style="background: linear-gradient(to bottom, transparent, #000)" />
                    <div
                        class="absolute w-4 h-4 rounded-full border-2 border-white shadow-lg -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                        :style="{ ...cursorStyle, backgroundColor: modelValue }"
                    />
                </div>

                <!-- Hue slider -->
                <div
                    ref="hueRef"
                    class="relative h-3 rounded-full cursor-pointer select-none"
                    style="background: linear-gradient(to right, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00)"
                    @mousedown.prevent="drag = 'hue'; pickHue($event)"
                    @touchstart.prevent="(e: TouchEvent) => { drag = 'hue'; pickHue(e.touches[0]); }"
                    @touchmove.prevent="(e: TouchEvent) => pickHue(e.touches[0])"
                >
                    <div
                        class="absolute top-1/2 w-5 h-5 rounded-full border-2 border-white shadow-md -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                        :style="hueThumbStyle"
                    />
                </div>

                <!-- Hex input row -->
                <div class="flex items-center gap-2.5">
                    <span
                        class="w-8 h-8 rounded-lg flex-shrink-0 shadow-md"
                        :style="{ backgroundColor: modelValue, boxShadow: `0 2px 10px ${modelValue}50` }"
                    />
                    <input
                        v-model="hexInput"
                        type="text"
                        maxlength="7"
                        class="input py-1.5 text-sm font-mono uppercase tracking-widest"
                        placeholder="#000000"
                        @input="syncFromHex(hexInput)"
                        @blur="hexInput = modelValue"
                    />
                </div>
            </div>
        </Transition>
    </div>
</template>
