<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthCard from '@/Components/Auth/AuthCard.vue';
import Field from '@/Components/Form/Field.vue';
import TextInput from '@/Components/Form/TextInput.vue';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'));
};
</script>

<template>
    <AuthCard :title="$t('auth.createAccount')">
        <form @submit.prevent="submit" class="space-y-4">
            <Field :label="$t('auth.name')" :error="form.errors.name">
                <TextInput
                    v-model="form.name"
                    type="text"
                    :placeholder="$t('auth.namePlaceholder')"
                    autocomplete="name"
                />
            </Field>

            <Field :label="$t('auth.username')" :error="form.errors.username">
                <TextInput
                    v-model="form.username"
                    type="text"
                    :placeholder="$t('auth.usernamePlaceholder')"
                    autocomplete="username"
                />
            </Field>

            <Field :label="$t('auth.email')" :error="form.errors.email">
                <TextInput
                    v-model="form.email"
                    type="email"
                    :placeholder="$t('auth.emailPlaceholder')"
                    autocomplete="email"
                />
            </Field>

            <Field :label="$t('auth.password')" :error="form.errors.password">
                <TextInput
                    v-model="form.password"
                    type="password"
                    :placeholder="$t('auth.passwordHint')"
                    autocomplete="new-password"
                />
            </Field>

            <Field :label="$t('auth.confirmPassword')" :error="form.errors.password_confirmation">
                <TextInput
                    v-model="form.password_confirmation"
                    type="password"
                    :placeholder="$t('auth.confirmPasswordPlaceholder')"
                    autocomplete="new-password"
                />
            </Field>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-neutral-900 text-white py-2 text-sm font-medium hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ form.processing ? $t('auth.creatingAccount') : $t('auth.createAccount') }}
            </button>

            <p class="text-sm text-neutral-600">
                {{ $t('auth.alreadyHaveAccount') }}
                <Link :href="route('login')" class="font-medium text-teal-700 hover:underline">{{ $t('auth.signIn') }}</Link>
            </p>
        </form>
    </AuthCard>
</template>
