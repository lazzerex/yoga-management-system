<template>
    <AuthCard title="Create account">
        <form @submit.prevent="submit" class="space-y-4">
            <LabeledInput
                id="name"
                v-model="form.name"
                label="Full name"
                type="text"
                placeholder="Your name"
                autocomplete="name"
                :error="form.errors.name"
            />

            <LabeledInput
                id="username"
                v-model="form.username"
                label="Username"
                type="text"
                placeholder="your_username"
                autocomplete="username"
                :error="form.errors.username"
            />

            <LabeledInput
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                placeholder="you@example.com"
                autocomplete="email"
                :error="form.errors.email"
            />

            <LabeledInput
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                :error="form.errors.password"
            />

            <LabeledInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                placeholder="Repeat password"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? 'Creating account...' : 'Create account' }}
            </button>

            <p class="text-sm text-neutral-600">
                Already have an account?
                <Link href="/cms/login" class="font-medium text-teal-700 hover:underline">Sign in</Link>
            </p>
        </form>
    </AuthCard>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthCard from '../../Components/Auth/AuthCard.vue';
import LabeledInput from '../../Components/Form/LabeledInput.vue';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/cms/register');
};
</script>
