<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create your account</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start tracking your finances today</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Your name"
                        class="input"
                        :class="{ 'border-red-400 focus:ring-red-400': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="username"
                        placeholder="you@example.com"
                        class="input"
                        :class="{ 'border-red-400 focus:ring-red-400': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="input"
                        :class="{ 'border-red-400 focus:ring-red-400': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="input"
                        :class="{ 'border-red-400 focus:ring-red-400': form.errors.password_confirmation }"
                    />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
                </div>

                <button
                    type="submit"
                    class="btn-primary w-full py-3 text-base"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating account…' : 'Create account' }}
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                Already have an account?
                <Link :href="route('login')" class="text-coin-primary font-medium hover:underline">Sign in</Link>
            </p>
        </div>
    </GuestLayout>
</template>
