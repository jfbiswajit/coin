<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useColorMode } from '@vueuse/core';
import {
    BarChart3,
    CircleDollarSign,
    LayoutDashboard,
    LogOut,
    Moon,
    PiggyBank,
    Sun,
    Tag,
} from 'lucide-vue-next';
import { computed } from 'vue';
import Toast from '@/Components/Toast.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const mode = useColorMode();

const toggleDark = () => {
    mode.value = mode.value === 'dark' ? 'light' : 'dark';
};

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, name: 'dashboard', mobileHidden: false },
    { label: 'Transactions', href: '/transactions', icon: CircleDollarSign, name: 'transactions', mobileHidden: false },
    { label: 'Budget', href: '/budget', icon: PiggyBank, name: 'budget', mobileHidden: false },
    { label: 'Categories', href: '/categories', icon: Tag, name: 'categories', mobileHidden: true },
    { label: 'Reports', href: '/reports', icon: BarChart3, name: 'reports', mobileHidden: true },
];

const mobileNavItems = navItems.filter(item => !item.mobileHidden);

const isActive = (name: string) => page.component.toLowerCase().startsWith(name);
</script>

<template>
    <div class="bg-app min-h-screen flex dark:bg-coin-dark-bg bg-[#f0edff]">
        <!-- Desktop Sidebar -->
        <aside class="hidden md:flex flex-col w-60 glass-panel fixed inset-y-0 left-0 z-30">
            <!-- Logo -->
            <div class="px-6 py-5 border-b border-white/40 dark:border-white/[0.06]">
                <div class="flex items-center gap-3">
                    <div class="relative flex-shrink-0">
                        <div class="absolute inset-0 rounded-2xl bg-violet-500/40 blur-md"></div>
                        <div class="relative w-9 h-9 rounded-2xl bg-gradient-to-br from-violet-500 via-violet-600 to-purple-700 flex items-center justify-center shadow-lg shadow-violet-500/50">
                            <span class="text-white font-black text-base leading-none">₵</span>
                        </div>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-black text-lg bg-gradient-to-r from-violet-600 to-purple-500 dark:from-violet-400 dark:to-purple-300 bg-clip-text text-transparent tracking-tight">Coin</span>
                        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 tracking-wider uppercase mt-0.5">Personal Finance</span>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-0.5">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
                    :class="isActive(item.name)
                        ? 'bg-coin-primary/90 backdrop-blur-sm text-white shadow-md shadow-violet-500/25'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-white/[0.06] hover:text-gray-900 dark:hover:text-white'"
                >
                    <component :is="item.icon" class="w-4.5 h-4.5 flex-shrink-0" />
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Footer -->
            <div class="px-3 py-3 border-t border-white/40 dark:border-white/[0.06]">
                <div class="flex items-center gap-2 px-2">
                    <div class="w-6 h-6 rounded-md bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                        {{ user?.name?.[0]?.toUpperCase() ?? 'U' }}
                    </div>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400 truncate flex-1">{{ user?.name }}</span>
                    <button
                        class="p-1.5 rounded-lg text-gray-400 hover:bg-white/60 dark:hover:bg-white/[0.08] hover:text-gray-700 dark:hover:text-gray-200 transition-all"
                        :title="mode === 'dark' ? 'Light mode' : 'Dark mode'"
                        @click="toggleDark"
                    >
                        <Sun v-if="mode === 'dark'" class="w-3.5 h-3.5" />
                        <Moon v-else class="w-3.5 h-3.5" />
                    </button>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        title="Log out"
                        class="p-1.5 rounded-lg text-gray-400 hover:bg-red-50/80 dark:hover:bg-red-500/10 hover:text-red-500 dark:hover:text-red-400 transition-all"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 md:ml-60 min-h-screen flex flex-col relative z-10">
            <!-- Top bar (mobile) -->
            <header class="md:hidden flex items-center justify-between px-4 pb-3 glass-panel sticky top-0 z-20" style="padding-top: max(env(safe-area-inset-top), 12px)">
                <div class="flex items-center gap-2.5">
                    <div class="relative flex-shrink-0">
                        <div class="absolute inset-0 rounded-xl bg-violet-500/40 blur-sm"></div>
                        <div class="relative w-8 h-8 rounded-xl bg-gradient-to-br from-violet-500 via-violet-600 to-purple-700 flex items-center justify-center shadow-md shadow-violet-500/40">
                            <span class="text-white font-black text-sm leading-none">₵</span>
                        </div>
                    </div>
                    <span class="font-black text-base bg-gradient-to-r from-violet-600 to-purple-500 dark:from-violet-400 dark:to-purple-300 bg-clip-text text-transparent tracking-tight">Coin</span>
                </div>
                <button
                    class="p-2 rounded-xl text-gray-500 hover:bg-white/60 dark:hover:bg-white/10 backdrop-blur-sm transition-all"
                    @click="toggleDark"
                >
                    <Sun v-if="mode === 'dark'" class="w-5 h-5" />
                    <Moon v-else class="w-5 h-5" />
                </button>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6">
                <slot />
            </main>
        </div>

        <Toast />

        <!-- Mobile Bottom Tab Bar -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 glass-panel z-30 px-1 pt-1.5" style="padding-bottom: max(env(safe-area-inset-bottom), 6px)">
            <div class="flex items-center justify-around">
                <Link
                    v-for="item in mobileNavItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex flex-col items-center gap-0.5 px-5 py-1.5 rounded-2xl transition-all duration-200"
                    :class="isActive(item.name)
                        ? 'text-coin-primary bg-coin-primary/10'
                        : 'text-gray-400 dark:text-gray-500'"
                >
                    <component :is="item.icon" class="w-5 h-5" />
                    <span class="text-[10px]" :class="isActive(item.name) ? 'font-bold' : 'font-medium'">{{ item.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
