<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sign in to your Coin account</p>
            </div>

            <div v-if="status" class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-sm">
                {{ status }}
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="you@example.com"
                        class="input"
                        :class="{ 'border-red-400 focus:ring-red-400': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-coin-primary hover:underline"
                        >Forgot password?</Link>
                    </div>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="input"
                        :class="{ 'border-red-400 focus:ring-red-400': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-gray-300 dark:border-white/20 text-coin-primary focus:ring-coin-primary bg-transparent"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                </label>

                <button
                    type="submit"
                    class="btn-primary w-full py-3 text-base"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                Don't have an account?
                <Link :href="route('register')" class="text-coin-primary font-medium hover:underline">Create one</Link>
            </p>
        </div>
    </GuestLayout>
</template>
