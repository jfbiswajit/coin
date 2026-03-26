<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CircleDollarSign,
    LayoutDashboard,
    LogOut,
    PiggyBank,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Toast from '@/Components/Toast.vue';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';

const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));

const offStart = router.on('start', () => { ready.value = false; });
const offFinish = router.on('finish', () => requestAnimationFrame(() => { ready.value = true; }));
onUnmounted(() => { offStart(); offFinish(); });

const page = usePage();
const user = computed(() => page.props.auth.user);

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, name: 'dashboard', mobileHidden: false },
    { label: 'Budget', href: '/budget', icon: PiggyBank, name: 'budget', mobileHidden: false },
    { label: 'Transactions', href: '/transactions', icon: CircleDollarSign, name: 'transactions', mobileHidden: false },
];

const mobileNavItems = navItems.filter(item => !item.mobileHidden);

const isActive = (name: string) => page.component.toLowerCase().startsWith(name);

useKeyboardShortcuts({
    d: () => router.visit('/dashboard'),
    t: () => router.visit('/transactions'),
    b: () => router.visit('/budget'),
});
</script>

<template>
    <div class="bg-app min-h-screen flex bg-coin-dark-bg">

        <aside class="hidden md:flex flex-col w-60 glass-panel fixed inset-y-0 left-0 z-30">

            <div class="px-6 py-5 border-b border-white/[0.06]">
                <div class="flex items-center gap-3">
                    <div class="relative flex-shrink-0">
                        <div class="absolute inset-0 rounded-2xl bg-violet-500/40 blur-md"></div>
                        <img src="/favicon.svg" alt="Coin" class="w-9 h-9" />
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-lg font-extrabold tracking-widest" style="color: #C8870A">COIN</span>
                        <span class="text-[10px] font-semibold text-gray-400 tracking-wider uppercase mt-0.5">Personal Finance</span>
                    </div>
                </div>
            </div>


            <nav class="flex-1 px-3 py-4 space-y-0.5">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
                    :class="isActive(item.name)
                        ? 'bg-coin-primary/90 backdrop-blur-sm text-white shadow-md shadow-violet-500/25'
                        : 'text-gray-400 hover:bg-white/[0.06] hover:text-white'"
                >
                    <component :is="item.icon" class="w-4.5 h-4.5 flex-shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>


            <div class="px-3 py-3 border-t border-white/[0.06]">
                <div class="flex items-center gap-2 px-2">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-coin-primary to-coin-accent flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                        {{ user?.name?.[0]?.toUpperCase() ?? 'U' }}
                    </div>
                    <span class="text-xs font-medium text-gray-400 truncate flex-1">{{ user?.name }}</span>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        title="Log out"
                        class="p-1.5 rounded-lg text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition-all"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </aside>


        <div class="flex-1 md:ml-60 min-h-screen flex flex-col relative z-10">

            <header class="md:hidden flex items-center px-4 pb-3 glass-panel sticky top-0 z-20" style="padding-top: max(env(safe-area-inset-top), 12px)">
                <div class="flex items-center gap-2.5">
                    <div class="relative flex-shrink-0">
                        <img src="/favicon.svg" alt="Coin" class="w-8 h-8" />
                    </div>
                    <span class="text-base font-extrabold tracking-widest" style="color: #C8870A">COIN</span>
                </div>
            </header>


            <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6 transition-all duration-500"
                :class="ready ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                <slot />
            </main>
        </div>

        <Toast />


        <nav class="md:hidden fixed bottom-0 inset-x-0 glass-panel z-30 px-1 pt-1.5" style="padding-bottom: max(env(safe-area-inset-bottom), 6px)">
            <div class="flex items-center justify-around">
                <Link
                    v-for="item in mobileNavItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center justify-center px-5 py-2 rounded-2xl transition-all duration-200"
                    :class="isActive(item.name)
                        ? 'text-coin-primary bg-coin-primary/10'
                        : 'text-gray-500'"
                >
                    <component :is="item.icon" class="w-7 h-7" />
                </Link>
            </div>
        </nav>
    </div>
</template>
