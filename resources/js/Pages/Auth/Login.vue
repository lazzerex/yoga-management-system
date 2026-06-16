<script setup>
import AuthCard from '@/Components/Auth/AuthCard.vue';
import LabeledInput from '@/Components/Form/LabeledInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'));
};
</script>

<template>
    <AuthCard title="CMS Login">
        <form @submit.prevent="submit" class="space-y-4">
            <LabeledInput
                id="username"
                v-model="form.username"
                label="Username"
                type="text"
                placeholder="your username"
                autocomplete="username"
                :error="form.errors.username"
            />

            <LabeledInput
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                autocomplete="current-password"
                :error="form.errors.password"
            />

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input v-model="form.remember" type="checkbox" class="rounded border-neutral-300" />
                Remember me
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>

            <p class="text-sm text-neutral-600">
                Need an account?
                <Link :href="route('register')" class="font-medium text-teal-700 hover:underline">Register</Link>
            </p>
        </form>
    </AuthCard>
</template>